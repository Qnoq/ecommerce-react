<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Activer les extensions PostgreSQL nécessaires pour l'e-commerce
     */
    public function up(): void
    {
        // EXTENSION UUID - Pour générer des UUID natifs
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        
        // EXTENSIONS RECHERCHE TEXTUELLE - Pour la recherche de produits
        DB::statement('CREATE EXTENSION IF NOT EXISTS "pg_trgm"');   // Recherche trigram (fuzzy search)
        DB::statement('CREATE EXTENSION IF NOT EXISTS "unaccent"');  // Suppression accents pour recherche
        
        // EXTENSION FUZZY MATCHING - Recherche approximative
        DB::statement('CREATE EXTENSION IF NOT EXISTS "fuzzystrmatch"'); // Recherche phonétique
        
        // EXTENSIONS POUR LA RECHERCHE VECTORIELLE (commentées pour plus tard)
        // DB::statement('CREATE EXTENSION IF NOT EXISTS "vector"'); // pgvector pour embeddings
        
        // CRÉER DES INDEX DE RECHERCHE TEXTUELLE AVANCÉS
        
        // Index de recherche pour les produits (français)
        DB::statement("
            CREATE INDEX IF NOT EXISTS products_fulltext_search_idx 
            ON products 
            USING GIN(to_tsvector('french', 
                COALESCE(name, '') || ' ' || 
                COALESCE(description, '') || ' ' || 
                COALESCE(search_content, '')
            ))
        ");
        
        // Index de recherche trigram pour les noms de produits (tolérance aux fautes)
        DB::statement("
            CREATE INDEX IF NOT EXISTS products_name_trgm_idx 
            ON products 
            USING GIN(name gin_trgm_ops)
        ");
        
        // Index pour les catégories
        DB::statement("
            CREATE INDEX IF NOT EXISTS categories_fulltext_search_idx 
            ON categories 
            USING GIN(to_tsvector('french', 
                COALESCE(name, '') || ' ' || 
                COALESCE(description, '')
            ))
        ");
        
        // Index pour les utilisateurs (recherche admin)
        DB::statement("
            CREATE INDEX IF NOT EXISTS users_fulltext_search_idx 
            ON users 
            USING GIN(to_tsvector('french', 
                COALESCE(name, '') || ' ' || 
                COALESCE(email, '') || ' ' ||
                COALESCE(first_name, '') || ' ' ||
                COALESCE(last_name, '')
            ))
        ");
        
        // Extensions PostgreSQL configurées pour la recherche avancée ✅
    }

    /**
     * Supprimer les index créés (on garde les extensions)
     */
    public function down(): void
    {
        // Supprimer les index personnalisés
        DB::statement('DROP INDEX IF EXISTS products_fulltext_search_idx');
        DB::statement('DROP INDEX IF EXISTS products_name_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS categories_fulltext_search_idx');
        DB::statement('DROP INDEX IF EXISTS users_fulltext_search_idx');
        
        // Note: On ne supprime pas les extensions PostgreSQL en down()
        // car elles peuvent être utilisées par d'autres applications
    }
};