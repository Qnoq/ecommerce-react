<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class ProductSeeder extends Seeder
{
    private $faker;

    public function __construct()
    {
        $this->faker = Faker::create('fr_FR'); // Faker français
    }

    public function run(): void
    {
        // Récupérer toutes les catégories
        $categories = Category::all();
        
        if ($categories->isEmpty()) {
            $this->command->error('❌ Aucune catégorie trouvée ! Lancez d\'abord CategorySeeder.');
            return;
        }

        // Produits par catégorie
        $productsByCategory = [
            'Électronique' => $this->getElectronicsProducts(),
            'Mode Femme' => $this->getWomenFashionProducts(),
            'Mode Homme' => $this->getMenFashionProducts(),
            'Maison & Déco' => $this->getHomeDecoProducts(),
            'Sport & Loisirs' => $this->getSportsProducts(),
            'Beauté & Bien-être' => $this->getBeautyProducts(),
            'Livres & Culture' => $this->getBooksProducts(),
            'Enfants & Bébés' => $this->getKidsProducts(),
            'Jardin & Extérieur' => $this->getGardenProducts(),
            'Auto & Moto' => $this->getAutoProducts(),
        ];

        $totalProducts = 0;

        foreach ($categories as $category) {
            $categoryProducts = $productsByCategory[$category->name] ?? [];
            
            foreach ($categoryProducts as $productData) {
                $product = Product::create([
                    'name' => $productData['name'],
                    'slug' => Str::slug($productData['name'] . '-' . $this->faker->unique()->numberBetween(1000, 9999)),
                    'description' => $productData['description'],
                    'short_description' => $productData['short_description'],
                    'price' => $productData['price'],
                    'original_price' => $productData['original_price'] ?? null,
                    'currency' => 'EUR',
                    'sku' => 'SKU-' . strtoupper(Str::random(8)),
                    'stock_quantity' => $this->faker->numberBetween(0, 100),
                    'manage_stock' => true,
                    'in_stock' => $this->faker->boolean(85), // 85% en stock
                    'low_stock_threshold' => 5,
                    'images' => $productData['images'],
                    'featured_image' => $productData['images'][0],
                    'weight' => $this->faker->randomFloat(2, 0.1, 10),
                    'dimensions' => [
                        'length' => $this->faker->numberBetween(10, 50),
                        'width' => $this->faker->numberBetween(10, 50),
                        'height' => $this->faker->numberBetween(5, 30)
                    ],
                    'status' => $this->faker->randomElement(['active', 'active', 'active', 'draft']), // 75% actifs
                    'is_featured' => $this->faker->boolean(20), // 20% en vedette
                    'is_digital' => $productData['is_digital'] ?? false,
                    'attributes' => $productData['attributes'] ?? null,
                    'seo_meta' => [
                        'title' => $productData['name'] . ' - Achat en ligne | ShopLux',
                        'description' => $productData['short_description'],
                        'keywords' => explode(' ', strtolower($productData['name']))
                    ],
                    'rating' => $this->faker->randomFloat(2, 3.5, 5), // Notes entre 3.5 et 5
                    'review_count' => $this->faker->numberBetween(0, 150),
                    'view_count' => $this->faker->numberBetween(10, 1000),
                    'sales_count' => $this->faker->numberBetween(0, 200),
                    'published_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
                ]);

                // Attacher à la catégorie
                $product->categories()->attach($category->id);
                
                $totalProducts++;
            }
        }

        $this->command->info("✅ {$totalProducts} produits créés avec succès !");
    }

    private function getElectronicsProducts(): array
    {
        return [
            [
                'name' => 'iPhone 15 Pro Max 256GB',
                'short_description' => 'Le smartphone le plus avancé d\'Apple avec puce A17 Pro et appareil photo professionnel.',
                'description' => 'L\'iPhone 15 Pro Max redéfinit l\'innovation mobile avec sa puce A17 Pro révolutionnaire, son système de caméra Pro avancé et son design en titane ultraléger. Profitez d\'une autonomie exceptionnelle et de performances inégalées.',
                'price' => 1229.00,
                'original_price' => 1329.00,
                'images' => [
                    'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=500&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1565849904461-04a58ad377e0?w=500&h=500&fit=crop'
                ],
                'attributes' => [
                    'color' => ['Titane naturel', 'Titane bleu', 'Titane blanc', 'Titane noir'],
                    'storage' => ['256GB', '512GB', '1TB'],
                    'brand' => 'Apple'
                ]
            ],
            [
                'name' => 'MacBook Pro 14" M3 Pro',
                'short_description' => 'Ordinateur portable professionnel avec puce M3 Pro pour une performance exceptionnelle.',
                'description' => 'Le MacBook Pro 14 pouces avec puce M3 Pro offre des performances révolutionnaires pour les professionnels créatifs. Écran Liquid Retina XDR, autonomie remarquable et connectivité avancée.',
                'price' => 2299.00,
                'images' => [
                    'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=500&h=500&fit=crop'
                ],
                'attributes' => [
                    'color' => ['Gris sidéral', 'Argent'],
                    'memory' => ['18GB', '36GB'],
                    'storage' => ['512GB', '1TB', '2TB', '4TB'],
                    'brand' => 'Apple'
                ]
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'short_description' => 'Smartphone premium Android avec S Pen intégré et caméra 200MP.',
                'description' => 'Le Galaxy S24 Ultra combine puissance et créativité avec son S Pen intégré, sa caméra de 200MP et son écran Dynamic AMOLED 2X. L\'IA Galaxy transforme votre expérience mobile.',
                'price' => 1199.00,
                'images' => [
                    'https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?w=500&h=500&fit=crop'
                ],
                'attributes' => [
                    'color' => ['Titanium Gray', 'Titanium Black', 'Titanium Violet', 'Titanium Yellow'],
                    'storage' => ['256GB', '512GB', '1TB'],
                    'brand' => 'Samsung'
                ]
            ],
            [
                'name' => 'AirPods Pro 2ème génération',
                'short_description' => 'Écouteurs sans fil avec réduction de bruit active et audio spatial.',
                'description' => 'Les AirPods Pro de 2ème génération offrent une réduction de bruit active révolutionnaire, un son immersif et une autonomie prolongée. Compatible avec l\'audio spatial pour une expérience d\'écoute unique.',
                'price' => 279.00,
                'original_price' => 299.00,
                'images' => [
                    'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&h=500&fit=crop'
                ],
                'attributes' => [
                    'color' => ['Blanc'],
                    'connectivity' => ['Bluetooth 5.3', 'Lightning', 'MagSafe'],
                    'brand' => 'Apple'
                ]
            ],
            [
                'name' => 'Sony WH-1000XM5',
                'short_description' => 'Casque audio premium avec réduction de bruit de pointe.',
                'description' => 'Le casque Sony WH-1000XM5 offre la meilleure réduction de bruit au monde, un son haute résolution et un confort exceptionnel pour vos longues sessions d\'écoute.',
                'price' => 349.00,
                'images' => [
                    'https://images.unsplash.com/photo-1484704849700-f032a568e944?w=500&h=500&fit=crop'
                ],
                'attributes' => [
                    'color' => ['Noir', 'Argent'],
                    'connectivity' => ['Bluetooth', 'Jack 3.5mm', 'USB-C'],
                    'battery' => '30 heures',
                    'brand' => 'Sony'
                ]
            ]
        ];
    }

    private function getWomenFashionProducts(): array
    {
        return [
            [
                'name' => 'Robe fluide à motifs floraux',
                'short_description' => 'Robe élégante en viscose avec imprimé floral, parfaite pour le printemps.',
                'description' => 'Cette magnifique robe fluide en viscose douce présente un superbe imprimé floral. Sa coupe évasée et sa longueur midi en font la pièce parfaite pour vos occasions spéciales ou votre quotidien chic.',
                'price' => 89.90,
                'original_price' => 119.90,
                'images' => [
                    'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=500&h=500&fit=crop',
                    'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=500&h=500&fit=crop'
                ],
                'attributes' => [
                    'size' => ['XS', 'S', 'M', 'L', 'XL'],
                    'color' => ['Rose poudré', 'Bleu marine', 'Vert menthe'],
                    'material' => '100% Viscose',
                    'care' => 'Lavage à 30°C'
                ]
            ],
            [
                'name' => 'Blazer ajusté en laine',
                'short_description' => 'Blazer classique en laine mélangée, coupe moderne et élégante.',
                'description' => 'Blazer intemporel en laine mélangée avec une coupe ajustée moderne. Parfait pour le bureau ou les occasions formelles, il se marie facilement avec pantalons et jupes.',
                'price' => 159.00,
                'images' => [
                    'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=500&h=500&fit=crop'
                ],
                'attributes' => [
                    'size' => ['34', '36', '38', '40', '42', '44'],
                    'color' => ['Noir', 'Gris anthracite', 'Camel'],
                    'material' => '70% Laine, 30% Polyester'
                ]
            ],
            [
                'name' => 'Jean mom taille haute',
                'short_description' => 'Jean vintage mom fit en denim brut, confort et style authentique.',
                'description' => 'Jean mom au style vintage avec taille haute et coupe décontractée. Fabriqué en denim de qualité supérieure pour un look authentique et un confort optimal.',
                'price' => 79.90,
                'images' => [
                    'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=500&h=500&fit=crop'
                ],
                'attributes' => [
                    'size' => ['25', '26', '27', '28', '29', '30', '31', '32'],
                    'color' => ['Bleu brut', 'Bleu délavé', 'Noir'],
                    'fit' => 'Mom fit',
                    'material' => '100% Coton'
                ]
            ]
        ];
    }

    private function getMenFashionProducts(): array
    {
        return [
            [
                'name' => 'Chemise en lin blanc',
                'short_description' => 'Chemise classique en lin naturel, fraîche et élégante.',
                'description' => 'Chemise intemporelle en lin 100% naturel. Sa texture légère et respirante en fait le choix parfait pour les beaux jours. Coupe classique avec col italien.',
                'price' => 69.90,
                'images' => [
                    'https://images.unsplash.com/photo-1620012253295-c15cc3e65df4?w=500&h=500&fit=crop'
                ],
                'attributes' => [
                    'size' => ['S', 'M', 'L', 'XL', 'XXL'],
                    'color' => ['Blanc', 'Bleu ciel', 'Beige'],
                    'material' => '100% Lin',
                    'collar' => 'Col italien'
                ]
            ],
            [
                'name' => 'Sneakers cuir premium',
                'short_description' => 'Baskets en cuir véritable, design minimaliste et moderne.',
                'description' => 'Sneakers haut de gamme en cuir véritable avec semelle en caoutchouc. Design épuré et moderne pour un style casual chic. Confort exceptionnel pour un usage quotidien.',
                'price' => 139.00,
                'original_price' => 179.00,
                'images' => [
                    'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=500&h=500&fit=crop'
                ],
                'attributes' => [
                    'size' => ['40', '41', '42', '43', '44', '45', '46'],
                    'color' => ['Blanc', 'Noir', 'Cognac'],
                    'material' => 'Cuir véritable'
                ]
            ]
        ];
    }

    private function getHomeDecoProducts(): array
    {
        return [
            [
                'name' => 'Canapé 3 places en velours',
                'short_description' => 'Canapé moderne en velours avec pieds en bois, confort optimal.',
                'description' => 'Magnifique canapé 3 places recouvert de velours doux au toucher. Structure en bois massif et pieds en chêne. Assise moelleuse avec coussins déhoussables.',
                'price' => 899.00,
                'images' => [
                    'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=500&h=500&fit=crop'
                ],
                'attributes' => [
                    'color' => ['Bleu marine', 'Gris perle', 'Vert émeraude'],
                    'material' => 'Velours et bois de chêne',
                    'dimensions' => '200x85x90 cm'
                ]
            ],
            [
                'name' => 'Lampe de table en céramique',
                'short_description' => 'Lampe décorative en céramique artisanale avec abat-jour en lin.',
                'description' => 'Élégante lampe de table en céramique façonnée à la main. Base texturée et abat-jour en lin naturel pour une lumière douce et chaleureuse.',
                'price' => 89.00,
                'images' => [
                    'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=500&h=500&fit=crop'
                ],
                'attributes' => [
                    'color' => ['Blanc cassé', 'Terracotta', 'Gris'],
                    'material' => 'Céramique et lin',
                    'height' => '45 cm'
                ]
            ]
        ];
    }

    private function getSportsProducts(): array
    {
        return [
            [
                'name' => 'Vélo électrique urbain',
                'short_description' => 'Vélo électrique design pour vos déplacements en ville.',
                'description' => 'Vélo électrique moderne avec batterie longue durée, parfait pour les déplacements urbains. Moteur silencieux et design épuré.',
                'price' => 1299.00,
                'images' => [
                    'https://images.unsplash.com/photo-1571068316344-75bc76f77890?w=500&h=500&fit=crop'
                ],
                'attributes' => [
                    'color' => ['Noir mat', 'Blanc', 'Gris'],
                    'battery' => '50km autonomie',
                    'weight' => '22 kg'
                ]
            ]
        ];
    }

    private function getBeautyProducts(): array
    {
        return [
            [
                'name' => 'Crème hydratante visage bio',
                'short_description' => 'Soin hydratant quotidien aux extraits naturels, tous types de peau.',
                'description' => 'Crème visage formulée avec des ingrédients biologiques. Hydrate en profondeur et protège la peau des agressions extérieures.',
                'price' => 34.90,
                'images' => [
                    'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=500&h=500&fit=crop'
                ],
                'attributes' => [
                    'volume' => '50ml',
                    'skin_type' => 'Tous types de peau',
                    'certification' => 'Bio'
                ]
            ]
        ];
    }

    private function getBooksProducts(): array
    {
        return [
            [
                'name' => 'Le Guide du Développeur Web',
                'short_description' => 'Manuel complet pour apprendre le développement web moderne.',
                'description' => 'Guide pratique couvrant HTML, CSS, JavaScript et les frameworks modernes. Parfait pour débuter ou perfectionner ses compétences.',
                'price' => 39.90,
                'is_digital' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=500&h=500&fit=crop'
                ],
                'attributes' => [
                    'author' => 'Jean Dupont',
                    'pages' => '450',
                    'language' => 'Français',
                    'format' => ['Papier', 'Digital']
                ]
            ]
        ];
    }

    private function getKidsProducts(): array
    {
        return [
            [
                'name' => 'Peluche licorne géante',
                'short_description' => 'Adorable peluche licorne douce et colorée, parfaite pour les câlins.',
                'description' => 'Grande peluche licorne aux couleurs pastel, fabriquée avec des matériaux hypoallergéniques. Parfaite pour décorer la chambre et faire de doux rêves.',
                'price' => 29.90,
                'images' => [
                    'https://images.unsplash.com/photo-1566694271453-390536dd1f68?w=500&h=500&fit=crop'
                ],
                'attributes' => [
                    'age' => '3+',
                    'size' => '45 cm',
                    'material' => 'Polyester hypoallergénique'
                ]
            ]
        ];
    }

    private function getGardenProducts(): array
    {
        return [
            [
                'name' => 'Set d\'outils de jardinage',
                'short_description' => 'Kit complet d\'outils essentiels pour l\'entretien du jardin.',
                'description' => 'Ensemble de 5 outils de jardinage en acier inoxydable avec manches ergonomiques. Parfait pour tous vos travaux de jardinage.',
                'price' => 49.90,
                'images' => [
                    'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=500&h=500&fit=crop'
                ],
                'attributes' => [
                    'pieces' => '5 outils',
                    'material' => 'Acier inoxydable',
                    'warranty' => '2 ans'
                ]
            ]
        ];
    }

    private function getAutoProducts(): array
    {
        return [
            [
                'name' => 'Tapis de sol universels',
                'short_description' => 'Set de 4 tapis de sol auto, résistants et faciles à nettoyer.',
                'description' => 'Tapis automobiles universels en caoutchouc haute qualité. Résistants à l\'eau et faciles à entretenir, ils protègent efficacement votre véhicule.',
                'price' => 39.90,
                'images' => [
                    'https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?w=500&h=500&fit=crop'
                ],
                'attributes' => [
                    'pieces' => '4 tapis',
                    'material' => 'Caoutchouc',
                    'compatibility' => 'Universal'
                ]
            ]
        ];
    }
}