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
        Schema::create('categories', function (Blueprint $table) {
            $table->id(); // Auto-increment pour les relations rapides
            $table->uuid('uuid')->unique(); // UUID pour les APIs publiques
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('seo_meta')->nullable(); // {"title": "", "description": "", "keywords": []}
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            // Pour la recherche vectorielle (optionnel pour les catégories)
            $table->text('search_content')->nullable(); // Contenu optimisé pour la recherche
            
            $table->timestamps();
            
            // Index PostgreSQL
            $table->index(['is_active', 'sort_order']);
            $table->index('slug');
        });
        
        // Extension PostgreSQL pour UUID si pas déjà activée
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
