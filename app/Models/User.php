<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        // CHAMPS ORIGINAUX
        'name',
        'email',
        'password',
        
        // NOUVEAUX CHAMPS E-COMMERCE
        'uuid',
        'first_name',
        'last_name',
        'phone',
        'date_of_birth',
        'gender',
        'avatar',
        'bio',
        'addresses',
        'preferred_language',
        'preferred_currency',
        'accepts_marketing',
        'accepts_sms',
        'roles',
        'is_admin',
        'is_vendor',
        'is_active',
        'is_verified',
        'last_login_at',
        'last_login_ip',
        'total_orders',
        'total_spent',
        'average_order_value',
        'first_order_at',
        'last_order_at',
        'loyalty_points',
        'customer_tier',
        'notification_preferences',
        'referral_code',
        'referred_by',
        'metadata',
        'admin_notes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'id', // On cache l'ID interne, on expose l'UUID
        'password',
        'remember_token',
        'admin_notes', // Notes internes cachées du client
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            
            // NOUVEAUX CASTS
            'date_of_birth' => 'date',
            'addresses' => 'array', // JSON -> Array PHP
            'roles' => 'array',
            'notification_preferences' => 'array',
            'metadata' => 'array',
            'accepts_marketing' => 'boolean',
            'accepts_sms' => 'boolean',
            'is_admin' => 'boolean',
            'is_vendor' => 'boolean',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'last_login_at' => 'datetime',
            'total_orders' => 'integer',
            'total_spent' => 'decimal:2',
            'average_order_value' => 'decimal:2',
            'loyalty_points' => 'integer',
            'first_order_at' => 'datetime',
            'last_order_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        
        // GÉNÉRATION AUTOMATIQUE DE L'UUID ET CODE PARRAINAGE
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
            
            // Générer un code de parrainage unique
            if (empty($model->referral_code)) {
                do {
                    $code = strtoupper(Str::random(8));
                } while (static::where('referral_code', $code)->exists());
                
                $model->referral_code = $code;
            }
            
            // Séparer le nom complet en prénom/nom si fourni
            if ($model->name && !$model->first_name && !$model->last_name) {
                $nameParts = explode(' ', $model->name, 2);
                $model->first_name = $nameParts[0];
                $model->last_name = $nameParts[1] ?? '';
            }
        });

        // MISE À JOUR DU NOM COMPLET
        static::updating(function ($model) {
            if ($model->isDirty(['first_name', 'last_name'])) {
                $model->name = trim($model->first_name . ' ' . $model->last_name);
            }
        });
    }

    // RELATIONS E-COMMERCE

    /**
     * Commandes de l'utilisateur
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Articles dans la wishlist
     */
    public function wishlistItems(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Avis produits écrits par l'utilisateur
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    /**
     * Paniers de l'utilisateur (actifs et historiques)
     */
    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Panier actuel de l'utilisateur
     */
    public function activeCart()
    {
        return $this->hasOne(Cart::class)->where('status', 'active');
    }

    /**
     * Utilisateur qui a parrainé celui-ci
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /**
     * Utilisateurs parrainés par celui-ci
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    // ROUTE MODEL BINDING - Utiliser UUID au lieu de l'ID
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    // SCOPES POUR LES REQUÊTES

    /**
     * Scope: Utilisateurs actifs seulement
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Administrateurs
     */
    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    /**
     * Scope: Clients (non-admins)
     */
    public function scopeCustomers($query)
    {
        return $query->where('is_admin', false);
    }

    /**
     * Scope: Utilisateurs vérifiés
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope: Utilisateurs qui acceptent le marketing
     */
    public function scopeAcceptsMarketing($query)
    {
        return $query->where('accepts_marketing', true);
    }

    /**
     * Scope: Clients VIP (niveau gold ou platinum)
     */
    public function scopeVip($query)
    {
        return $query->whereIn('customer_tier', ['gold', 'platinum']);
    }

    // MÉTHODES UTILITAIRES

    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     */
    public function hasRole($role)
    {
        return in_array($role, $this->roles ?? []);
    }

    /**
     * Ajouter un rôle à l'utilisateur
     */
    public function addRole($role)
    {
        $roles = $this->roles ?? [];
        if (!in_array($role, $roles)) {
            $roles[] = $role;
            $this->update(['roles' => $roles]);
        }
    }

    /**
     * Obtenir ou créer le panier actif
     */
    public function getOrCreateActiveCart()
    {
        $cart = $this->activeCart;
        
        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $this->id,
                'status' => 'active',
            ]);
        }
        
        return $cart;
    }

    /**
     * Mettre à jour les statistiques client après une commande
     */
    public function updateOrderStats()
    {
        $orders = $this->orders()->where('status', 'delivered');
        
        $this->update([
            'total_orders' => $orders->count(),
            'total_spent' => $orders->sum('total_amount'),
            'average_order_value' => $orders->avg('total_amount') ?? 0,
            'first_order_at' => $orders->oldest()->first()?->created_at,
            'last_order_at' => $orders->latest()->first()?->created_at,
        ]);
        
        // Mettre à jour le niveau de fidélité
        $this->updateCustomerTier();
    }

    /**
     * Calculer et mettre à jour le niveau client
     */
    protected function updateCustomerTier()
    {
        $tier = match(true) {
            $this->total_spent >= 5000 => 'platinum',
            $this->total_spent >= 2000 => 'gold',
            $this->total_spent >= 500 => 'silver',
            default => 'bronze',
        };
        
        $this->update(['customer_tier' => $tier]);
    }

    /**
     * Ajouter des points de fidélité
     */
    public function addLoyaltyPoints($points)
    {
        $this->increment('loyalty_points', $points);
    }

    /**
     * Utiliser des points de fidélité
     */
    public function spendLoyaltyPoints($points)
    {
        if ($this->loyalty_points >= $points) {
            $this->decrement('loyalty_points', $points);
            return true;
        }
        return false;
    }

    // ACCESSEURS (ATTRIBUTS CALCULÉS)

    /**
     * Nom complet formaté
     */
    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name) ?: $this->name;
    }

    /**
     * Initiales
     */
    public function getInitialsAttribute()
    {
        $names = explode(' ', $this->full_name);
        $initials = '';
        
        foreach ($names as $name) {
            $initials .= strtoupper(substr($name, 0, 1));
        }
        
        return $initials;
    }

    /**
     * Niveau de fidélité en français
     */
    public function getCustomerTierLabelAttribute()
    {
        return match($this->customer_tier) {
            'bronze' => 'Bronze',
            'silver' => 'Argent',
            'gold' => 'Or',
            'platinum' => 'Platine',
            default => 'Nouveau',
        };
    }

    /**
     * Adresse de livraison par défaut
     */
    public function getDefaultShippingAddressAttribute()
    {
        $addresses = $this->addresses ?? [];
        
        // Chercher l'adresse marquée comme défaut
        foreach ($addresses as $address) {
            if ($address['is_default'] ?? false) {
                return $address;
            }
        }
        
        // Sinon retourner la première
        return $addresses[0] ?? null;
    }

    /**
     * Vérifier si le client est VIP
     */
    public function getIsVipAttribute()
    {
        return in_array($this->customer_tier, ['gold', 'platinum']);
    }
}