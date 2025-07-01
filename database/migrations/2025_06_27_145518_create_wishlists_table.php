<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // UUID public pour les URLs
            
            // RELATIONS - Un utilisateur peut ajouter un produit à sa wishlist
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Si user supprimé -> wishlist supprimée
            $table->foreignId('product_id')->constrained()->onDelete('cascade'); // Si produit supprimé -> item wishlist supprimé
            
            // MÉTADONNÉES - Informations sur l'ajout à la wishlist
            $table->text('note')->nullable(); // Note personnelle du client sur pourquoi il aime ce produit
            $table->integer('priority')->default(1); // Priorité 1-5 (1 = très envie, 5 = juste pour plus tard)
            
            // SNAPSHOT DU PRODUIT - Au cas où le produit change de prix/stock
            $table->decimal('price_when_added', 10, 2)->nullable(); // Prix au moment de l'ajout
            $table->boolean('was_in_stock_when_added')->default(true); // Stock au moment de l'ajout
            
            // NOTIFICATIONS - Pour alerter le client
            $table->boolean('notify_price_drop')->default(true); // Alerter si le prix baisse
            $table->boolean('notify_back_in_stock')->default(true); // Alerter si retour en stock
            $table->boolean('notify_promotion')->default(true); // Alerter si promo sur ce produit
            
            // PARTAGE - Pour les wishlists publiques (cadeaux, mariages, etc.)
            $table->boolean('is_public')->default(false); // Wishlist publique ou privée
            $table->string('public_token')->nullable()->unique(); // Token pour partager la wishlist
            
            // ORGANISATION - Pour catégoriser les souhaits
            $table->string('category')->nullable(); // Ex: "Noël", "Anniversaire", "Envies"
            $table->json('tags')->nullable(); // Tags libres ["urgent", "pas cher", "cadeau papa"]
            
            $table->timestamps();
            
            // CONTRAINTES - Un utilisateur ne peut ajouter qu'une fois le même produit
            $table->unique(['user_id', 'product_id'], 'user_product_wishlist_unique');
            
            // INDEX POUR PERFORMANCES
            $table->index(['user_id', 'created_at']); // Wishlist d'un user triée par date
            $table->index(['user_id', 'priority']); // Wishlist d'un user triée par priorité
            $table->index(['product_id', 'notify_price_drop']); // Tous ceux qui veulent être alertés prix
            $table->index(['product_id', 'notify_back_in_stock']); // Tous ceux qui veulent être alertés stock
            $table->index('public_token'); // Recherche rapide des wishlists publiques
            $table->index(['is_public', 'created_at']); // Wishlists publiques récentes
        });
    }

    public function down()
    {
        Schema::dropIfExists('wishlists');
    }
};