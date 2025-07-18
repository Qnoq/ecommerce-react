<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClearProductsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🗑️ Suppression de tous les produits...');
        
        // Supprimer dans l'ordre des dépendances
        DB::table('order_items')->delete();
        DB::table('cart_items')->delete();
        DB::table('product_categories')->delete();
        DB::table('product_variant_attributes')->delete();
        DB::table('product_variants')->delete();
        DB::table('products')->delete();
        
        $this->command->info('✅ Tous les produits ont été supprimés !');
    }
}