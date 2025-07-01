<?php

namespace Database\Seeders;

use App\Models\Wishlist;
use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class WishlistSeeder extends Seeder
{
    private $faker;

    public function __construct()
    {
        $this->faker = Faker::create('fr_FR');
    }

    public function run(): void
    {
        $users = User::where('is_admin', false)->get();
        $products = Product::where('status', 'active')->get();

        if ($users->isEmpty() || $products->isEmpty()) {
            $this->command->error('❌ Pas assez d\'utilisateurs ou de produits pour créer des wishlists.');
            return;
        }

        $wishlistCount = 0;

        foreach ($users as $user) {
            // Nombre d'items en wishlist selon le profil client
            $numWishlistItems = match($user->customer_tier) {
                'platinum' => $this->faker->numberBetween(8, 20),  // VIP = beaucoup d'envies
                'gold' => $this->faker->numberBetween(5, 12),
                'silver' => $this->faker->numberBetween(2, 8),
                'bronze' => $this->faker->numberBetween(0, 5),     // Nouveaux = peu d'items
                default => 0
            };

            if ($numWishlistItems === 0) continue;

            // Sélectionner des produits aléatoirement
            $randomProducts = $products->random(min($numWishlistItems, $products->count()));

            foreach ($randomProducts as $product) {
                // Éviter les doublons
                if (Wishlist::where('user_id', $user->id)
                    ->where('product_id', $product->id)->exists()) {
                    continue;
                }

                Wishlist::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'note' => $this->getWishlistNote(),
                    'priority' => $this->faker->numberBetween(1, 5),
                    'price_when_added' => $product->price,
                    'was_in_stock_when_added' => $product->in_stock,
                    'notify_price_drop' => $this->faker->boolean(70), // 70% veulent être notifiés prix
                    'notify_back_in_stock' => !$product->in_stock ? true : $this->faker->boolean(30),
                    'notify_promotion' => $this->faker->boolean(80), // 80% veulent les promos
                    'is_public' => $this->faker->boolean(20), // 20% de wishlists publiques
                    'category' => $this->getWishlistCategory(),
                    'tags' => $this->getWishlistTags(),
                    'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
                ]);

                $wishlistCount++;
            }
        }

        $this->command->info("✅ {$wishlistCount} items ajoutés aux wishlists !");
    }

    private function getWishlistNote(): ?string
    {
        return $this->faker->optional(0.4)->randomElement([
            'Pour mon anniversaire 🎂',
            'Idée cadeau sympa',
            'Quand il sera en promo',
            'J\'adore ce style !',
            'Pour renouveler le mien',
            'Coup de cœur ❤️',
            'Pour les vacances',
            'Cadeau pour maman',
            'Quand j\'aurai le budget',
            'Parfait pour la maison',
            'Style que je cherchais',
            'Excellentes critiques',
        ]);
    }

    private function getWishlistCategory(): ?string
    {
        return $this->faker->optional(0.6)->randomElement([
            'Anniversaire',
            'Noël',
            'Fête des mères',
            'Fête des pères',
            'Saint-Valentin',
            'Rentrée',
            'Vacances',
            'Mariage',
            'Crémaillère',
            'Envies',
            'Plus tard',
            'Urgent',
        ]);
    }

    private function getWishlistTags(): ?array
    {
        return $this->faker->optional(0.5)->randomElements([
            'urgent', 'pas cher', 'qualité', 'design', 'pratique',
            'tendance', 'écolo', 'français', 'luxe', 'vintage',
            'moderne', 'coloré', 'discret', 'original', 'cadeau',
            'famille', 'travail', 'loisir', 'sport', 'maison'
        ], $this->faker->numberBetween(1, 4));
    }
}