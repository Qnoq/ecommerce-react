# 🔍 Composants de Recherche - Approche Inertia.js

Ce dossier contient des composants de recherche optimisés pour Laravel + Inertia.js + React suivant les **bonnes pratiques Inertia**.

## 📁 Structure

```
Search/
├── SearchWithSuggestions.tsx    # Recherche avec autocomplétion temps réel
├── SimpleSearch.tsx             # Recherche basique sans suggestions  
├── TestSearch.tsx               # Page de test et documentation
├── index.ts                     # Exports centralisés
└── README.md                    # Documentation (ce fichier)
```

## 🎯 Approche Architecturale

### **Principe Inertia.js**
- **Recherche principale** : `useForm()` d'Inertia pour navigation avec état
- **Suggestions temps réel** : `fetch()` API pour autocomplétion rapide
- **Pas d'API REST** : Communication directe Laravel ↔ React via Inertia

### **Hook useSearch**
Le hook `useSearch()` encapsule toute la logique de recherche Inertia :

```typescript
const {
  filters,           // État des filtres (search, category, price, etc.)
  handleFilterChange, // Fonction pour modifier un filtre avec debounce
  handleSearch,      // Recherche manuelle
  clearSearch,       // Reset complet
  setSortBy,         // Tri rapide
  processing         // État de chargement Inertia
} = useSearch({
  initialFilters: filters,
  preserveState: true,
  preserveScroll: true
})
```

## 🔧 Configuration Controller Laravel

### ProductController avec Inertia

```php
class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Construction requête avec filtres
        $query = Product::with(['categories', 'reviews'])
            ->where('is_active', true);

        // Filtres dynamiques
        if ($request->filled('search')) {
            $query->where('name', 'ilike', "%{$request->search}%");
        }
        
        if ($request->filled('category')) {
            $query->whereHas('categories', fn($q) => 
                $q->where('slug', $request->category)
            );
        }

        // Pagination avec query string
        $products = $query->paginate(12)->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => Category::active()->get(),
            'filters' => $request->only(['search', 'category', 'sort'])
        ]);
    }

    // API JSON pour suggestions temps réel
    public function suggestions(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $suggestions = Product::where('name', 'ilike', "%{$query}%")
            ->limit(8)
            ->get()
            ->map(fn($product) => [
                'id' => $product->uuid,
                'type' => 'product',
                'title' => $product->name,
                'subtitle' => '€' . number_format($product->price, 2),
                'url' => route('products.show', $product->uuid),
                'image' => $product->image
            ]);

        return response()->json(['suggestions' => $suggestions]);
    }
}
```

## 🎮 Utilisation des Composants

### 1. SearchWithSuggestions (Recommandé)

**Recherche complète avec suggestions en temps réel**

```tsx
import { SearchWithSuggestions } from '@/components/Search'

// Dans votre layout ou page
<SearchWithSuggestions 
  onSearch={(query) => router.get('/products', { search: query })}
  placeholder="Rechercher des produits..."
  autoFocus={false}
/>
```

**Fonctionnalités :**
- ✅ Suggestions produits + catégories depuis BD
- ✅ Historique recherches récentes (localStorage)  
- ✅ Navigation clavier (flèches, Enter, Escape)
- ✅ Debounce 300ms pour suggestions
- ✅ Images et prix dans suggestions
- ✅ Types TypeScript complets

### 2. Hook useSearch pour Pages

**Page complète avec filtres et pagination**

```tsx
import { useSearch } from '@/hooks/useSearch'
import { usePage } from '@inertiajs/react'

export default function ProductsIndex() {
  const { products, categories, filters } = usePage().props

  const {
    filters: searchFilters,
    handleFilterChange,
    clearSearch,
    setSortBy,
    processing
  } = useSearch({
    initialFilters: filters,
    preserveState: true,
    preserveScroll: true
  })

  return (
    <div>
      {/* Recherche avec debounce automatique */}
      <input
        value={searchFilters.search || ''}
        onChange={(e) => handleFilterChange('search', e.target.value)}
        placeholder="Rechercher..."
      />

      {/* Filtre catégorie */}
      <select
        value={searchFilters.category || ''}
        onChange={(e) => handleFilterChange('category', e.target.value)}
      >
        <option value="">Toutes catégories</option>
        {categories.map(cat => (
          <option key={cat.id} value={cat.slug}>{cat.name}</option>
        ))}
      </select>

      {/* Tri rapide */}
      <button onClick={() => setSortBy('price', 'asc')}>
        Prix croissant
      </button>

      {/* Reset */}
      <button onClick={clearSearch}>Effacer filtres</button>

      {/* Produits avec pagination Inertia */}
      <div className="grid">
        {products.data.map(product => (
          <ProductCard key={product.id} product={product} />
        ))}
      </div>

      {/* Pagination automatique */}
      <Pagination links={products.links} />
    </div>
  )
}
```

### 3. SimpleSearch (Léger)

**Version basique sans suggestions**

```tsx
import { SimpleSearch } from '@/components/Search'

<SimpleSearch 
  onSearch={(query) => router.get('/products', { search: query })}
  placeholder="Recherche simple..."
  showClearButton={true}
  showSubmitButton={true}
/>
```

## 🌟 Avantages de cette Approche

### **Performance**
- **Debounce intelligent** : Évite les requêtes inutiles
- **Pagination Inertia** : Navigation sans reload complet
- **PreserveState** : Maintient l'état pendant navigation
- **Only props** : Ne recharge que les données nécessaires

### **UX Optimale**
- **Recherche temps réel** pour suggestions
- **Navigation clavier** complète  
- **Historique persistant** (localStorage)
- **Loading states** intégrés
- **URL synchronisée** avec filtres

### **Developer Experience**
- **Types TypeScript** complets
- **Hook réutilisable** pour toutes les pages
- **Logique centralisée** dans useSearch
- **Composants modulaires** et extensibles

## 🎛️ Configuration Avancée

### Routes Laravel

```php
// routes/web.php
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/suggestions', [ProductController::class, 'suggestions'])->name('products.suggestions');
Route::get('/products/{product:uuid}', [ProductController::class, 'show'])->name('products.show');
```

### Types TypeScript

```typescript
// types/index.d.ts
interface SearchSuggestion {
  id: string
  type: 'product' | 'category' | 'recent' | 'trending'
  title: string
  subtitle?: string
  url: string
  image?: string
  price?: string
}

interface SearchFilters {
  search?: string
  category?: string
  price_min?: number
  price_max?: number
  sort?: string
  order?: 'asc' | 'desc'
  [key: string]: any
}
```

### Traductions

```json
// lang/fr.json
{
  "search": "Rechercher",
  "search_products": "Rechercher des produits...",
  "suggestions": "Suggestions",
  "recent_searches": "Recherches récentes",
  "no_results_found": "Aucun résultat trouvé",
  "product": "Produit",
  "products": "Produits"
}
```

## 🚀 Optimisations Futures

### Recherche Vectorielle (Préparé)
Le système est prêt pour intégrer la recherche sémantique avec pgvector :

```php
// Dans ProductController::suggestions()
$embedding = OpenAI::embeddings()->create([
    'model' => 'text-embedding-3-small',
    'input' => $query
])->embeddings[0]->embedding;

$products = Product::selectRaw('*, (1 - (description_embedding <=> ?::vector)) as similarity', [$embedding])
    ->where('description_embedding IS NOT NULL')
    ->orderBy('similarity', 'desc')
    ->limit(8)
    ->get();
```

### Cache Intelligent
```php
// Cache des suggestions populaires
Cache::remember("search_suggestions_{$query}", 300, function() use ($query) {
    return Product::search($query)->get();
});
```

### Analytics Recherche
```php
// Tracker les recherches populaires
SearchAnalytic::create([
    'query' => $query,
    'results_count' => $products->count(),
    'user_id' => auth()->id(),
    'ip' => request()->ip()
]);
```

## 📝 Notes de Migration

Si vous migrez depuis une version avec hooks personnalisés :

1. **Remplacer useSearch custom** par le hook Inertia
2. **Supprimer fetch() manual** dans les composants de recherche  
3. **Utiliser usePage().props** pour les données
4. **Adapter les controllers** pour retourner `Inertia::render()`

## 🎯 Bonnes Pratiques

1. **Toujours utiliser useForm()** pour les formulaires de recherche
2. **Garder fetch() uniquement** pour les suggestions temps réel
3. **Utiliser preserveState: true** pour maintenir l'état
4. **Debounce les requêtes** pour éviter le spam
5. **Types TypeScript stricts** pour tous les props

---

**Cette approche suit les recommandations officielles Inertia.js et garantit une expérience utilisateur fluide avec de meilleures performances.** 🚀 