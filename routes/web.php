<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProductController;

// Changement de locale (en dehors du groupe middleware)
Route::post('/locale', [LocaleController::class, 'change'])->name('locale.change');

// Routes principales avec middleware locale
Route::middleware(['set.locale'])->group(function () {
    
    // Page d'accueil
    Route::get('/', function () {
        return Inertia::render('welcome', [
            'user' => Auth::user(),
            'cartCount' => 3,
            'breadcrumbs' => [
                ['title' => 'Accueil', 'href' => '/'],
            ],
        ]);
    })->name('home');

    // Routes e-commerce produits - NETTOYÉES
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

    // Routes de recherche - UNIFIÉES STYLE AMAZON
    Route::get('/s', [ProductController::class, 'searchPage'])->name('search.page');
    Route::get('/products/suggestions', [ProductController::class, 'suggestions'])->name('products.suggestions');

    // Analytics de recherche - SIMPLIFIÉES
    Route::get('/api/popular-searches', [ProductController::class, 'popularSearches'])->name('api.popular.searches');
    
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