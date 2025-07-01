<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ajouter les index JSON avancés pour PostgreSQL
     */
    public function up(): void
    {
        // D'ABORD convertir les colonnes JSON en JSONB pour PostgreSQL
        DB::statement('ALTER TABLE users ALTER COLUMN roles TYPE jsonb USING roles::jsonb');
        DB::statement('ALTER TABLE users ALTER COLUMN addresses TYPE jsonb USING addresses::jsonb');
        DB::statement('ALTER TABLE users ALTER COLUMN notification_preferences TYPE jsonb USING notification_preferences::jsonb');
        DB::statement('ALTER TABLE users ALTER COLUMN metadata TYPE jsonb USING metadata::jsonb');
        
        DB::statement('ALTER TABLE products ALTER COLUMN attributes TYPE jsonb USING attributes::jsonb');
        DB::statement('ALTER TABLE products ALTER COLUMN dimensions TYPE jsonb USING dimensions::jsonb');
        DB::statement('ALTER TABLE products ALTER COLUMN seo_meta TYPE jsonb USING seo_meta::jsonb');
        DB::statement('ALTER TABLE products ALTER COLUMN images TYPE jsonb USING images::jsonb');
        DB::statement('ALTER TABLE products ALTER COLUMN videos TYPE jsonb USING videos::jsonb');
        
        DB::statement('ALTER TABLE cart_items ALTER COLUMN product_options TYPE jsonb USING product_options::jsonb');
        DB::statement('ALTER TABLE cart_items ALTER COLUMN product_snapshot TYPE jsonb USING product_snapshot::jsonb');
        DB::statement('ALTER TABLE cart_items ALTER COLUMN customizations TYPE jsonb USING customizations::jsonb');
        
        DB::statement('ALTER TABLE orders ALTER COLUMN shipping_address TYPE jsonb USING shipping_address::jsonb');
        DB::statement('ALTER TABLE orders ALTER COLUMN billing_address TYPE jsonb USING billing_address::jsonb');
        DB::statement('ALTER TABLE orders ALTER COLUMN payment_metadata TYPE jsonb USING payment_metadata::jsonb');
        DB::statement('ALTER TABLE orders ALTER COLUMN shipping_metadata TYPE jsonb USING shipping_metadata::jsonb');
        DB::statement('ALTER TABLE orders ALTER COLUMN metadata TYPE jsonb USING metadata::jsonb');
        
        DB::statement('ALTER TABLE order_items ALTER COLUMN product_options TYPE jsonb USING product_options::jsonb');
        DB::statement('ALTER TABLE order_items ALTER COLUMN customizations TYPE jsonb USING customizations::jsonb');
        DB::statement('ALTER TABLE order_items ALTER COLUMN metadata TYPE jsonb USING metadata::jsonb');
        
        DB::statement('ALTER TABLE wishlists ALTER COLUMN tags TYPE jsonb USING tags::jsonb');
        
        DB::statement('ALTER TABLE product_reviews ALTER COLUMN pros TYPE jsonb USING pros::jsonb');
        DB::statement('ALTER TABLE product_reviews ALTER COLUMN cons TYPE jsonb USING cons::jsonb');
        DB::statement('ALTER TABLE product_reviews ALTER COLUMN images TYPE jsonb USING images::jsonb');
        DB::statement('ALTER TABLE product_reviews ALTER COLUMN videos TYPE jsonb USING videos::jsonb');
        DB::statement('ALTER TABLE product_reviews ALTER COLUMN metadata TYPE jsonb USING metadata::jsonb');
        
        DB::statement('ALTER TABLE categories ALTER COLUMN seo_meta TYPE jsonb USING seo_meta::jsonb');
        
        DB::statement('ALTER TABLE carts ALTER COLUMN metadata TYPE jsonb USING metadata::jsonb');
        
        // MAINTENANT créer les index GIN avec jsonb_path_ops
        DB::statement('CREATE INDEX IF NOT EXISTS users_roles_gin_idx ON users USING GIN (roles jsonb_path_ops) WHERE roles IS NOT NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS users_addresses_gin_idx ON users USING GIN (addresses jsonb_path_ops) WHERE addresses IS NOT NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS users_notification_prefs_gin_idx ON users USING GIN (notification_preferences jsonb_path_ops) WHERE notification_preferences IS NOT NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS users_metadata_gin_idx ON users USING GIN (metadata jsonb_path_ops) WHERE metadata IS NOT NULL');
        
        DB::statement('CREATE INDEX IF NOT EXISTS products_attributes_gin_idx ON products USING GIN (attributes jsonb_path_ops) WHERE attributes IS NOT NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS products_dimensions_gin_idx ON products USING GIN (dimensions jsonb_path_ops) WHERE dimensions IS NOT NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS products_seo_meta_gin_idx ON products USING GIN (seo_meta jsonb_path_ops) WHERE seo_meta IS NOT NULL');
        
        DB::statement('CREATE INDEX IF NOT EXISTS cart_items_options_gin_idx ON cart_items USING GIN (product_options jsonb_path_ops) WHERE product_options IS NOT NULL');
        
        DB::statement('CREATE INDEX IF NOT EXISTS orders_metadata_gin_idx ON orders USING GIN (metadata jsonb_path_ops) WHERE metadata IS NOT NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS orders_shipping_address_gin_idx ON orders USING GIN (shipping_address jsonb_path_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS orders_billing_address_gin_idx ON orders USING GIN (billing_address jsonb_path_ops)');
        
        DB::statement('CREATE INDEX IF NOT EXISTS wishlists_tags_gin_idx ON wishlists USING GIN (tags jsonb_path_ops) WHERE tags IS NOT NULL');
        
        DB::statement('CREATE INDEX IF NOT EXISTS product_reviews_pros_gin_idx ON product_reviews USING GIN (pros jsonb_path_ops) WHERE pros IS NOT NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS product_reviews_cons_gin_idx ON product_reviews USING GIN (cons jsonb_path_ops) WHERE cons IS NOT NULL');
    }

    /**
     * Supprimer les index JSON avancés et revenir au type json
     */
    public function down(): void
    {
        // Supprimer tous les index créés
        DB::statement('DROP INDEX IF EXISTS users_roles_gin_idx');
        DB::statement('DROP INDEX IF EXISTS users_addresses_gin_idx');
        DB::statement('DROP INDEX IF EXISTS users_notification_prefs_gin_idx');
        DB::statement('DROP INDEX IF EXISTS users_metadata_gin_idx');
        DB::statement('DROP INDEX IF EXISTS products_attributes_gin_idx');
        DB::statement('DROP INDEX IF EXISTS products_dimensions_gin_idx');
        DB::statement('DROP INDEX IF EXISTS products_seo_meta_gin_idx');
        DB::statement('DROP INDEX IF EXISTS cart_items_options_gin_idx');
        DB::statement('DROP INDEX IF EXISTS orders_metadata_gin_idx');
        DB::statement('DROP INDEX IF EXISTS orders_shipping_address_gin_idx');
        DB::statement('DROP INDEX IF EXISTS orders_billing_address_gin_idx');
        DB::statement('DROP INDEX IF EXISTS wishlists_tags_gin_idx');
        DB::statement('DROP INDEX IF EXISTS product_reviews_pros_gin_idx');
        DB::statement('DROP INDEX IF EXISTS product_reviews_cons_gin_idx');
    }
};