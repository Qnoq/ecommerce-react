<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'seo_meta',
        'image_url',
        'is_active',
        'sort_order',
        'search_content',
    ];

    protected $casts = [
        'seo_meta' => 'array',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'id', // On cache l'ID interne, on expose l'UUID
    ];

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
            // Contenu de recherche automatique
            $model->search_content = $model->name . ' ' . $model->description;
        });
    }

    // Relations
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_categories');
    }

    // Route model binding par UUID
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    // Scopes pour les requêtes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}