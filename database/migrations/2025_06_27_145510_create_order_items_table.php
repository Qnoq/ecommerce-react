<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Relations
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict'); // Pas de suppression si dans commande
            
            // Informations du produit au moment de la commande (snapshot)
            $table->string('product_name');
            $table->string('product_sku');
            $table->text('product_description')->nullable();
            $table->string('product_image')->nullable();
            
            // Quantité et prix
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);      // Prix unitaire à la commande
            $table->decimal('total_price', 10, 2);     // quantity * unit_price
            
            // Variantes et options du produit
            $table->json('product_options')->nullable();    // {"color": "rouge", "size": "L"}
            $table->json('customizations')->nullable();     // Personnalisations
            
            // Statut de l'article dans la commande
            $table->enum('status', [
                'pending',        // En attente
                'processing',     // En cours
                'shipped',        // Expédié
                'delivered',      // Livré
                'cancelled',      // Annulé
                'returned',       // Retourné
                'refunded'        // Remboursé
            ])->default('pending');
            
            // Informations de livraison spécifique à cet item
            $table->string('tracking_number')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            // Remboursements
            $table->decimal('refunded_amount', 10, 2)->default(0);
            $table->timestamp('refunded_at')->nullable();
            
            // Retours
            $table->enum('return_status', [
                'none', 'requested', 'approved', 'received', 'processed'
            ])->default('none');
            $table->text('return_reason')->nullable();
            $table->timestamp('return_requested_at')->nullable();
            
            // Métadonnées
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index(['order_id', 'status']);
            $table->index('product_id');
            $table->index('product_sku');
            $table->index('tracking_number');
        });
        
        // Contraintes PostgreSQL
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_quantity_positive CHECK (quantity > 0)');
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_refunded_amount_valid CHECK (refunded_amount >= 0 AND refunded_amount <= total_price)');
    }

    public function down()
    {
        Schema::dropIfExists('order_items');
    }
};