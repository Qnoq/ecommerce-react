<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Démarrage du seeding de la base e-commerce...');
        $this->command->line('');

        // 1. Extensions PostgreSQL
        $this->command->info('📦 Configuration des extensions PostgreSQL...');
        // Les extensions sont déjà créées via les migrations
        
        // 2. Catégories (en premier car les produits en dépendent)
        $this->command->info('📂 Création des catégories...');
        $this->call(CategorySeeder::class);
        
        // 3. Produits (dépendent des catégories)
        $this->command->info('🛍️  Création des produits...');
        $this->call(ProductSeeder::class);
        
        // 4. Utilisateurs
        $this->command->info('👥 Création des utilisateurs...');
        $this->call(UserSeeder::class);
        
        // 5. Commandes d'exemple
        $this->command->info('📦 Création des commandes d\'exemple...');
        $this->call(OrderSeeder::class);
        
        // 6. Avis produits
        $this->command->info('⭐ Création des avis produits...');
        $this->call(ReviewSeeder::class);
        
        // 7. Wishlists
        $this->command->info('❤️  Création des listes de souhaits...');
        $this->call(WishlistSeeder::class);
        
        // 8. Paniers actifs
        $this->command->info('🛒 Création des paniers actifs...');
        $this->call(CartSeeder::class);

        $this->command->line('');
        $this->command->info('🎉 Base de données e-commerce créée avec succès !');
        $this->command->line('');
        $this->command->info('📊 Résumé :');
        $this->command->info('   📂 ' . \App\Models\Category::count() . ' catégories');
        $this->command->info('   🛍️  ' . \App\Models\Product::count() . ' produits');
        $this->command->info('   👥 ' . \App\Models\User::count() . ' utilisateurs');
        $this->command->info('   📦 ' . \App\Models\Order::count() . ' commandes');
        $this->command->info('   ⭐ ' . \App\Models\ProductReview::count() . ' avis');
        $this->command->info('   ❤️  ' . \App\Models\Wishlist::count() . ' items wishlist');
        $this->command->info('   🛒 ' . \App\Models\Cart::count() . ' paniers');
        $this->command->line('');
        $this->command->info('🔑 Connexions de test :');
        $this->command->info('   Admin: admin@shoplux.fr / password');
        $this->command->info('   VIP: marie.dubois@email.fr / password');
        $this->command->info('   Client: pierre.martin@email.fr / password');
    }
}