<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /**
     * Page d'accueil avec données dynamiques
     */
    public function index()
    {
        // Récupérer les produits vedettes (mis en cache)
        $featuredProducts = Cache::remember('home.featured_products', 1800, function () {
            return Product::where('status', 'active')
                ->where('is_featured', true)
                ->with(['categories'])
                ->orderBy('sales_count', 'desc')
                ->orderBy('rating', 'desc')
                ->take(8)
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'uuid' => $product->uuid,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'price' => (float) $product->price,
                        'original_price' => $product->original_price ? (float) $product->original_price : null,
                        'featured_image' => $product->featured_image,
                        'rating' => $product->rating ? (float) $product->rating : null,
                        'review_count' => (int) $product->review_count,
                        'is_featured' => (bool) $product->is_featured,
                        'badges' => $this->getProductBadges($product),
                        'min_stock' => $this->getMinStock($product),
                    ];
                });
        });

        // Récupérer les catégories populaires
        $categories = Cache::remember('home.categories', 3600, function () {
            return Category::where('is_active', true)
                ->withCount('products')
                ->orderBy('products_count', 'desc')
                ->take(6)
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'count' => $category->products_count . '+',
                        'href' => '/categories/' . $category->slug,
                        'icon' => $this->getCategoryIcon($category->name),
                    ];
                });
        });

        // Statistiques du site
        $stats = Cache::remember('home.stats', 3600, function () {
            return [
                'total_products' => Product::where('status', 'active')->count(),
                'total_categories' => Category::where('is_active', true)->count(),
                'avg_rating' => Product::where('status', 'active')->avg('rating'),
            ];
        });

        return Inertia::render('welcome', [
            'user' => Auth::user(),
            'featuredProducts' => $featuredProducts,
            'categories' => $categories,
            'stats' => $stats,
            'searchResults' => [
                'products' => ['data' => [], 'total' => 0],
                'suggestions' => []
            ],
        ]);
    }

    /**
     * Obtenir les badges d'un produit
     */
    private function getProductBadges($product): array
    {
        $badges = [];
        
        if ($product->is_featured) {
            $badges[] = 'Coup de cœur';
        }
        
        if ($product->sales_count > 100) {
            $badges[] = 'Best seller';
        }
        
        if ($product->created_at > now()->subDays(30)) {
            $badges[] = 'Nouveauté';
        }
        
        if ($product->original_price && $product->original_price > $product->price) {
            $badges[] = 'Promo';
        }
        
        return $badges;
    }

    /**
     * Obtenir le stock minimum d'un produit
     */
    private function getMinStock($product): int
    {
        // Vérifier s'il y a des variantes
        $variants = \DB::table('product_variants')
            ->where('product_id', $product->id)
            ->where('status', 'active')
            ->get();

        if ($variants->count() > 0) {
            return $variants->min('stock_quantity');
        }

        return $product->stock_quantity;
    }

    /**
     * Obtenir l'icône d'une catégorie
     */
    private function getCategoryIcon($categoryName): string
    {
        $icons = [
            'Electronics' => '📱',
            'Électronique' => '📱',
            'Fashion' => '👕',
            'Mode' => '👕',
            'Home' => '🏠',
            'Maison' => '🏠',
            'Sports' => '⚽',
            'Sport' => '⚽',
            'Beauty' => '💄',
            'Beauté' => '💄',
            'Books' => '📚',
            'Livres' => '📚',
            'Toys' => '🧸',
            'Jouets' => '🧸',
            'Automotive' => '🚗',
            'Auto' => '🚗',
        ];

        return $icons[$categoryName] ?? '🛍️';
    }
}
