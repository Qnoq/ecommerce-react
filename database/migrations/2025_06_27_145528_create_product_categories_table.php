<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            
            // Optionnel : ordre d'affichage du produit dans la catégorie
            $table->integer('sort_order')->default(0);
            
            // Optionnel : si le produit est mis en avant dans cette catégorie
            $table->boolean('is_featured_in_category')->default(false);
            
            $table->timestamps();
            
            // Contrainte unique : un produit ne peut être qu'une fois dans une catégorie
            $table->unique(['product_id', 'category_id']);
            
            // Index pour les requêtes rapides
            $table->index(['category_id', 'is_featured_in_category', 'sort_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_categories');
    }
};