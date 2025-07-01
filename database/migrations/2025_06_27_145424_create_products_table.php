<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('short_description')->nullable();
            
            // Prix et stock
            $table->decimal('price', 10, 2);
            $table->decimal('original_price', 10, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->string('sku')->unique();
            $table->integer('stock_quantity')->default(0);
            $table->boolean('manage_stock')->default(true);
            $table->boolean('in_stock')->default(true);
            $table->integer('low_stock_threshold')->default(5);
            
            // Médias
            $table->json('images')->nullable(); // URLs des images
            $table->string('featured_image')->nullable();
            $table->json('videos')->nullable(); // URLs des vidéos
            
            // Caractéristiques physiques
            $table->decimal('weight', 8, 2)->nullable(); // en kg
            $table->json('dimensions')->nullable(); // {"length": 10, "width": 5, "height": 3}
            
            // Statut et visibilité
            $table->enum('status', ['active', 'inactive', 'draft', 'archived'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_digital')->default(false);
            
            // Métadonnées
            $table->json('attributes')->nullable(); // Attributs dynamiques (couleur, taille, etc.)
            $table->json('seo_meta')->nullable();
            
            // Analytics et ratings
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('review_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->integer('sales_count')->default(0);
            $table->integer('wishlist_count')->default(0);
            
            // RECHERCHE VECTORIELLE - C'est là que ça devient puissant ! 🚀
            $table->text('search_content')->nullable(); // Contenu concaténé pour la recherche
            // Colonne pour stocker les embeddings vectoriels (pgvector)
            // $table->vector('embedding', 1536)->nullable(); // OpenAI embeddings dimension
            
            // Dates
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            // Index PostgreSQL optimisés
            $table->index(['status', 'is_featured', 'published_at']);
            $table->index(['is_featured', 'rating']);
            $table->index('sku');
            $table->index('slug');
            $table->index(['in_stock', 'stock_quantity']);
        });
        
        // Index pour recherche textuelle PostgreSQL
        DB::statement('CREATE INDEX products_search_gin ON products USING GIN(to_tsvector(\'french\', COALESCE(name, \'\') || \' \' || COALESCE(description, \'\')))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
