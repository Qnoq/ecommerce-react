<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /**
     * Index produits avec filtres et pagination
     */
    public function index(Request $request)
    {
        $query = Product::with(['categories', 'reviews'])
            ->where('status', 'active');

        // Appliquer les filtres
        $this->applyFilters($query, $request);

        // Pagination
        $products = $query->paginate(12)->withQueryString();

        // Réponse JSON pour AJAX
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'products' => [
                    'data' => $this->formatProducts($products->getCollection()),
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage()
                ]
            ]);
        }

        // Catégories pour les filtres
        $categories = Cache::remember('categories.active', 3600, function () {
            return Category::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug']);
        });

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $this->getRequestFilters($request)
        ]);
    }

    /**
     * Affichage d'un produit avec slug et UUID
     */
    public function show(string $slug, string $uuid)
    {
        // Récupérer le produit par UUID
        $product = Product::where('uuid', $uuid)->firstOrFail();
        
        // Vérifier que le slug correspond (optionnel mais recommandé pour SEO)
        if ($product->slug !== $slug) {
            // Rediriger vers la bonne URL si le slug est incorrect
            return redirect()->route('products.show', [
                'slug' => $product->slug,
                'uuid' => $product->uuid
            ], 301);
        }
        
        $product->load([
            'categories',
            'reviews' => function ($query) {
                $query->where('is_approved', true)
                      ->orderBy('created_at', 'desc')
                      ->take(10);
            },
            'reviews.user'
        ]);

        // Charger les variantes avec leurs attributs
        $variants = DB::table('product_variants as pv')
            ->select([
                'pv.id',
                'pv.uuid',
                'pv.sku',
                'pv.name',
                'pv.price',
                'pv.original_price',
                'pv.stock_quantity',
                'pv.in_stock',
                'pv.featured_image',
                'pv.images',
                'pv.is_default',
                'pv.sort_order'
            ])
            ->where('pv.product_id', $product->id)
            ->where('pv.status', 'active')
            ->orderBy('pv.is_default', 'desc')
            ->orderBy('pv.sort_order')
            ->get()
            ->map(function ($variant) {
                // Charger les attributs de chaque variante
                $attributes = DB::table('product_variant_attributes')
                    ->where('product_variant_id', $variant->id)
                    ->orderBy('sort_order')
                    ->get();
                
                $variant->attributes = $attributes;
                $variant->images = $variant->images ? json_decode($variant->images, true) : [];
                
                return $variant;
            });

        // Organiser les attributs disponibles pour les sélecteurs
        $availableAttributes = [];
        foreach ($variants as $variant) {
            foreach ($variant->attributes as $attr) {
                if (!isset($availableAttributes[$attr->attribute_name])) {
                    $availableAttributes[$attr->attribute_name] = [];
                }
                
                $availableAttributes[$attr->attribute_name][] = [
                    'value' => $attr->attribute_value,
                    'display_name' => $attr->display_name ?: $attr->attribute_value,
                    'color_code' => $attr->color_code,
                    'sort_order' => $attr->sort_order
                ];
            }
        }

        // Dédupliquer et trier les attributs
        foreach ($availableAttributes as $attrName => $values) {
            $availableAttributes[$attrName] = collect($values)
                ->unique('value')
                ->sortBy('sort_order')
                ->values()
                ->toArray();
        }

        // Produits similaires
        $relatedProducts = Product::with(['categories'])
            ->where('status', 'active')
            ->where('id', '!=', $product->id)
            ->whereHas('categories', function ($query) use ($product) {
                $query->whereIn('categories.id', $product->categories->pluck('id'));
            })
            ->inRandomOrder()
            ->take(4)
            ->get();


        return Inertia::render('Products/Show', [
            'product' => $product,
            'variants' => $variants,
            'availableAttributes' => $availableAttributes,
            'relatedProducts' => $relatedProducts,
            'maxStock' => $this->getMaxAvailableStock($product, $variants)
        ]);
    }

    /**
     * Méthode de fallback pour les anciens liens avec /products/
     */
    public function showByUuid(string $slugOrUuid, string $uuid = null)
    {
        // Si on a deux paramètres, c'est un ancien lien /products/{slug}/{uuid}
        if ($uuid) {
            $product = Product::where('uuid', $uuid)->firstOrFail();
            return redirect()->route('products.show', [
                'slug' => $product->slug,
                'uuid' => $product->uuid
            ], 301);
        }
        
        // Sinon, c'est un ancien lien /products/{uuid}
        $product = Product::where('uuid', $slugOrUuid)->firstOrFail();
        return redirect()->route('products.show', [
            'slug' => $product->slug,
            'uuid' => $product->uuid
        ], 301);
    }


    /**
     * Suggestions pour autocomplétion
     */
    public function suggestions(Request $request)
    {
        $query = $request->get('query', $request->get('q', ''));
        
        Log::info("Suggestions called", ['query' => $query, 'request_params' => $request->all()]);
        
        if (strlen($query) < 2) {
            Log::info("Suggestions: Query too short", ['query' => $query]);
            return response()->json(['suggestions' => []]);
        }

        $cacheKey = 'suggestions:' . md5(strtolower($query));
        $suggestions = Cache::remember($cacheKey, 300, function () use ($query) {
            return $this->generateSuggestions($query);
        });

        Log::info("Suggestions results", ['query' => $query, 'suggestions_count' => count($suggestions)]);

        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * Page de recherche unifiée (style Amazon)
     */
    public function searchPage(Request $request)
    {
        $query = $request->input('k', '');
        $page = $request->input('page', 1);
        $filters = [
            'price_min' => $request->input('price_min'),
            'price_max' => $request->input('price_max'),
            'category' => $request->input('category'),
            'sort' => $request->input('sort', 'relevance')
        ];

        // Si pas de recherche, page d'accueil de recherche
        if (empty($query) || strlen(trim($query)) < 1) {
            return $this->renderEmptySearchPage();
        }

        // Recherche avec cache
        $cacheKey = 'search:' . md5(strtolower($query) . serialize($filters) . $page);
        $results = Cache::remember($cacheKey, 600, function () use ($query, $filters, $page) {
            return $this->performSearch($query, 20, $filters, $page);
        });

        // Analytics
        $this->trackSearchAnalytics($query);

        return Inertia::render('SearchPage', [
            'searchQuery' => $query,
            'searchResults' => $results,
            'currentFilters' => $filters,
            'filters' => $this->getAvailableFilters()
        ]);
    }

    /**
     * Recherche live pour la modal (retourne JSON pour Inertia)
     */
    public function searchLive(Request $request)
    {
        $query = $request->input('search', $request->input('q', ''));
        $limit = $request->input('limit', 10);
        
        Log::info("SearchLive called", ['query' => $query, 'limit' => $limit, 'request_params' => $request->all()]);
        
        if (empty($query) || strlen(trim($query)) < 2) {
            Log::info("SearchLive: Query too short", ['query' => $query]);
            return response()->json([
                'products' => [],
                'totalResults' => 0,
                'query' => $query
            ]);
        }

        // Recherche avec cache
        $cacheKey = 'search_live:' . md5(strtolower($query) . $limit);
        $results = Cache::remember($cacheKey, 300, function () use ($query, $limit) {
            return $this->performSearch($query, $limit);
        });

        Log::info("SearchLive results", ['query' => $query, 'products_count' => count($results['products']['data']), 'total' => $results['products']['total']]);

        // Analytics
        $this->trackSearchAnalytics($query);

        return response()->json([
            'products' => $results['products']['data'],
            'totalResults' => $results['products']['total'],
            'query' => $query
        ]);
    }

    // ==========================================
    // MÉTHODES PRIVÉES
    // ==========================================

    /**
     * Méthode unifiée de recherche
     */
    private function performSearch(string $query, int $limit = 20, array $filters = [], int $page = 1): array
    {
        $cleanQuery = $this->cleanQuery($query);
        
        Log::info("PerformSearch called", ['query' => $query, 'cleanQuery' => $cleanQuery, 'limit' => $limit]);
        
        try {
            // Construire la requête de base
            $baseQuery = Product::where('status', 'active');
            
            // Recherche textuelle PostgreSQL avec unaccent - approche simple
            $baseQuery->where(function ($q) use ($cleanQuery) {
                $normalizedQuery = strtolower($cleanQuery);
                
                // Créer toutes les variations possibles de la recherche
                $searchVariations = [
                    $normalizedQuery,                                    // "t shirt"
                    str_replace(' ', '-', $normalizedQuery),            // "t-shirt"  
                    str_replace(' ', '', $normalizedQuery),             // "tshirt"
                    str_replace('-', ' ', $normalizedQuery),            // "t shirt" (si "t-shirt" entré)
                    str_replace('-', '', $normalizedQuery),             // "tshirt" (si "t-shirt" entré)
                ];
                
                // Recherche dans toutes les variations
                foreach (array_unique($searchVariations) as $variation) {
                    $q->orWhereRaw('unaccent(lower(name)) ILIKE unaccent(lower(?))', ["%{$variation}%"])
                      ->orWhereRaw('unaccent(lower(description)) ILIKE unaccent(lower(?))', ["%{$variation}%"])
                      ->orWhereRaw('unaccent(lower(search_content)) ILIKE unaccent(lower(?))', ["%{$variation}%"]);
                }
                
                // Recherche par mots individuels pour "t shirt" -> trouver des produits contenant "t" ET "shirt"
                $words = preg_split('/[\s\-]+/', $normalizedQuery);
                if (count($words) > 1) {
                    $q->orWhere(function ($subQ) use ($words) {
                        foreach ($words as $word) {
                            $word = trim($word);
                            if (strlen($word) > 0) {
                                $subQ->where(function ($wordQ) use ($word) {
                                    $wordQ->whereRaw('unaccent(lower(name)) ILIKE unaccent(lower(?))', ["%{$word}%"])
                                          ->orWhereRaw('unaccent(lower(description)) ILIKE unaccent(lower(?))', ["%{$word}%"])
                                          ->orWhereRaw('unaccent(lower(search_content)) ILIKE unaccent(lower(?))', ["%{$word}%"]);
                                });
                            }
                        }
                    });
                }
            });

            // Appliquer les filtres
            $this->applySearchFilters($baseQuery, $filters);

            // Tri
            $this->applySorting($baseQuery, $filters['sort'] ?? 'relevance');

            // Pagination ou limite
            if (!empty($filters) && $page > 1) {
                $products = $baseQuery->paginate($limit, ['*'], 'page', $page);
                $formattedProducts = $this->formatProducts($products->getCollection());
                $total = $products->total();
                $pagination = [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage()
                ];
            } else {
                $products = $baseQuery->limit($limit)->get();
                $formattedProducts = $this->formatProducts($products);
                $total = $products->count();
                $pagination = [];
            }

            Log::info("PerformSearch results", ['query' => $query, 'products_found' => $total, 'formatted_count' => count($formattedProducts)]);

            // Suggestions si peu de résultats
            $suggestions = $total < 3 ? $this->generateSuggestions($cleanQuery) : [];

            return array_merge([
                'products' => ['data' => $formattedProducts, 'total' => $total],
                'suggestions' => $suggestions
            ], $pagination);

        } catch (\Exception $e) {
            Log::error('Erreur recherche: ' . $e->getMessage(), ['query' => $query, 'exception' => $e->getTraceAsString()]);
            return [
                'products' => ['data' => [], 'total' => 0],
                'suggestions' => []
            ];
        }
    }

    /**
     * Générer des suggestions
     */
    private function generateSuggestions(string $query): array
    {
        $cleanQuery = $this->cleanQuery($query);
        
        
        try {
            // Suggestions de produits similaires - Éviter les doublons par nom
            $products = DB::select("
                SELECT DISTINCT ON (p.name) p.name, p.uuid, p.slug, p.price, p.featured_image, p.sales_count
                FROM products p 
                WHERE p.status = 'active'
                AND (
                    unaccent(lower(p.name)) ILIKE unaccent(lower(?))
                    OR unaccent(lower(p.search_content)) ILIKE unaccent(lower(?))
                )
                ORDER BY p.name, p.sales_count DESC
                LIMIT 5
            ", ["%{$cleanQuery}%", "%{$cleanQuery}%"]);

            $suggestions = [];
            foreach ($products as $product) {
                $suggestions[] = [
                    'id' => $product->uuid,
                    'type' => 'product',
                    'title' => $product->name,
                    'subtitle' => '€' . number_format($product->price, 2),
                    'url' => route('products.show', ['slug' => $product->slug, 'uuid' => $product->uuid]),
                    'image' => $product->featured_image
                ];
            }

            return $suggestions;

        } catch (\Exception $e) {
            Log::error('Erreur génération suggestions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Appliquer les filtres à la requête
     */
    private function applyFilters($query, Request $request)
    {
        // Recherche textuelle
        if ($request->filled('search')) {
            $searchTerm = $this->cleanQuery($request->search);
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw("to_tsvector('french', COALESCE(name, '') || ' ' || COALESCE(description, '')) @@ websearch_to_tsquery('french', ?)", [$searchTerm])
                  ->orWhere('name', 'ILIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'ILIKE', "%{$searchTerm}%");
            });
        }

        // Filtre par catégorie
        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Filtres de prix
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        // Tri
        $sortField = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        
        $allowedSorts = ['name', 'price', 'created_at', 'updated_at', 'sales_count'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }

    /**
     * Appliquer les filtres de recherche
     */
    private function applySearchFilters($query, array $filters)
    {
        if (!empty($filters['price_min'])) {
            $query->where('price', '>=', $filters['price_min']);
        }
        if (!empty($filters['price_max'])) {
            $query->where('price', '<=', $filters['price_max']);
        }
        if (!empty($filters['category'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('slug', $filters['category']);
            });
        }
    }

    /**
     * Appliquer le tri
     */
    private function applySorting($query, string $sortBy)
    {
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'rating':
                $query->orderBy('rating', 'desc');
                break;
            case 'popularity':
                $query->orderBy('sales_count', 'desc');
                break;
            default:
                $query->orderBy('sales_count', 'desc')->orderBy('rating', 'desc');
                break;
        }
    }

    /**
     * Formater les produits pour l'API
     */
    private function formatProducts($products): array
    {
        return $products->map(function ($product) {
            $image = $product->featured_image;
            if (!$image && $product->images) {
                $images = is_string($product->images) ? json_decode($product->images, true) : $product->images;
                $image = is_array($images) && count($images) > 0 ? $images[0] : null;
            }

            $badges = [];
            if ($product->is_featured) $badges[] = 'Coup de cœur';
            if ($product->sales_count > 100) $badges[] = 'Best seller';
            if ($product->created_at > now()->subDays(30)) $badges[] = 'Nouveauté';

            // Vérifier si le produit a des variantes (plus d'une variante = choix requis)
            $variantsCount = DB::table('product_variants')
                ->where('product_id', $product->id)
                ->where('status', 'active')
                ->count();
            
            // Calculer le stock minimum disponible
            $minStock = $this->getMinAvailableStock($product);
            

            return [
                'id' => $product->id,
                'uuid' => $product->uuid,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => (float) $product->price,
                'featured_image' => $image,
                'rating' => $product->rating ? (float) $product->rating : null,
                'review_count' => (int) $product->review_count,
                'is_featured' => (bool) $product->is_featured,
                'badges' => $badges,
                'has_variants' => $variantsCount > 1,
                'min_stock' => $minStock
            ];
        })->toArray();
    }

    /**
     * Nettoyer la requête de recherche
     */
    private function cleanQuery(string $query): string
    {
        $cleaned = preg_replace('/[^\w\s\-àâäéèêëïîôöùûüÿç]/ui', ' ', $query);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        return trim($cleaned);
    }

    /**
     * Tracker les analytics de recherche
     */
    private function trackSearchAnalytics(string $query): void
    {
        try {
            $normalizedQuery = strtolower(trim($query));
            
            Redis::pipeline(function ($pipe) use ($normalizedQuery) {
                $pipe->zincrby('search_analytics', 1, $normalizedQuery);
                $pipe->expire('search_analytics', 86400);
                
                if (Auth::check()) {
                    $userKey = 'user_searches:' . Auth::id();
                    $pipe->lpush($userKey, $normalizedQuery);
                    $pipe->ltrim($userKey, 0, 9);
                    $pipe->expire($userKey, 2592000);
                }
            });
        } catch (\Exception $e) {
            Log::warning('Erreur analytics recherche: ' . $e->getMessage());
        }
    }

    /**
     * Obtenir les filtres de la requête
     */
    private function getRequestFilters(Request $request): array
    {
        return [
            'search' => $request->search,
            'category' => $request->category,
            'price_min' => $request->price_min,
            'price_max' => $request->price_max,
            'sort' => $request->get('sort', 'created_at'),
            'order' => $request->get('order', 'desc'),
        ];
    }

    /**
     * Rendre la page de recherche vide
     */
    private function renderEmptySearchPage()
    {
        return Inertia::render('SearchPage', [
            'searchQuery' => '',
            'searchResults' => [
                'products' => ['data' => [], 'total' => 0],
                'suggestions' => []
            ],
            'currentFilters' => [],
            'filters' => $this->getAvailableFilters()
        ]);
    }

    /**
     * Obtenir le stock maximum disponible pour un produit
     */
    private function getMaxAvailableStock($product, $variants)
    {
        if ($variants->count() > 1) {
            // Si plusieurs variantes, retourner le stock max parmi les variantes
            return $variants->max('stock_quantity');
        } else {
            // Si une seule variante ou pas de variantes, utiliser le stock du produit ou de la variante
            return $variants->count() > 0 ? $variants->first()->stock_quantity : $product->stock_quantity;
        }
    }

    /**
     * Obtenir le stock minimum disponible pour un produit (pour la recherche)
     */
    private function getMinAvailableStock($product)
    {
        $variants = DB::table('product_variants')
            ->where('product_id', $product->id)
            ->where('status', 'active')
            ->get();
            
        if ($variants->count() > 0) {
            // Retourner 0 seulement si TOUTES les variantes sont épuisées
            $maxStock = $variants->max('stock_quantity');
            return $maxStock > 0 ? 1 : 0; // 1 = en stock, 0 = rupture totale
        } else {
            // Sinon, utiliser le stock du produit de base
            return $product->stock_quantity > 0 ? 1 : 0;
        }
    }

    /**
     * Obtenir les filtres disponibles
     */
    private function getAvailableFilters(): array
    {
        $categories = Cache::remember('categories.active', 3600, function () {
            return Category::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug']);
        });

        $sortOptions = [
            'relevance' => 'Pertinence',
            'price_asc' => 'Prix croissant',
            'price_desc' => 'Prix décroissant',
            'newest' => 'Plus récents',
            'rating' => 'Mieux notés',
            'popularity' => 'Plus populaires'
        ];

        return [
            'categories' => $categories,
            'sortOptions' => $sortOptions
        ];
    }
}