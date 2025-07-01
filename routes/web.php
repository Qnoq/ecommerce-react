<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\HomeController;

Route::post('/locale', [LocaleController::class, 'change'])->name('locale.change');

// Routes principales (avec middleware locale)
Route::middleware(['set.locale'])->group(function () {
    
    Route::get('/', function () {
        return Inertia::render('welcome', [
            'user' => Auth::user(),
            'cartCount' => 3,
            'breadcrumbs' => [
                ['title' => 'Accueil', 'href' => '/'],
            ],
        ]);
    })->name('home');

    // Routes e-commerce produits
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/search', [ProductController::class, 'search'])->name('products.search');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

    // Routes recherche avancée
    Route::get('/search/live', [ProductController::class, 'liveSearch'])->name('search.live');
    Route::get('/api/search', [ProductController::class, 'apiSearch'])->name('api.search');
    Route::get('/api/suggestions', [ProductController::class, 'suggestions'])->name('api.suggestions');

    // Route de test pour la recherche (développement seulement)
    Route::get('/test-search', [ProductController::class, 'testSearch'])->name('test.search');
    Route::get('/test-simple-search', [ProductController::class, 'testSimpleSearch'])->name('test.simple.search');
    Route::get('/debug-products', [ProductController::class, 'debugProducts'])->name('debug.products');

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
