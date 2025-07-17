<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'price',
        'original_price',
        'currency',
        'sku',
        'stock_quantity',
        'manage_stock',
        'in_stock',
        'low_stock_threshold',
        'images',
        'featured_image',
        'videos',
        'weight',
        'dimensions',
        'status',
        'is_featured',
        'is_digital',
        'attributes',
        'seo_meta',
        'rating',
        'review_count',
        'view_count',
        'sales_count',
        'wishlist_count',
        'search_content',
        'published_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'rating' => 'decimal:2',
        'images' => 'array',
        'videos' => 'array',
        'dimensions' => 'array',
        'attributes' => 'array',
        'seo_meta' => 'array',
        'manage_stock' => 'boolean',
        'in_stock' => 'boolean',
        'is_featured' => 'boolean',
        'is_digital' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected $hidden = ['id'];

    /**
     * 🚀 OPTIMISATION: Événements pour invalidation automatique du cache
     */
    protected static function booted()
    {
        // Générer UUID automatiquement
        static::creating(function ($product) {
            if (empty($product->uuid)) {
                $product->uuid = (string) Str::uuid();
            }
        });

        // 🔥 INVALIDATION CACHE AUTOMATIQUE
        static::saved(function ($product) {
            static::invalidateProductCaches($product);
        });

        static::deleted(function ($product) {
            static::invalidateProductCaches($product);
        });
    }

    /**
     * 🚀 Invalidation intelligente des caches
     */
    private static function invalidateProductCaches(Product $product): void
    {
        try {
            // 1. Vider le cache de recherche (toutes les clés search:*)
            $searchPattern = config('cache.prefix') . 'search:*';
            $searchKeys = \Illuminate\Support\Facades\Redis::connection('cache')->keys($searchPattern);
            if (!empty($searchKeys)) {
                \Illuminate\Support\Facades\Redis::connection('cache')->del($searchKeys);
                info('Cache de recherche invalidé', [
                    'product_id' => $product->id,
                    'keys_deleted' => count($searchKeys)
                ]);
            }

            // 2. Vider le cache du catalogue
            $catalogPattern = config('cache.prefix') . 'catalog:*';
            $catalogKeys = \Illuminate\Support\Facades\Redis::connection('cache')->keys($catalogPattern);
            if (!empty($catalogKeys)) {
                \Illuminate\Support\Facades\Redis::connection('cache')->del($catalogKeys);
            }

            // 3. Vider les caches spécifiques si c'est un produit populaire
            if ($product->is_featured || $product->sales_count > 100) {
                Cache::forget('products.bestsellers');
                Cache::forget('products.featured');
                Cache::forget('search.popular');
            }

            // 4. Vider le cache des catégories si le produit a changé de catégories
            if ($product->isDirty('status') || $product->wasRecentlyCreated) {
                Cache::forget('categories.active');
            }

            info('Caches produit invalidés avec succès', [
                'product_id' => $product->id,
                'product_name' => $product->name
            ]);

        } catch (\Exception $e) {
            info('Erreur invalidation cache produit: ' . $e->getMessage(), [
                'product_id' => $product->id
            ]);
        }
    }

    /**
     * 🚀 Méthodes de cache statiques pour produits populaires
     */
    public static function getBestsellers(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('products.bestsellers', 3600, function () use ($limit) {
            return static::where('status', 'active')
                ->orderBy('sales_count', 'desc')
                ->orderBy('rating', 'desc')
                ->take($limit)
                ->get();
        });
    }

    public static function getFeatured(int $limit = 12): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('products.featured', 1800, function () use ($limit) {
            return static::where('status', 'active')
                ->where('is_featured', true)
                ->orderBy('created_at', 'desc')
                ->take($limit)
                ->get();
        });
    }

    public static function getRecent(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('products.recent', 900, function () use ($limit) {
            return static::where('status', 'active')
                ->where('created_at', '>', now()->subDays(30))
                ->orderBy('created_at', 'desc')
                ->take($limit)
                ->get();
        });
    }

    /**
     * 🚀 Méthode pour vider tous les caches liés aux produits
     */
    public static function clearAllProductCaches(): int
    {
        $deletedKeys = 0;
        
        try {
            $redis = \Illuminate\Support\Facades\Redis::connection('cache');
            $prefix = config('cache.prefix');
            
            // Patterns de cache à vider
            $patterns = [
                $prefix . 'search:*',
                $prefix . 'catalog:*',
                $prefix . 'products.*',
                $prefix . 'categories.*'
            ];
            
            foreach ($patterns as $pattern) {
                $keys = $redis->keys($pattern);
                if (!empty($keys)) {
                    $redis->del($keys);
                    $deletedKeys += count($keys);
                }
            }
            
            info('Tous les caches produits vidés', ['keys_deleted' => $deletedKeys]);
            
        } catch (\Exception $e) {
            info('Erreur lors du vidage des caches produits: ' . $e->getMessage());
        }
        
        return $deletedKeys;
    }

    // Méthode pour mettre à jour le contenu de recherche
    public function updateSearchContent()
    {
        $content = collect([
            $this->name,
            $this->description,
            $this->short_description,
            $this->sku,
            // Attributs dynamiques
            collect($this->attributes ?? [])->values()->implode(' '),
            // Noms des catégories
            $this->categories->pluck('name')->implode(' '),
        ])
        ->filter()
        ->map(function($text) {
            // Nettoyer et normaliser chaque partie
            return trim(preg_replace('/\s+/', ' ', $text));
        })
        ->filter(function($text) {
            return !empty($text);
        })
        ->unique() // Éviter les doublons
        ->implode(' ');

        $this->search_content = $content;
    }

    /**
     * 🚀 Recherche optimisée avec cache
     */
    public static function searchWithCache(string $query, array $filters = [], int $limit = 20): array
    {
        $cacheKey = 'search:advanced:' . md5($query . serialize($filters) . $limit);
        
        return Cache::remember($cacheKey, 600, function () use ($query, $filters, $limit) {
            info('Cache MISS - Recherche avancée produits', [
                'query' => $query,
                'filters' => $filters
            ]);
            
            // Implémentation de la recherche avancée
            $queryBuilder = static::query()
                ->where('status', 'active');
            
            // Appliquer les filtres de recherche textuelle
            if (!empty($query)) {
                $queryBuilder->where(function ($q) use ($query) {
                    // Normaliser la requête (enlever les accents et mettre en minuscules)
                    $normalizedQuery = strtolower($query);
                    
                    
                    $q->whereRaw('unaccent(lower(name)) ILIKE unaccent(lower(?))', ["%{$normalizedQuery}%"])
                      ->orWhereRaw('unaccent(lower(description)) ILIKE unaccent(lower(?))', ["%{$normalizedQuery}%"])
                      ->orWhereRaw('unaccent(lower(search_content)) ILIKE unaccent(lower(?))', ["%{$normalizedQuery}%"]);
                });
            }
            
            // Appliquer les filtres additionnels
            if (!empty($filters['category'])) {
                $queryBuilder->whereHas('categories', function ($q) use ($filters) {
                    $q->where('slug', $filters['category']);
                });
            }
            
            if (!empty($filters['price_min'])) {
                $queryBuilder->where('price', '>=', $filters['price_min']);
            }
            
            if (!empty($filters['price_max'])) {
                $queryBuilder->where('price', '<=', $filters['price_max']);
            }
            
            // Tri par pertinence si recherche textuelle
            if (!empty($query)) {
                $queryBuilder->orderByRaw("
                    CASE 
                        WHEN name ILIKE ? THEN 100
                        WHEN name ILIKE ? THEN 80
                        WHEN description ILIKE ? THEN 60
                        ELSE 40
                    END DESC,
                    sales_count DESC,
                    rating DESC
                ", ["{$query}%", "%{$query}%", "%{$query}%"]);
            } else {
                $queryBuilder->orderBy('sales_count', 'desc')
                            ->orderBy('rating', 'desc');
            }
            
            $results = $queryBuilder->limit($limit)->get();
            
            return [
                'products' => $results->toArray(),
                'total' => $results->count(),
                'query' => $query
            ];
        });
    }

    // Relations
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    // Route model binding par UUID
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePublished($query)
    {
        return $query->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('in_stock', true);
    }

    // Recherche textuelle PostgreSQL
    public function scopeSearch($query, $term)
    {
        return $query->whereRaw(
            "to_tsvector('french', COALESCE(name, '') || ' ' || COALESCE(description, '')) @@ plainto_tsquery('french', ?)",
            [$term]
        );
    }

    // Accesseurs
    public function getDiscountPercentageAttribute()
    {
        if ($this->original_price && $this->original_price > $this->price) {
            return round(((($this->original_price - $this->price) / $this->original_price) * 100), 2);
        }
        return 0;
    }

    public function getIsOnSaleAttribute()
    {
        return $this->original_price && $this->original_price > $this->price;
    }
}