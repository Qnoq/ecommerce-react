<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // UUID public pour les URLs
            
            // RELATIONS - Un avis lie un produit, un utilisateur et potentiellement une commande
            $table->foreignId('product_id')->constrained()->onDelete('cascade'); // Si produit supprimé -> avis supprimés
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Si user supprimé -> avis supprimés
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null'); // Commande liée (optionnel)
            $table->foreignId('order_item_id')->nullable()->constrained()->onDelete('set null'); // Article spécifique commandé
            
            // CONTENU DE L'AVIS
            $table->integer('rating'); // Note de 1 à 5 étoiles
            $table->string('title')->nullable(); // Titre de l'avis (ex: "Très bon produit !")
            $table->text('comment')->nullable(); // Commentaire détaillé
            $table->json('pros')->nullable(); // Points positifs ["qualité", "livraison rapide"]
            $table->json('cons')->nullable(); // Points négatifs ["un peu cher", "fragile"]
            
            // RECOMMANDATION - Est-ce que le client recommande le produit ?
            $table->boolean('would_recommend')->nullable(); // true/false/null si pas renseigné
            
            // VÉRIFICATION - Avis vérifié ou non
            $table->boolean('is_verified_purchase')->default(false); // Achat vérifié dans nos commandes
            $table->boolean('is_approved')->default(false); // Avis approuvé par un admin
            $table->timestamp('approved_at')->nullable(); // Date d'approbation
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // Admin qui a approuvé
            
            // MÉDIAS - Photos/vidéos jointes à l'avis
            $table->json('images')->nullable(); // URLs des images uploadées par le client
            $table->json('videos')->nullable(); // URLs des vidéos uploadées par le client
            
            // UTILITÉ DE L'AVIS - Votes des autres utilisateurs
            $table->integer('helpful_votes')->default(0); // Nombre de "Cet avis m'a été utile"
            $table->integer('unhelpful_votes')->default(0); // Nombre de "Cet avis ne m'a pas été utile"
            
            // RÉPONSE DU VENDEUR
            $table->text('vendor_response')->nullable(); // Réponse officielle du vendeur
            $table->timestamp('vendor_response_at')->nullable(); // Date de la réponse vendeur
            $table->foreignId('vendor_response_by')->nullable()->constrained('users')->onDelete('set null'); // Qui a répondu
            
            // MODÉRATION
            $table->boolean('is_flagged')->default(false); // Avis signalé comme inapproprié
            $table->integer('flag_count')->default(0); // Nombre de signalements
            $table->text('moderation_notes')->nullable(); // Notes internes de modération
            
            // MÉTADONNÉES
            $table->string('source')->default('website'); // Origine: website, email, mobile_app
            $table->ipAddress('ip_address')->nullable(); // IP pour détecter les faux avis
            $table->string('user_agent')->nullable(); // User agent du navigateur
            $table->json('metadata')->nullable(); // Métadonnées supplémentaires
            
            $table->timestamps();
            
            // CONTRAINTES - Un utilisateur ne peut donner qu'un seul avis par produit
            $table->unique(['product_id', 'user_id'], 'product_user_review_unique');
            
            // INDEX POUR PERFORMANCES
            $table->index(['product_id', 'is_approved', 'created_at']); // Avis approuvés d'un produit par date
            $table->index(['product_id', 'rating', 'is_approved']); // Avis par note
            $table->index(['user_id', 'created_at']); // Avis d'un utilisateur
            $table->index(['is_verified_purchase', 'is_approved']); // Avis vérifiés et approuvés
            $table->index(['is_flagged', 'flag_count']); // Avis à modérer
            $table->index('order_id'); // Avis liés à une commande
        });
            
        // CONTRAINTES DE VALIDATION
        DB::statement('ALTER TABLE product_reviews ADD CONSTRAINT rating_valid CHECK (rating >= 1 AND rating <= 5)');
        DB::statement('ALTER TABLE product_reviews ADD CONSTRAINT helpful_votes_positive CHECK (helpful_votes >= 0)');
        DB::statement('ALTER TABLE product_reviews ADD CONSTRAINT unhelpful_votes_positive CHECK (unhelpful_votes >= 0)');
    }

    public function down()
    {
        Schema::dropIfExists('product_reviews');
    }
};