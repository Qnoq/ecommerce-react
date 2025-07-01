import React, { useState, useRef, useEffect, useCallback } from 'react'
import { Search, X, SlidersHorizontal, Star, ShoppingBag } from 'lucide-react'
import { cn } from '@/lib/utils'
import { router } from '@inertiajs/react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Sheet, SheetContent, SheetHeader } from '@/components/ui/sheet'

export interface SearchSuggestion {
  id: string
  type: 'product' | 'category' | 'recent' | 'trending'
  title: string
  subtitle?: string
  url: string
  image?: string
  price?: string
}

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
  const [query, setQuery] = useState('')
  const [suggestions, setSuggestions] = useState<SearchSuggestion[]>([])
  const [products, setProducts] = useState<Product[]>([])
  const [totalResults, setTotalResults] = useState(0)
  const [isLoading, setIsLoading] = useState(false)
  const [recentSearches, setRecentSearches] = useState<string[]>([])

  const inputRef = useRef<HTMLInputElement>(null)
  const debounceRef = useRef<NodeJS.Timeout | null>(null)

  // Charger les recherches récentes
  useEffect(() => {
    const stored = localStorage.getItem('shoplux_recent_searches')
    if (stored) {
      try {
        setRecentSearches(JSON.parse(stored))
      } catch (e) {
        console.error('Erreur lors du parsing des recherches récentes:', e)
      }
    }
  }, [])

  // Focus automatique quand on ouvre
  useEffect(() => {
    if (isOpen && inputRef.current) {
      setTimeout(() => inputRef.current?.focus(), 100)
    }
  }, [isOpen])

  // Reset quand on ferme
  useEffect(() => {
    if (!isOpen) {
      setQuery('')
      setSuggestions([])
      setProducts([])
      setTotalResults(0)
    }
  }, [isOpen])

  // Fonction pour récupérer les suggestions et produits
  const fetchSearchResults = useCallback(async (searchQuery: string) => {
    if (searchQuery.length < 2) {
      setSuggestions([])
      setProducts([])
      setTotalResults(0)
      setIsLoading(false)
      return
    }

    setIsLoading(true)

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
    } finally {
      setIsLoading(false)
    }
  }, [])

  // Debounce pour la recherche
  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value
    setQuery(value)

    if (debounceRef.current) {
      clearTimeout(debounceRef.current)
    }

    debounceRef.current = setTimeout(() => {
      fetchSearchResults(value)
    }, 300)
  }

  // Effectuer une recherche complète
  const performSearch = useCallback((searchQuery: string) => {
    if (!searchQuery.trim()) return

    const trimmedQuery = searchQuery.trim()
    
    // Sauvegarder dans l'historique
    const newRecent = [
      trimmedQuery,
      ...recentSearches.filter(item => item !== trimmedQuery)
    ].slice(0, 5)
    
    setRecentSearches(newRecent)
    localStorage.setItem('shoplux_recent_searches', JSON.stringify(newRecent))

    // Fermer le modal et naviguer
    onClose()
    router.get('/products', { search: trimmedQuery })
  }, [onClose, recentSearches])

  // Navigation vers une suggestion
  const navigateToSuggestion = useCallback((suggestion: SearchSuggestion) => {
    if (suggestion.type !== 'recent') {
      const newRecent = [
        suggestion.title,
        ...recentSearches.filter(item => item !== suggestion.title)
      ].slice(0, 5)
      
      setRecentSearches(newRecent)
      localStorage.setItem('shoplux_recent_searches', JSON.stringify(newRecent))
    }
    
    onClose()
    router.visit(suggestion.url)
  }, [onClose, recentSearches])

  // Cards promotionnelles (état initial)
  const promotionalCards = [
    {
      id: 'nouveautes',
      title: 'Découvrez nos actualités',
      subtitle: 'Nouveautés',
      image: 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=600&h=300&fit=crop',
      url: '/products?category=nouveautes',
      className: 'bg-gradient-to-r from-blue-500 to-purple-600 text-white'
    },
    {
      id: 'livraison',
      title: 'LIVRAISON GRATUITE',
      subtitle: 'À partir de 50€',
      icon: '🚚',
      url: '/livraison',
      className: 'bg-gradient-to-r from-green-500 to-blue-500 text-white'
    },
    {
      id: 'seconde-main',
      title: 'La SECONDE MAIN des familles',
      subtitle: '100% qualité, 100% style, 100% petits prix.',
      icon: '♻️',
      url: '/seconde-main',
      className: 'bg-gradient-to-r from-purple-500 to-pink-500 text-white'
    }
  ]

  // Si on a une query, on affiche les résultats
  const showResults = query.length >= 2

  return (
    <Sheet open={isOpen} onOpenChange={onClose}>
      <SheetContent side="top" className="h-full w-full p-0 max-w-none">
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
              onChange={handleInputChange}
              onKeyDown={(e) => e.key === 'Enter' && performSearch(query)}
              placeholder={placeholder}
              className="w-full pl-10 pr-10 py-3 text-base border border-input rounded-lg bg-muted/50 focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
            />
            {query && (
              <button
                type="button"
                onClick={() => setQuery('')}
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
                {isLoading ? 'Recherche...' : `${totalResults} articles`}
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
                {promotionalCards.map((card) => (
                  <div
                    key={card.id}
                    onClick={() => router.visit(card.url)}
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
                    {suggestions.slice(0, 3).map((suggestion) => (
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
              {isLoading ? (
                <div className="text-center py-8">
                  <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-2"></div>
                  <p className="text-muted-foreground">Recherche en cours...</p>
                </div>
              ) : products.length > 0 ? (
                <div className="grid grid-cols-2 gap-4 p-4">
                  {products.map((product) => (
                    <div
                      key={product.uuid}
                      className="bg-card border border-border rounded-lg overflow-hidden hover:shadow-md transition-shadow cursor-pointer"
                      onClick={() => router.visit(`/products/${product.uuid}`)}
                    >
                      {/* Image */}
                      <div className="relative aspect-square">
                        {product.featured_image ? (
                          <img
                            src={product.featured_image}
                            alt={product.name}
                            className="w-full h-full object-cover"
                          />
                        ) : (
                          <div className="w-full h-full bg-muted flex items-center justify-center">
                            <ShoppingBag className="h-8 w-8 text-muted-foreground" />
                          </div>
                        )}
                        
                        {/* Badges */}
                        <div className="absolute top-2 left-2 space-y-1">
                          {product.badges?.map((badge, index) => (
                            <Badge key={index} variant="secondary" className="text-xs">
                              {badge}
                            </Badge>
                          ))}
                        </div>
                      </div>

                      {/* Contenu */}
                      <div className="p-3 space-y-2">
                        {/* Rating */}
                        {product.rating && (
                          <div className="flex items-center gap-1">
                            <div className="flex">
                              {[...Array(5)].map((_, i) => (
                                <Star
                                  key={i}
                                  className={cn(
                                    "h-3 w-3",
                                    i < Math.floor(product.rating!) 
                                      ? "fill-yellow-400 text-yellow-400" 
                                      : "text-gray-300"
                                  )}
                                />
                              ))}
                            </div>
                            <span className="text-xs text-muted-foreground">
                              ({product.review_count})
                            </span>
                          </div>
                        )}

                        {/* Nom */}
                        <h3 className="font-medium text-sm line-clamp-2 text-card-foreground">{product.name}</h3>

                        {/* Prix */}
                        <div className="font-bold text-primary">
                          {product.price.toFixed(2)} €
                        </div>

                        {/* Bouton Ajouter */}
                        <Button
                          size="sm"
                          className="w-full"
                          onClick={(e) => {
                            e.stopPropagation()
                            // Logique ajout panier
                          }}
                        >
                          <ShoppingBag className="h-3 w-3 mr-1" />
                          Ajouter
                        </Button>
                      </div>
                    </div>
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