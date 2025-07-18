<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Créer une variante par défaut pour chaque produit existant
        DB::statement("
            INSERT INTO product_variants (
                uuid, 
                product_id, 
                sku, 
                name, 
                price, 
                original_price, 
                stock_quantity, 
                manage_stock, 
                in_stock, 
                low_stock_threshold,
                weight,
                dimensions,
                images,
                featured_image,
                is_default,
                status,
                created_at,
                updated_at
            )
            SELECT 
                gen_random_uuid() as uuid,
                p.id as product_id,
                p.sku as sku,
                p.name as name,
                p.price,
                p.original_price,
                p.stock_quantity,
                p.manage_stock,
                p.in_stock,
                p.low_stock_threshold,
                p.weight,
                p.dimensions,
                p.images,
                p.featured_image,
                true as is_default,
                'active' as status,
                NOW() as created_at,
                NOW() as updated_at
            FROM products p
            WHERE NOT EXISTS (
                SELECT 1 FROM product_variants pv WHERE pv.product_id = p.id
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer toutes les variantes par défaut
        DB::statement("DELETE FROM product_variants WHERE is_default = true");
    }
};