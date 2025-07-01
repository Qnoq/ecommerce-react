<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_sku',
        'product_description',
        'product_image',
        'quantity',
        'unit_price',
        'total_price',
        'product_options',
        'customizations',
        'status',
        'tracking_number',
        'shipped_at',
        'delivered_at',
        'refunded_amount',
        'refunded_at',
        'return_status',
        'return_reason',
        'return_requested_at',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'product_options' => 'array',
        'customizations' => 'array',
        'metadata' => 'array',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'refunded_at' => 'datetime',
        'return_requested_at' => 'datetime',
    ];

    protected $hidden = ['id'];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
            
            // Calcul automatique du prix total
            $model->total_price = $model->quantity * $model->unit_price;
        });
    }

    // Relations
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
    public function scopeShipped($query)
    {
        return $query->where('status', 'shipped');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeReturnable($query)
    {
        return $query->where('status', 'delivered')
                    ->where('return_status', 'none')
                    ->where('delivered_at', '>', now()->subDays(30)); // Retour possible 30 jours
    }

    // Méthodes utilitaires
    public function requestReturn($reason)
    {
        $this->update([
            'return_status' => 'requested',
            'return_reason' => $reason,
            'return_requested_at' => now(),
        ]);
    }

    public function approveReturn()
    {
        $this->update(['return_status' => 'approved']);
    }

    public function processRefund($amount = null)
    {
        $refundAmount = $amount ?? $this->total_price;
        
        $this->update([
            'refunded_amount' => $this->refunded_amount + $refundAmount,
            'refunded_at' => now(),
            'status' => $refundAmount >= $this->total_price ? 'refunded' : $this->status,
        ]);
    }

    // Accesseurs
    public function getDisplayNameAttribute()
    {
        $name = $this->product_name;
        
        if ($this->product_options) {
            $options = collect($this->product_options)
                ->map(fn($value, $key) => "$key: $value")
                ->implode(', ');
            $name .= " ($options)";
        }
        
        return $name;
    }

    public function getCanBeReturnedAttribute()
    {
        return $this->status === 'delivered' 
               && $this->return_status === 'none'
               && $this->delivered_at 
               && $this->delivered_at->gt(now()->subDays(30));
    }

    public function getIsRefundedAttribute()
    {
        return $this->refunded_amount > 0;
    }

    public function getIsFullyRefundedAttribute()
    {
        return $this->refunded_amount >= $this->total_price;
    }
}