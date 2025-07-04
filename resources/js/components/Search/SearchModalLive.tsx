import React, { useState, useRef, useEffect, useCallback } from 'react'
import { Search, X, SlidersHorizontal, Star, ShoppingBag } from 'lucide-react'
import { cn } from '@/lib/utils'
import { router, usePage } from '@inertiajs/react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Sheet, SheetContent, SheetHeader } from '@/components/ui/sheet'
import { useSearchContext } from '@/contexts/SearchContext'

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

interface SearchModalLiveProps {
  isOpen: boolean
  onClose: () => void
  placeholder?: string
}

export default function SearchModalLive({
  isOpen,
  onClose,
  placeholder = "Recherchez des produits..."
}: SearchModalLiveProps) {
  // État local simplifié - plus de gestion localStorage
  const [query, setQuery] = useState('')
  const [isSearching, setIsSearching] = useState(false)
  
  // Utilisation du Context comme seule source de vérité
  const { recentSearches, addRecentSearch } = useSearchContext()

  const inputRef = useRef<HTMLInputElement>(null)
  const debounceRef = useRef<NodeJS.Timeout | null>(null)

  // Récupération des données Inertia (inchangé)
  const page = usePage()
  const searchResults = (page.props as any).searchResults || {}
  const products: Product[] = searchResults.products?.data || []
  const totalResults = searchResults.products?.total || 0
  const suggestions = searchResults.suggestions || []

  // Focus automatique simplifié
  useEffect(() => {
    if (isOpen && inputRef.current) {
      // Délai légèrement plus long pour s'assurer que la modale est complètement ouverte
      const timer = setTimeout(() => inputRef.current?.focus(), 150)
      return () => clearTimeout(timer)
    }
  }, [isOpen])

  // 🔧 CORRECTION: Nettoyage sans navigation automatique
  useEffect(() => {
    if (!isOpen) {
      // Réinitialiser seulement l'état local de ce composant
      setQuery('')
      setIsSearching(false)
      
      // 🚫 SUPPRIMÉ: Ne plus naviguer automatiquement car cela efface les paramètres URL
      // La navigation doit être intentionnelle, pas automatique
    }
  }, [isOpen])

  // Fonction de recherche live inchangée mais simplifiée
  const performLiveSearch = useCallback((searchQuery: string) => {
    if (searchQuery.length < 2) {
      setIsSearching(false)
      return
    }

    setIsSearching(true)
    const searchUrl = `/search/live?q=${encodeURIComponent(searchQuery)}&modal=true`
    
    router.visit(searchUrl, {
      method: 'get',
      preserveState: true,
      preserveScroll: true,
      only: ['searchResults'],
      onFinish: () => setIsSearching(false)
    })
  }, [])

  // Gestion de la saisie avec debounce (inchangé)
  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value
    setQuery(value)

    if (debounceRef.current) {
      clearTimeout(debounceRef.current)
    }

    debounceRef.current = setTimeout(() => {
      performLiveSearch(value)
    }, 300)
  }

  // Fonction unifiée pour naviguer vers la page de recherche
  const navigateToSearchPage = useCallback((searchQuery: string) => {
    const trimmedQuery = searchQuery.trim()
    
    if (trimmedQuery.length >= 2) {
      // Utiliser le Context pour gérer les recherches récentes
      addRecentSearch(trimmedQuery)
      
      // Fermer la modale et naviguer
      onClose()
      // 🔧 CORRECTION: Utiliser router.visit pour cohérence
      router.visit(`/s?k=${encodeURIComponent(trimmedQuery)}`, {
        method: 'get',
        preserveScroll: false,
        preserveState: false
      })
    }
  }, [addRecentSearch, onClose])

  // Navigation vers un produit simplifiée
  const navigateToProduct = useCallback((productUuid: string, productName: string) => {
    // Sauvegarder la recherche actuelle si elle est valide
    if (query.trim().length >= 2) {
      addRecentSearch(query.trim())
    }
    
    onClose()
    router.visit(`/products/${productUuid}`)
  }, [addRecentSearch, onClose, query])

  // Navigation vers une suggestion
  const navigateToSuggestion = useCallback((suggestion: any) => {
    addRecentSearch(suggestion.title)
    onClose()
    router.visit(suggestion.url)
  }, [addRecentSearch, onClose])

  // Cards promotionnelles (inchangé mais peut être externalisé)
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

  const showResults = query.length >= 2

  return (
    <Sheet open={isOpen} onOpenChange={onClose}>
      <SheetContent side="top" className="h-full w-full p-0 max-w-none">
        {/* Header avec barre de recherche */}
        <SheetHeader className="border-b bg-background p-4 space-y-4">
          <h2 className="text-lg font-semibold">Recherche</h2>

          {/* Champ de recherche */}
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
            <input
              ref={inputRef}
              type="text"
              value={query}
              onChange={handleInputChange}
              onKeyDown={(e) => e.key === 'Enter' && navigateToSearchPage(query)}
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

          {/* Barre de résultats et actions */}
          {showResults && (
            <div className="flex items-center justify-between">
              <span className="text-sm text-muted-foreground">
                {isSearching ? 'Recherche...' : `${totalResults} articles`}
              </span>
              <div className="flex gap-2">
                <Button variant="outline" size="sm">
                  <SlidersHorizontal className="h-4 w-4 mr-2" />
                  Filtrer
                </Button>
                
                {/* Bouton "Voir tous les résultats" simplifié */}
                {totalResults > 5 && (
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => navigateToSearchPage(query)}
                  >
                    Voir tous les {totalResults} résultats
                  </Button>
                )}
              </div>
            </div>
          )}
        </SheetHeader>

        {/* Contenu principal */}
        <div className="flex-1 overflow-y-auto bg-background">
          {!showResults ? (
            /* État initial avec recherches récentes et cards promotionnelles */
            <div className="p-4 space-y-4">
              {/* Recherches récentes utilisant le Context */}
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
                        onClick={() => navigateToSearchPage(search)}
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
                    onClick={() => {
                      onClose()
                      router.visit(card.url)
                    }}
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
            /* État de recherche avec résultats */
            <div className="space-y-4">
              {/* Suggestions */}
              {suggestions.length > 0 && (
                <div className="px-4 py-2 border-b">
                  <div className="flex flex-wrap gap-2">
                    <span className="text-sm text-muted-foreground">Suggestions :</span>
                    {suggestions.slice(0, 3).map((suggestion: any) => (
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
                    <div
                      key={product.uuid}
                      className="bg-card border border-border rounded-lg overflow-hidden hover:shadow-md transition-shadow cursor-pointer"
                      onClick={() => navigateToProduct(product.uuid, product.name)}
                    >
                      {/* Image du produit */}
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
                        
                        {/* Badges produit */}
                        <div className="absolute top-2 left-2 space-y-1">
                          {product.badges?.map((badge, index) => (
                            <Badge key={index} variant="secondary" className="text-xs">
                              {badge}
                            </Badge>
                          ))}
                        </div>
                      </div>

                      {/* Informations produit */}
                      <div className="p-3 space-y-2">
                        {/* Évaluation */}
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
                              ({product.review_count || 0})
                            </span>
                          </div>
                        )}

                        {/* Nom du produit */}
                        <h3 className="font-medium text-sm line-clamp-2 text-card-foreground">
                          {product.name}
                        </h3>

                        {/* Prix */}
                        <div className="font-bold text-primary">
                          {product.price.toFixed(2)} €
                        </div>

                        {/* Bouton d'ajout au panier */}
                        <Button
                          size="sm"
                          className="w-full"
                          onClick={(e) => {
                            e.stopPropagation()
                            // Logique d'ajout au panier à implémenter
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
                /* Aucun résultat trouvé */
                <div className="text-center py-8">
                  <Search className="h-12 w-12 text-muted-foreground mx-auto mb-2" />
                  <p className="text-muted-foreground">Aucun produit trouvé pour "{query}"</p>
                  <Button 
                    variant="outline" 
                    className="mt-4"
                    onClick={() => navigateToSearchPage(query)}
                  >
                    Rechercher dans tout le catalogue
                  </Button>
                </div>
              ) : null}
            </div>
          )}
        </div>
      </SheetContent>
    </Sheet>
  )
}