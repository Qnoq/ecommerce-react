<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Index complémentaires pour la recherche ultra-rapide
     * (Les index de base sont déjà dans 2025_06_27_150110_setup_postgresql_extensions.php)
     */
    public function up(): void
    {
        // Index composé pour recherche rapide statut + nom + popularité
        DB::statement('CREATE INDEX IF NOT EXISTS idx_products_search_optimized 
                      ON products (status, name, sales_count DESC) 
                      WHERE status = \'active\'');

        // Index partiel pour les bestsellers (ultra-rapide)
        DB::statement('CREATE INDEX IF NOT EXISTS idx_products_bestsellers 
                      ON products (sales_count DESC, name) 
                      WHERE status = \'active\' AND sales_count > 10');

        // Index optimisé pour les suggestions avec scoring
        DB::statement('CREATE INDEX IF NOT EXISTS idx_products_suggestions_optimized 
                      ON products (name, sales_count DESC, created_at DESC) 
                      WHERE status = \'active\'');

        // Index pour recherche par prix avec filtres
        DB::statement('CREATE INDEX IF NOT EXISTS idx_products_price_search 
                      ON products (status, price, sales_count DESC) 
                      WHERE status = \'active\'');

        // Index pour recherche par date de création (nouveautés) - CORRIGÉ
        // Utilisation d'une date fixe ou suppression de la condition temporelle
        DB::statement('CREATE INDEX IF NOT EXISTS idx_products_recent 
                      ON products (status, created_at DESC, sales_count DESC) 
                      WHERE status = \'active\'');

        // Index pour recherche par featured (coup de coeur)
        DB::statement('CREATE INDEX IF NOT EXISTS idx_products_featured 
                      ON products (status, is_featured, sales_count DESC) 
                      WHERE status = \'active\' AND is_featured = true');
    }

    /**
     * Rollback des index complémentaires
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_products_search_optimized');
        DB::statement('DROP INDEX IF EXISTS idx_products_bestsellers');
        DB::statement('DROP INDEX IF EXISTS idx_products_suggestions_optimized');
        DB::statement('DROP INDEX IF EXISTS idx_products_price_search');
        DB::statement('DROP INDEX IF EXISTS idx_products_recent');
        DB::statement('DROP INDEX IF EXISTS idx_products_featured');
    }
};