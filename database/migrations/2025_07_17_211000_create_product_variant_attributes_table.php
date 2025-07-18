<?php

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
        Schema::create('product_variant_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            
            // Nom et valeur de l'attribut
            $table->string('attribute_name'); // Ex: "couleur", "taille", "stockage"
            $table->string('attribute_value'); // Ex: "rouge", "L", "256GB"
            
            // Données supplémentaires pour l'attribut
            $table->string('display_name')->nullable(); // Nom d'affichage (ex: "Rouge Passion")
            $table->string('color_code')->nullable(); // Code couleur hex pour les couleurs
            $table->string('image_url')->nullable(); // Image spécifique à cet attribut
            $table->integer('sort_order')->default(0); // Ordre d'affichage
            
            // Métadonnées
            $table->json('metadata')->nullable(); // Infos supplémentaires
            
            $table->timestamps();
            
            // Index pour performance et unicité
            $table->unique(['product_variant_id', 'attribute_name']); // Une seule valeur par attribut par variante
            $table->index(['attribute_name', 'attribute_value']); // Pour rechercher par attribut
            $table->index(['product_variant_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variant_attributes');
    }
};