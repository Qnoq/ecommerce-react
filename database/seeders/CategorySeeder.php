<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Électronique',
                'description' => 'Smartphones, ordinateurs, accessoires high-tech et gadgets électroniques dernière génération.',
                'image_url' => 'https://images.unsplash.com/photo-1468495244123-6c6c332eeece?w=400&h=300&fit=crop',
                'sort_order' => 1,
                'seo_meta' => [
                    'title' => 'Électronique - High-tech et gadgets | ShopLux',
                    'description' => 'Découvrez notre gamme complète d\'électronique : smartphones, ordinateurs, accessoires tech.',
                    'keywords' => ['électronique', 'smartphone', 'ordinateur', 'high-tech', 'gadgets']
                ]
            ],
            [
                'name' => 'Mode Femme',
                'description' => 'Vêtements, chaussures et accessoires de mode pour femmes. Tendances et styles actuels.',
                'image_url' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=400&h=300&fit=crop',
                'sort_order' => 2,
                'seo_meta' => [
                    'title' => 'Mode Femme - Vêtements et accessoires | ShopLux',
                    'description' => 'Collection mode femme : robes, tops, pantalons, chaussures et accessoires tendance.',
                    'keywords' => ['mode femme', 'vêtements', 'robes', 'chaussures', 'accessoires']
                ]
            ],
            [
                'name' => 'Mode Homme',
                'description' => 'Collection masculine : vêtements, chaussures et accessoires pour hommes modernes.',
                'image_url' => 'https://images.unsplash.com/photo-1490114538077-0a7f8cb49891?w=400&h=300&fit=crop',
                'sort_order' => 3,
                'seo_meta' => [
                    'title' => 'Mode Homme - Vêtements masculins | ShopLux',
                    'description' => 'Vêtements homme : chemises, pantalons, costumes, chaussures et accessoires.',
                    'keywords' => ['mode homme', 'vêtements homme', 'chemises', 'costumes', 'chaussures']
                ]
            ],
            [
                'name' => 'Maison & Déco',
                'description' => 'Décoration d\'intérieur, meubles, luminaires et accessoires pour embellir votre maison.',
                'image_url' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=400&h=300&fit=crop',
                'sort_order' => 4,
                'seo_meta' => [
                    'title' => 'Maison & Déco - Meubles et décoration | ShopLux',
                    'description' => 'Meubles, décoration, luminaires et accessoires pour votre intérieur.',
                    'keywords' => ['maison', 'décoration', 'meubles', 'luminaires', 'intérieur']
                ]
            ],
            [
                'name' => 'Sport & Loisirs',
                'description' => 'Équipements sportifs, vêtements de sport et accessoires pour tous vos loisirs.',
                'image_url' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=400&h=300&fit=crop',
                'sort_order' => 5,
                'seo_meta' => [
                    'title' => 'Sport & Loisirs - Équipements sportifs | ShopLux',
                    'description' => 'Matériel de sport, vêtements sportifs et équipements pour vos activités.',
                    'keywords' => ['sport', 'fitness', 'équipement sportif', 'loisirs', 'activités']
                ]
            ],
            [
                'name' => 'Beauté & Bien-être',
                'description' => 'Cosmétiques, parfums, soins du corps et produits de bien-être premium.',
                'image_url' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=400&h=300&fit=crop',
                'sort_order' => 6,
                'seo_meta' => [
                    'title' => 'Beauté & Bien-être - Cosmétiques et soins | ShopLux',
                    'description' => 'Cosmétiques, parfums, soins visage et corps pour votre beauté.',
                    'keywords' => ['beauté', 'cosmétiques', 'parfums', 'soins', 'bien-être']
                ]
            ],
            [
                'name' => 'Livres & Culture',
                'description' => 'Livres, BD, magazines et produits culturels pour enrichir vos connaissances.',
                'image_url' => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=400&h=300&fit=crop',
                'sort_order' => 7,
                'seo_meta' => [
                    'title' => 'Livres & Culture - Romans, BD et magazines | ShopLux',
                    'description' => 'Large sélection de livres, bandes dessinées et produits culturels.',
                    'keywords' => ['livres', 'romans', 'BD', 'culture', 'lecture']
                ]
            ],
            [
                'name' => 'Enfants & Bébés',
                'description' => 'Vêtements, jouets, puériculture et tout pour le bonheur des enfants.',
                'image_url' => 'https://images.unsplash.com/photo-1514090458221-65bb69cf63e6?w=400&h=300&fit=crop',
                'sort_order' => 8,
                'seo_meta' => [
                    'title' => 'Enfants & Bébés - Vêtements et jouets | ShopLux',
                    'description' => 'Vêtements enfants, jouets, puériculture et accessoires bébé.',
                    'keywords' => ['enfants', 'bébé', 'jouets', 'puériculture', 'vêtements enfants']
                ]
            ],
            [
                'name' => 'Jardin & Extérieur',
                'description' => 'Outils de jardinage, mobilier d\'extérieur et décoration pour votre jardin.',
                'image_url' => 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=400&h=300&fit=crop',
                'sort_order' => 9,
                'seo_meta' => [
                    'title' => 'Jardin & Extérieur - Mobilier et outils | ShopLux',
                    'description' => 'Mobilier de jardin, outils de jardinage et décoration extérieure.',
                    'keywords' => ['jardin', 'extérieur', 'mobilier jardin', 'outils jardinage', 'plantes']
                ]
            ],
            [
                'name' => 'Auto & Moto',
                'description' => 'Accessoires automobiles, équipements moto et produits d\'entretien véhicules.',
                'image_url' => 'https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?w=400&h=300&fit=crop',
                'sort_order' => 10,
                'seo_meta' => [
                    'title' => 'Auto & Moto - Accessoires véhicules | ShopLux',
                    'description' => 'Accessoires auto, équipements moto et produits d\'entretien.',
                    'keywords' => ['auto', 'moto', 'accessoires auto', 'équipement moto', 'entretien']
                ]
            ]
        ];

        foreach ($categories as $categoryData) {
            Category::create([
                'name' => $categoryData['name'],
                'slug' => Str::slug($categoryData['name']),
                'description' => $categoryData['description'],
                'image_url' => $categoryData['image_url'],
                'is_active' => true,
                'sort_order' => $categoryData['sort_order'],
                'seo_meta' => $categoryData['seo_meta']
            ]);
        }

        $this->command->info('✅ ' . count($categories) . ' catégories créées avec succès !');
    }
}