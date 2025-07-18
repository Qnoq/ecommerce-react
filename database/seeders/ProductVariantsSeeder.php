<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Product;

class ProductVariantsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Exemples de produits avec vraies variantes
        $this->createTshirtVariants();
        $this->createPhoneVariants();
        $this->createShoesVariants();
        $this->createLaptopVariants();
    }

    private function createTshirtVariants()
    {
        // Chercher un produit de type vêtement ou en créer un
        $product = Product::firstOrCreate([
            'slug' => 'tshirt-premium-coton'
        ], [
            'uuid' => Str::uuid(),
            'name' => 'T-shirt Premium Coton Bio',
            'description' => 'T-shirt en coton bio de qualité supérieure, confortable et durable.',
            'short_description' => 'T-shirt premium en coton bio',
            'price' => 29.99,
            'original_price' => 39.99,
            'sku' => 'TSHIRT-PREMIUM-BASE',
            'stock_quantity' => 0, // Sera géré par les variantes
            'status' => 'active',
            'is_featured' => true,
            'featured_image' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=500&h=500&fit=crop',
            'published_at' => now()
        ]);

        // Supprimer les anciennes variantes par défaut
        DB::table('product_variants')->where('product_id', $product->id)->delete();

        $colors = [
            'blanc' => ['#FFFFFF', 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=500&h=500&fit=crop'],
            'noir' => ['#000000', 'https://images.unsplash.com/photo-1503341504253-dff4815485f1?w=500&h=500&fit=crop'],
            'rouge' => ['#DC2626', 'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?w=500&h=500&fit=crop'],
            'bleu' => ['#2563EB', 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=500&h=500&fit=crop&auto=format&fit=crop&w=500&q=60']
        ];

        $sizes = ['S', 'M', 'L', 'XL'];

        foreach ($colors as $colorName => $colorData) {
            foreach ($sizes as $size) {
                $variantId = DB::table('product_variants')->insertGetId([
                    'uuid' => Str::uuid(),
                    'product_id' => $product->id,
                    'sku' => "TSHIRT-PREMIUM-{$colorName}-{$size}",
                    'name' => "T-shirt Premium {$colorName} {$size}",
                    'price' => 29.99,
                    'original_price' => 39.99,
                    'stock_quantity' => rand(5, 50),
                    'in_stock' => true,
                    'featured_image' => $colorData[1],
                    'images' => json_encode([$colorData[1]]),
                    'is_default' => $colorName === 'blanc' && $size === 'M',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Ajouter les attributs - UN PAR UN pour éviter l'erreur PostgreSQL
                DB::table('product_variant_attributes')->insert([
                    'product_variant_id' => $variantId,
                    'attribute_name' => 'couleur',
                    'attribute_value' => $colorName,
                    'display_name' => ucfirst($colorName),
                    'color_code' => $colorData[0],
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                DB::table('product_variant_attributes')->insert([
                    'product_variant_id' => $variantId,
                    'attribute_name' => 'taille',
                    'attribute_value' => $size,
                    'display_name' => "Taille {$size}",
                    'sort_order' => 2,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }

    private function createPhoneVariants()
    {
        $product = Product::firstOrCreate([
            'slug' => 'smartphone-ultra-pro'
        ], [
            'uuid' => Str::uuid(),
            'name' => 'Smartphone Ultra Pro',
            'description' => 'Le smartphone le plus avancé avec technologie de pointe.',
            'short_description' => 'Smartphone haut de gamme',
            'price' => 999.99,
            'sku' => 'PHONE-ULTRA-BASE',
            'stock_quantity' => 0,
            'status' => 'active',
            'is_featured' => true,
            'featured_image' => 'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=500&h=500&fit=crop',
            'published_at' => now()
        ]);

        DB::table('product_variants')->where('product_id', $product->id)->delete();

        $storages = [
            '128GB' => ['price' => 999.99, 'original' => null],
            '256GB' => ['price' => 1099.99, 'original' => null],
            '512GB' => ['price' => 1299.99, 'original' => 1399.99]
        ];

        foreach ($storages as $storage => $pricing) {
            $variantId = DB::table('product_variants')->insertGetId([
                'uuid' => Str::uuid(),
                'product_id' => $product->id,
                'sku' => "PHONE-ULTRA-{$storage}",
                'name' => "Smartphone Ultra Pro {$storage}",
                'price' => $pricing['price'],
                'original_price' => $pricing['original'],
                'stock_quantity' => rand(10, 30),
                'in_stock' => true,
                'featured_image' => 'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=500&h=500&fit=crop',
                'is_default' => $storage === '256GB',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::table('product_variant_attributes')->insert([
                'product_variant_id' => $variantId,
                'attribute_name' => 'stockage',
                'attribute_value' => $storage,
                'display_name' => "Stockage {$storage}",
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    private function createShoesVariants()
    {
        $product = Product::firstOrCreate([
            'slug' => 'sneakers-sport-elite'
        ], [
            'uuid' => Str::uuid(),
            'name' => 'Sneakers Sport Elite',
            'description' => 'Chaussures de sport haute performance pour tous les sports.',
            'short_description' => 'Sneakers haute performance',
            'price' => 129.99,
            'original_price' => 159.99,
            'sku' => 'SHOES-ELITE-BASE',
            'stock_quantity' => 0,
            'status' => 'active',
            'featured_image' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=500&h=500&fit=crop',
            'published_at' => now()
        ]);

        DB::table('product_variants')->where('product_id', $product->id)->delete();

        $sizes = ['38', '39', '40', '41', '42', '43', '44', '45'];
        $colors = [
            'blanc' => '#FFFFFF',
            'noir' => '#000000',
            'rouge' => '#DC2626'
        ];

        foreach ($colors as $colorName => $colorCode) {
            foreach ($sizes as $size) {
                $variantId = DB::table('product_variants')->insertGetId([
                    'uuid' => Str::uuid(),
                    'product_id' => $product->id,
                    'sku' => "SHOES-ELITE-{$colorName}-{$size}",
                    'name' => "Sneakers Elite {$colorName} {$size}",
                    'price' => 129.99,
                    'original_price' => 159.99,
                    'stock_quantity' => rand(2, 15),
                    'in_stock' => true,
                    'featured_image' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=500&h=500&fit=crop',
                    'is_default' => $colorName === 'blanc' && $size === '42',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Couleur
                DB::table('product_variant_attributes')->insert([
                    'product_variant_id' => $variantId,
                    'attribute_name' => 'couleur',
                    'attribute_value' => $colorName,
                    'color_code' => $colorCode,
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                // Pointure
                DB::table('product_variant_attributes')->insert([
                    'product_variant_id' => $variantId,
                    'attribute_name' => 'pointure',
                    'attribute_value' => $size,
                    'display_name' => "Pointure {$size}",
                    'sort_order' => 2,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }

    private function createLaptopVariants()
    {
        $product = Product::firstOrCreate([
            'slug' => 'laptop-pro-gaming'
        ], [
            'uuid' => Str::uuid(),
            'name' => 'Laptop Pro Gaming',
            'description' => 'Ordinateur portable gaming haute performance.',
            'short_description' => 'Laptop gaming pro',
            'price' => 1499.99,
            'sku' => 'LAPTOP-GAMING-BASE',
            'stock_quantity' => 0,
            'status' => 'active',
            'featured_image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500&h=500&fit=crop',
            'published_at' => now()
        ]);

        DB::table('product_variants')->where('product_id', $product->id)->delete();

        $configs = [
            'i5-16GB-512GB' => ['price' => 1499.99, 'processor' => 'Intel i5', 'ram' => '16GB', 'storage' => '512GB SSD'],
            'i7-16GB-1TB' => ['price' => 1799.99, 'processor' => 'Intel i7', 'ram' => '16GB', 'storage' => '1TB SSD'],
            'i7-32GB-1TB' => ['price' => 2099.99, 'processor' => 'Intel i7', 'ram' => '32GB', 'storage' => '1TB SSD']
        ];

        foreach ($configs as $configKey => $config) {
            $variantId = DB::table('product_variants')->insertGetId([
                'uuid' => Str::uuid(),
                'product_id' => $product->id,
                'sku' => "LAPTOP-GAMING-{$configKey}",
                'name' => "Laptop Gaming {$config['processor']} {$config['ram']} {$config['storage']}",
                'price' => $config['price'],
                'stock_quantity' => rand(3, 10),
                'in_stock' => true,
                'featured_image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500&h=500&fit=crop',
                'is_default' => $configKey === 'i7-16GB-1TB',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Ajouter plusieurs attributs de configuration - UN PAR UN
            DB::table('product_variant_attributes')->insert([
                'product_variant_id' => $variantId,
                'attribute_name' => 'processeur',
                'attribute_value' => $config['processor'],
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::table('product_variant_attributes')->insert([
                'product_variant_id' => $variantId,
                'attribute_name' => 'ram',
                'attribute_value' => $config['ram'],
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::table('product_variant_attributes')->insert([
                'product_variant_id' => $variantId,
                'attribute_name' => 'stockage',
                'attribute_value' => $config['storage'],
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}