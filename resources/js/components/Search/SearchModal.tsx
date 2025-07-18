import React, { useState, useCallback, useRef, useEffect } from 'react'
import { Search, X, Clock } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { useSearchContext } from '@/contexts/SearchContext'
import { router } from '@inertiajs/react'
import type { Product } from '@/types/index.d.ts'

interface SearchModalProps {
  isOpen: boolean
  onClose: () => void
  placeholder?: string
}

export default function SearchModal({
  isOpen,
  onClose,
  placeholder = "Recherchez des produits..."
}: SearchModalProps) {
  const [query, setQuery] = useState('')
  const [isSearching, setIsSearching] = useState(false)
  const [products, setProducts] = useState<Product[]>([])
  const inputRef = useRef<HTMLInputElement>(null)
  const debounceRef = useRef<NodeJS.Timeout | null>(null)
  
  const { addRecentSearch, recentSearches } = useSearchContext()

  // Focus automatique à l'ouverture
  useEffect(() => {
    if (isOpen && inputRef.current) {
      const timer = setTimeout(() => inputRef.current?.focus(), 150)
      return () => clearTimeout(timer)
    }
  }, [isOpen])

  // Reset à la fermeture
  useEffect(() => {
    if (!isOpen) {
      setQuery('')
      setIsSearching(false)
      setProducts([])
      if (debounceRef.current) {
        clearTimeout(debounceRef.current)
      }
    }
  }, [isOpen])

  // Fonction pour récupérer les produits
  const fetchProducts = useCallback(async (searchValue: string) => {
    if (searchValue.length >= 2) {
      setIsSearching(true)
      try {
        console.log('Fetching products for:', searchValue)
        const response = await fetch(`/search/live?search=${encodeURIComponent(searchValue)}&limit=20`, {
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          }
        })
        
        console.log('Response status:', response.status)
        
        if (response.ok) {
          const result = await response.json()
          console.log('Response data:', result)
          if (result.products) {
            setProducts(result.products)
            console.log('Products set:', result.products.length)
          } else {
            setProducts([])
            console.log('No products in response')
          }
        } else {
          setProducts([])
          console.log('Response not ok')
        }
      } catch (error) {
        console.error('Erreur lors de la recherche:', error)
        setProducts([])
      } finally {
        setIsSearching(false)
      }
    } else {
      setProducts([])
    }
  }, [])

  // Gestion du changement de query avec debounce
  const handleInputChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value
    setQuery(value)

    if (debounceRef.current) {
      clearTimeout(debounceRef.current)
    }
    
    debounceRef.current = setTimeout(() => {
      fetchProducts(value)
    }, 300)
  }, [fetchProducts])

  // Navigation vers un produit
  const handleProductClick = useCallback((product: Product) => {
    if (query.trim().length >= 2) {
      addRecentSearch(query.trim())
    }
    
    onClose()
    
    // Debug : vérifier les données du produit
    console.log('Product clicked:', product)
    console.log('Slug:', product.slug)
    console.log('UUID:', product.uuid)
    
    const productUrl = route('products.show', { slug: product.slug || 'product', uuid: product.uuid })
    console.log('Generated URL:', productUrl)
    
    router.visit(productUrl)
  }, [addRecentSearch, onClose, query])

  // Effacer la recherche
  const clearQuery = useCallback(() => {
    setQuery('')
    setProducts([])
    if (inputRef.current) {
      inputRef.current.focus()
    }
  }, [])

  // Utiliser une recherche de l'historique
  const handleHistorySearch = useCallback((searchTerm: string) => {
    setQuery(searchTerm)
    fetchProducts(searchTerm)
  }, [fetchProducts])

  return (
    <Sheet open={isOpen} onOpenChange={onClose}>
      <SheetContent side="top" className="h-full w-full p-0 max-w-none">
        {/* Header fixe */}
        <SheetHeader className="border-b bg-background p-4 space-y-4">
          <SheetTitle className="flex items-center justify-between">
            <span className="text-lg font-semibold">Recherche</span>
          </SheetTitle>

          {/* Champ de recherche */}
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
            <input
              ref={inputRef}
              type="text"
              value={query}
              onChange={handleInputChange}
              placeholder={placeholder}
              className="w-full pl-10 pr-4 py-3 text-base border border-input rounded-lg bg-muted/50 focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
            />
          </div>

          {/* Barre de résultats */}
          {query.length >= 2 && (
            <div className="flex items-center justify-between">
              <span className="text-sm text-muted-foreground">
                {isSearching ? 'Recherche...' : `${products.length} produit${products.length > 1 ? 's' : ''} trouvé${products.length > 1 ? 's' : ''}`}
              </span>
            </div>
          )}
        </SheetHeader>

        {/* Contenu scrollable */}
        <div className="flex-1 overflow-y-auto bg-background">
          {query.length < 2 ? (
            <div className="p-4">
              {recentSearches.length > 0 ? (
                <div>
                  <h3 className="text-sm font-medium text-muted-foreground mb-3 flex items-center">
                    <Clock className="h-4 w-4 mr-2" />
                    Recherches récentes
                  </h3>
                  <div className="space-y-2">
                    {recentSearches.map((search, index) => (
                      <button
                        key={index}
                        onClick={() => handleHistorySearch(search)}
                        className="w-full text-left p-3 rounded-lg hover:bg-muted/50 transition-colors flex items-center"
                      >
                        <Clock className="h-4 w-4 mr-3 text-muted-foreground" />
                        <span className="text-sm">{search}</span>
                      </button>
                    ))}
                  </div>
                </div>
              ) : (
                <div className="flex items-center justify-center py-16">
                  <div className="text-center">
                    <Search className="h-16 w-16 text-muted-foreground mx-auto mb-4" />
                    <p className="text-muted-foreground text-lg">Tapez au moins 2 caractères pour rechercher</p>
                  </div>
                </div>
              )}
            </div>
          ) : isSearching ? (
            <div className="flex items-center justify-center py-16">
              <div className="text-center">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
                <p className="text-muted-foreground">Recherche en cours...</p>
              </div>
            </div>
          ) : products.length > 0 ? (
            <div className="p-4">
              <div className="grid grid-cols-1 gap-4">
                {products.map((product) => (
                  <div
                    key={product.uuid}
                    onClick={() => handleProductClick(product)}
                    className="flex items-center space-x-4 p-4 rounded-lg hover:bg-muted/50 cursor-pointer transition-colors"
                  >
                    {/* Image */}
                    <div className="flex-shrink-0 w-16 h-16 bg-muted rounded-lg overflow-hidden">
                      {product.featured_image ? (
                        <img
                          src={product.featured_image}
                          alt={product.name}
                          className="w-full h-full object-cover"
                        />
                      ) : (
                        <div className="w-full h-full flex items-center justify-center text-muted-foreground">
                          <Search className="h-6 w-6" />
                        </div>
                      )}
                    </div>

                    {/* Informations */}
                    <div className="flex-1 min-w-0">
                      <h3 className="font-medium text-sm truncate">{product.name}</h3>
                      <p className="text-lg font-semibold text-primary mt-1">
                        €{product.price}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ) : (
            <div className="flex items-center justify-center py-16">
              <div className="text-center">
                <Search className="h-16 w-16 text-muted-foreground mx-auto mb-4" />
                <p className="text-muted-foreground text-lg">Aucun produit trouvé pour "{query}"</p>
              </div>
            </div>
          )}
        </div>
      </SheetContent>
    </Sheet>
  )
}