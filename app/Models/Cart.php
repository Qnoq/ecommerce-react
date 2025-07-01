<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_id',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total',
        'currency',
        'coupon_code',
        'status',
        'metadata',
        'last_activity_at',
        'expires_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'metadata' => 'array',
        'last_activity_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = ['id'];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
            
            // Expiration par défaut : 30 jours
            if (empty($model->expires_at)) {
                $model->expires_at = now()->addDays(30);
            }
            
            $model->last_activity_at = now();
        });

        static::updating(function ($model) {
            $model->last_activity_at = now();
        });
    }

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function activeItems(): HasMany
    {
        return $this->hasMany(CartItem::class)->where('status', 'active');
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

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId)->whereNull('user_id');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    // Méthodes utilitaires
    public function calculateTotals()
    {
        $activeItems = $this->activeItems;
        
        $this->subtotal = $activeItems->sum('total_price');
        $this->tax_amount = $this->calculateTax();
        $this->shipping_amount = $this->calculateShipping();
        $this->total = $this->subtotal + $this->tax_amount + $this->shipping_amount - $this->discount_amount;
        
        $this->save();
    }

    public function getItemsCountAttribute()
    {
        return $this->activeItems->sum('quantity');
    }

    public function isEmpty()
    {
        return $this->activeItems->count() === 0;
    }

    protected function calculateTax()
    {
        // Logique de calcul de TVA (20% en France)
        return $this->subtotal * 0.20;
    }

    protected function calculateShipping()
    {
        // Livraison gratuite dès 50€
        return $this->subtotal >= 50 ? 0 : 5.99;
    }
}