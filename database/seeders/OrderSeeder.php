<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class OrderSeeder extends Seeder
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
            $this->command->error('❌ Pas assez d\'utilisateurs ou de produits pour créer des commandes.');
            return;
        }

        $orderCount = 0;

        foreach ($users as $user) {
            // Nombre de commandes basé sur le profil client
            $numOrders = match($user->customer_tier) {
                'platinum' => $this->faker->numberBetween(15, 30),
                'gold' => $this->faker->numberBetween(8, 15),
                'silver' => $this->faker->numberBetween(3, 8),
                'bronze' => $this->faker->numberBetween(0, 3),
                default => 0
            };

            for ($i = 0; $i < $numOrders; $i++) {
                $this->createOrder($user, $products);
                $orderCount++;
            }
        }

        $this->command->info("✅ {$orderCount} commandes créées avec succès !");
    }

    private function createOrder(User $user, $products)
    {
        // 1. D'ABORD calculer le contenu et les prix
        $numItems = $this->faker->numberBetween(1, 5);
        $selectedProducts = $products->random($numItems);
        
        $subtotal = 0;
        $orderItems = [];

        // Calculer le sous-total AVANT de créer la commande
        foreach ($selectedProducts as $product) {
            $quantity = $this->faker->numberBetween(1, 3);
            $unitPrice = $product->price;
            $totalPrice = $quantity * $unitPrice;
            $subtotal += $totalPrice;

            $orderItems[] = [
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ];
        }

        // 2. Calculer TOUS les totaux
        $taxAmount = $subtotal * 0.20; // TVA 20%
        $shippingAmount = $subtotal >= 50 ? 0 : 5.99; // Livraison gratuite dès 50€
        $discountAmount = 0; // Pas de réduction pour l'instant
        $totalAmount = $subtotal + $taxAmount + $shippingAmount - $discountAmount;

        // 3. Statuts avec probabilités réalistes
        $status = $this->faker->randomElement([
            'delivered', 'delivered', 'delivered', 'delivered', 'delivered', // 50%
            'shipped', 'shipped', 'shipped', // 30%
            'processing', 'processing', // 20%
            'pending', 'cancelled' // 10%
        ]);

        $paymentStatus = match($status) {
            'delivered', 'shipped', 'processing' => 'paid',
            'pending' => $this->faker->randomElement(['pending', 'paid']),
            'cancelled' => 'cancelled',
            default => 'pending'
        };

        // 4. Dates cohérentes
        $createdAt = $this->faker->dateTimeBetween('-2 years', 'now');
        $confirmedAt = $paymentStatus === 'paid' ? $createdAt : null;
        $shippedAt = in_array($status, ['shipped', 'delivered']) ? 
            $this->faker->dateTimeBetween($createdAt, 'now') : null;
        $deliveredAt = $status === 'delivered' ? 
            $this->faker->dateTimeBetween($shippedAt ?? $createdAt, 'now') : null;

        // 5. Adresses par défaut de l'utilisateur
        $defaultAddress = $user->addresses[0] ?? [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'address_line_1' => $this->faker->streetAddress,
            'city' => $this->faker->city,
            'postal_code' => $this->faker->postcode,
            'country' => 'France',
            'phone' => $user->phone ?? $this->faker->phoneNumber
        ];

        // 6. MAINTENANT créer la commande avec TOUS les totaux
        $order = Order::create([
            'user_id' => $user->id,
            'status' => $status,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_amount' => $shippingAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'currency' => 'EUR',
            'payment_status' => $paymentStatus,
            'payment_method' => $paymentStatus === 'paid' ? 
                $this->faker->randomElement(['stripe', 'paypal', 'bank_transfer']) : null,
            'payment_date' => $confirmedAt,
            'shipping_method' => $this->faker->randomElement(['standard', 'express', 'pickup']),
            'shipping_cost' => $shippingAmount,
            'billing_address' => $defaultAddress,
            'shipping_address' => $defaultAddress,
            'confirmed_at' => $confirmedAt,
            'shipped_at' => $shippedAt,
            'delivered_at' => $deliveredAt,
            'source' => 'website',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        // 7. ENSUITE créer les articles de commande
        foreach ($orderItems as $itemData) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $itemData['product']->id,
                'product_name' => $itemData['product']->name,
                'product_sku' => $itemData['product']->sku,
                'product_description' => $itemData['product']->short_description,
                'product_image' => $itemData['product']->featured_image,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'total_price' => $itemData['total_price'],
                'product_options' => $this->getProductOptions($itemData['product']),
                'status' => $status === 'delivered' ? 'delivered' : 
                           ($status === 'shipped' ? 'shipped' : 'pending'),
                'shipped_at' => $shippedAt,
                'delivered_at' => $deliveredAt,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        return $order;
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
}