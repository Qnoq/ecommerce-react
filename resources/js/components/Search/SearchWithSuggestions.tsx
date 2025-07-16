import React, { useState, useRef, useCallback } from 'react'
import { Search, X, Clock, ShoppingBag } from 'lucide-react'
import { cn } from '@/lib/utils'
import { router, usePage } from '@inertiajs/react'
import { useSearchContext } from '@/contexts/SearchContext'
import { formatPrice } from '@/utils/price'

// Types inchangés
interface Product {
  id: string
  uuid: string
  name: string
  price: number
  featured_image?: string
  images?: string[]
  rating?: number
  review_count?: number
  is_featured?: boolean
  badges?: string[]
}

interface SearchSuggestion {
  id: string
  type: 'product' | 'category' | 'recent' | 'trending'
  title: string
  subtitle?: string
  url: string
  image?: string
  price?: string
}

interface SearchWithSuggestionsProps {
  onSearch?: (query: string) => void
  placeholder?: string
  className?: string
  autoFocus?: boolean
}

export default function SearchWithSuggestions({
  onSearch,
  placeholder = "Rechercher des produits...",
  className = "",
  autoFocus = false
}: SearchWithSuggestionsProps) {
  // État local simplifié - plus de gestion des recherches récentes ici
  const [query, setQuery] = useState('')
  const [isOpen, setIsOpen] = useState(false)
  const [isSearching, setIsSearching] = useState(false)
  const [selectedIndex, setSelectedIndex] = useState(-1)
  
  // Utilisation du Context - c'est notre seule source de vérité
  const { recentSearches, addRecentSearch } = useSearchContext()

  const inputRef = useRef<HTMLInputElement>(null)
  const debounceRef = useRef<NodeJS.Timeout | null>(null)

  // Récupération des données Inertia (inchangé)
  const page = usePage()
  const searchResults = (page.props as any).searchResults || {}
  const products: Product[] = searchResults.products?.data || []
  const totalResults = searchResults.products?.total || 0
  const suggestions: SearchSuggestion[] = searchResults.suggestions || []

  // Fonction de recherche live simplifiée
  const performLiveSearch = useCallback((searchQuery: string) => {
    if (searchQuery.length < 2) {
      setIsSearching(false)
      return
    }

    setIsSearching(true)
    const searchUrl = `/s?k=${encodeURIComponent(searchQuery)}`
    
    router.visit(searchUrl, {
      method: 'get',
      preserveState: true,
      preserveScroll: true,
      only: ['searchResults'],
      onFinish: () => setIsSearching(false)
    })
  }, [])

  // Gestion de la saisie avec debounce
  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value
    setQuery(value)
    setSelectedIndex(-1)

    if (debounceRef.current) {
      clearTimeout(debounceRef.current)
    }

    setIsOpen(true)
    debounceRef.current = setTimeout(() => {
      performLiveSearch(value)
    }, 300)
  }

  // Fonction unifiée pour toute navigation vers la page de recherche
  const navigateToSearchPage = useCallback((searchQuery: string) => {
    const trimmedQuery = searchQuery.trim()
    
    if (trimmedQuery.length >= 2) {
      addRecentSearch(trimmedQuery)
      setIsOpen(false)
      
      // Navigation vers page de recherche
      
      // CORRECTION: Utiliser directement l'URL au lieu de route() qui peut causer des problèmes
      router.visit(`/s?k=${encodeURIComponent(trimmedQuery)}`, {
        method: 'get',
        preserveScroll: false,
        preserveState: false,
        replace: false
      })
    }
  }, [addRecentSearch])

  // Navigation vers un produit
  const navigateToProduct = useCallback((productUuid: string) => {
    // Sauvegarder la recherche actuelle si elle est valide
    if (query.trim().length >= 2) {
      addRecentSearch(query.trim())
    }
    
    setIsOpen(false)
    router.visit(`/products/${productUuid}`)
  }, [query, addRecentSearch])

  // Navigation vers une suggestion
  const navigateToSuggestion = useCallback((suggestion: SearchSuggestion) => {
    // Sauvegarder uniquement si ce n'est pas déjà une recherche récente
    if (suggestion.type !== 'recent') {
      addRecentSearch(suggestion.title)
    }
    
    setIsOpen(false)
    router.visit(suggestion.url)
  }, [addRecentSearch])

  // Gestion du clavier simplifiée
  const handleKeyDown = (e: React.KeyboardEvent) => {
    const allItems = [
      ...suggestions,
      ...products.map(p => ({ ...p, type: 'product' as const })),
      ...recentSearches.slice(0, 3).map((search, index) => ({
        id: `recent-${index}`,
        type: 'recent' as const,
        title: search,
        subtitle: 'Recherche récente',
        url: `/s?k=${encodeURIComponent(search)}`
      }))
    ]

    if (e.key === 'ArrowDown') {
      e.preventDefault()
      setSelectedIndex(prev => (prev + 1) % allItems.length)
    } else if (e.key === 'ArrowUp') {
      e.preventDefault()
      setSelectedIndex(prev => (prev - 1 + allItems.length) % allItems.length)
    } else if (e.key === 'Enter') {
      e.preventDefault()
      
      if (selectedIndex >= 0 && allItems[selectedIndex]) {
        const selected = allItems[selectedIndex]
        if ('uuid' in selected) {
          navigateToProduct(selected.uuid)
        } else {
          navigateToSuggestion(selected as SearchSuggestion)
        }
      } else {
        navigateToSearchPage(query)
      }
    } else if (e.key === 'Escape') {
      setIsOpen(false)
      inputRef.current?.blur()
    }
  }

  // Fermeture du dropdown
  const handleBlur = () => {
    setTimeout(() => setIsOpen(false), 200)
  }

  // Préparation des suggestions pour l'affichage
  const combinedSuggestions = query.length < 2 ? 
    recentSearches.slice(0, 3).map((search, index) => ({
      id: `recent-${index}`,
      type: 'recent' as const,
      title: search,
      subtitle: 'Recherche récente',
      url: `/s?k=${encodeURIComponent(search)}`
    })) : suggestions

  const hasResults = query.length >= 2 && (products.length > 0 || suggestions.length > 0)
  const showDropdown = isOpen && (hasResults || combinedSuggestions.length > 0 || isSearching)

  return (
    <div className={cn("relative", className)}>
      <div className="relative">
        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
        <input
          ref={inputRef}
          type="text"
          value={query}
          onChange={handleInputChange}
          onKeyDown={handleKeyDown}
          onFocus={() => setIsOpen(true)}
          onBlur={handleBlur}
          placeholder={placeholder}
          autoFocus={autoFocus}
          className="w-full pl-10 pr-10 py-2 border border-input rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
        />
        {query && (
          <button
            type="button"
            onClick={() => {
              setQuery('')
              setIsOpen(false)
              inputRef.current?.focus()
            }}
            className="absolute right-3 top-1/2 transform -translate-y-1/2 text-muted-foreground hover:text-foreground"
          >
            <X className="h-4 w-4" />
          </button>
        )}
      </div>

      {/* Dropdown simplifié */}
      {showDropdown && (
        <div className="absolute top-full left-0 right-0 mt-1 bg-background border border-border rounded-md shadow-lg z-50 max-h-96 overflow-y-auto">
          
          {/* État de chargement */}
          {isSearching && (
            <div className="p-4 text-center text-muted-foreground">
              <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-primary mx-auto mb-2"></div>
              Recherche en cours...
            </div>
          )}
          
          {/* Suggestions */}
          {!isSearching && query.length >= 2 && suggestions.length > 0 && (
            <div className="border-b border-border">
              <div className="px-3 py-2 text-xs font-medium text-muted-foreground uppercase tracking-wide">
                Suggestions
              </div>
              {suggestions.map((suggestion, index) => (
                <button
                  key={suggestion.id}
                  type="button"
                  onClick={() => navigateToSuggestion(suggestion)}
                  className={cn(
                    "w-full text-left px-4 py-3 hover:bg-muted/50 transition-colors border-b border-border last:border-b-0",
                    selectedIndex === index && "bg-muted/50"
                  )}
                >
                  <div className="flex items-center space-x-3">
                    <div className="w-8 h-8 bg-muted rounded flex items-center justify-center">
                      <Search className="h-4 w-4 text-muted-foreground" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="font-medium text-foreground truncate">
                        {suggestion.title}
                      </div>
                      {suggestion.subtitle && (
                        <div className="text-sm text-muted-foreground truncate">
                          {suggestion.subtitle}
                        </div>
                      )}
                    </div>
                  </div>
                </button>
              ))}
            </div>
          )}

          {/* Produits */}
          {!isSearching && query.length >= 2 && products.length > 0 && (
            <div className="border-b border-border">
              <div className="px-3 py-2 text-xs font-medium text-muted-foreground uppercase tracking-wide">
                Produits ({totalResults})
              </div>
              {products.slice(0, 5).map((product, index) => (
                <button
                  key={product.uuid}
                  type="button"
                  onClick={() => navigateToProduct(product.uuid)}
                  className={cn(
                    "w-full text-left px-4 py-3 hover:bg-muted/50 transition-colors border-b border-border last:border-b-0",
                    selectedIndex === (suggestions.length + index) && "bg-muted/50"
                  )}
                >
                  <div className="flex items-center space-x-3">
                    {product.featured_image ? (
                      <img
                        src={product.featured_image}
                        alt={product.name}
                        className="w-10 h-10 object-cover rounded"
                      />
                    ) : (
                      <div className="w-10 h-10 bg-muted rounded flex items-center justify-center">
                        <ShoppingBag className="h-4 w-4 text-muted-foreground" />
                      </div>
                    )}
                    
                    <div className="flex-1 min-w-0">
                      <div className="font-medium text-foreground truncate">
                        {product.name}
                      </div>
                      <div className="text-sm text-muted-foreground">
                        {formatPrice(product.price)}
                      </div>
                    </div>
                  </div>
                </button>
              ))}
              
              {/* Bouton "Voir tous les résultats" simplifié */}
              {totalResults > 0 && (
                <div className="border-t p-3">
                  <button
                    type="button"
                    className="w-full px-4 py-3 text-sm text-primary hover:bg-muted/50 transition-colors font-medium"
                    onClick={() => navigateToSearchPage(query)}
                  >
                    Voir tous les {totalResults} résultats
                  </button>
                </div>
              )}
            </div>
          )}

          {/* Recherches récentes */}
          {!isSearching && combinedSuggestions.length > 0 && query.length < 2 && (
            <div>
              <div className="px-3 py-2 text-xs font-medium text-muted-foreground uppercase tracking-wide">
                Recherches récentes
              </div>
              {combinedSuggestions.map((suggestion, index) => (
                <button
                  key={suggestion.id}
                  type="button"
                  onClick={() => navigateToSearchPage(suggestion.title)}
                  className={cn(
                    "w-full text-left px-4 py-3 hover:bg-muted/50 transition-colors border-b border-border last:border-b-0",
                    selectedIndex === index && "bg-muted/50"
                  )}
                >
                  <div className="flex items-center space-x-3">
                    <div className="w-8 h-8 bg-muted rounded flex items-center justify-center">
                      <Clock className="h-4 w-4 text-muted-foreground" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="font-medium text-foreground truncate">
                        {suggestion.title}
                      </div>
                      <div className="text-sm text-muted-foreground truncate">
                        {suggestion.subtitle}
                      </div>
                    </div>
                  </div>
                </button>
              ))}
            </div>
          )}

          {/* Aucun résultat */}
          {!isSearching && query.length >= 2 && products.length === 0 && suggestions.length === 0 && (
            <div className="p-4 text-center">
              <Search className="h-8 w-8 text-muted-foreground mx-auto mb-2" />
              <p className="text-muted-foreground">Aucun résultat pour "{query}"</p>
              <button 
                type="button"
                onClick={() => navigateToSearchPage(query)}
                className="mt-2 text-sm text-primary hover:underline"
              >
                Rechercher dans tout le catalogue
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  )
}