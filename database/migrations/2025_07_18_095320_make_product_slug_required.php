<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Générer des slugs pour les produits qui n'en ont pas
        $products = DB::table('products')->whereNull('slug')->get();
        
        foreach ($products as $product) {
            $baseSlug = Str::slug($product->name);
            $slug = $baseSlug;
            $counter = 1;
            
            // Vérifier l'unicité du slug
            while (DB::table('products')->where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            
            DB::table('products')
                ->where('id', $product->id)
                ->update(['slug' => $slug]);
        }
        
        // Rendre le slug obligatoire (la contrainte unique existe déjà)
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->nullable()->change();
        });
    }
};
