<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Wishlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'note',
        'priority',
        'price_when_added',
        'was_in_stock_when_added',
        'notify_price_drop',
        'notify_back_in_stock',
        'notify_promotion',
        'is_public',
        'public_token',
        'category',
        'tags',
    ];

    protected $casts = [
        'price_when_added' => 'decimal:2',
        'was_in_stock_when_added' => 'boolean',
        'notify_price_drop' => 'boolean',
        'notify_back_in_stock' => 'boolean',
        'notify_promotion' => 'boolean',
        'is_public' => 'boolean',
        'tags' => 'array', // Conversion automatique JSON <-> Array PHP
    ];

    protected $hidden = ['id']; // On cache l'ID interne, on expose l'UUID

    protected static function boot()
    {
        parent::boot();
        
        // HOOK DE CRÉATION - Actions automatiques lors de la création
        static::creating(function ($model) {
            // Génération automatique de l'UUID si pas fourni
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
            
            // Si le produit existe, on sauvegarde son prix et stock actuels
            if ($model->product) {
                $model->price_when_added = $model->product->price;
                $model->was_in_stock_when_added = $model->product->in_stock;
            }
            
            // Génération du token public si la wishlist est publique
            if ($model->is_public && empty($model->public_token)) {
                $model->public_token = Str::random(32);
            }
        });

        // HOOK DE MISE À JOUR
        static::updating(function ($model) {
            // Si on rend la wishlist publique, générer un token
            if ($model->is_public && empty($model->public_token)) {
                $model->public_token = Str::random(32);
            }
            
            // Si on rend la wishlist privée, supprimer le token
            if (!$model->is_public) {
                $model->public_token = null;
            }
        });
    }

    // RELATIONS ELOQUENT
    
    /**
     * Utilisateur propriétaire de la wishlist
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Produit dans la wishlist
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ROUTE MODEL BINDING - Utiliser l'UUID au lieu de l'ID
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    // SCOPES - Requêtes prédéfinies réutilisables

    /**
     * Scope: Wishlists publiques seulement
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope: Wishlists privées seulement
     */
    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    /**
     * Scope: Wishlists d'un utilisateur triées par priorité
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId)
                    ->orderBy('priority')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Scope: Items qui veulent être notifiés des baisses de prix
     */
    public function scopeWantsPriceDropNotification($query)
    {
        return $query->where('notify_price_drop', true);
    }

    /**
     * Scope: Items qui veulent être notifiés du retour en stock
     */
    public function scopeWantsStockNotification($query)
    {
        return $query->where('notify_back_in_stock', true);
    }

    /**
     * Scope: Wishlist par catégorie
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // MÉTHODES UTILITAIRES

    /**
     * Vérifier si le prix a baissé depuis l'ajout à la wishlist
     */
    public function hasPriceDropped()
    {
        if (!$this->price_when_added || !$this->product) {
            return false;
        }
        
        return $this->product->price < $this->price_when_added;
    }

    /**
     * Calculer le pourcentage de baisse de prix
     */
    public function getPriceDropPercentage()
    {
        if (!$this->hasPriceDropped()) {
            return 0;
        }
        
        $oldPrice = $this->price_when_added;
        $newPrice = $this->product->price;
        
        return round((($oldPrice - $newPrice) / $oldPrice) * 100, 2);
    }

    /**
     * Vérifier si le produit est revenu en stock
     */
    public function isBackInStock()
    {
        return !$this->was_in_stock_when_added && 
               $this->product && 
               $this->product->in_stock;
    }

    /**
     * Générer l'URL publique de partage
     */
    public function getPublicUrl()
    {
        if (!$this->is_public || !$this->public_token) {
            return null;
        }
        
        return route('wishlist.public', $this->public_token);
    }

    /**
     * Basculer l'état public/privé
     */
    public function togglePublic()
    {
        $this->update(['is_public' => !$this->is_public]);
    }

    // ACCESSEURS - Attributs calculés

    /**
     * Obtenir le niveau de priorité en texte
     */
    public function getPriorityLabelAttribute()
    {
        return match($this->priority) {
            1 => 'Très envie',
            2 => 'Envie',
            3 => 'Neutre',
            4 => 'Pas pressé',
            5 => 'Plus tard',
            default => 'Non défini',
        };
    }

    /**
     * Obtenir la couleur CSS pour la priorité
     */
    public function getPriorityColorAttribute()
    {
        return match($this->priority) {
            1 => 'text-red-600',    // Rouge = très envie
            2 => 'text-orange-600', // Orange = envie
            3 => 'text-yellow-600', // Jaune = neutre
            4 => 'text-blue-600',   // Bleu = pas pressé
            5 => 'text-gray-600',   // Gris = plus tard
            default => 'text-gray-400',
        };
    }

    /**
     * Vérifier si des notifications sont activées
     */
    public function getHasNotificationsAttribute()
    {
        return $this->notify_price_drop || 
               $this->notify_back_in_stock || 
               $this->notify_promotion;
    }
}