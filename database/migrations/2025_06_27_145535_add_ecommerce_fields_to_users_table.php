<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ajouter les champs e-commerce à la table users existante
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // UUID PUBLIC - D'ABORD nullable
            $table->uuid('uuid')->nullable()->after('id');
            
            // INFORMATIONS PERSONNELLES ÉTENDUES
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone')->nullable()->after('email');
            $table->date('date_of_birth')->nullable()->after('phone');
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable()->after('date_of_birth');
            
            // AVATAR ET PROFIL
            $table->string('avatar')->nullable()->after('gender');
            $table->text('bio')->nullable()->after('avatar');
            
            // ADRESSES
            $table->json('addresses')->nullable()->after('bio');
            
            // PRÉFÉRENCES E-COMMERCE
            $table->string('preferred_language', 2)->default('fr')->after('addresses');
            $table->string('preferred_currency', 3)->default('EUR')->after('preferred_language');
            $table->boolean('accepts_marketing')->default(true)->after('preferred_currency');
            $table->boolean('accepts_sms')->default(false)->after('accepts_marketing');
            
            // RÔLES ET PERMISSIONS
            $table->json('roles')->nullable()->after('accepts_sms');
            $table->boolean('is_admin')->default(false)->after('roles');
            $table->boolean('is_vendor')->default(false)->after('is_admin');
            
            // STATUT DU COMPTE
            $table->boolean('is_active')->default(true)->after('is_vendor');
            $table->boolean('is_verified')->default(false)->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('is_verified');
            $table->ipAddress('last_login_ip')->nullable()->after('last_login_at');
            
            // ANALYTICS CLIENT
            $table->integer('total_orders')->default(0)->after('last_login_ip');
            $table->decimal('total_spent', 10, 2)->default(0)->after('total_orders');
            $table->decimal('average_order_value', 10, 2)->default(0)->after('total_spent');
            $table->timestamp('first_order_at')->nullable()->after('average_order_value');
            $table->timestamp('last_order_at')->nullable()->after('first_order_at');
            
            // PROGRAMME DE FIDÉLITÉ
            $table->integer('loyalty_points')->default(0)->after('last_order_at');
            $table->string('customer_tier')->default('bronze')->after('loyalty_points');
            
            // NOTIFICATIONS ET COMMUNICATIONS
            $table->json('notification_preferences')->nullable()->after('customer_tier');
            $table->string('referral_code')->nullable()->after('notification_preferences');
            $table->foreignId('referred_by')->nullable()->constrained('users')->onDelete('set null')->after('referral_code');
            
            // MÉTADONNÉES
            $table->json('metadata')->nullable()->after('referred_by');
            $table->text('admin_notes')->nullable()->after('metadata');
        });
        
        // GÉNÉRER LES UUID pour tous les users existants
        DB::statement("UPDATE users SET uuid = gen_random_uuid() WHERE uuid IS NULL");
        
        // GÉNÉRER LES CODES DE PARRAINAGE uniques
        $users = DB::table('users')->whereNull('referral_code')->get(['id']);
        foreach ($users as $user) {
            do {
                $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
            } while (DB::table('users')->where('referral_code', $code)->exists());
            
            DB::table('users')->where('id', $user->id)->update(['referral_code' => $code]);
        }
        
        // RENDRE LES COLONNES NOT NULL et UNIQUE
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->unique()->change();
            $table->string('referral_code')->unique()->change();
        });
        
        // INDEX POUR LES PERFORMANCES (sans les problématiques JSON GIN)
        DB::statement('CREATE INDEX users_customer_analytics_idx ON users (total_orders, total_spent)');
        DB::statement('CREATE INDEX users_active_customers_idx ON users (is_active, last_login_at) WHERE is_active = true');
        DB::statement('CREATE INDEX users_referral_code_idx ON users (referral_code) WHERE referral_code IS NOT NULL');
        
        // Pour les rôles JSON, on utilise une approche différente si on en a besoin plus tard
        // DB::statement('CREATE INDEX users_roles_idx ON users USING GIN (roles jsonb_path_ops) WHERE roles IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Supprimer les contraintes de clé étrangère d'abord
            $table->dropForeign(['referred_by']);
            
            // Supprimer tous les champs ajoutés
            $table->dropColumn([
                'admin_notes',
                'metadata',
                'referred_by',
                'referral_code',
                'notification_preferences',
                'customer_tier',
                'loyalty_points',
                'last_order_at',
                'first_order_at',
                'average_order_value',
                'total_spent',
                'total_orders',
                'last_login_ip',
                'last_login_at',
                'is_verified',
                'is_active',
                'is_vendor',
                'is_admin',
                'roles',
                'accepts_sms',
                'accepts_marketing',
                'preferred_currency',
                'preferred_language',
                'addresses',
                'bio',
                'avatar',
                'gender',
                'date_of_birth',
                'phone',
                'last_name',
                'first_name',
                'uuid',
            ]);
        });
    }
};