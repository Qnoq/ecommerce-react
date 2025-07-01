<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'coupon_code',
        'coupon_discount',
        'billing_address',
        'shipping_address',
        'payment_status',
        'payment_method',
        'payment_intent_id',
        'payment_metadata',
        'payment_date',
        'shipping_method',
        'shipping_cost',
        'tracking_number',
        'carrier',
        'shipping_metadata',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'customer_notes',
        'admin_notes',
        'source',
        'user_agent',
        'ip_address',
        'invoice_number',
        'invoice_date',
        'metadata',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'coupon_discount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'payment_metadata' => 'array',
        'shipping_metadata' => 'array',
        'metadata' => 'array',
        'payment_date' => 'datetime',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'invoice_date' => 'datetime',
    ];

    protected $hidden = ['id'];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
            
            if (empty($model->order_number)) {
                $model->order_number = $model->generateOrderNumber();
            }
        });
    }

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Route model binding
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    // Scopes
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeShipped($query)
    {
        return $query->where('status', 'shipped');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Méthodes utilitaires
    protected function generateOrderNumber()
    {
        $year = date('Y');
        
        // Récupérer le DERNIER numéro (pas seulement cette année)
        $lastOrder = static::orderBy('id', 'desc')->first();
        
        if ($lastOrder && $lastOrder->order_number) {
            // Extraire le numéro et incrémenter
            $lastNumber = intval(substr($lastOrder->order_number, -6));
            $number = $lastNumber + 1;
        } else {
            $number = 1;
        }
        
        return sprintf('ORD-%s-%06d', $year, $number);
    }

    public function markAsPaid($paymentMethod = null, $paymentIntentId = null)
    {
        $this->update([
            'payment_status' => 'paid',
            'payment_method' => $paymentMethod,
            'payment_intent_id' => $paymentIntentId,
            'payment_date' => now(),
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    public function markAsShipped($trackingNumber = null, $carrier = null)
    {
        $this->update([
            'status' => 'shipped',
            'tracking_number' => $trackingNumber,
            'carrier' => $carrier,
            'shipped_at' => now(),
        ]);
        
        // Marquer tous les items comme expédiés
        $this->items()->update([
            'status' => 'shipped',
            'shipped_at' => now(),
            'tracking_number' => $trackingNumber,
        ]);
    }

    public function markAsDelivered()
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
        
        $this->items()->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'admin_notes' => $this->admin_notes . "\nAnnulée: " . $reason,
        ]);
    }

    // Accesseurs
    public function getTotalItemsAttribute()
    {
        return $this->items->sum('quantity');
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'En attente',
            'processing' => 'En traitement',
            'confirmed' => 'Confirmée',
            'preparing' => 'Préparation',
            'shipped' => 'Expédiée',
            'delivered' => 'Livrée',
            'cancelled' => 'Annulée',
            'refunded' => 'Remboursée',
            'returned' => 'Retournée',
            default => 'Inconnu',
        };
    }

    public function getPaymentStatusLabelAttribute()
    {
        return match($this->payment_status) {
            'pending' => 'En attente',
            'authorized' => 'Autorisé',
            'paid' => 'Payé',
            'partially_paid' => 'Partiellement payé',
            'failed' => 'Échec',
            'cancelled' => 'Annulé',
            'refunded' => 'Remboursé',
            'partially_refunded' => 'Partiellement remboursé',
            default => 'Inconnu',
        };
    }

    public function getCanBeCancelledAttribute()
    {
        return in_array($this->status, ['pending', 'processing', 'confirmed']);
    }

    public function getIsShippedAttribute()
    {
        return in_array($this->status, ['shipped', 'delivered']);
    }

    public function getIsDeliveredAttribute()
    {
        return $this->status === 'delivered';
    }
}