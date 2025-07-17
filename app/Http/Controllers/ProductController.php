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
     * Affichage d'un produit
     */
    public function show(Product $product)
    {
        $product->load([
            'categories',
            'reviews' => function ($query) {
                $query->where('is_approved', true)
                      ->orderBy('created_at', 'desc')
                      ->take(10);
            },
            'reviews.user'
        ]);

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
            'relatedProducts' => $relatedProducts
        ]);
    }


    /**
     * Suggestions pour autocomplétion
     */
    public function suggestions(Request $request)
    {
        $query = $request->get('query', $request->get('q', ''));
        
        if (strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $cacheKey = 'suggestions:' . md5(strtolower($query));
        $suggestions = Cache::remember($cacheKey, 300, function () use ($query) {
            return $this->generateSuggestions($query);
        });

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
     * API de recherche pour la modal (retourne JSON)
     */
    public function searchApi(Request $request)
    {
        $query = $request->input('k', '');
        $limit = $request->input('limit', 10);
        
        if (empty($query) || strlen(trim($query)) < 2) {
            return response()->json([
                'products' => [],
                'totalResults' => 0,
                'query' => $query
            ]);
        }

        // Recherche avec cache
        $cacheKey = 'search_api:' . md5(strtolower($query) . $limit);
        $results = Cache::remember($cacheKey, 300, function () use ($query, $limit) {
            return $this->performSearch($query, $limit);
        });

        // Analytics
        $this->trackSearchAnalytics($query);

        return response()->json([
            'products' => $results['products'],
            'totalResults' => $results['totalResults'],
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
        
        try {
            // Construire la requête de base
            $baseQuery = Product::where('status', 'active');
            
            // Recherche textuelle PostgreSQL avec unaccent
            $baseQuery->where(function ($q) use ($cleanQuery) {
                $normalizedQuery = strtolower($cleanQuery);
                $q->whereRaw('unaccent(lower(name)) ILIKE unaccent(lower(?))', ["%{$normalizedQuery}%"])
                  ->orWhereRaw('unaccent(lower(description)) ILIKE unaccent(lower(?))', ["%{$normalizedQuery}%"])
                  ->orWhereRaw('unaccent(lower(search_content)) ILIKE unaccent(lower(?))', ["%{$normalizedQuery}%"]);
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

            // Suggestions si peu de résultats
            $suggestions = $total < 3 ? $this->generateSuggestions($cleanQuery) : [];

            return array_merge([
                'products' => ['data' => $formattedProducts, 'total' => $total],
                'suggestions' => $suggestions
            ], $pagination);

        } catch (\Exception $e) {
            Log::error('Erreur recherche: ' . $e->getMessage());
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
            // Suggestions de produits similaires - Prendre le produit avec le plus de ventes par nom
            $products = DB::select("
                SELECT DISTINCT ON (p.name) p.name, p.uuid, p.price, p.featured_image, p.sales_count
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
                    'url' => route('products.show', $product->uuid),
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

            return [
                'id' => $product->id,
                'uuid' => $product->uuid,
                'name' => $product->name,
                'price' => (float) $product->price,
                'featured_image' => $image,
                'rating' => $product->rating ? (float) $product->rating : null,
                'review_count' => (int) $product->review_count,
                'is_featured' => (bool) $product->is_featured,
                'badges' => $badges
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