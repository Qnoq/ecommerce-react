<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CartService
{
    protected $redis;
    protected $cartDb;
    protected $ttl = 2592000; // 30 jours en secondes

    public function __construct()
    {
        $this->cartDb = (int) env('REDIS_CART_DB', 4);
        $this->redis = Redis::connection('default');
        $this->redis->select($this->cartDb);
    }

    /**
     * Obtenir la clé Redis pour le panier
     */
    protected function getCartKey(?string $sessionId = null): string
    {
        if (Auth::check()) {
            return "cart:user:" . Auth::id();
        }
        
        $sessionId = $sessionId ?? session()->getId();
        return "cart:guest:" . $sessionId;
    }

    /**
     * Obtenir le contenu du panier
     */
    public function getCart(?string $sessionId = null): array
    {
        $cartKey = $this->getCartKey($sessionId);
        
        $cartData = $this->redis->hgetall($cartKey);
        
        if (empty($cartData)) {
            return [
                'items' => [],
                'total' => 0,
                'quantity' => 0,
                'updated_at' => now()->toISOString()
            ];
        }

        $items = [];
        $total = 0;
        $totalQuantity = 0;

        foreach ($cartData as $itemKey => $itemData) {
            if ($itemKey === 'metadata') continue;
            
            $item = json_decode($itemData, true);
            
            // Extraire l'UUID du produit de la clé (peut être "uuid" ou "uuid:variant:id")
            $productUuid = explode(':', $itemKey)[0];
            
            // Récupérer le produit pour avoir les infos à jour
            $product = Product::where('uuid', $productUuid)->first();
            
            if ($product) {
                $item['product'] = $product;
                $item['item_key'] = $itemKey; // Clé unique pour cet item (avec variante)
                $item['subtotal'] = $item['quantity'] * $item['price']; // Utiliser le prix de la variante si disponible
                $total += $item['subtotal'];
                $totalQuantity += $item['quantity'];
                $items[] = $item;
            } else {
                // Produit supprimé, nettoyer l'item
                $this->redis->hdel($cartKey, $itemKey);
            }
        }

        return [
            'items' => $items,
            'total' => $total,
            'quantity' => $totalQuantity,
            'updated_at' => now()->toISOString()
        ];
    }

    /**
     * Ajouter un item au panier
     */
    public function addItem(string $productUuid, int $quantity = 1, array $variants = [], ?string $sessionId = null, ?int $productVariantId = null): array
    {
        $product = Product::where('uuid', $productUuid)->first();
        
        if (!$product) {
            throw new \Exception('Produit non trouvé');
        }

        if ($quantity <= 0) {
            throw new \Exception('Quantité invalide');
        }

        $cartKey = $this->getCartKey($sessionId);
        
        // Créer une clé unique pour la variante (ou produit de base)
        $itemKey = $productVariantId ? "{$productUuid}:variant:{$productVariantId}" : $productUuid;
        
        // Vérifier si l'item existe déjà
        $existingItem = $this->redis->hget($cartKey, $itemKey);
        
        // Récupérer le prix depuis la variante si disponible
        $price = $product->price;
        if ($productVariantId) {
            $variant = DB::table('product_variants')->find($productVariantId);
            if ($variant) {
                $price = $variant->price;
            }
        }
        
        if ($existingItem) {
            $itemData = json_decode($existingItem, true);
            $itemData['quantity'] += $quantity;
            $itemData['updated_at'] = now()->toISOString();
        } else {
            $itemData = [
                'product_uuid' => $productUuid,
                'product_variant_id' => $productVariantId,
                'quantity' => $quantity,
                'variants' => $variants,
                'price' => $price,
                'added_at' => now()->toISOString(),
                'updated_at' => now()->toISOString()
            ];
        }

        // Sauvegarder dans Redis
        $this->redis->hset($cartKey, $itemKey, json_encode($itemData));
        
        // Définir TTL pour les invités
        if (!Auth::check()) {
            $this->redis->expire($cartKey, $this->ttl);
        }

        // Mettre à jour les métadonnées
        $this->updateCartMetadata($cartKey);

        return $this->getCart($sessionId);
    }

    /**
     * Mettre à jour la quantité d'un item
     */
    public function updateItem(string $itemKey, int $quantity, ?string $sessionId = null): array
    {
        if ($quantity <= 0) {
            return $this->removeItem($itemKey, $sessionId);
        }

        $cartKey = $this->getCartKey($sessionId);
        
        $existingItem = $this->redis->hget($cartKey, $itemKey);
        
        if (!$existingItem) {
            throw new \Exception('Produit non trouvé dans le panier');
        }

        $itemData = json_decode($existingItem, true);
        $itemData['quantity'] = $quantity;
        $itemData['updated_at'] = now()->toISOString();

        $this->redis->hset($cartKey, $itemKey, json_encode($itemData));
        
        $this->updateCartMetadata($cartKey);

        return $this->getCart($sessionId);
    }

    /**
     * Supprimer un item du panier
     */
    public function removeItem(string $itemKey, ?string $sessionId = null): array
    {
        $cartKey = $this->getCartKey($sessionId);
        
        $this->redis->hdel($cartKey, $itemKey);
        
        $this->updateCartMetadata($cartKey);

        return $this->getCart($sessionId);
    }

    /**
     * Vider le panier
     */
    public function clearCart(?string $sessionId = null): array
    {
        $cartKey = $this->getCartKey($sessionId);
        
        $this->redis->del($cartKey);

        return $this->getCart($sessionId);
    }

    /**
     * Obtenir le nombre d'items dans le panier
     */
    public function getCartCount(?string $sessionId = null): int
    {
        $cart = $this->getCart($sessionId);
        return $cart['quantity'];
    }

    /**
     * Transférer le panier invité vers utilisateur connecté
     */
    public function transferGuestCart(string $guestSessionId, int $userId): array
    {
        $guestCartKey = "cart:guest:" . $guestSessionId;
        $userCartKey = "cart:user:" . $userId;
        
        $guestCart = $this->redis->hgetall($guestCartKey);
        
        if (empty($guestCart)) {
            return $this->getCart();
        }

        $userCart = $this->redis->hgetall($userCartKey);
        
        // Fusionner les paniers
        foreach ($guestCart as $productUuid => $itemData) {
            if ($productUuid === 'metadata') continue;
            
            $guestItem = json_decode($itemData, true);
            
            if (isset($userCart[$productUuid])) {
                // Produit déjà dans le panier utilisateur, additionner les quantités
                $userItem = json_decode($userCart[$productUuid], true);
                $userItem['quantity'] += $guestItem['quantity'];
                $userItem['updated_at'] = now()->toISOString();
                $this->redis->hset($userCartKey, $productUuid, json_encode($userItem));
            } else {
                // Nouveau produit, copier directement
                $this->redis->hset($userCartKey, $productUuid, $itemData);
            }
        }
        
        // Supprimer le panier invité
        $this->redis->del($guestCartKey);
        
        $this->updateCartMetadata($userCartKey);

        return $this->getCart();
    }

    /**
     * Sauvegarder le panier en base de données (pour backup)
     */
    public function saveToDatabase(?string $sessionId = null): void
    {
        if (!Auth::check()) {
            return; // Pas de sauvegarde pour les invités
        }

        $cart = $this->getCart($sessionId);
        
        if (empty($cart['items'])) {
            return;
        }

        // TODO: Implémenter la sauvegarde en DB si nécessaire
        // Utile pour l'historique des paniers abandonnés
    }

    /**
     * Mettre à jour les métadonnées du panier
     */
    protected function updateCartMetadata(string $cartKey): void
    {
        $metadata = [
            'updated_at' => now()->toISOString(),
            'user_id' => Auth::id(),
            'session_id' => session()->getId()
        ];

        $this->redis->hset($cartKey, 'metadata', json_encode($metadata));
    }

    /**
     * Valider le stock avant ajout
     */
    public function validateStock(string $productUuid, int $quantity, ?int $productVariantId = null): bool
    {
        $product = Product::where('uuid', $productUuid)->first();
        
        if (!$product) {
            return false;
        }

        // Si on a une variante, vérifier le stock de la variante
        if ($productVariantId) {
            $variant = DB::table('product_variants')->find($productVariantId);
            
            if (!$variant) {
                return false;
            }

            return $variant->stock_quantity >= $quantity;
        }

        // Sinon, vérifier le stock du produit de base
        if (!$product->manage_stock) {
            return true; // Stock non géré
        }

        return $product->stock_quantity >= $quantity;
    }

    /**
     * Calculer les totaux avec taxes et frais
     */
    public function calculateTotals(array $cart): array
    {
        $subtotal = $cart['total'];
        $taxRate = 0.20; // 20% TVA
        $tax = $subtotal * $taxRate;
        $shippingCost = $subtotal >= 50 ? 0 : 4.99; // Gratuit dès 50€
        $total = $subtotal + $tax + $shippingCost;

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shippingCost,
            'total' => $total
        ];
    }
}