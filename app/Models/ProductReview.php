<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'order_id',
        'order_item_id',
        'rating',
        'title',
        'comment',
        'pros',
        'cons',
        'would_recommend',
        'is_verified_purchase',
        'is_approved',
        'approved_at',
        'approved_by',
        'images',
        'videos',
        'helpful_votes',
        'unhelpful_votes',
        'vendor_response',
        'vendor_response_at',
        'vendor_response_by',
        'is_flagged',
        'flag_count',
        'moderation_notes',
        'source',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'rating' => 'integer',
        'pros' => 'array',                    // Liste des points positifs
        'cons' => 'array',                    // Liste des points négatifs
        'would_recommend' => 'boolean',
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'images' => 'array',                  // URLs des images
        'videos' => 'array',                  // URLs des vidéos
        'helpful_votes' => 'integer',
        'unhelpful_votes' => 'integer',
        'vendor_response_at' => 'datetime',
        'is_flagged' => 'boolean',
        'flag_count' => 'integer',
        'metadata' => 'array',
    ];

    protected $hidden = ['id', 'ip_address']; // Cacher l'ID et l'IP pour la privacy

    protected static function boot()
    {
        parent::boot();
        
        // HOOK DE CRÉATION
        static::creating(function ($model) {
            // Génération automatique de l'UUID
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
            
            // Vérification automatique si c'est un achat vérifié
            if ($model->order_id && !isset($model->is_verified_purchase)) {
                $model->is_verified_purchase = true;
            }
            
            // Récupération automatique de l'IP et User Agent
            if (request()) {
                $model->ip_address = $model->ip_address ?? request()->ip();
                $model->user_agent = $model->user_agent ?? request()->userAgent();
            }
        });

        // HOOK APRÈS SAUVEGARDE - Mettre à jour les stats du produit
        static::saved(function ($model) {
            if ($model->is_approved) {
                $model->updateProductRating();
            }
        });

        // HOOK APRÈS SUPPRESSION - Mettre à jour les stats du produit
        static::deleted(function ($model) {
            $model->updateProductRating();
        });
    }

    // RELATIONS ELOQUENT

    /**
     * Produit concerné par l'avis
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Utilisateur qui a écrit l'avis
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Commande liée à l'avis (si achat vérifié)
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Article spécifique de la commande
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Admin qui a approuvé l'avis
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Personne qui a répondu pour le vendeur
     */
    public function vendorResponseBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_response_by');
    }

    // ROUTE MODEL BINDING
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    // SCOPES - Requêtes prédéfinies

    /**
     * Scope: Avis approuvés seulement
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope: Avis en attente de modération
     */
    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    /**
     * Scope: Avis vérifiés (achat confirmé)
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Scope: Avis signalés
     */
    public function scopeFlagged($query)
    {
        return $query->where('is_flagged', true);
    }

    /**
     * Scope: Avis par note
     */
    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope: Avis avec une note minimum
     */
    public function scopeMinRating($query, $minRating)
    {
        return $query->where('rating', '>=', $minRating);
    }

    /**
     * Scope: Avis les plus utiles
     */
    public function scopeMostHelpful($query)
    {
        return $query->orderByRaw('(helpful_votes - unhelpful_votes) DESC');
    }

    /**
     * Scope: Avis récents
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // MÉTHODES UTILITAIRES

    /**
     * Approuver l'avis
     */
    public function approve($approvedBy = null)
    {
        $this->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $approvedBy ?? auth()->id(),
        ]);
    }

    /**
     * Rejeter l'avis
     */
    public function reject()
    {
        $this->update([
            'is_approved' => false,
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * Signaler l'avis comme inapproprié
     */
    public function flag($reason = null)
    {
        $this->increment('flag_count');
        $this->update(['is_flagged' => true]);
        
        // Si trop de signalements, retirer automatiquement l'approbation
        if ($this->flag_count >= 3) {
            $this->reject();
        }
    }

    /**
     * Voter pour l'utilité de l'avis
     */
    public function voteHelpful()
    {
        $this->increment('helpful_votes');
    }

    public function voteUnhelpful()
    {
        $this->increment('unhelpful_votes');
    }

    /**
     * Ajouter une réponse du vendeur
     */
    public function addVendorResponse($response, $respondedBy = null)
    {
        $this->update([
            'vendor_response' => $response,
            'vendor_response_at' => now(),
            'vendor_response_by' => $respondedBy ?? auth()->id(),
        ]);
    }

    /**
     * Mettre à jour la note moyenne du produit
     */
    protected function updateProductRating()
    {
        if (!$this->product) return;

        // Calculer la nouvelle moyenne et le nombre d'avis approuvés
        $approvedReviews = static::where('product_id', $this->product_id)
            ->where('is_approved', true)
            ->get();

        $avgRating = $approvedReviews->avg('rating') ?? 0;
        $reviewCount = $approvedReviews->count();

        // Mettre à jour le produit
        $this->product->update([
            'rating' => round($avgRating, 2),
            'review_count' => $reviewCount,
        ]);
    }

    // ACCESSEURS - Attributs calculés

    /**
     * Score d'utilité de l'avis
     */
    public function getHelpfulnessScoreAttribute()
    {
        $total = $this->helpful_votes + $this->unhelpful_votes;
        
        if ($total === 0) {
            return 0;
        }
        
        return round(($this->helpful_votes / $total) * 100, 1);
    }

    /**
     * Obtenir les étoiles sous forme de texte
     */
    public function getStarsAttribute()
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    /**
     * Obtenir le statut en français
     */
    public function getStatusLabelAttribute()
    {
        if ($this->is_flagged) {
            return 'Signalé';
        }
        
        if (!$this->is_approved) {
            return 'En attente';
        }
        
        return 'Approuvé';
    }

    /**
     * Vérifier si l'avis a des médias
     */
    public function getHasMediaAttribute()
    {
        return !empty($this->images) || !empty($this->videos);
    }

    /**
     * Obtenir le nombre total d'images et vidéos
     */
    public function getMediaCountAttribute()
    {
        return count($this->images ?? []) + count($this->videos ?? []);
    }
}