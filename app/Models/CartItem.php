<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'product_options',
        'options_hash', // NOUVEAU CHAMP
        'product_snapshot',
        'customizations',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'product_options' => 'array',
        'product_snapshot' => 'array',
        'customizations' => 'array',
    ];

    protected $hidden = ['id'];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
            
            // GÉNÉRER LE HASH DES OPTIONS pour l'unicité
            $model->options_hash = $model->generateOptionsHash();
            
            // Calcul automatique du prix total
            $model->total_price = $model->quantity * $model->unit_price;
            
            // Snapshot du produit
            if ($model->product && empty($model->product_snapshot)) {
                $model->product_snapshot = [
                    'name' => $model->product->name,
                    'image' => $model->product->featured_image,
                    'sku' => $model->product->sku,
                ];
            }
        });

        static::updating(function ($model) {
            // Recalculer le hash si les options changent
            if ($model->isDirty('product_options')) {
                $model->options_hash = $model->generateOptionsHash();
            }
            
            // Recalcul du prix total si quantité ou prix change
            if ($model->isDirty(['quantity', 'unit_price'])) {
                $model->total_price = $model->quantity * $model->unit_price;
            }
        });

        // Recalculer les totaux du panier après modification
        static::saved(function ($model) {
            $model->cart->calculateTotals();
        });

        static::deleted(function ($model) {
            $model->cart->calculateTotals();
        });
    }

    /**
     * Générer un hash unique pour les options du produit
     */
    protected function generateOptionsHash()
    {
        if (empty($this->product_options)) {
            return null;
        }
        
        // Trier les options pour avoir un hash cohérent
        $options = $this->product_options;
        ksort($options);
        
        return md5(json_encode($options));
    }

    // Relations
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Route model binding
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Méthodes utilitaires
    public function updateQuantity(int $quantity)
    {
        if ($quantity <= 0) {
            $this->delete();
            return;
        }

        $this->update([
            'quantity' => $quantity,
            'total_price' => $quantity * $this->unit_price,
        ]);
    }

    public function getProductDisplayNameAttribute()
    {
        $name = $this->product_snapshot['name'] ?? $this->product->name ?? 'Produit supprimé';
        
        if ($this->product_options) {
            $options = collect($this->product_options)
                ->map(fn($value, $key) => "$key: $value")
                ->implode(', ');
            $name .= " ($options)";
        }
        
        return $name;
    }

    /**
     * Méthode statique pour trouver un item existant
     */
    public static function findExisting($cartId, $productId, $options = null)
    {
        $query = static::where('cart_id', $cartId)
                      ->where('product_id', $productId);
        
        if ($options) {
            ksort($options);
            $hash = md5(json_encode($options));
            $query->where('options_hash', $hash);
        } else {
            $query->whereNull('options_hash');
        }
        
        return $query->first();
    }
}