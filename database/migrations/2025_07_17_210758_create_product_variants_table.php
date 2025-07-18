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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            
            // Identification
            $table->string('sku')->unique();
            $table->string('name')->nullable(); // Ex: "iPhone 15 Pro 256GB Bleu"
            $table->string('slug')->nullable();
            
            // Prix spécifique à la variante
            $table->decimal('price', 10, 2);
            $table->decimal('original_price', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->nullable(); // Prix d'achat
            
            // Stock spécifique à la variante
            $table->integer('stock_quantity')->default(0);
            $table->boolean('manage_stock')->default(true);
            $table->boolean('in_stock')->default(true);
            $table->integer('low_stock_threshold')->default(5);
            
            // Caractéristiques physiques spécifiques
            $table->decimal('weight', 8, 2)->nullable();
            $table->json('dimensions')->nullable(); // {"length": 10, "width": 5, "height": 3}
            
            // Médias spécifiques à la variante
            $table->json('images')->nullable(); // Images spécifiques (couleur différente)
            $table->string('featured_image')->nullable();
            
            // Position d'affichage
            $table->integer('sort_order')->default(0);
            
            // Status
            $table->enum('status', ['active', 'inactive', 'out_of_stock'])->default('active');
            $table->boolean('is_default')->default(false); // Variante par défaut
            
            // Métadonnées
            $table->json('metadata')->nullable(); // Données supplémentaires
            
            // Analytics spécifiques à la variante
            $table->integer('view_count')->default(0);
            $table->integer('sales_count')->default(0);
            
            $table->timestamps();
            
            // Index pour performance
            $table->index(['product_id', 'status']);
            $table->index(['product_id', 'is_default']);
            $table->index(['sku']);
            $table->index(['in_stock', 'stock_quantity']);
            $table->index(['product_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};