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
     * Index produits avec cache pour filtres populaires
     */
    public function index(Request $request)
    {
        // Construction de la requête avec les filtres
        $query = Product::with(['categories', 'reviews'])
            ->where('status', 'active');

        // 🚀 CACHE pour requêtes de catalogue populaires
        $cacheKey = $this->getCatalogCacheKey($request);
        $useCache = $this->shouldUseCacheForCatalog($request);

        if ($useCache) {
            $products = Cache::remember($cacheKey, 1800, function () use ($query, $request) {
                return $this->buildCatalogQuery($query, $request)->paginate(12)->withQueryString();
            });
        } else {
            $products = $this->buildCatalogQuery($query, $request)->paginate(12)->withQueryString();
        }

        // Si c'est une requête AJAX, retourner JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'products' => [
                    'data' => $this->formatProductsForAPI($products->getCollection()),
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage()
                ]
            ]);
        }

        // Catégories avec cache (changent rarement)
        $categories = Cache::remember('categories.active', 3600, function () {
            return Category::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug']);
        });

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'search' => $request->search,
                'category' => $request->category,
                'price_min' => $request->price_min,
                'price_max' => $request->price_max,
                'sort' => $request->get('sort', 'created_at'),
                'order' => $request->get('order', 'desc'),
            ]
        ]);
    }

    /**
     * 🔥 NOUVEAUTÉ: Recherches populaires en temps réel
     */
    public function popularSearches()
    {
        $popularSearches = Cache::remember('search.popular', 1800, function () {
            // Récupérer les recherches les plus populaires des dernières 24h
            $searches = Redis::zrevrange('search_analytics', 0, 9, 'WITHSCORES');
            
            $popular = [];
            for ($i = 0; $i < count($searches); $i += 2) {
                $popular[] = [
                    'query' => $searches[$i],
                    'count' => (int) $searches[$i + 1]
                ];
            }
            
            return $popular;
        });

        return response()->json(['popular_searches' => $popularSearches]);
    }

    /**
     * 🔥 NOUVEAUTÉ: Invalidation intelligente du cache
     */
    public function clearSearchCache()
    {
        // Vider tous les caches de recherche
        $pattern = config('cache.prefix') . 'search:*';
        
        $redis = Redis::connection('cache');
        $keys = $redis->keys($pattern);
        
        if (!empty($keys)) {
            $redis->del($keys);
            Log::info('Cache de recherche vidé', ['keys_deleted' => count($keys)]);
        }

        return response()->json(['message' => 'Cache de recherche vidé avec succès']);
    }
    
    /**
     * Génération de clé de cache pour recherche
     */
    private function getSearchCacheKey(string $query): string
    {
        $normalizedQuery = $this->normalizeSearchQuery($query);
        return "search:live:" . md5($normalizedQuery);
    }

    /**
     * Génération de clé de cache pour suggestions
     */
    private function getSuggestionsCacheKey(string $query): string
    {
        $normalizedQuery = $this->normalizeSearchQuery($query);
        return "search:suggestions:" . md5($normalizedQuery);
    }

    /**
     * Génération de clé de cache pour catalogue
     */
    private function getCatalogCacheKey(Request $request): string
    {
        $filters = [
            'category' => $request->category,
            'price_min' => $request->price_min,
            'price_max' => $request->price_max,
            'sort' => $request->get('sort', 'created_at'),
            'order' => $request->get('order', 'desc'),
            'page' => $request->get('page', 1)
        ];
        
        return "catalog:" . md5(serialize($filters));
    }

    /**
     * Détermine si on doit utiliser le cache pour le catalogue
     */
    private function shouldUseCacheForCatalog(Request $request): bool
    {
        // Pas de cache pour les recherches textuelles (trop dynamiques)
        if ($request->filled('search')) {
            return false;
        }
        
        // Cache pour les filtres simples et pages populaires
        return $request->get('page', 1) <= 5; // Cache seulement les 5 premières pages
    }

    /**
     * Construction de la requête de catalogue
     */
    private function buildCatalogQuery($query, Request $request)
    {
        // Filtre de recherche textuelle
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query = $this->applyAdvancedSearch($query, $searchTerm);
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
        
        if ($request->filled('search')) {
            $query = $this->applySortByRelevance($query, $request->search, $sortField, $sortOrder);
        } else {
            $allowedSorts = ['name', 'price', 'created_at', 'updated_at', 'sales_count'];
            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortOrder);
            } else {
                $query->orderBy('created_at', 'desc');
            }
        }

        return $query;
    }

    /**
     * Normalisation des requêtes de recherche pour cache
     */
    private function normalizeSearchQuery(string $query): string
    {
        // Minuscules, supprimer espaces multiples, trim
        return trim(preg_replace('/\s+/', ' ', strtolower($query)));
    }

    /**
     * Analytics des recherches pour recommandations
     */
    private function trackSearchAnalytics(string $query): void
    {
        try {
            $normalizedQuery = $this->normalizeSearchQuery($query);
            
            // Incrémenter le compteur de cette recherche (expire dans 24h)
            Redis::zincrby('search_analytics', 1, $normalizedQuery);
            Redis::expire('search_analytics', 86400);
            
            // Optionnel: Tracker aussi les recherches récentes par utilisateur
            if (Auth::check()) {
                $userKey = 'user_searches:' . Auth::id();
                Redis::lpush($userKey, $normalizedQuery);
                Redis::ltrim($userKey, 0, 9); // Garder seulement 10 dernières
                Redis::expire($userKey, 2592000); // 30 jours
            }
        } catch (\Exception $e) {
            Log::warning('Erreur analytics recherche: ' . $e->getMessage());
        }
    }

    /**
     * Recherche live ULTRA-OPTIMISÉE avec cache Redis intelligent
     */
    public function liveSearch(Request $request)
    {
        $query = trim((string) $request->query('q'));

        // Pas de query ou moins de 2 caractères → pas de recherche
        if (strlen($query) < 2) {
            return inertia('welcome', [
                'searchResults' => [
                    'products' => ['data' => [], 'total' => 0, 'query' => $query],
                    'suggestions' => [],
                ],
            ]);
        }

        // 🚀 CACHE INTELLIGENT - Clé unique par recherche
        $cacheKey = $this->getSearchCacheKey($query);
        
        // Essayer de récupérer depuis le cache (TTL: 10 minutes)
        $results = Cache::remember($cacheKey, 600, function () use ($query) {
            Log::info('Cache MISS - Exécution recherche PostgreSQL', ['query' => $query]);
            return $this->performAdvancedLiveSearch($query);
        });

        // Analytics Redis (optionnel) - Compter les recherches populaires
        $this->trackSearchAnalytics($query);

        return inertia('welcome', [
            'searchResults' => [
                'products' => [
                    'data' => $results['products'],
                    'total' => $results['total'],
                    'query' => $query
                ],
                'suggestions' => $results['suggestions'],
            ],
        ]);
    }

    /**
     * Suggestions ULTRA-RAPIDES avec cache Redis
     */
    public function suggestions(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        // 🚀 CACHE SUGGESTIONS - TTL 5 minutes (plus court car plus dynamique)
        $cacheKey = $this->getSuggestionsCacheKey($query);
        
        $suggestions = Cache::remember($cacheKey, 300, function () use ($query) {
            Log::info('Cache MISS - Génération suggestions', ['query' => $query]);
            return $this->generateSuggestions($query);
        });

        return response()->json([
            'suggestions' => $suggestions
        ]);
    }

    // ==========================================
    // MÉTHODES PRIVÉES POUR RECHERCHE AVANCÉE
    // ==========================================

    /**
     * 🚀 NOUVELLE VERSION SIMPLIFIÉE - Recherche live avec Laravel + PostgreSQL natif
     */
    private function performAdvancedLiveSearch(string $query, int $limit = 20): array
    {
        $cleanQuery = $this->cleanSearchQuery($query);
        
        try {
            Log::info('🔍 Recherche live simplifiée', ['query' => $cleanQuery]);
            
            // 🚀 ÉTAPE 1: Recherche Full-Text PostgreSQL avec les index existants
            $fullTextResults = Product::where('status', 'active')
                ->whereRaw("to_tsvector('french', COALESCE(name, '') || ' ' || COALESCE(description, '')) @@ websearch_to_tsquery('french', ?)", [$cleanQuery])
                ->orderBy('sales_count', 'desc')
                ->orderBy('rating', 'desc')
                ->limit($limit)
                ->get();

            if ($fullTextResults->count() >= 5) {
                // Assez de résultats avec Full-Text
                return [
                    'products' => $this->formatProductsForAPI($fullTextResults),
                    'total' => $fullTextResults->count(),
                    'suggestions' => []
                ];
            }

            // 🚀 ÉTAPE 2: Compléter avec recherche ILIKE si peu de résultats
            $additionalResults = Product::where('status', 'active')
                ->where(function ($q) use ($cleanQuery) {
                    $q->where('name', 'ILIKE', "%{$cleanQuery}%")
                      ->orWhere('description', 'ILIKE', "%{$cleanQuery}%");
                })
                ->whereNotIn('id', $fullTextResults->pluck('id')) // Éviter les doublons
                ->orderBy('sales_count', 'desc')
                ->orderBy('rating', 'desc')
                ->limit($limit - $fullTextResults->count())
                ->get();

            $allResults = $fullTextResults->concat($additionalResults);
            
            // Générer des suggestions si pas assez de résultats
            $suggestions = $allResults->count() < 3 ? $this->generateSimpleSuggestions($cleanQuery) : [];
            
            return [
                'products' => $this->formatProductsForAPI($allResults),
                'total' => $allResults->count(),
                'suggestions' => $suggestions
            ];

        } catch (\Exception $e) {
            Log::error('Erreur recherche simplifiée: ' . $e->getMessage());
            
            // Fallback vers recherche basique
            return $this->performSimpleLiveSearch($query, $limit);
        }
    }

    /**
     * Fallback vers recherche simple
     */
    private function performSimpleLiveSearch(string $query, int $limit = 20): array
    {
        $products = Product::where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('name', 'ILIKE', "%{$query}%")
                  ->orWhere('description', 'ILIKE', "%{$query}%");
            })
            ->orderBy('sales_count', 'desc')
            ->limit($limit)
            ->get();

        return [
            'products' => $this->formatProductsForAPI($products),
            'total' => $products->count(),
            'suggestions' => []
        ];
    }

    /**
     * Suggestions avancées pour autocomplétion
     */
    private function getAdvancedSuggestions(string $query): array
    {
        $cleanQuery = $this->cleanSearchQuery($query);
        
        try {
            $productSuggestions = DB::select("
                SELECT DISTINCT 
                    p.uuid,
                    p.name,
                    p.price,
                    p.featured_image,
                    p.images,
                    (
                        similarity(unaccent(p.name), unaccent(?)) * 0.6 +
                        CASE WHEN unaccent(lower(p.name)) LIKE unaccent(lower(?)) || '%' THEN 0.3 ELSE 0 END +
                        CASE WHEN levenshtein(unaccent(lower(p.name)), unaccent(lower(?))) <= 1 THEN 0.1 ELSE 0 END
                    ) as relevance
                FROM products p 
                WHERE 
                    p.status = 'active'
                    AND (
                        similarity(unaccent(p.name), unaccent(?)) > 0.15
                        OR unaccent(lower(p.name)) LIKE unaccent(lower(?)) || '%'
                        OR levenshtein(unaccent(lower(p.name)), unaccent(lower(?))) <= 2
                        OR unaccent(p.name) ILIKE '%' || unaccent(?) || '%'
                    )
                ORDER BY relevance DESC, p.sales_count DESC
                LIMIT 6
            ", [$cleanQuery, $cleanQuery, $cleanQuery, $cleanQuery, $cleanQuery, $cleanQuery, $cleanQuery]);

            $categorySuggestions = DB::select("
                SELECT DISTINCT 
                    c.name,
                    c.slug,
                    similarity(unaccent(c.name), unaccent(?)) as sim
                FROM categories c 
                WHERE 
                    c.is_active = true
                    AND (
                        similarity(unaccent(c.name), unaccent(?)) > 0.2
                        OR unaccent(c.name) ILIKE '%' || unaccent(?) || '%'
                    )
                ORDER BY sim DESC
                LIMIT 4
            ", [$cleanQuery, $cleanQuery, $cleanQuery]);

            $suggestions = [];

            foreach ($productSuggestions as $product) {
                $image = $product->featured_image;
                if (!$image && $product->images) {
                    $images = is_string($product->images) ? json_decode($product->images, true) : $product->images;
                    $image = is_array($images) && count($images) > 0 ? $images[0] : null;
                }
                
                $suggestions[] = [
                    'id' => $product->uuid,
                    'type' => 'product',
                    'title' => $product->name,
                    'subtitle' => '€' . number_format($product->price, 2),
                    'url' => route('products.show', $product->uuid),
                    'image' => $image
                ];
            }

            foreach ($categorySuggestions as $category) {
                $suggestions[] = [
                    'id' => $category->slug,
                    'type' => 'category',
                    'title' => $category->name,
                    'subtitle' => 'Catégorie',
                    'url' => route('products.index', ['category' => $category->slug])
                ];
            }

            return array_slice($suggestions, 0, 8);

        } catch (\Exception $e) {
            Log::error('Erreur suggestions avancées: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 🚀 NOUVELLE VERSION SIMPLIFIÉE - Suggestions basées sur Laravel/PostgreSQL natif
     */
    private function generateSimpleSuggestions(string $query): array
    {
        try {
            $cleanQuery = $this->cleanSearchQuery($query);
            $suggestions = [];
            
            // 🚀 ÉTAPE 1: Suggestions de produits similaires
            $similarProducts = Product::where('status', 'active')
                ->where('name', 'ILIKE', "%{$cleanQuery}%")
                ->orderBy('sales_count', 'desc')
                ->limit(4)
                ->get(['name']);
            
            foreach ($similarProducts as $product) {
                if (stripos($product->name, $cleanQuery) !== false) {
                    $suggestions[] = [
                        'id' => 'product_' . md5($product->name),
                        'type' => 'product',
                        'title' => $product->name,
                        'subtitle' => 'Produit suggéré'
                    ];
                }
            }
            
            // 🚀 ÉTAPE 2: Suggestions de catégories similaires
            $similarCategories = \App\Models\Category::where('is_active', true)
                ->where('name', 'ILIKE', "%{$cleanQuery}%")
                ->orderBy('name')
                ->limit(3)
                ->get(['name', 'slug']);
            
            foreach ($similarCategories as $category) {
                $suggestions[] = [
                    'id' => 'category_' . $category->slug,
                    'type' => 'category', 
                    'title' => $category->name,
                    'subtitle' => 'Catégorie'
                ];
            }
            
            return array_slice($suggestions, 0, 6);
            
        } catch (\Exception $e) {
            Log::error('Erreur génération suggestions simplifiées: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Méthode unifiée pour générer des suggestions intelligentes
     * Remplace generateSmartSuggestions() et complète getAdvancedSuggestions()
     */
    private function generateSuggestions(string $query, bool $includeProducts = true): array
    {
        $cleanQuery = $this->cleanSearchQuery($query);
        
        try {
            $suggestions = [];
            
            if ($includeProducts) {
                // Suggestions de produits avec images et prix
                $productSuggestions = DB::select("
                    SELECT DISTINCT 
                        p.uuid, p.name, p.price, p.featured_image, p.images,
                        (similarity(unaccent(p.name), unaccent(?)) * 0.8) as relevance
                    FROM products p 
                    WHERE 
                        p.status = 'active'
                        AND similarity(unaccent(p.name), unaccent(?)) > 0.15
                    ORDER BY relevance DESC, p.sales_count DESC
                    LIMIT 6
                ", [$cleanQuery, $cleanQuery]);
                
                foreach ($productSuggestions as $product) {
                    $suggestions[] = [
                        'id' => $product->uuid,
                        'type' => 'product',
                        'title' => $product->name,
                        'subtitle' => '€' . number_format($product->price, 2),
                        'url' => route('products.show', $product->uuid),
                        'image' => $product->featured_image
                    ];
                }
            } else {
                // Suggestions de termes simples pour correction orthographique
                $termSuggestions = DB::select("
                    SELECT DISTINCT 
                        p.name,
                        similarity(unaccent(p.name), unaccent(?)) as sim
                    FROM products p 
                    WHERE 
                        p.status = 'active'
                        AND similarity(unaccent(p.name), unaccent(?)) > 0.2
                    ORDER BY sim DESC
                    LIMIT 5
                ", [$cleanQuery, $cleanQuery]);
                
                foreach ($termSuggestions as $term) {
                    $suggestions[] = $term->name;
                }
            }
            
            return $suggestions;
            
        } catch (\Exception $e) {
            Log::error('Erreur génération suggestions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Suggestions intelligentes pour cas sans résultats
     */
    private function generateSmartSuggestions(string $query, int $resultCount): array
    {
        if ($resultCount > 0) {
            return [];
        }

        $suggestions = [];
        
        try {
            $similarTerms = DB::select("
                SELECT DISTINCT 
                    p.name,
                    similarity(p.name, ?) as sim
                FROM products p 
                WHERE 
                    p.status = 'active'
                    AND similarity(p.name, ?) > 0.2
                ORDER BY sim DESC
                LIMIT 3
            ", [$query, $query]);

            foreach ($similarTerms as $term) {
                $suggestions[] = $term->name;
            }

        } catch (\Exception $e) {
            Log::error('Erreur génération suggestions: ' . $e->getMessage());
        }

        return $suggestions;
    }

    /**
     * Appliquer la recherche avancée à une requête Eloquent
     */
    private function applyAdvancedSearch($query, string $searchTerm)
    {
        $cleanTerm = $this->cleanSearchQuery($searchTerm);
        
        return $query->whereRaw("
            to_tsvector('french', COALESCE(name, '') || ' ' || COALESCE(description, ''))
            @@ websearch_to_tsquery('french', ?)
            OR similarity(name, ?) > 0.2
            OR similarity(description, ?) > 0.15
            OR name ILIKE ?
            OR description ILIKE ?
        ", [$cleanTerm, $cleanTerm, $cleanTerm, "%{$cleanTerm}%", "%{$cleanTerm}%"]);
    }

    /**
     * Appliquer le tri par pertinence
     */
    private function applySortByRelevance($query, string $searchTerm, string $fallbackSort = 'created_at', string $fallbackOrder = 'desc')
    {
        $cleanTerm = $this->cleanSearchQuery($searchTerm);
        
        return $query->orderByRaw("
            (
                COALESCE(
                    ts_rank(
                        to_tsvector('french', COALESCE(name, '') || ' ' || COALESCE(description, '')),
                        websearch_to_tsquery('french', ?)
                    ), 0
                ) * 0.4 +
                COALESCE(similarity(name, ?), 0) * 0.3 +
                COALESCE(similarity(description, ?), 0) * 0.2 +
                (COALESCE(sales_count, 0) / 1000.0) * 0.1
            ) DESC, {$fallbackSort} {$fallbackOrder}
        ", [$cleanTerm, $cleanTerm, $cleanTerm]);
    }

    /**
     * Nettoyage intelligent de la requête
     */
    private function cleanSearchQuery(string $query): string
    {
        $cleaned = preg_replace('/[^\w\s\-àâäéèêëïîôöùûüÿç]/ui', ' ', $query);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        return trim($cleaned);
    }

    /**
     * Formatter un produit unique depuis la DB
     */
    private function formatSingleProduct($product): array
    {
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
            'uuid' => $product->uuid,
            'name' => $product->name,
            'price' => (float) $product->price,
            'featured_image' => $image,
            'rating' => $product->rating ? (float) $product->rating : null,
            'review_count' => (int) ($product->review_count ?? 0),
            'is_featured' => (bool) $product->is_featured,
            'badges' => $badges,
            'relevance_score' => $product->relevance_score ?? 0
        ];
    }

    /**
     * Formatter une collection de produits pour l'API
     */
    private function formatProductsForAPI($products): array
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

    // ==========================================
    // MÉTHODES EXISTANTES (inchangées)
    // ==========================================

    /**
     * Affichage d'un produit
     */
    public function show(Product $product)
    {
        // Charger les relations nécessaires
        $product->load([
            'categories',
            'reviews' => function ($query) {
                $query->where('is_approved', true)
                      ->orderBy('created_at', 'desc')
                      ->take(10);
            },
            'reviews.user'
        ]);

        // Produits similaires (même catégorie)
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
     * Page de recherche avec approche par paramètres de requête (comme Amazon)
     * URL: /s?k=terme&price_min=10&price_max=100&category=electronique&page=2
     */
    public function searchPage(Request $request)
    {
        // Récupérer tous les paramètres de recherche et filtres
        $query = $request->input('k', ''); // 'k' comme Amazon (keyword)
        $page = $request->input('page', 1);
        $priceMin = $request->input('price_min');
        $priceMax = $request->input('price_max');
        $category = $request->input('category');
        $sortBy = $request->input('sort', 'relevance');
        
        // Log simplifié pour monitoring
        Log::info('SearchPage - Recherche', ['query' => $query]);
        
        // Si pas de terme de recherche, afficher la page d'accueil de recherche
        if (!$query || trim($query) === '' || strlen(trim($query)) < 1) {
            return $this->renderEmptySearchPage();
        }
        
        // 🔧 TEMPORAIRE: Désactiver le cache pour debug
        Log::info('🔍 SearchPage - Recherche directe (sans cache)', [
            'query' => $query,
            'page' => $page,
            'filters' => compact('priceMin', 'priceMax', 'category', 'sortBy')
        ]);
        
        $results = $this->performAdvancedSearchWithFilters($query, [
            'page' => $page,
            'price_min' => $priceMin,
            'price_max' => $priceMax,
            'category' => $category,
            'sort' => $sortBy
        ]);
        
        // 🐛 DEBUG: Vérifier si on a des résultats
        Log::info('🔍 SearchPage - Résultats obtenus', [
            'query' => $query,
            'total_results' => $results['products']['total'] ?? 0,
            'products_count' => count($results['products']['data'] ?? [])
        ]);
        
        // Analytics avec tous les paramètres
        $this->trackSearchAnalytics($query);
        
        return Inertia::render('SearchPage', [
            'searchQuery' => $query,
            'searchResults' => $results,
            'currentFilters' => [
                'price_min' => $priceMin,
                'price_max' => $priceMax,
                'category' => $category,
                'sort' => $sortBy
            ],
            'filters' => $this->getAvailableFilters()
        ]);
    }

    /**
     * 🚀 NOUVELLE VERSION SIMPLIFIÉE - Recherche avec filtres via Eloquent
     */
    private function performAdvancedSearchWithFilters(string $query, array $filters = []): array
    {
        $startTime = microtime(true);
        $page = $filters['page'] ?? 1;
        $perPage = 20;
        
        try {
            $cleanQuery = $this->cleanSearchQuery($query);
            
            Log::info('🔍 Recherche avec filtres ENTRÉE', [
                'query_original' => $query,
                'query_clean' => $cleanQuery,
                'filters' => $filters
            ]);
            
            // 🚀 ÉTAPE 1: Construire la requête Eloquent de base
            $baseQuery = Product::where('status', 'active');
            
            // 🚀 ÉTAPE 2: Appliquer la recherche textuelle (ILIKE simple pour debug)
            $baseQuery->where(function ($q) use ($cleanQuery) {
                $q->where('name', 'ILIKE', "%{$cleanQuery}%")
                  ->orWhere('description', 'ILIKE', "%{$cleanQuery}%");
            });
            
            // 🚀 ÉTAPE 3: Appliquer les filtres
            if (!empty($filters['price_min'])) {
                $baseQuery->where('price', '>=', $filters['price_min']);
            }
            if (!empty($filters['price_max'])) {
                $baseQuery->where('price', '<=', $filters['price_max']);
            }
            if (!empty($filters['category'])) {
                $baseQuery->whereHas('categories', function ($q) use ($filters) {
                    $q->where('slug', $filters['category']);
                });
            }
            
            // 🚀 ÉTAPE 4: Appliquer le tri
            $this->applySorting($baseQuery, $filters['sort'] ?? 'relevance');
            
            // 🚀 ÉTAPE 5: Paginer
            $products = $baseQuery->paginate($perPage, ['*'], 'page', $page);
            
            // 🐛 DEBUG: Vérifier les résultats obtenus
            Log::info('🔍 Recherche avec filtres RÉSULTATS', [
                'query' => $cleanQuery,
                'total' => $products->total(),
                'count' => $products->count(),
                'current_page' => $products->currentPage()
            ]);
            
            // 🚀 ÉTAPE 6: Générer des suggestions si peu de résultats
            $suggestions = $products->count() < 3 ? $this->generateSimpleSuggestions($cleanQuery) : [];
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'products' => [
                    'data' => $this->formatProductsForAPI($products->getCollection()),
                    'total' => $products->total(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage()
                ],
                'suggestions' => $suggestions,
                'executionTime' => $executionTime
            ];
            
        } catch (\Exception $e) {
            Log::error('Erreur recherche avec filtres simplifiée: ' . $e->getMessage());
            
            // Fallback vers recherche basique
            $fallbackResults = $this->performSimpleLiveSearch($query, $perPage);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'products' => [
                    'data' => $fallbackResults['products'],
                    'total' => $fallbackResults['total'],
                    'current_page' => $page,
                    'last_page' => 1,
                    'per_page' => $perPage
                ],
                'suggestions' => $fallbackResults['suggestions'],
                'executionTime' => $executionTime
            ];
        }
    }
    
    /**
     * 🚀 Appliquer le tri de façon simplifiée
     */
    private function applySorting($query, string $sortBy): void
    {
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('price', 'asc')->orderBy('sales_count', 'desc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc')->orderBy('sales_count', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc')->orderBy('sales_count', 'desc');
                break;
            case 'rating':
                $query->orderBy('rating', 'desc')->orderBy('review_count', 'desc');
                break;
            case 'popularity':
                $query->orderBy('sales_count', 'desc')->orderBy('rating', 'desc');
                break;
            case 'relevance':
            default:
                // Pour la pertinence, on s'appuie sur PostgreSQL Full-Text ranking + popularité
                $query->orderBy('sales_count', 'desc')->orderBy('rating', 'desc');
                break;
        }
    }
    
    /**
     * Construire la clause ORDER BY selon le type de tri
     * 🔧 CORRECTION: Ne plus retourner de paramètres ici, c'est géré dans performAdvancedSearchWithFilters
     */
    private function buildOrderClause(string $sortBy, string $cleanQuery): string
    {
        switch ($sortBy) {
            case 'price_asc':
                return 'ORDER BY p.price ASC, p.sales_count DESC';
            case 'price_desc':
                return 'ORDER BY p.price DESC, p.sales_count DESC';
            case 'newest':
                return 'ORDER BY p.created_at DESC, p.sales_count DESC';
            case 'rating':
                return 'ORDER BY p.rating DESC, p.review_count DESC, p.sales_count DESC';
            case 'popularity':
                return 'ORDER BY p.sales_count DESC, p.rating DESC';
            case 'relevance':
            default:
                // Note: Le cas 'relevance' est maintenant géré directement dans performAdvancedSearchWithFilters
                return 'ORDER BY p.sales_count DESC, p.rating DESC'; // Fallback
        }
    }
    
    /**
     * Rendre la page de recherche vide
     */
    private function renderEmptySearchPage()
    {
        $categories = Cache::remember('categories.active', 3600, function () {
            return Category::where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']);
        });
        
        return Inertia::render('SearchPage', [
            'searchQuery' => '',
            'searchResults' => [
                'products' => ['data' => [], 'total' => 0, 'current_page' => 1, 'last_page' => 1, 'per_page' => 20],
                'suggestions' => [],
                'executionTime' => 0
            ],
            'currentFilters' => [],
            'filters' => $this->getAvailableFilters()
        ]);
    }
    
    /**
    * Récupérer tous les filtres disponibles pour l'interface de recherche
    * Cette méthode centralise la logique de filtres pour éviter la duplication
    */
    private function getAvailableFilters(): array
    {
        // Utiliser le cache pour les données qui changent rarement
        $categories = Cache::remember('categories.active', 3600, function () {
            return Category::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug']);
        });
        
        // Calculer les tranches de prix dynamiquement basées sur les produits actuels
        $priceStats = Cache::remember('products.price_stats', 1800, function () {
            return Product::where('status', 'active')
                ->selectRaw('MIN(price) as min_price, MAX(price) as max_price, AVG(price) as avg_price')
                ->first();
        });
        
        // Générer les tranches de prix intelligemment
        $priceRanges = $this->generateSmartPriceRanges($priceStats);
        
        // Options de tri disponibles
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
            'priceRanges' => $priceRanges,
            'sortOptions' => $sortOptions,
            'priceStats' => $priceStats // Utile pour les sliders de prix
        ];
    }

    /**
     * Générer des tranches de prix intelligentes basées sur les données réelles
     */
    private function generateSmartPriceRanges($priceStats): array
    {
        if (!$priceStats || !$priceStats->min_price || !$priceStats->max_price) {
            // Fallback vers les tranches par défaut si pas de données
            return $this->getPriceRanges();
        }
        
        $minPrice = (float) $priceStats->min_price;
        $maxPrice = (float) $priceStats->max_price;
        $avgPrice = (float) $priceStats->avg_price;
        
        // Créer des tranches intelligentes basées sur les données réelles
        $ranges = [];
        
        // Tranche "économique" (moins de la moitié du prix moyen)
        $economicThreshold = $avgPrice * 0.5;
        if ($economicThreshold > $minPrice) {
            $ranges[] = [
                'min' => $minPrice,
                'max' => $economicThreshold,
                'label' => 'Moins de ' . number_format($economicThreshold, 0) . '€'
            ];
        }
        
        // Tranche "standard" (autour du prix moyen)
        $ranges[] = [
            'min' => $economicThreshold,
            'max' => $avgPrice * 1.5,
            'label' => number_format($economicThreshold, 0) . '€ - ' . number_format($avgPrice * 1.5, 0) . '€'
        ];
        
        // Tranche "premium" (plus que 1.5 fois le prix moyen)
        $premiumThreshold = $avgPrice * 1.5;
        if ($premiumThreshold < $maxPrice) {
            $ranges[] = [
                'min' => $premiumThreshold,
                'max' => $maxPrice,
                'label' => 'Plus de ' . number_format($premiumThreshold, 0) . '€'
            ];
        }
        
        return $ranges;
    }

    /**
     * Générer les tranches de prix pour les filtres
     */
    private function getPriceRanges(): array
    {
        return [
            ['min' => 0, 'max' => 25, 'label' => 'Moins de 25€'],
            ['min' => 25, 'max' => 50, 'label' => '25€ - 50€'],
            ['min' => 50, 'max' => 100, 'label' => '50€ - 100€'],
            ['min' => 100, 'max' => 200, 'label' => '100€ - 200€'],
            ['min' => 200, 'max' => 999999, 'label' => 'Plus de 200€'],
        ];
    }
}