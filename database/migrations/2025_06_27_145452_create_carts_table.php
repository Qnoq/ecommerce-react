<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Session ID pour les utilisateurs non connectés
            $table->string('session_id')->nullable();
            
            // User ID pour les utilisateurs connectés (nullable pour guests)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            
            // Informations du panier
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('shipping_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            
            // Code promo appliqué
            $table->string('coupon_code')->nullable();
            
            // Statut du panier
            $table->enum('status', ['active', 'abandoned', 'converted', 'expired'])->default('active');
            
            // Métadonnées
            $table->json('metadata')->nullable(); // Infos supplémentaires (IP, user agent, etc.)
            
            // Date de dernière activité
            $table->timestamp('last_activity_at')->nullable();
            
            // Date d'expiration (pour nettoyer les vieux paniers)
            $table->timestamp('expires_at')->nullable();
            
            $table->timestamps();
            
            // Index pour les requêtes
            $table->index(['session_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index('last_activity_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('carts');
    }
};