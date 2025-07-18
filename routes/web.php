<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;

// Changement de locale (en dehors du groupe middleware)
Route::post('/locale', [LocaleController::class, 'change'])->name('locale.change');

// Routes principales avec middleware locale
Route::middleware(['set.locale'])->group(function () {
    
    // Page d'accueil
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // Routes e-commerce produits - NETTOYÉES
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/suggestions', [ProductController::class, 'suggestions'])->name('products.suggestions');
    
    // Routes de recherche - UNIFIÉES STYLE AMAZON (AVANT les routes produits)
    Route::get('/s', [ProductController::class, 'searchPage'])->name('search.page');
    Route::get('/search/live', [ProductController::class, 'searchLive'])->name('search.live');
    
    // Routes produits style Amazon (sans /products/ - plus clean)
    Route::get('/{slug}/{uuid}', [ProductController::class, 'show'])->name('products.show');
    
    // Routes de fallback pour compatibilité avec les anciens liens
    Route::get('/products/{slug}/{uuid}', [ProductController::class, 'showByUuid'])->name('products.show.legacy');
    Route::get('/products/{uuid}', [ProductController::class, 'showByUuid'])->name('products.show.uuid');

    // Analytics de recherche - SIMPLIFIÉES
    Route::get('/api/popular-searches', [ProductController::class, 'popularSearches'])->name('api.popular.searches');
    
    // Routes panier
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/{itemKey}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{itemKey}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
    Route::delete('/cart/remove/last', [CartController::class, 'removeLast'])->name('cart.remove.last');
    Route::get('/api/cart/count', [CartController::class, 'count'])->name('cart.count');
    
    // Routes admin pour gestion cache (seulement si vraiment nécessaire)
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::delete('/admin/cache/search', [ProductController::class, 'clearSearchCache'])->name('admin.cache.search.clear');
    });

    // Route catégorie
    Route::get('/categories/{slug}', function ($slug) {
        return Inertia::render('categories/show', compact('slug'));
    })->name('categories.show');

    // Routes authentifiées
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('dashboard', function () {
            return Inertia::render('dashboard');
        })->name('dashboard');
        
        Route::get('/profile', function () {
            return Inertia::render('profile');
        })->name('profile');
        
        Route::get('/orders', function () {
            return Inertia::render('orders/index');
        })->name('orders.index');
        
        Route::get('/wishlist', function () {
            return Inertia::render('wishlist');
        })->name('wishlist');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';