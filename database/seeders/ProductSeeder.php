<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ProductSeeder extends Seeder
{
    private $faker;

    public function __construct()
    {
        $this->faker = Faker::create('fr_FR');
    }

    public function run(): void
    {
        // Supprimer tous les produits existants et leurs relations
        $this->command->info('🗑️ Suppression des produits existants...');
        DB::table('order_items')->delete();
        DB::table('cart_items')->delete();
        DB::table('product_categories')->delete();
        DB::table('product_variant_attributes')->delete();
        DB::table('product_variants')->delete();
        DB::table('products')->delete();
        
        // Récupérer toutes les catégories
        $categories = Category::all();
        
        if ($categories->isEmpty()) {
            $this->command->error('❌ Aucune catégorie trouvée ! Lancez d\'abord CategorySeeder.');
            return;
        }

        $this->command->info('📦 Création de 50 produits uniques...');

        // Créer les produits
        $products = $this->getUniqueProducts();
        
        foreach ($products as $index => $productData) {
            // Assigner une catégorie aléatoire
            $category = $categories->random();
            
            $product = Product::create([
                'name' => $productData['name'],
                'slug' => Str::slug($productData['name'] . '-' . $this->faker->unique()->numberBetween(1000, 9999)),
                'uuid' => Str::uuid(),
                'description' => $productData['description'],
                'short_description' => $productData['short_description'],
                'price' => $productData['price'],
                'original_price' => $productData['original_price'] ?? null,
                'currency' => 'EUR',
                'sku' => 'SKU-' . strtoupper(Str::random(8)),
                'stock_quantity' => $this->faker->numberBetween(0, 150),
                'manage_stock' => true,
                'in_stock' => true,
                'low_stock_threshold' => 5,
                'images' => $productData['images'],
                'featured_image' => $productData['images'][0],
                'weight' => $this->faker->randomFloat(2, 0.1, 10),
                'dimensions' => [
                    'length' => $this->faker->numberBetween(10, 50),
                    'width' => $this->faker->numberBetween(10, 50),
                    'height' => $this->faker->numberBetween(5, 30)
                ],
                'status' => 'active',
                'is_featured' => $this->faker->boolean(25),
                'is_digital' => $productData['is_digital'] ?? false,
                'attributes' => $productData['attributes'] ?? null,
                'search_content' => $this->generateSearchContent($productData),
                'seo_meta' => [
                    'title' => $productData['name'] . ' - Achat en ligne | ShopLux',
                    'description' => $productData['short_description'],
                    'keywords' => array_slice(explode(' ', strtolower($productData['name'])), 0, 5)
                ],
                'rating' => $this->faker->randomFloat(2, 3.5, 5),
                'review_count' => $this->faker->numberBetween(5, 200),
                'view_count' => $this->faker->numberBetween(50, 2000),
                'sales_count' => $this->faker->numberBetween(10, 500),
                'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            ]);

            // Attacher à la catégorie
            DB::table('product_categories')->insert([
                'product_id' => $product->id,
                'category_id' => $category->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Créer les variantes si nécessaire
            if ($productData['has_variants'] ?? false) {
                $this->createVariants($product, $productData['variants']);
            } else {
                // Créer une variante par défaut
                $this->createDefaultVariant($product);
            }

            $this->command->info("✅ Produit créé: {$product->name}");
        }

        $this->command->info("🎉 50 produits créés avec succès avec leurs variantes !");
    }

    private function createVariants($product, $variants): void
    {
        foreach ($variants as $index => $variantData) {
            $variant = DB::table('product_variants')->insertGetId([
                'product_id' => $product->id,
                'uuid' => Str::uuid(),
                'sku' => $product->sku . '-V' . ($index + 1),
                'name' => $variantData['name'],
                'price' => $variantData['price'],
                'original_price' => $variantData['original_price'] ?? null,
                'stock_quantity' => $this->faker->numberBetween(0, 200),
                'in_stock' => true,
                'featured_image' => $variantData['image'] ?? $product->featured_image,
                'images' => json_encode([$variantData['image'] ?? $product->featured_image]),
                'is_default' => $index === 0,
                'sort_order' => $index,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Créer les attributs de variante
            if (isset($variantData['attributes'])) {
                foreach ($variantData['attributes'] as $attrName => $attrValue) {
                    DB::table('product_variant_attributes')->insert([
                        'product_variant_id' => $variant,
                        'attribute_name' => $attrName,
                        'attribute_value' => $attrValue,
                        'display_name' => $attrValue,
                        'color_code' => $attrName === 'color' ? $this->getColorCode($attrValue) : null,
                        'sort_order' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    private function createDefaultVariant($product): void
    {
        $variantId = DB::table('product_variants')->insertGetId([
            'product_id' => $product->id,
            'uuid' => Str::uuid(),
            'sku' => $product->sku . '-DEFAULT',
            'name' => $product->name,
            'price' => $product->price,
            'original_price' => $product->original_price,
            'stock_quantity' => $product->stock_quantity,
            'in_stock' => $product->in_stock,
            'featured_image' => $product->featured_image,
            'images' => json_encode($product->images),
            'is_default' => true,
            'sort_order' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function getColorCode($colorName): ?string
    {
        $colors = [
            'Rouge' => '#FF0000',
            'Bleu' => '#0000FF',
            'Vert' => '#00FF00',
            'Noir' => '#000000',
            'Blanc' => '#FFFFFF',
            'Jaune' => '#FFFF00',
            'Rose' => '#FFC0CB',
            'Violet' => '#800080',
            'Orange' => '#FFA500',
            'Gris' => '#808080',
            'Marron' => '#8B4513',
            'Beige' => '#F5F5DC',
        ];

        return $colors[$colorName] ?? null;
    }

    private function getUniqueProducts(): array
    {
        return [
            // === SMARTPHONES ===
            [
                'name' => 'iPhone 16 Pro Max',
                'short_description' => 'Le smartphone Apple le plus avancé avec puce A18 Pro.',
                'description' => 'iPhone 16 Pro Max avec puce A18 Pro, caméra 48MP, écran Super Retina XDR et design titane.',
                'price' => 1299.00,
                'original_price' => 1399.00,
                'images' => ['https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'iPhone 16 Pro Max Titane Noir 256GB',
                        'price' => 1299.00,
                        'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=500&h=500&fit=crop',
                        'attributes' => ['color' => 'Noir', 'storage' => '256GB']
                    ],
                    [
                        'name' => 'iPhone 16 Pro Max Titane Blanc 256GB',
                        'price' => 1299.00,
                        'image' => 'https://images.unsplash.com/photo-1565849904461-04a58ad377e0?w=500&h=500&fit=crop',
                        'attributes' => ['color' => 'Blanc', 'storage' => '256GB']
                    ],
                    [
                        'name' => 'iPhone 16 Pro Max Titane Noir 512GB',
                        'price' => 1499.00,
                        'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=500&h=500&fit=crop',
                        'attributes' => ['color' => 'Noir', 'storage' => '512GB']
                    ]
                ]
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'short_description' => 'Smartphone premium avec S Pen et caméra 200MP.',
                'description' => 'Galaxy S24 Ultra avec S Pen intégré, caméra 200MP, écran Dynamic AMOLED 2X et performances exceptionnelles.',
                'price' => 1199.00,
                'images' => ['https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Galaxy S24 Ultra Titanium Gray 256GB',
                        'price' => 1199.00,
                        'attributes' => ['color' => 'Gris', 'storage' => '256GB']
                    ],
                    [
                        'name' => 'Galaxy S24 Ultra Titanium Black 512GB',
                        'price' => 1399.00,
                        'attributes' => ['color' => 'Noir', 'storage' => '512GB']
                    ]
                ]
            ],
            [
                'name' => 'Google Pixel 8 Pro',
                'short_description' => 'Smartphone Google avec IA avancée et photo computationnelle.',
                'description' => 'Pixel 8 Pro avec puce Tensor G3, Magic Eraser, Night Sight et expérience Android pure.',
                'price' => 899.00,
                'images' => ['https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Pixel 8 Pro Obsidian 128GB',
                        'price' => 899.00,
                        'attributes' => ['color' => 'Noir', 'storage' => '128GB']
                    ],
                    [
                        'name' => 'Pixel 8 Pro Porcelain 256GB',
                        'price' => 1099.00,
                        'attributes' => ['color' => 'Blanc', 'storage' => '256GB']
                    ]
                ]
            ],

            // === LAPTOPS ===
            [
                'name' => 'MacBook Pro 16" M3 Max',
                'short_description' => 'MacBook Pro professionnel avec puce M3 Max.',
                'description' => 'MacBook Pro 16" avec puce M3 Max, 36GB RAM, écran Liquid Retina XDR pour les créatifs professionnels.',
                'price' => 2799.00,
                'images' => ['https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'MacBook Pro 16" M3 Max Gris Sidéral 1TB',
                        'price' => 2799.00,
                        'attributes' => ['color' => 'Gris', 'storage' => '1TB']
                    ],
                    [
                        'name' => 'MacBook Pro 16" M3 Max Argent 2TB',
                        'price' => 3199.00,
                        'attributes' => ['color' => 'Blanc', 'storage' => '2TB']
                    ]
                ]
            ],
            [
                'name' => 'Dell XPS 13 Plus',
                'short_description' => 'Ultrabook Windows premium avec écran OLED.',
                'description' => 'Dell XPS 13 Plus avec processeur Intel Core i7, écran OLED 4K et design ultra-compact.',
                'price' => 1499.00,
                'images' => ['https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'XPS 13 Plus Platinum 512GB',
                        'price' => 1499.00,
                        'attributes' => ['color' => 'Blanc', 'storage' => '512GB']
                    ],
                    [
                        'name' => 'XPS 13 Plus Graphite 1TB',
                        'price' => 1799.00,
                        'attributes' => ['color' => 'Noir', 'storage' => '1TB']
                    ]
                ]
            ],

            // === SNEAKERS ===
            [
                'name' => 'Nike Air Max 270',
                'short_description' => 'Baskets lifestyle avec amorti Air Max visible.',
                'description' => 'Nike Air Max 270 avec la plus grande unité Air Max pour un confort exceptionnel toute la journée.',
                'price' => 149.99,
                'images' => ['https://images.unsplash.com/photo-1549298916-b41d501d3772?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Air Max 270 Noir/Blanc 42',
                        'price' => 149.99,
                        'attributes' => ['color' => 'Noir', 'size' => '42']
                    ],
                    [
                        'name' => 'Air Max 270 Blanc/Gris 43',
                        'price' => 149.99,
                        'attributes' => ['color' => 'Blanc', 'size' => '43']
                    ],
                    [
                        'name' => 'Air Max 270 Rouge/Noir 44',
                        'price' => 149.99,
                        'attributes' => ['color' => 'Rouge', 'size' => '44']
                    ]
                ]
            ],
            [
                'name' => 'Adidas Stan Smith',
                'short_description' => 'Baskets iconiques en cuir blanc avec détails verts.',
                'description' => 'Les légendaires Stan Smith d\'Adidas, baskets intemporelles en cuir blanc avec les fameux détails verts.',
                'price' => 89.99,
                'images' => ['https://images.unsplash.com/photo-1549298916-b41d501d3772?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Stan Smith Blanc/Vert 39',
                        'price' => 89.99,
                        'attributes' => ['color' => 'Blanc', 'size' => '39']
                    ],
                    [
                        'name' => 'Stan Smith Blanc/Vert 41',
                        'price' => 89.99,
                        'attributes' => ['color' => 'Blanc', 'size' => '41']
                    ],
                    [
                        'name' => 'Stan Smith Blanc/Vert 43',
                        'price' => 89.99,
                        'attributes' => ['color' => 'Blanc', 'size' => '43']
                    ]
                ]
            ],

            // === T-SHIRTS ===
            [
                'name' => 'T-shirt Premium Coton Bio',
                'short_description' => 'T-shirt basique en coton biologique, coupe moderne.',
                'description' => 'T-shirt essentiel en coton biologique certifié, coupe ajustée moderne et couleurs intemporelles.',
                'price' => 24.99,
                'images' => ['https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'T-shirt Premium Noir S',
                        'price' => 24.99,
                        'attributes' => ['color' => 'Noir', 'size' => 'S']
                    ],
                    [
                        'name' => 'T-shirt Premium Blanc M',
                        'price' => 24.99,
                        'attributes' => ['color' => 'Blanc', 'size' => 'M']
                    ],
                    [
                        'name' => 'T-shirt Premium Gris L',
                        'price' => 24.99,
                        'attributes' => ['color' => 'Gris', 'size' => 'L']
                    ],
                    [
                        'name' => 'T-shirt Premium Bleu XL',
                        'price' => 24.99,
                        'attributes' => ['color' => 'Bleu', 'size' => 'XL']
                    ]
                ]
            ],

            // === CASQUES AUDIO ===
            [
                'name' => 'Sony WH-1000XM5',
                'short_description' => 'Casque sans fil avec réduction de bruit leader du marché.',
                'description' => 'Sony WH-1000XM5 avec la meilleure réduction de bruit active, 30h d\'autonomie et qualité audio Hi-Res.',
                'price' => 349.00,
                'images' => ['https://images.unsplash.com/photo-1484704849700-f032a568e944?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'WH-1000XM5 Noir',
                        'price' => 349.00,
                        'attributes' => ['color' => 'Noir']
                    ],
                    [
                        'name' => 'WH-1000XM5 Argent',
                        'price' => 349.00,
                        'attributes' => ['color' => 'Blanc']
                    ]
                ]
            ],
            [
                'name' => 'AirPods Pro 2',
                'short_description' => 'Écouteurs sans fil Apple avec réduction de bruit active.',
                'description' => 'AirPods Pro 2ème génération avec réduction de bruit active améliorée, audio spatial et boîtier MagSafe.',
                'price' => 279.00,
                'images' => ['https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&h=500&fit=crop'],
            ],

            // === MONTRES ===
            [
                'name' => 'Apple Watch Series 9',
                'short_description' => 'Montre connectée Apple avec puce S9 et Double Tap.',
                'description' => 'Apple Watch Series 9 avec puce S9, nouvelle fonction Double Tap, écran Always-On et suivi santé avancé.',
                'price' => 449.00,
                'images' => ['https://images.unsplash.com/photo-1434056886845-dac89ffe9b56?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Apple Watch Series 9 41mm Starlight',
                        'price' => 449.00,
                        'attributes' => ['color' => 'Blanc', 'size' => '41mm']
                    ],
                    [
                        'name' => 'Apple Watch Series 9 45mm Midnight',
                        'price' => 479.00,
                        'attributes' => ['color' => 'Noir', 'size' => '45mm']
                    ],
                    [
                        'name' => 'Apple Watch Series 9 45mm Pink',
                        'price' => 479.00,
                        'attributes' => ['color' => 'Rose', 'size' => '45mm']
                    ]
                ]
            ],

            // === VÊTEMENTS FEMME ===
            [
                'name' => 'Robe Midi Fleurie',
                'short_description' => 'Robe élégante à motifs floraux, parfaite pour l\'été.',
                'description' => 'Robe midi fluide en viscose avec imprimé floral romantique, manches courtes et ceinture à nouer.',
                'price' => 79.99,
                'images' => ['https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Robe Midi Fleurie Rose S',
                        'price' => 79.99,
                        'attributes' => ['color' => 'Rose', 'size' => 'S']
                    ],
                    [
                        'name' => 'Robe Midi Fleurie Bleu M',
                        'price' => 79.99,
                        'attributes' => ['color' => 'Bleu', 'size' => 'M']
                    ],
                    [
                        'name' => 'Robe Midi Fleurie Vert L',
                        'price' => 79.99,
                        'attributes' => ['color' => 'Vert', 'size' => 'L']
                    ]
                ]
            ],
            [
                'name' => 'Jean Skinny Taille Haute',
                'short_description' => 'Jean skinny moderne en denim stretch, taille haute.',
                'description' => 'Jean skinny taille haute en denim stretch confortable, coupe ajustée et délavage moderne.',
                'price' => 59.99,
                'images' => ['https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Jean Skinny Bleu Foncé 36',
                        'price' => 59.99,
                        'attributes' => ['color' => 'Bleu', 'size' => '36']
                    ],
                    [
                        'name' => 'Jean Skinny Bleu Clair 38',
                        'price' => 59.99,
                        'attributes' => ['color' => 'Bleu', 'size' => '38']
                    ],
                    [
                        'name' => 'Jean Skinny Noir 40',
                        'price' => 59.99,
                        'attributes' => ['color' => 'Noir', 'size' => '40']
                    ]
                ]
            ],

            // === VÊTEMENTS HOMME ===
            [
                'name' => 'Polo Classique',
                'short_description' => 'Polo intemporel en coton piqué, coupe régulière.',
                'description' => 'Polo classique en coton piqué de qualité, col polo traditionnel et coupe confortable.',
                'price' => 39.99,
                'images' => ['https://images.unsplash.com/photo-1620012253295-c15cc3e65df4?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Polo Classique Blanc M',
                        'price' => 39.99,
                        'attributes' => ['color' => 'Blanc', 'size' => 'M']
                    ],
                    [
                        'name' => 'Polo Classique Bleu L',
                        'price' => 39.99,
                        'attributes' => ['color' => 'Bleu', 'size' => 'L']
                    ],
                    [
                        'name' => 'Polo Classique Noir XL',
                        'price' => 39.99,
                        'attributes' => ['color' => 'Noir', 'size' => 'XL']
                    ]
                ]
            ],
            [
                'name' => 'Chemise Oxford',
                'short_description' => 'Chemise classique en coton Oxford, parfaite pour le bureau.',
                'description' => 'Chemise intemporelle en coton Oxford tissé, col boutonné et coupe classique pour un look professionnel.',
                'price' => 69.99,
                'images' => ['https://images.unsplash.com/photo-1620012253295-c15cc3e65df4?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Chemise Oxford Blanc S',
                        'price' => 69.99,
                        'attributes' => ['color' => 'Blanc', 'size' => 'S']
                    ],
                    [
                        'name' => 'Chemise Oxford Bleu M',
                        'price' => 69.99,
                        'attributes' => ['color' => 'Bleu', 'size' => 'M']
                    ],
                    [
                        'name' => 'Chemise Oxford Rose L',
                        'price' => 69.99,
                        'attributes' => ['color' => 'Rose', 'size' => 'L']
                    ]
                ]
            ],

            // === ACCESSOIRES ===
            [
                'name' => 'Sac à Dos Urban',
                'short_description' => 'Sac à dos moderne avec compartiment laptop et ports USB.',
                'description' => 'Sac à dos urbain avec compartiment laptop 15", port USB intégré et design résistant à l\'eau.',
                'price' => 89.99,
                'images' => ['https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Sac à Dos Urban Noir',
                        'price' => 89.99,
                        'attributes' => ['color' => 'Noir']
                    ],
                    [
                        'name' => 'Sac à Dos Urban Gris',
                        'price' => 89.99,
                        'attributes' => ['color' => 'Gris']
                    ],
                    [
                        'name' => 'Sac à Dos Urban Bleu',
                        'price' => 89.99,
                        'attributes' => ['color' => 'Bleu']
                    ]
                ]
            ],
            [
                'name' => 'Lunettes de Soleil Aviator',
                'short_description' => 'Lunettes aviator classiques avec verres polarisés.',
                'description' => 'Lunettes de soleil aviator iconiques avec verres polarisés, monture métallique et protection UV400.',
                'price' => 129.99,
                'images' => ['https://images.unsplash.com/photo-1511499767150-a48a237f0083?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Aviator Or/Vert',
                        'price' => 129.99,
                        'attributes' => ['color' => 'Jaune', 'lens' => 'Vert']
                    ],
                    [
                        'name' => 'Aviator Argent/Bleu',
                        'price' => 129.99,
                        'attributes' => ['color' => 'Blanc', 'lens' => 'Bleu']
                    ],
                    [
                        'name' => 'Aviator Noir/Gris',
                        'price' => 129.99,
                        'attributes' => ['color' => 'Noir', 'lens' => 'Gris']
                    ]
                ]
            ],

            // === MAISON & DÉCO ===
            [
                'name' => 'Coussin Velours Premium',
                'short_description' => 'Coussin décoratif en velours doux, disponible en plusieurs couleurs.',
                'description' => 'Coussin décoratif en velours de qualité supérieure, fermeture éclair invisible et garnissage moelleux.',
                'price' => 29.99,
                'images' => ['https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Coussin Velours Bleu Marine',
                        'price' => 29.99,
                        'attributes' => ['color' => 'Bleu']
                    ],
                    [
                        'name' => 'Coussin Velours Vert Émeraude',
                        'price' => 29.99,
                        'attributes' => ['color' => 'Vert']
                    ],
                    [
                        'name' => 'Coussin Velours Rose Poudré',
                        'price' => 29.99,
                        'attributes' => ['color' => 'Rose']
                    ],
                    [
                        'name' => 'Coussin Velours Gris Perle',
                        'price' => 29.99,
                        'attributes' => ['color' => 'Gris']
                    ]
                ]
            ],
            [
                'name' => 'Lampe de Bureau LED',
                'short_description' => 'Lampe de bureau moderne avec éclairage LED réglable.',
                'description' => 'Lampe de bureau LED avec bras articulé, intensité réglable et port USB pour charger vos appareils.',
                'price' => 79.99,
                'images' => ['https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Lampe LED Blanc',
                        'price' => 79.99,
                        'attributes' => ['color' => 'Blanc']
                    ],
                    [
                        'name' => 'Lampe LED Noir',
                        'price' => 79.99,
                        'attributes' => ['color' => 'Noir']
                    ]
                ]
            ],

            // === SPORT & FITNESS ===
            [
                'name' => 'Tapis de Yoga Premium',
                'short_description' => 'Tapis de yoga antidérapant en matière naturelle.',
                'description' => 'Tapis de yoga en caoutchouc naturel, surface antidérapante et épaisseur optimale pour le confort.',
                'price' => 49.99,
                'images' => ['https://images.unsplash.com/photo-1506629905607-0e2fb72ad7a1?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Tapis Yoga Violet',
                        'price' => 49.99,
                        'attributes' => ['color' => 'Violet']
                    ],
                    [
                        'name' => 'Tapis Yoga Bleu',
                        'price' => 49.99,
                        'attributes' => ['color' => 'Bleu']
                    ],
                    [
                        'name' => 'Tapis Yoga Vert',
                        'price' => 49.99,
                        'attributes' => ['color' => 'Vert']
                    ]
                ]
            ],
            [
                'name' => 'Haltères Ajustables',
                'short_description' => 'Paire d\'haltères ajustables pour entrainement à domicile.',
                'description' => 'Haltères ajustables de 2,5kg à 25kg par haltère, système de réglage rapide et base de rangement.',
                'price' => 299.99,
                'images' => ['https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Haltères Ajustables 2.5-25kg',
                        'price' => 299.99,
                        'attributes' => ['weight' => '2.5-25kg']
                    ],
                    [
                        'name' => 'Haltères Ajustables 5-50kg',
                        'price' => 549.99,
                        'attributes' => ['weight' => '5-50kg']
                    ]
                ]
            ],

            // === BEAUTÉ & COSMÉTIQUES ===
            [
                'name' => 'Palette Maquillage Complete',
                'short_description' => 'Palette maquillage professionnelle avec 48 teintes.',
                'description' => 'Palette maquillage complète avec 48 teintes d\'ombres à paupières, fards à joues et highlighters.',
                'price' => 59.99,
                'images' => ['https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Palette Maquillage Nude',
                        'price' => 59.99,
                        'attributes' => ['theme' => 'Nude']
                    ],
                    [
                        'name' => 'Palette Maquillage Colorée',
                        'price' => 59.99,
                        'attributes' => ['theme' => 'Colorée']
                    ]
                ]
            ],
            [
                'name' => 'Sérum Vitamine C',
                'short_description' => 'Sérum anti-âge à la vitamine C pure pour le visage.',
                'description' => 'Sérum concentré en vitamine C pure, action anti-âge et éclaircissante pour un teint lumineux.',
                'price' => 39.99,
                'images' => ['https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Sérum Vitamine C 30ml',
                        'price' => 39.99,
                        'attributes' => ['size' => '30ml']
                    ],
                    [
                        'name' => 'Sérum Vitamine C 50ml',
                        'price' => 59.99,
                        'attributes' => ['size' => '50ml']
                    ]
                ]
            ],

            // === GAMING ===
            [
                'name' => 'Manette PS5 DualSense',
                'short_description' => 'Manette officielle PlayStation 5 avec retour haptique.',
                'description' => 'Manette DualSense officielle avec retour haptique, gâchettes adaptatives et micro intégré.',
                'price' => 69.99,
                'images' => ['https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'DualSense Blanc',
                        'price' => 69.99,
                        'attributes' => ['color' => 'Blanc']
                    ],
                    [
                        'name' => 'DualSense Noir',
                        'price' => 69.99,
                        'attributes' => ['color' => 'Noir']
                    ],
                    [
                        'name' => 'DualSense Rouge',
                        'price' => 69.99,
                        'attributes' => ['color' => 'Rouge']
                    ]
                ]
            ],
            [
                'name' => 'Clavier Gaming Mécanique',
                'short_description' => 'Clavier mécanique RGB pour gaming avec switches Cherry MX.',
                'description' => 'Clavier gaming mécanique avec switches Cherry MX, rétroéclairage RGB personnalisable et touches anti-ghosting.',
                'price' => 149.99,
                'images' => ['https://images.unsplash.com/photo-1541140532154-b024d705b90a?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Clavier Gaming Cherry MX Red',
                        'price' => 149.99,
                        'attributes' => ['switch' => 'Cherry MX Red']
                    ],
                    [
                        'name' => 'Clavier Gaming Cherry MX Blue',
                        'price' => 149.99,
                        'attributes' => ['switch' => 'Cherry MX Blue']
                    ],
                    [
                        'name' => 'Clavier Gaming Cherry MX Brown',
                        'price' => 149.99,
                        'attributes' => ['switch' => 'Cherry MX Brown']
                    ]
                ]
            ],

            // === CUISINE ===
            [
                'name' => 'Mixeur Plongeant Premium',
                'short_description' => 'Mixeur plongeant professionnel avec accessoires.',
                'description' => 'Mixeur plongeant haute performance avec lames en acier inoxydable, 5 vitesses et accessoires inclus.',
                'price' => 89.99,
                'images' => ['https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Mixeur Plongeant Blanc',
                        'price' => 89.99,
                        'attributes' => ['color' => 'Blanc']
                    ],
                    [
                        'name' => 'Mixeur Plongeant Noir',
                        'price' => 89.99,
                        'attributes' => ['color' => 'Noir']
                    ]
                ]
            ],
            [
                'name' => 'Set Couteaux Japonais',
                'short_description' => 'Set de 3 couteaux japonais en acier Damas.',
                'description' => 'Set de couteaux japonais forgés main en acier Damas, lames ultra-tranchantes et manches ergonomiques.',
                'price' => 199.99,
                'images' => ['https://images.unsplash.com/photo-1593618998160-e34014e67546?w=500&h=500&fit=crop'],
            ],

            // === JARDIN ===
            [
                'name' => 'Jardinière Moderne',
                'short_description' => 'Jardinière en fibre de verre, design contemporain.',
                'description' => 'Jardinière moderne en fibre de verre résistante aux intempéries, drainage optimisé et design élégant.',
                'price' => 79.99,
                'images' => ['https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Jardinière Anthracite 60cm',
                        'price' => 79.99,
                        'attributes' => ['color' => 'Gris', 'size' => '60cm']
                    ],
                    [
                        'name' => 'Jardinière Blanc 80cm',
                        'price' => 99.99,
                        'attributes' => ['color' => 'Blanc', 'size' => '80cm']
                    ],
                    [
                        'name' => 'Jardinière Terracotta 100cm',
                        'price' => 119.99,
                        'attributes' => ['color' => 'Marron', 'size' => '100cm']
                    ]
                ]
            ],

            // === LIVRES ===
            [
                'name' => 'Guide Complet du Développement Web',
                'short_description' => 'Manuel complet pour apprendre le développement web moderne.',
                'description' => 'Guide exhaustif couvrant HTML5, CSS3, JavaScript, React, Node.js et les meilleures pratiques du développement web.',
                'price' => 49.99,
                'is_digital' => true,
                'images' => ['https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Guide Développement Web Papier',
                        'price' => 49.99,
                        'attributes' => ['format' => 'Papier']
                    ],
                    [
                        'name' => 'Guide Développement Web Numérique',
                        'price' => 34.99,
                        'attributes' => ['format' => 'Numérique']
                    ]
                ]
            ],

            // === ENFANTS ===
            [
                'name' => 'Jouet Éducatif Montessori',
                'short_description' => 'Jouet en bois inspiré de la pédagogie Montessori.',
                'description' => 'Jouet éducatif en bois naturel, développe la motricité fine et la coordination œil-main selon la méthode Montessori.',
                'price' => 34.99,
                'images' => ['https://images.unsplash.com/photo-1566694271453-390536dd1f68?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Jouet Montessori Formes',
                        'price' => 34.99,
                        'attributes' => ['type' => 'Formes']
                    ],
                    [
                        'name' => 'Jouet Montessori Couleurs',
                        'price' => 34.99,
                        'attributes' => ['type' => 'Couleurs']
                    ],
                    [
                        'name' => 'Jouet Montessori Chiffres',
                        'price' => 34.99,
                        'attributes' => ['type' => 'Chiffres']
                    ]
                ]
            ],

            // === AUTOMOBILE ===
            [
                'name' => 'Chargeur Voiture Sans Fil',
                'short_description' => 'Chargeur sans fil pour voiture avec fixation ventilation.',
                'description' => 'Chargeur sans fil Qi pour voiture, fixation sur grille d\'aération, compatible tous smartphones Qi.',
                'price' => 39.99,
                'images' => ['https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Chargeur Sans Fil Noir',
                        'price' => 39.99,
                        'attributes' => ['color' => 'Noir']
                    ],
                    [
                        'name' => 'Chargeur Sans Fil Blanc',
                        'price' => 39.99,
                        'attributes' => ['color' => 'Blanc']
                    ]
                ]
            ],

            // === ÉLECTROMÉNAGER ===
            [
                'name' => 'Cafetière Espresso Automatique',
                'short_description' => 'Machine à café espresso avec broyeur intégré.',
                'description' => 'Cafetière espresso automatique avec broyeur céramique, écran tactile et système de mousse de lait.',
                'price' => 599.99,
                'images' => ['https://images.unsplash.com/photo-1542558137-91d7a9e672c9?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Cafetière Espresso Noire',
                        'price' => 599.99,
                        'attributes' => ['color' => 'Noir']
                    ],
                    [
                        'name' => 'Cafetière Espresso Argent',
                        'price' => 599.99,
                        'attributes' => ['color' => 'Blanc']
                    ]
                ]
            ],

            // === OUTILS ===
            [
                'name' => 'Perceuse Visseuse Sans Fil',
                'short_description' => 'Perceuse visseuse 18V avec batterie lithium-ion.',
                'description' => 'Perceuse visseuse professionnelle 18V, mandrin auto-serrant, LED d\'éclairage et 2 batteries incluses.',
                'price' => 149.99,
                'images' => ['https://images.unsplash.com/photo-1572981779307-38b8cabb2407?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Perceuse 18V avec 1 batterie',
                        'price' => 149.99,
                        'attributes' => ['battery' => '1 batterie']
                    ],
                    [
                        'name' => 'Perceuse 18V avec 2 batteries',
                        'price' => 199.99,
                        'attributes' => ['battery' => '2 batteries']
                    ]
                ]
            ],

            // === BAGAGERIE ===
            [
                'name' => 'Valise Cabine Rigide',
                'short_description' => 'Valise cabine en polycarbonate avec roulettes 360°.',
                'description' => 'Valise cabine ultra-légère en polycarbonate, 4 roulettes 360°, serrure TSA et intérieur organisé.',
                'price' => 129.99,
                'images' => ['https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Valise Cabine Noir',
                        'price' => 129.99,
                        'attributes' => ['color' => 'Noir']
                    ],
                    [
                        'name' => 'Valise Cabine Bleu',
                        'price' => 129.99,
                        'attributes' => ['color' => 'Bleu']
                    ],
                    [
                        'name' => 'Valise Cabine Rouge',
                        'price' => 129.99,
                        'attributes' => ['color' => 'Rouge']
                    ]
                ]
            ],

            // === PAPETERIE ===
            [
                'name' => 'Carnet Moleskine Classique',
                'short_description' => 'Carnet Moleskine ligné avec couverture rigide.',
                'description' => 'Carnet Moleskine classique avec pages lignées, couverture rigide et élastique de fermeture.',
                'price' => 19.99,
                'images' => ['https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=500&h=500&fit=crop'],
                'has_variants' => true,
                'variants' => [
                    [
                        'name' => 'Moleskine Noir A5',
                        'price' => 19.99,
                        'attributes' => ['color' => 'Noir', 'size' => 'A5']
                    ],
                    [
                        'name' => 'Moleskine Rouge A5',
                        'price' => 19.99,
                        'attributes' => ['color' => 'Rouge', 'size' => 'A5']
                    ],
                    [
                        'name' => 'Moleskine Bleu A4',
                        'price' => 24.99,
                        'attributes' => ['color' => 'Bleu', 'size' => 'A4']
                    ]
                ]
            ]
        ];
    }

    private function generateSearchContent(array $productData): string
    {
        $content = collect([
            $productData['name'],
            $productData['description'],
            $productData['short_description'],
            // Ajouter des mots-clés de la catégorie selon le type de produit
            $this->getProductKeywords($productData['name']),
            // Ajouter des synonymes et termes de recherche
            $this->getSearchSynonyms($productData['name']),
        ])
        ->filter()
        ->map(function($text) {
            return trim(preg_replace('/\s+/', ' ', $text));
        })
        ->filter(function($text) {
            return !empty($text);
        })
        ->unique()
        ->implode(' ');

        return $content;
    }

    private function getProductKeywords(string $productName): string
    {
        $keywords = [];
        $name = strtolower($productName);
        
        // Mots-clés par catégorie
        if (str_contains($name, 'iphone') || str_contains($name, 'samsung') || str_contains($name, 'pixel')) {
            $keywords[] = 'smartphone téléphone mobile android ios';
        }
        if (str_contains($name, 'macbook') || str_contains($name, 'laptop') || str_contains($name, 'dell') || str_contains($name, 'xps')) {
            $keywords[] = 'ordinateur portable laptop computer pc';
        }
        if (str_contains($name, 'nike') || str_contains($name, 'adidas') || str_contains($name, 'baskets') || str_contains($name, 'air max')) {
            $keywords[] = 'chaussures sneakers sport running';
        }
        if (str_contains($name, 't-shirt') || str_contains($name, 'polo') || str_contains($name, 'chemise')) {
            $keywords[] = 'vêtement haut homme femme mode fashion';
        }
        if (str_contains($name, 'robe') || str_contains($name, 'jean')) {
            $keywords[] = 'vêtement femme mode fashion';
        }
        if (str_contains($name, 'casque') || str_contains($name, 'airpods') || str_contains($name, 'sony')) {
            $keywords[] = 'audio musique son écouteurs headphone';
        }
        if (str_contains($name, 'watch') || str_contains($name, 'montre')) {
            $keywords[] = 'montre connectée smartwatch apple';
        }
        if (str_contains($name, 'sac') || str_contains($name, 'valise')) {
            $keywords[] = 'bagagerie voyage transport';
        }
        if (str_contains($name, 'coussin') || str_contains($name, 'lampe')) {
            $keywords[] = 'maison décoration home déco';
        }
        if (str_contains($name, 'tapis') || str_contains($name, 'haltères')) {
            $keywords[] = 'sport fitness gym entrainement';
        }
        if (str_contains($name, 'palette') || str_contains($name, 'sérum')) {
            $keywords[] = 'beauté cosmétique maquillage skincare';
        }
        if (str_contains($name, 'manette') || str_contains($name, 'clavier') || str_contains($name, 'gaming')) {
            $keywords[] = 'gaming jeu vidéo gamer esport';
        }
        if (str_contains($name, 'mixeur') || str_contains($name, 'couteau') || str_contains($name, 'cafetière')) {
            $keywords[] = 'cuisine cook chef ustensile électroménager';
        }
        
        return implode(' ', $keywords);
    }

    private function getSearchSynonyms(string $productName): string
    {
        $synonyms = [];
        $name = strtolower($productName);
        
        // Synonymes courants
        if (str_contains($name, 'premium') || str_contains($name, 'pro')) {
            $synonyms[] = 'haut de gamme qualité supérieure professionnel';
        }
        if (str_contains($name, 'noir')) {
            $synonyms[] = 'black dark sombre';
        }
        if (str_contains($name, 'blanc')) {
            $synonyms[] = 'white clair lumineux';
        }
        if (str_contains($name, 'rouge')) {
            $synonyms[] = 'red vermillon';
        }
        if (str_contains($name, 'bleu')) {
            $synonyms[] = 'blue marine navy';
        }
        if (str_contains($name, 'sans fil') || str_contains($name, 'wireless')) {
            $synonyms[] = 'bluetooth wifi connecté';
        }
        if (str_contains($name, 'led')) {
            $synonyms[] = 'éclairage luminaire';
        }
        
        return implode(' ', $synonyms);
    }
}