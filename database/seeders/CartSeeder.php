<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class CartSeeder extends Seeder
{
    private $faker;

    public function __construct()
    {
        $this->faker = Faker::create('fr_FR');
    }

    public function run(): void
    {
        $users = User::where('is_admin', false)->get();
        $products = Product::where('status', 'active')->where('in_stock', true)->get();

        if ($users->isEmpty() || $products->isEmpty()) {
            $this->command->error('❌ Pas assez d\'utilisateurs ou de produits pour créer des paniers.');
            return;
        }

        $cartCount = 0;
        $cartItemCount = 0;

        foreach ($users as $user) {
            // Probabilité d'avoir un panier actif selon le profil
            $hasActiveCart = match($user->customer_tier) {
                'platinum' => $this->faker->boolean(80), // VIP = 80% de chance d'avoir un panier
                'gold' => $this->faker->boolean(60),
                'silver' => $this->faker->boolean(40),
                'bronze' => $this->faker->boolean(20),   // Nouveaux = 20% seulement
                default => false
            };

            if (!$hasActiveCart) continue;

            // Créer le panier
            $cart = Cart::create([
                'user_id' => $user->id,
                'session_id' => null, // Utilisateur connecté
                'status' => 'active',
                'currency' => 'EUR',
                'coupon_code' => $this->faker->optional(0.15)->randomElement([
                    'WELCOME10', 'PROMO15', 'FIRSTBUY', 'LOYALTY5'
                ]),
                'last_activity_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
                'expires_at' => now()->addDays(30),
                'metadata' => [
                    'user_agent' => 'Mozilla/5.0 (compatible; ShopLux/1.0)',
                    'ip_address' => $this->faker->ipv4,
                    'referrer' => $this->faker->optional(0.3)->url,
                ],
                'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            ]);

            // Nombre d'articles dans le panier
            $numItems = $this->faker->numberBetween(1, 6);
            $randomProducts = $products->random(min($numItems, $products->count()));

            foreach ($randomProducts as $product) {
                $quantity = $this->faker->numberBetween(1, 3);
                $unitPrice = $product->price;

                // Gestion des variantes pour certains produits
                $productOptions = $this->getProductOptions($product);

                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $quantity * $unitPrice,
                    'product_options' => $productOptions,
                    'options_hash' => $productOptions ? md5(json_encode($productOptions)) : null,
                    'product_snapshot' => [
                        'name' => $product->name,
                        'image' => $product->featured_image,
                        'sku' => $product->sku,
                        'description' => $product->short_description,
                    ],
                    'customizations' => $this->getCustomizations($product),
                    'status' => $this->faker->randomElement([
                        'active', 'active', 'active', 'active', // 80% actifs
                        'saved_for_later' // 20% sauvés pour plus tard
                    ]),
                    'created_at' => $cart->created_at,
                ]);

                $cartItemCount++;
            }

            // Calculer les totaux du panier
            $cart->calculateTotals();
            $cartCount++;
        }

        // Créer quelques paniers abandonnés (invités)
        $this->createAbandonedCarts($products);

        $this->command->info("✅ {$cartCount} paniers actifs créés avec {$cartItemCount} articles !");
    }

    private function getProductOptions($product): ?array
    {
        // Simuler des options selon la catégorie du produit
        $category = $product->categories->first()?->name;

        return match($category) {
            'Mode Femme', 'Mode Homme' => [
                'size' => $this->faker->randomElement(['XS', 'S', 'M', 'L', 'XL']),
                'color' => $this->faker->randomElement(['Noir', 'Blanc', 'Gris', 'Bleu', 'Rouge']),
            ],
            'Électronique' => [
                'color' => $this->faker->randomElement(['Noir', 'Blanc', 'Gris', 'Or']),
                'storage' => $this->faker->optional(0.5)->randomElement(['64GB', '128GB', '256GB', '512GB']),
            ],
            'Maison & Déco' => [
                'color' => $this->faker->randomElement(['Blanc', 'Beige', 'Gris', 'Bleu', 'Vert']),
                'material' => $this->faker->optional(0.3)->randomElement(['Bois', 'Métal', 'Tissu', 'Céramique']),
            ],
            default => null
        };
    }

    private function getCustomizations($product): ?array
    {
        // Personnalisations possibles selon le type de produit
        if ($this->faker->boolean(15)) { // 15% de chance d'avoir des personnalisations
            return [
                'engraving' => $this->faker->optional(0.7)->words(3, true),
                'gift_message' => $this->faker->optional(0.5)->sentence(),
                'special_instructions' => $this->faker->optional(0.3)->sentence(),
            ];
        }

        return null;
    }

    private function createAbandonedCarts($products): void
    {
        // Créer 5-10 paniers abandonnés (session d'invités)
        $numAbandonedCarts = $this->faker->numberBetween(5, 10);

        for ($i = 0; $i < $numAbandonedCarts; $i++) {
            $cart = Cart::create([
                'user_id' => null,
                'session_id' => 'sess_' . $this->faker->uuid,
                'status' => 'abandoned',
                'currency' => 'EUR',
                'last_activity_at' => $this->faker->dateTimeBetween('-2 weeks', '-3 days'),
                'expires_at' => $this->faker->dateTimeBetween('-1 week', '+1 week'),
                'metadata' => [
                    'user_agent' => $this->faker->userAgent,
                    'ip_address' => $this->faker->ipv4,
                    'referrer' => $this->faker->optional(0.5)->url,
                    'abandonment_reason' => $this->faker->optional(0.3)->randomElement([
                        'price_too_high', 'shipping_cost', 'registration_required', 
                        'payment_issues', 'distraction', 'comparison_shopping'
                    ]),
                ],
                'created_at' => $this->faker->dateTimeBetween('-1 month', '-3 days'),
            ]);

            // Ajouter 1-3 articles au panier abandonné
            $numItems = $this->faker->numberBetween(1, 3);
            $randomProducts = $products->random(min($numItems, $products->count()));

            foreach ($randomProducts as $product) {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $this->faker->numberBetween(1, 2),
                    'unit_price' => $product->price,
                    'total_price' => $product->price,
                    'product_snapshot' => [
                        'name' => $product->name,
                        'image' => $product->featured_image,
                        'sku' => $product->sku,
                    ],
                    'status' => 'active',
                    'created_at' => $cart->created_at,
                ]);
            }

            $cart->calculateTotals();
        }

        $this->command->info("✅ {$numAbandonedCarts} paniers abandonnés créés pour l'analyse !");
    }
}