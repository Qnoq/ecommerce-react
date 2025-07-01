<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Liste des produits avec pagination et filtres
     */
    public function index(Request $request)
    {
        // Construction de la requête avec les filtres
        $query = Product::with(['categories', 'reviews'])
            ->where('status', 'active');

        // Filtre de recherche textuelle AMÉLIORÉE
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

        // Filtre par prix
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        // Tri AMÉLIORÉ avec pertinence
        $sortField = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        
        // Si recherche textuelle, trier par pertinence d'abord
        if ($request->filled('search')) {
            $query = $this->applySortByRelevance($query, $request->search, $sortField, $sortOrder);
        } else {
            // Validation des champs de tri autorisés
            $allowedSorts = ['name', 'price', 'created_at', 'updated_at', 'sales_count'];
            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortOrder);
            } else {
                $query->orderBy('created_at', 'desc');
            }
        }

        // Limite pour les requêtes AJAX
        $limit = $request->get('limit', 12);
        if ($request->ajax() || $request->wantsJson()) {
            $limit = min($limit, 50); // Max 50 pour les requêtes AJAX
        }

        // Pagination
        $products = $query->paginate($limit)->withQueryString();

        // Si c'est une requête AJAX (pour le modal de recherche), retourner JSON
        if ($request->ajax() || $request->wantsJson()) {
            Log::info('Requête AJAX détectée', [
                'ajax' => $request->ajax(),
                'wantsJson' => $request->wantsJson(),
                'search' => $request->search
            ]);
            
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

        // Récupération des catégories pour les filtres (navigation normale)
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'search' => $request->search,
                'category' => $request->category,
                'price_min' => $request->price_min,
                'price_max' => $request->price_max,
                'sort' => $sortField,
                'order' => $sortOrder,
            ]
        ]);
    }

    /**
     * Recherche live AMÉLIORÉE pour le modal Inertia
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

        // RECHERCHE AVANCÉE avec PostgreSQL
        $results = $this->performAdvancedLiveSearch($query);

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
     * API dédiée pour le modal de recherche avec recherche avancée
     */
    public function apiSearch(Request $request)
    {
        $searchTerm = $request->get('search', '');
        $limit = min($request->get('limit', 20), 50);
        
        Log::info('API Search appelée', [
            'search' => $searchTerm,
            'limit' => $limit
        ]);

        if (strlen($searchTerm) < 2) {
            return response()->json([
                'products' => [
                    'data' => [],
                    'total' => 0,
                    'per_page' => $limit,
                    'current_page' => 1,
                    'last_page' => 1
                ]
            ]);
        }

        // Utiliser la recherche avancée
        $results = $this->performAdvancedLiveSearch($searchTerm, $limit);

        return response()->json([
            'products' => [
                'data' => $results['products'],
                'total' => $results['total'],
                'per_page' => $limit,
                'current_page' => 1,
                'last_page' => ceil($results['total'] / $limit)
            ]
        ]);
    }

    /**
     * Suggestions AMÉLIORÉES pour l'autocomplétion
     */
    public function suggestions(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        // SUGGESTIONS AVANCÉES avec PostgreSQL
        $suggestions = $this->getAdvancedSuggestions($query);

        return response()->json([
            'suggestions' => $suggestions
        ]);
    }

    // ==========================================
    // MÉTHODES PRIVÉES POUR RECHERCHE AVANCÉE
    // ==========================================

    /**
     * Recherche live avancée avec PostgreSQL - VERSION QUI MARCHE
     */
    private function performAdvancedLiveSearch(string $query, int $limit = 20): array
    {
        // Nettoyer la requête
        $cleanQuery = $this->cleanSearchQuery($query);
        
        try {
            // Utiliser la requête qui fonctionne parfaitement
            $results = DB::select("
                SELECT 
                    p.uuid,
                    p.name,
                    p.price,
                    p.featured_image,
                    p.images,
                    p.rating,
                    p.review_count,
                    p.is_featured,
                    p.sales_count,
                    p.created_at,
                    (similarity(unaccent(p.name), unaccent(?)) * 80.0) as relevance_score
                FROM products p
                WHERE 
                    p.status = 'active'
                    AND similarity(unaccent(p.name), unaccent(?)) > 0.1
                ORDER BY relevance_score DESC, p.sales_count DESC
                LIMIT ?
            ", [$cleanQuery, $cleanQuery, $limit]);

            // Formatter les résultats
            $formattedProducts = array_map([$this, 'formatSingleProduct'], $results);
            
            // Générer des suggestions si pas de résultats
            $suggestions = count($formattedProducts) === 0 ? $this->generateSmartSuggestions($cleanQuery, 0) : [];
            
            return [
                'products' => $formattedProducts,
                'total' => count($formattedProducts),
                'suggestions' => $suggestions
            ];

        } catch (\Exception $e) {
            Log::error('Erreur recherche trigram: ' . $e->getMessage());
            
            // Fallback vers recherche simple
            return $this->performSimpleLiveSearch($query, $limit);
        }
    }

    /**
     * Test simple pour vérifier les extensions PostgreSQL
     */
    public function testSearch(Request $request)
    {
        if (!$request->has('debug')) {
            abort(404);
        }
        
        $testQuery = $request->get('q', 'ipone');
        
        try {
            // Test des extensions
            $extensions = DB::select("
                SELECT extname 
                FROM pg_extension 
                WHERE extname IN ('pg_trgm', 'unaccent', 'fuzzystrmatch')
            ");
            
            // Test trigram avec 'ipone' -> 'iphone'
            $trigramTest = DB::select("
                SELECT 
                    name,
                    similarity(unaccent(name), unaccent(?)) as sim
                FROM products 
                WHERE similarity(unaccent(name), unaccent(?)) > 0.1
                ORDER BY sim DESC
                LIMIT 10
            ", [$testQuery, $testQuery]);
            
            // Test Levenshtein
            $levenshteinTest = DB::select("
                SELECT 
                    name,
                    levenshtein(unaccent(lower(name)), unaccent(lower(?))) as distance
                FROM products 
                WHERE levenshtein(unaccent(lower(name)), unaccent(lower(?))) <= 3
                ORDER BY distance ASC
                LIMIT 10
            ", [$testQuery, $testQuery]);

            // Test de notre méthode de recherche optimisée
            $optimizedSearchResults = $this->performAdvancedLiveSearch($testQuery, 10);
            
            return response()->json([
                'extensions' => $extensions,
                'trigram_test' => $trigramTest,
                'levenshtein_test' => $levenshteinTest,
                'optimized_search' => $optimizedSearchResults,
                'query' => $testQuery,
                'success' => true
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'query' => $testQuery,
                'success' => false
            ]);
        }
    }

    /**
     * Test simplifié pour isoler le problème
     */
    public function testSimpleSearch(Request $request)
    {
        if (!$request->has('debug')) {
            abort(404);
        }
        
        $testQuery = $request->get('q', 'ipone');
        
        try {
            // Test 1: Recherche exacte
            $exactTest = DB::select("
                SELECT 
                    p.uuid, p.name, p.status
                FROM products p
                WHERE 
                    p.status = 'active'
                    AND (
                        unaccent(lower(p.name)) = unaccent(lower(?))
                        OR unaccent(lower(p.name)) LIKE unaccent(lower(?)) || '%'
                    )
            ", [$testQuery, $testQuery]);

            // Test 2: Recherche trigram simple
            $trigramTest = DB::select("
                SELECT 
                    p.uuid, p.name, p.status,
                    similarity(unaccent(p.name), unaccent(?)) as sim
                FROM products p
                WHERE 
                    p.status = 'active'
                    AND similarity(unaccent(p.name), unaccent(?)) > 0.1
                ORDER BY sim DESC
            ", [$testQuery, $testQuery]);

            // Test 3: Recherche trigram avec toutes les colonnes qu'on utilise
            $fullTrigramTest = DB::select("
                SELECT 
                    p.uuid,
                    p.name,
                    p.price,
                    p.featured_image,
                    p.images,
                    p.rating,
                    p.review_count,
                    p.is_featured,
                    p.sales_count,
                    p.created_at,
                    similarity(unaccent(p.name), unaccent(?)) as sim
                FROM products p
                WHERE 
                    p.status = 'active'
                    AND similarity(unaccent(p.name), unaccent(?)) > 0.1
                ORDER BY sim DESC
            ", [$testQuery, $testQuery]);

            return response()->json([
                'exact_test' => $exactTest,
                'trigram_test' => $trigramTest,
                'full_trigram_test' => $fullTrigramTest,
                'query' => $testQuery,
                'success' => true
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'query' => $testQuery,
                'success' => false
            ]);
        }
    }

    /**
     * Debug: voir les produits dans la base
     */
    public function debugProducts(Request $request)
    {
        if (!$request->has('debug')) {
            abort(404);
        }
        
        try {
            // Compter tous les produits
            $totalProducts = Product::count();
            
            // Produits par statut
            $productsByStatus = Product::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get();
            
            // Quelques produits avec leurs noms
            $sampleProducts = Product::select('name', 'status', 'uuid')
                ->take(10)
                ->get();
            
            // Produits qui contiennent "phone" ou "iPhone"
            $phoneProducts = Product::where('name', 'ILIKE', '%phone%')
                ->orWhere('name', 'ILIKE', '%iphone%')
                ->select('name', 'status', 'uuid')
                ->get();
            
            return response()->json([
                'total_products' => $totalProducts,
                'products_by_status' => $productsByStatus,
                'sample_products' => $sampleProducts,
                'phone_products' => $phoneProducts,
                'success' => true
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'success' => false
            ]);
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
     * Suggestions ultra-intelligentes
     */
    private function generateUltraSmartSuggestions(string $query, int $resultCount): array
    {
        if ($resultCount > 0) {
            return []; // Pas de suggestions si on a des résultats
        }
    
        $suggestions = [];
        
        try {
            // 1. Recherche par correction orthographique automatique
            $correctionSuggestions = DB::select("
                SELECT DISTINCT 
                    p.name,
                    similarity(unaccent(p.name), unaccent(?)) as sim,
                    levenshtein(unaccent(lower(p.name)), unaccent(lower(?))) as distance
                FROM products p 
                WHERE 
                    p.status = 'active'
                    AND (
                        similarity(unaccent(p.name), unaccent(?)) > 0.15
                        OR levenshtein(unaccent(lower(p.name)), unaccent(lower(?))) <= 3
                        OR soundex(p.name) = soundex(?)
                    )
                ORDER BY sim DESC, distance ASC
                LIMIT 5
            ", [$query, $query, $query, $query, $query]);
    
            foreach ($correctionSuggestions as $suggestion) {
                $suggestions[] = $suggestion->name;
            }
    
            // 2. Suggestions de catégories similaires
            $categorySuggestions = DB::select("
                SELECT DISTINCT 
                    c.name,
                    similarity(unaccent(c.name), unaccent(?)) as sim
                FROM categories c 
                WHERE 
                    c.is_active = true
                    AND similarity(unaccent(c.name), unaccent(?)) > 0.2
                ORDER BY sim DESC
                LIMIT 3
            ", [$query, $query]);
    
            foreach ($categorySuggestions as $category) {
                $suggestions[] = $category->name;
            }
    
        } catch (\Exception $e) {
            Log::error('Erreur génération suggestions ultra: ' . $e->getMessage());
        }
    
        return array_unique(array_slice($suggestions, 0, 5));
    }

    /**
     * Suggestions avancées pour autocomplétion
     */
    private function getAdvancedSuggestions(string $query): array
    {
        $cleanQuery = $this->cleanSearchQuery($query);
        
        // Suggestions de produits avec scoring ultra-précis
        $productSuggestions = DB::select("
            SELECT DISTINCT 
                p.uuid,
                p.name,
                p.price,
                p.featured_image,
                p.images,
                
                -- Score de suggestion
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

        // Suggestions de catégories
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

        // Formatter les suggestions de produits
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

        // Formatter les suggestions de catégories
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
    }

    /**
     * Suggestions intelligentes basées sur les résultats
     */
    private function generateSmartSuggestions(string $query, int $resultCount): array
    {
        if ($resultCount > 0) {
            return []; // Pas de suggestions si on a des résultats
        }

        // Générer des suggestions alternatives avec correction d'orthographe
        $suggestions = [];
        
        try {
            // Recherche de termes similaires
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
        // Supprimer les caractères spéciaux
        $cleaned = preg_replace('/[^\w\s\-àâäéèêëïîôöùûüÿç]/ui', ' ', $query);
        
        // Supprimer les espaces multiples
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        
        // Trim
        $cleaned = trim($cleaned);
        
        return $cleaned;
    }

    /**
     * Formatter un produit unique depuis la DB
     */
    private function formatSingleProduct($product): array
    {
        // Récupérer l'image principale ou la première des images
        $image = $product->featured_image;
        if (!$image && $product->images) {
            $images = is_string($product->images) ? json_decode($product->images, true) : $product->images;
            $image = is_array($images) && count($images) > 0 ? $images[0] : null;
        }

        // Badges du produit
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
            // Récupérer l'image principale ou la première des images
            $image = $product->featured_image;
            if (!$image && $product->images) {
                $images = is_string($product->images) ? json_decode($product->images, true) : $product->images;
                $image = is_array($images) && count($images) > 0 ? $images[0] : null;
            }

            // Badges du produit
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
     * Page de recherche dédiée (si besoin)
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        if (empty($query)) {
            return redirect()->route('products.index');
        }

        // Utiliser la recherche avancée
        $products = Product::with(['categories', 'reviews'])
            ->where('status', 'active');
            
        $products = $this->applyAdvancedSearch($products, $query);
        $products = $this->applySortByRelevance($products, $query);
        
        $products = $products->paginate(16)->withQueryString();

        return Inertia::render('Products/Search', [
            'products' => $products,
            'query' => $query,
            'total' => $products->total()
        ]);
    }
}