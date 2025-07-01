<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    private $faker;

    public function __construct()
    {
        $this->faker = Faker::create('fr_FR');
    }

    public function run(): void
    {
        // 1. ADMIN PRINCIPAL
        $admin = User::create([
            'name' => 'Admin ShopLux',
            'first_name' => 'Admin',
            'last_name' => 'ShopLux',
            'email' => 'admin@shoplux.fr',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => '01 23 45 67 89',
            'is_admin' => true,
            'is_active' => true,
            'is_verified' => true,
            'roles' => ['admin', 'super_admin'],
            'accepts_marketing' => false,
            'customer_tier' => 'platinum',
            'total_orders' => 0,
            'total_spent' => 0,
            'loyalty_points' => 0,
            'last_login_at' => now(),
            'addresses' => [
                [
                    'type' => 'billing',
                    'is_default' => true,
                    'first_name' => 'Admin',
                    'last_name' => 'ShopLux',
                    'company' => 'ShopLux SAS',
                    'address_line_1' => '123 Avenue des Champs-Élysées',
                    'address_line_2' => '',
                    'city' => 'Paris',
                    'postal_code' => '75008',
                    'country' => 'France',
                    'phone' => '01 23 45 67 89'
                ]
            ],
            'admin_notes' => 'Compte administrateur principal'
        ]);

        // 2. CLIENT VIP (gros acheteur)
        $vipClient = User::create([
            'name' => 'Marie Dubois',
            'first_name' => 'Marie',
            'last_name' => 'Dubois',
            'email' => 'marie.dubois@email.fr',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => '06 12 34 56 78',
            'date_of_birth' => $this->faker->date('Y-m-d', '1985-01-01'),
            'gender' => 'female',
            'is_admin' => false,
            'is_active' => true,
            'is_verified' => true,
            'roles' => ['customer', 'vip'],
            'accepts_marketing' => true,
            'accepts_sms' => true,
            'customer_tier' => 'platinum',
            'total_orders' => 47,
            'total_spent' => 8450.00,
            'average_order_value' => 179.79,
            'loyalty_points' => 2535,
            'first_order_at' => now()->subMonths(18),
            'last_order_at' => now()->subDays(5),
            'last_login_at' => now()->subHours(2),
            'addresses' => [
                [
                    'type' => 'billing',
                    'is_default' => true,
                    'first_name' => 'Marie',
                    'last_name' => 'Dubois',
                    'address_line_1' => '45 Rue de la Paix',
                    'address_line_2' => 'Apt 12B',
                    'city' => 'Lyon',
                    'postal_code' => '69001',
                    'country' => 'France',
                    'phone' => '06 12 34 56 78'
                ],
                [
                    'type' => 'shipping',
                    'is_default' => false,
                    'first_name' => 'Marie',
                    'last_name' => 'Dubois',
                    'company' => 'Tech Corp',
                    'address_line_1' => '78 Boulevard Haussmann',
                    'address_line_2' => '',
                    'city' => 'Paris',
                    'postal_code' => '75009',
                    'country' => 'France',
                    'phone' => '01 45 67 89 12'
                ]
            ],
            'notification_preferences' => [
                'email_promotions' => true,
                'sms_alerts' => true,
                'push_notifications' => true,
                'newsletter' => true
            ]
        ]);

        // 3. CLIENT RÉGULIER
        $regularClient = User::create([
            'name' => 'Pierre Martin',
            'first_name' => 'Pierre',
            'last_name' => 'Martin',
            'email' => 'pierre.martin@email.fr',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => '07 98 76 54 32',
            'date_of_birth' => $this->faker->date('Y-m-d', '1990-01-01'),
            'gender' => 'male',
            'is_admin' => false,
            'is_active' => true,
            'is_verified' => true,
            'roles' => ['customer'],
            'accepts_marketing' => true,
            'accepts_sms' => false,
            'customer_tier' => 'gold',
            'total_orders' => 12,
            'total_spent' => 1250.00,
            'average_order_value' => 104.17,
            'loyalty_points' => 375,
            'first_order_at' => now()->subMonths(8),
            'last_order_at' => now()->subDays(15),
            'last_login_at' => now()->subDays(3),
            'addresses' => [
                [
                    'type' => 'billing',
                    'is_default' => true,
                    'first_name' => 'Pierre',
                    'last_name' => 'Martin',
                    'address_line_1' => '12 Place Bellecour',
                    'address_line_2' => '',
                    'city' => 'Lyon',
                    'postal_code' => '69002',
                    'country' => 'France',
                    'phone' => '07 98 76 54 32'
                ]
            ]
        ]);

        // 4. NOUVEAU CLIENT
        $newClient = User::create([
            'name' => 'Sophie Leroy',
            'first_name' => 'Sophie',
            'last_name' => 'Leroy',
            'email' => 'sophie.leroy@email.fr',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => '06 11 22 33 44',
            'date_of_birth' => $this->faker->date('Y-m-d', '1995-01-01'),
            'gender' => 'female',
            'is_admin' => false,
            'is_active' => true,
            'is_verified' => false, // Pas encore vérifié
            'roles' => ['customer'],
            'accepts_marketing' => true,
            'accepts_sms' => true,
            'customer_tier' => 'bronze',
            'total_orders' => 1,
            'total_spent' => 89.90,
            'average_order_value' => 89.90,
            'loyalty_points' => 9,
            'first_order_at' => now()->subDays(3),
            'last_order_at' => now()->subDays(3),
            'last_login_at' => now()->subHours(6),
            'addresses' => [
                [
                    'type' => 'billing',
                    'is_default' => true,
                    'first_name' => 'Sophie',
                    'last_name' => 'Leroy',
                    'address_line_1' => '234 Rue de Rivoli',
                    'address_line_2' => '',
                    'city' => 'Paris',
                    'postal_code' => '75001',
                    'country' => 'France',
                    'phone' => '06 11 22 33 44'
                ]
            ]
        ]);

        // 5. VENDEUR/MODÉRATEUR
        $vendor = User::create([
            'name' => 'Jean Vendeur',
            'first_name' => 'Jean',
            'last_name' => 'Vendeur',
            'email' => 'vendeur@shoplux.fr',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => '04 56 78 90 12',
            'is_admin' => false,
            'is_vendor' => true,
            'is_active' => true,
            'is_verified' => true,
            'roles' => ['vendor', 'moderator'],
            'accepts_marketing' => false,
            'customer_tier' => 'bronze',
            'total_orders' => 0,
            'total_spent' => 0,
            'loyalty_points' => 0,
            'last_login_at' => now()->subHours(1),
            'admin_notes' => 'Compte vendeur avec droits de modération'
        ]);

        // 6. GÉNÉRER 15 CLIENTS SUPPLÉMENTAIRES avec Faker
        for ($i = 0; $i < 15; $i++) {
            $firstName = $this->faker->firstName;
            $lastName = $this->faker->lastName;
            $totalOrders = $this->faker->numberBetween(0, 25);
            $totalSpent = $totalOrders > 0 ? $this->faker->randomFloat(2, 50, 2000) : 0;
            $averageOrderValue = $totalOrders > 0 ? $totalSpent / $totalOrders : 0;
            
            // Déterminer le tier client basé sur le montant dépensé
            $customerTier = match(true) {
                $totalSpent >= 1500 => 'platinum',
                $totalSpent >= 800 => 'gold',
                $totalSpent >= 200 => 'silver',
                default => 'bronze'
            };

            User::create([
                'name' => $firstName . ' ' . $lastName,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => strtolower($firstName . '.' . $lastName . '@email.fr'),
                'email_verified_at' => $this->faker->boolean(80) ? now() : null,
                'password' => Hash::make('password'),
                'phone' => $this->faker->phoneNumber,
                'date_of_birth' => $this->faker->optional(0.7)->date('Y-m-d', '2000-01-01'),
                'gender' => $this->faker->optional(0.6)->randomElement(['male', 'female', 'other']),
                'is_admin' => false,
                'is_active' => $this->faker->boolean(95), // 95% actifs
                'is_verified' => $this->faker->boolean(70), // 70% vérifiés
                'roles' => ['customer'],
                'accepts_marketing' => $this->faker->boolean(60),
                'accepts_sms' => $this->faker->boolean(30),
                'customer_tier' => $customerTier,
                'total_orders' => $totalOrders,
                'total_spent' => $totalSpent,
                'average_order_value' => $averageOrderValue,
                'loyalty_points' => (int)($totalSpent * 0.3), // 0.3 point par euro
                'first_order_at' => $totalOrders > 0 ? $this->faker->dateTimeBetween('-2 years', '-1 month') : null,
                'last_order_at' => $totalOrders > 0 ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
                'last_login_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
                'addresses' => [
                    [
                        'type' => 'billing',
                        'is_default' => true,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'address_line_1' => $this->faker->streetAddress,
                        'address_line_2' => $this->faker->optional(0.3)->secondaryAddress,
                        'city' => $this->faker->city,
                        'postal_code' => $this->faker->postcode,
                        'country' => 'France',
                        'phone' => $this->faker->phoneNumber
                    ]
                ],
                'notification_preferences' => [
                    'email_promotions' => $this->faker->boolean(50),
                    'sms_alerts' => $this->faker->boolean(20),
                    'push_notifications' => $this->faker->boolean(40),
                    'newsletter' => $this->faker->boolean(60)
                ]
            ]);
        }

        // Afficher un résumé
        $totalUsers = User::count();
        $adminCount = User::where('is_admin', true)->count();
        $vendorCount = User::where('is_vendor', true)->count();
        $customerCount = User::where('roles', '@>', '["customer"]')->count();

        $this->command->info("✅ {$totalUsers} utilisateurs créés :");
        $this->command->info("   👑 {$adminCount} administrateurs");
        $this->command->info("   🏪 {$vendorCount} vendeurs");
        $this->command->info("   👥 {$customerCount} clients");
        $this->command->line('');
        $this->command->info("🔑 Comptes de test :");
        $this->command->info("   Admin: admin@shoplux.fr / password");
        $this->command->info("   VIP: marie.dubois@email.fr / password");
        $this->command->info("   Client: pierre.martin@email.fr / password");
        $this->command->info("   Vendeur: vendeur@shoplux.fr / password");
    }
}