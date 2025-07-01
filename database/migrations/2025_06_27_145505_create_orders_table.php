<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Numéro de commande public (ex: ORD-2025-001234)
            $table->string('order_number')->unique();
            
            // Relation utilisateur
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Statuts de la commande
            $table->enum('status', [
                'pending',        // En attente de paiement
                'processing',     // En cours de traitement
                'confirmed',      // Confirmée
                'preparing',      // Préparation
                'shipped',        // Expédiée
                'delivered',      // Livrée
                'cancelled',      // Annulée
                'refunded',       // Remboursée
                'returned'        // Retournée
            ])->default('pending');
            
            // Montants (en centimes pour éviter les erreurs de précision)
            $table->decimal('subtotal', 10, 2);           // Sous-total produits
            $table->decimal('tax_amount', 10, 2)->default(0);        // TVA
            $table->decimal('shipping_amount', 10, 2)->default(0);   // Frais de port
            $table->decimal('discount_amount', 10, 2)->default(0);   // Réduction
            $table->decimal('total_amount', 10, 2);       // Total final
            $table->string('currency', 3)->default('EUR');
            
            // Code promo utilisé
            $table->string('coupon_code')->nullable();
            $table->decimal('coupon_discount', 10, 2)->default(0);
            
            // Adresses (JSON pour flexibilité)
            $table->json('billing_address');   // Adresse de facturation
            $table->json('shipping_address');  // Adresse de livraison
            
            // Informations de paiement
            $table->enum('payment_status', [
                'pending',        // En attente
                'authorized',     // Autorisé (carte bloquée)
                'paid',          // Payé
                'partially_paid', // Partiellement payé
                'failed',        // Échec
                'cancelled',     // Annulé
                'refunded',      // Remboursé
                'partially_refunded' // Partiellement remboursé
            ])->default('pending');
            
            $table->string('payment_method')->nullable();     // stripe, paypal, bank_transfer, etc.
            $table->string('payment_intent_id')->nullable();  // ID Stripe/PayPal
            $table->json('payment_metadata')->nullable();     // Métadonnées paiement
            $table->timestamp('payment_date')->nullable();
            
            // Informations de livraison
            $table->string('shipping_method')->nullable();    // standard, express, pickup
            $table->decimal('shipping_cost', 8, 2)->default(0);
            $table->string('tracking_number')->nullable();
            $table->string('carrier')->nullable();           // La Poste, Chronopost, UPS...
            $table->json('shipping_metadata')->nullable();   // Infos transporteur
            
            // Dates importantes
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            // Notes et commentaires
            $table->text('customer_notes')->nullable();      // Notes du client
            $table->text('admin_notes')->nullable();         // Notes internes
            
            // Origine de la commande
            $table->string('source')->default('website');    // website, mobile_app, admin
            $table->string('user_agent')->nullable();
            $table->ipAddress('ip_address')->nullable();
            
            // Facturation
            $table->string('invoice_number')->nullable()->unique();
            $table->timestamp('invoice_date')->nullable();
            
            // Métadonnées supplémentaires
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Index pour les performances
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['payment_status', 'created_at']);
            $table->index('order_number');
            $table->index('invoice_number');
            $table->index(['shipped_at', 'tracking_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
};