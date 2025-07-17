<?php

namespace App\Http\Middleware;

use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Session;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        // Récupérer le compteur panier pour toutes les pages
        $cartCount = 0;
        try {
            $cartService = app(\App\Services\CartService::class);
            $cartCount = $cartService->getCartCount();
        } catch (\Exception $e) {
            // En cas d'erreur, garder 0
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
            ],
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            
            'locale' => fn() => App::getLocale(),
            'available_locales' => available_locales(),
            'translations' => $this->getTranslations(),
            'cartCount' => $cartCount,
        ];
    }

    protected function getTranslations(): array
    {
        $locale = Session::get('locale', config('app.locale'));
        
        return cache()->tags('translations')->rememberForever("translations_{$locale}", function() use ($locale) {
            return $this->loadTranslationFiles($locale);
        });
    }

    private function loadTranslationFiles($locale): array
    {
        $translations = [];
        
        // 1. Charger le fichier principal lang/fr.json (sans namespace)
        $mainFile = lang_path("{$locale}.json");
        if (file_exists($mainFile)) {
            $mainTranslations = json_decode(file_get_contents($mainFile), true) ?? [];
            $translations = array_merge($translations, $this->flattenTranslations($mainTranslations));
        }
        
        // 2. Charger les fichiers organisés lang/fr/*.json (avec namespace)
        $langPath = lang_path($locale);
        if (is_dir($langPath)) {
            $files = glob($langPath . '/*.json');
            foreach ($files as $file) {
                $namespace = basename($file, '.json');
                $fileTranslations = json_decode(file_get_contents($file), true) ?? [];
                $flattenedTranslations = $this->flattenTranslations($fileTranslations, $namespace);
                $translations = array_merge($translations, $flattenedTranslations);
            }
        }
        
        return $translations;
    }

    /**
     * Aplatir récursivement les traductions imbriquées
     */
    private function flattenTranslations(array $translations, string $prefix = ''): array
    {
        $flattened = [];
        
        foreach ($translations as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;
            
            if (is_array($value)) {
                // Récursion pour les objets imbriqués
                $flattened = array_merge($flattened, $this->flattenTranslations($value, $newKey));
            } else {
                // Valeur simple (string, number, etc.)
                $flattened[$newKey] = $value;
            }
        }
        
        return $flattened;
    }
}