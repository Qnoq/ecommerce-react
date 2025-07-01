<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
            if (empty($model->sku)) {
                $model->sku = 'SKU-' . strtoupper(Str::random(8));
            }
            // Contenu de recherche automatique
            $model->updateSearchContent();
        });

        static::updating(function ($model) {
            $model->updateSearchContent();
        });
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
        ])->filter()->implode(' ');

        $this->search_content = $content;
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