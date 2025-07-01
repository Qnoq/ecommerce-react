<?php

namespace Database\Seeders;

use App\Models\ProductReview;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ReviewSeeder extends Seeder
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
            $this->command->error('❌ Pas assez d\'utilisateurs ou de produits pour créer des avis.');
            return;
        }

        $reviewCount = 0;

        // Créer des avis pour des produits populaires
        $popularProducts = $products->take(30); // 30 produits les plus "populaires"

        foreach ($popularProducts as $product) {
            $numReviews = $this->faker->numberBetween(2, 15);
            $randomUsers = $users->random(min($numReviews, $users->count()));

            foreach ($randomUsers as $user) {
                // Éviter les doublons (un avis par user par produit)
                if (ProductReview::where('product_id', $product->id)
                    ->where('user_id', $user->id)->exists()) {
                    continue;
                }

                $rating = $this->faker->numberBetween(3, 5); // Plutôt des bonnes notes
                $isPositive = $rating >= 4;

                $review = ProductReview::create([
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'rating' => $rating,
                    'title' => $this->getReviewTitle($rating),
                    'comment' => $this->getReviewComment($rating, $product->name),
                    'pros' => $isPositive ? $this->getPositivePoints() : null,
                    'cons' => !$isPositive ? $this->getNegativePoints() : null,
                    'would_recommend' => $rating >= 4,
                    'is_verified_purchase' => $this->faker->boolean(70),
                    'is_approved' => $this->faker->boolean(90),
                    'approved_at' => now(),
                    'helpful_votes' => $this->faker->numberBetween(0, 25),
                    'unhelpful_votes' => $this->faker->numberBetween(0, 3),
                    'source' => 'website',
                    'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
                ]);

                $reviewCount++;
            }
        }

        $this->command->info("✅ {$reviewCount} avis produits créés !");
    }

    private function getReviewTitle($rating): string
    {
        return match($rating) {
            5 => $this->faker->randomElement([
                'Parfait !', 'Excellent produit', 'Je recommande vivement', 
                'Très satisfait', 'Au top !', 'Parfait, rien à redire'
            ]),
            4 => $this->faker->randomElement([
                'Très bien', 'Bon produit', 'Content de mon achat', 
                'Bonne qualité', 'Conforme à mes attentes'
            ]),
            3 => $this->faker->randomElement([
                'Correct', 'Pas mal', 'Dans la moyenne', 'Ça va'
            ]),
            default => $this->faker->randomElement([
                'Déçu', 'Pas terrible', 'Bof', 'Peut mieux faire'
            ])
        };
    }

    private function getReviewComment($rating, $productName): string
    {
        $positiveComments = [
            "Très satisfait de cet achat ! La qualité est au rendez-vous et la livraison a été rapide.",
            "Excellent rapport qualité-prix. Je recommande ce produit sans hésitation.",
            "Parfait pour mes besoins. L'article correspond exactement à la description.",
            "Très bonne qualité, conforme à mes attentes. Service client réactif.",
            "Produit de qualité, emballage soigné, livraison dans les temps. Parfait !",
        ];

        $averageComments = [
            "Produit correct sans plus. Fait le travail mais sans être exceptionnel.",
            "Pas mal dans l'ensemble, quelques petits défauts mais ça reste acceptable.",
            "Conforme à la description. Qualité standard pour le prix.",
        ];

        $negativeComments = [
            "Déçu de cet achat. La qualité n'est pas au rendez-vous.",
            "Le produit ne correspond pas vraiment à mes attentes.",
            "Qualité décevante pour le prix. Je ne recommande pas.",
        ];

        return match($rating) {
            5, 4 => $this->faker->randomElement($positiveComments),
            3 => $this->faker->randomElement($averageComments),
            default => $this->faker->randomElement($negativeComments)
        };
    }

    private function getPositivePoints(): array
    {
        return $this->faker->randomElements([
            'Excellente qualité', 'Livraison rapide', 'Bon rapport qualité-prix',
            'Facile à utiliser', 'Design élégant', 'Très pratique',
            'Conforme à la description', 'Emballage soigné', 'Service client réactif'
        ], $this->faker->numberBetween(2, 4));
    }

    private function getNegativePoints(): array
    {
        return $this->faker->randomElements([
            'Qualité décevante', 'Prix un peu élevé', 'Livraison trop lente',
            'Difficile à utiliser', 'Fragile', 'Pas très pratique',
            'Ne correspond pas à la description', 'Emballage abîmé'
        ], $this->faker->numberBetween(1, 3));
    }
}