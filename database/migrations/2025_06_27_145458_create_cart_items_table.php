<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Relations
            $table->foreignId('cart_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            
            // Quantité et prix
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            
            // Variantes du produit (couleur, taille, etc.)
            $table->json('product_options')->nullable();
            
            // Hash des options pour l'unicité (solution PostgreSQL)
            $table->string('options_hash')->nullable();
            
            // Snapshot des infos produit
            $table->json('product_snapshot')->nullable();
            
            // Informations de personnalisation
            $table->json('customizations')->nullable();
            
            // Statut de l'article
            $table->enum('status', ['active', 'saved_for_later', 'unavailable'])->default('active');
            
            $table->timestamps();
            
            // SOLUTION: Contrainte unique sur cart_id + product_id + hash des options
            $table->unique(['cart_id', 'product_id', 'options_hash'], 'cart_items_unique_constraint');
            
            // Index pour les requêtes
            $table->index(['cart_id', 'status']);
            $table->index('product_id');
        });
        
        // Contrainte PostgreSQL pour éviter les quantités négatives
        DB::statement('ALTER TABLE cart_items ADD CONSTRAINT cart_items_quantity_positive CHECK (quantity > 0)');
    }

    public function down()
    {
        Schema::dropIfExists('cart_items');
    }
};