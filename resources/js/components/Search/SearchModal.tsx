import React from 'react'
import { Search, X, SlidersHorizontal } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { useSearchContext } from '@/contexts/SearchContext'
import { useSearchModal } from '@/hooks/useSearchModal'
import { PROMOTIONAL_CARDS } from '@/data/promotionalCards'
import ProductCardCompact from '@/components/Product/ProductCardCompact'
import type { Product, SearchSuggestion } from '@/types/index.d.ts'

export type { SearchSuggestion } from '@/types/index.d.ts'

interface SearchModalProps {
  isOpen: boolean
  onClose: () => void
  placeholder?: string
}

export default function SearchModal({
  isOpen,
  onClose,
  placeholder = "Recherchez une couleur"
}: SearchModalProps) {
  const { recentSearches } = useSearchContext()
  const {
    query,
    isSearching,
    inputRef,
    handleInputChange,
    handleKeyDown,
    clearQuery,
    navigateToSearchPage,
    navigateToProduct,
    navigateToSuggestion,
    navigateToUrl,
    showResults,
    performSearch
  } = useSearchModal({ isOpen, onClose })

  // État pour les données de recherche
  const [suggestions, setSuggestions] = React.useState<SearchSuggestion[]>([])
  const [products, setProducts] = React.useState<Product[]>([])
  const [totalResults, setTotalResults] = React.useState(0)

  // Reset des données de recherche quand on ferme
  React.useEffect(() => {
    if (!isOpen) {
      setSuggestions([])
      setProducts([])
      setTotalResults(0)
    }
  }, [isOpen])

  // Fonction pour récupérer les suggestions et produits
  const fetchSearchResults = React.useCallback(async (searchQuery: string) => {
    if (searchQuery.length < 2) {
      setSuggestions([])
      setProducts([])
      setTotalResults(0)
      return
    }

    try {
      // Suggestions rapides
      const suggestionsResponse = await fetch(`/products/suggestions?q=${encodeURIComponent(searchQuery)}`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
      })
      
      if (suggestionsResponse.ok) {
        const suggestionResult = await suggestionsResponse.json()
        setSuggestions(suggestionResult.suggestions || [])
      }

      // Produits complets pour la liste via API dédiée
      const productsResponse = await fetch(`/api/products/search?search=${encodeURIComponent(searchQuery)}&limit=20`, {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        }
      })
      
      if (productsResponse.ok) {
        const productResult = await productsResponse.json()
        
        // Vérifier la structure des données
        if (productResult.products && productResult.products.data) {
          setProducts(productResult.products.data)
          setTotalResults(productResult.products.total || 0)
        } else {
          setProducts([])
          setTotalResults(0)
        }
      }
    } catch (error) {
      setSuggestions([])
      setProducts([])
    }
  }, [])

  // Debounce pour la recherche avec le hook personnalisé
  const handleSearchInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    handleInputChange(e)
    
    const value = e.target.value
    if (value.length >= 2) {
      setTimeout(() => fetchSearchResults(value), 300)
    }
  }

  // Gestion du clic sur un produit
  const handleProductClick = (uuid: string, name: string) => {
    navigateToProduct({ uuid, name } as Product)
  }

  // Gestion de l'ajout au panier
  const handleAddToCart = (product: Product) => {
    // Logique d'ajout au panier à implémenter
    console.log('Ajout au panier:', product.name)
  }

  // Utilisation des cards promotionnelles externalisées

  return (
    <Sheet open={isOpen} onOpenChange={onClose}>
      <SheetContent side="top" className="h-full w-full p-0 max-w-none">
        <SheetTitle className="sr-only">Recherche</SheetTitle>
        {/* Header fixe */}
        <SheetHeader className="border-b bg-background p-4 space-y-4">
          <div className="flex items-center justify-between">
            <h2 className="text-lg font-semibold">Recherche</h2>
            <Button variant="ghost" size="icon" onClick={onClose}>
              <X className="h-5 w-5" />
            </Button>
          </div>

          {/* Champ de recherche */}
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
            <input
              ref={inputRef}
              type="text"
              value={query}
              onChange={handleSearchInputChange}
              onKeyDown={handleKeyDown}
              placeholder={placeholder}
              className="w-full pl-10 pr-10 py-3 text-base border border-input rounded-lg bg-muted/50 focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
            />
            {query && (
              <button
                type="button"
                onClick={clearQuery}
                className="absolute right-3 top-1/2 transform -translate-y-1/2 text-muted-foreground hover:text-foreground"
              >
                <X className="h-4 w-4" />
              </button>
            )}
          </div>

          {/* Barre de résultats si recherche active */}
          {showResults && (
            <div className="flex items-center justify-between">
              <span className="text-sm text-muted-foreground">
                {isSearching ? 'Recherche...' : `${totalResults} articles`}
              </span>
              <Button variant="outline" size="sm">
                <SlidersHorizontal className="h-4 w-4 mr-2" />
                Trier & filtrer
              </Button>
            </div>
          )}
        </SheetHeader>

        {/* Contenu scrollable */}
        <div className="flex-1 overflow-y-auto bg-background">
          {!showResults ? (
            /* État initial : Cards promotionnelles */
            <div className="p-4 space-y-4">
              {/* Recherches récentes si disponibles */}
              {recentSearches.length > 0 && (
                <div className="space-y-2">
                  <h3 className="font-medium text-sm text-muted-foreground uppercase tracking-wide">
                    Recherches récentes
                  </h3>
                  <div className="flex flex-wrap gap-2">
                    {recentSearches.slice(0, 3).map((search, index) => (
                      <Button
                        key={index}
                        variant="outline"
                        size="sm"
                        onClick={() => performSearch(search)}
                        className="text-sm"
                      >
                        {search}
                      </Button>
                    ))}
                  </div>
                </div>
              )}

              {/* Cards promotionnelles */}
              <div className="space-y-4">
                {PROMOTIONAL_CARDS.map((card) => (
                  <div
                    key={card.id}
                    onClick={() => navigateToUrl(card.url)}
                    className={cn(
                      "relative overflow-hidden rounded-lg p-6 cursor-pointer transition-transform hover:scale-[1.02]",
                      card.className
                    )}
                  >
                    {card.image && (
                      <img
                        src={card.image}
                        alt={card.title}
                        className="absolute inset-0 w-full h-full object-cover"
                      />
                    )}
                    <div className="relative z-10">
                      {card.icon && (
                        <div className="text-2xl mb-2">{card.icon}</div>
                      )}
                      <h3 className="text-xl font-bold mb-1">{card.title}</h3>
                      <p className="text-sm opacity-90">{card.subtitle}</p>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ) : (
            /* État recherche : Suggestions + Produits */
            <div className="space-y-4">
              {/* Suggestions de recherche */}
              {suggestions.length > 0 && (
                <div className="px-4 py-2 border-b">
                  <div className="flex flex-wrap gap-2">
                    <span className="text-sm text-muted-foreground">Suggestions :</span>
                    {suggestions.slice(0, 3).map((suggestion: SearchSuggestion) => (
                      <Button
                        key={suggestion.id}
                        variant="outline"
                        size="sm"
                        onClick={() => navigateToSuggestion(suggestion)}
                        className="text-sm"
                      >
                        {suggestion.title}
                      </Button>
                    ))}
                  </div>
                </div>
              )}

              {/* Grille de produits */}
              {isSearching ? (
                <div className="text-center py-8">
                  <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-2"></div>
                  <p className="text-muted-foreground">Recherche en cours...</p>
                </div>
              ) : products.length > 0 ? (
                <div className="grid grid-cols-2 gap-4 p-4">
                  {products.map((product) => (
                    <ProductCardCompact
                      key={product.uuid}
                      product={product}
                      onNavigate={handleProductClick}
                      onAddToCart={handleAddToCart}
                    />
                  ))}
                </div>
              ) : query.length >= 2 ? (
                <div className="text-center py-8">
                  <Search className="h-12 w-12 text-muted-foreground mx-auto mb-2" />
                  <p className="text-muted-foreground">Aucun produit trouvé pour "{query}"</p>
                </div>
              ) : null}
            </div>
          )}
        </div>
      </SheetContent>
    </Sheet>
  )
} 