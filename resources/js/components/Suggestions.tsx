import React, { useState, useEffect, useRef, useCallback } from 'react'
import { Link, router } from '@inertiajs/react'
import { Search, Clock, TrendingUp, X, ArrowUpRight } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { useTranslation } from '@/hooks/useTranslation'

// Types
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
  placeholder?: string
  className?: string
  onSearch?: (query: string) => void
  autoFocus?: boolean
}

// Données mockées - À remplacer par des appels API
const mockSuggestions: SearchSuggestion[] = [
  {
    id: '1',
    type: 'trending',
    title: 'iPhone 15',
    subtitle: 'Smartphone',
    url: '/products/iphone-15',
    image: 'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=100&h=100&fit=crop',
    price: '899€'
  },
  {
    id: '2',
    type: 'category',
    title: 'Électronique',
    subtitle: '1,234 produits',
    url: '/categories/electronique'
  },
  {
    id: '3',
    type: 'product',
    title: 'MacBook Pro 14"',
    subtitle: 'Ordinateur portable',
    url: '/products/macbook-pro-14',
    image: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=100&h=100&fit=crop',
    price: '2,299€'
  }
]

export function SearchWithSuggestions({ 
  placeholder,
  className = '',
  onSearch,
  autoFocus = false
}: SearchWithSuggestionsProps) {
  const { __ } = useTranslation()
  const [query, setQuery] = useState('')
  const [isOpen, setIsOpen] = useState(false)
  const [suggestions, setSuggestions] = useState<SearchSuggestion[]>([])
  const [recentSearches, setRecentSearches] = useState<string[]>([])
  const [isLoading, setIsLoading] = useState(false)
  const [highlightedIndex, setHighlightedIndex] = useState(-1)

  const searchRef = useRef<HTMLDivElement>(null)
  const inputRef = useRef<HTMLInputElement>(null)
  const debounceRef = useRef<NodeJS.Timeout>()

  // Charger les recherches récentes depuis localStorage
  useEffect(() => {
    const stored = localStorage.getItem('recent_searches')
    if (stored) {
      try {
        setRecentSearches(JSON.parse(stored))
      } catch (e) {
        console.error('Error parsing recent searches:', e)
      }
    }
  }, [])

  // Sauvegarder les recherches récentes
  const saveRecentSearch = useCallback((searchQuery: string) => {
    if (!searchQuery.trim()) return

    const newRecent = [
      searchQuery,
      ...recentSearches.filter(item => item !== searchQuery)
    ].slice(0, 5) // Garder seulement les 5 dernières

    setRecentSearches(newRecent)
    localStorage.setItem('recent_searches', JSON.stringify(newRecent))
  }, [recentSearches])

  // Recherche avec debounce
  const debouncedSearch = useCallback((searchQuery: string) => {
    if (debounceRef.current) {
      clearTimeout(debounceRef.current)
    }

    debounceRef.current = setTimeout(async () => {
      if (searchQuery.trim().length >= 2) {
        setIsLoading(true)
        
        // Simuler un appel API
        setTimeout(() => {
          const filtered = mockSuggestions.filter(suggestion =>
            suggestion.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
            suggestion.subtitle?.toLowerCase().includes(searchQuery.toLowerCase())
          )
          setSuggestions(filtered)
          setIsLoading(false)
        }, 200)
      } else {
        setSuggestions([])
        setIsLoading(false)
      }
    }, 300)
  }, [])

  // Gestion du changement de texte
  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value
    setQuery(value)
    setHighlightedIndex(-1)
    
    if (value.trim()) {
      debouncedSearch(value)
      setIsOpen(true)
    } else {
      setSuggestions([])
      setIsOpen(false)
    }
  }

  // Gestion du focus
  const handleFocus = () => {
    setIsOpen(true)
    if (query.trim().length >= 2) {
      debouncedSearch(query)
    }
  }

  // Fermer les suggestions quand on clique ailleurs
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (searchRef.current && !searchRef.current.contains(event.target as Node)) {
        setIsOpen(false)
      }
    }

    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  // Gestion des touches clavier
  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (!isOpen) return

    const totalItems = suggestions.length + recentSearches.length
    
    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault()
        setHighlightedIndex(prev => 
          prev < totalItems - 1 ? prev + 1 : -1
        )
        break
      case 'ArrowUp':
        e.preventDefault()
        setHighlightedIndex(prev => 
          prev > -1 ? prev - 1 : totalItems - 1
        )
        break
      case 'Enter':
        e.preventDefault()
        if (highlightedIndex >= 0) {
          // Navigation vers l'item sélectionné
          const allItems = [...suggestions, ...recentSearches.map(term => ({
            id: term,
            type: 'recent' as const,
            title: term,
            url: `/products?search=${encodeURIComponent(term)}`
          }))]
          
          const selected = allItems[highlightedIndex]
          if (selected) {
            handleItemClick(selected)
          }
        } else {
          handleSubmit(e)
        }
        break
      case 'Escape':
        setIsOpen(false)
        inputRef.current?.blur()
        break
    }
  }

  // Gestion du submit
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (query.trim()) {
      saveRecentSearch(query.trim())
      setIsOpen(false)
      onSearch?.(query)
      router.get('/products', { search: query })
    }
  }

  // Clic sur un item
  const handleItemClick = (item: SearchSuggestion | { id: string, type: 'recent', title: string, url: string }) => {
    if (item.type === 'recent') {
      setQuery(item.title)
      saveRecentSearch(item.title)
      onSearch?.(item.title)
      router.get('/products', { search: item.title })
    } else {
      saveRecentSearch(item.title)
      router.get(item.url)
    }
    setIsOpen(false)
  }

  // Supprimer une recherche récente
  const removeRecentSearch = (term: string, e: React.MouseEvent) => {
    e.stopPropagation()
    const newRecent = recentSearches.filter(item => item !== term)
    setRecentSearches(newRecent)
    localStorage.setItem('recent_searches', JSON.stringify(newRecent))
  }

  // Icône par type
  const getTypeIcon = (type: SearchSuggestion['type']) => {
    const iconClass = "h-3 w-3 sm:h-4 sm:w-4 text-muted-foreground"
    
    switch (type) {
      case 'trending':
        return <TrendingUp className={iconClass} />
      case 'recent':
        return <Clock className={iconClass} />
      default:
        return <Search className={iconClass} />
    }
  }

  return (
    <div ref={searchRef} className={`relative ${className}`}>
      <form onSubmit={handleSubmit} className="relative">
        <Input
          ref={inputRef}
          type="text"
          placeholder={placeholder || __('common.search_products')}
          value={query}
          onChange={handleInputChange}
          onFocus={handleFocus}
          onKeyDown={handleKeyDown}
          autoFocus={autoFocus}
          className="pr-12 h-10 sm:h-11 bg-muted/50 border-muted-foreground/20 focus:bg-background"
          autoComplete="off"
        />
        
        <Button 
          type="submit"
          size="sm"
          className="absolute right-1 top-1 h-8 w-8 sm:h-9 sm:w-9 p-0"
          disabled={!query.trim()}
        >
          <Search className="h-3 w-3 sm:h-4 sm:w-4" />
        </Button>
      </form>

      {/* Dropdown de suggestions */}
      {isOpen && (
        <div className="absolute top-full left-0 right-0 mt-1 bg-background border border-border rounded-lg shadow-lg z-50 max-h-96 overflow-y-auto">
          
          {/* Recherches récentes */}
          {!query.trim() && recentSearches.length > 0 && (
            <div className="p-2 sm:p-3 border-b border-border">
              <h4 className="text-xs sm:text-sm font-medium text-muted-foreground mb-2">
                {__('common.recent_searches')}
              </h4>
              {recentSearches.map((term, index) => (
                <div
                  key={term}
                  className={`flex items-center justify-between p-2 rounded-md hover:bg-muted/50 cursor-pointer text-sm ${
                    highlightedIndex === suggestions.length + index ? 'bg-muted/50' : ''
                  }`}
                  onClick={() => handleItemClick({
                    id: term,
                    type: 'recent',
                    title: term,
                    url: `/products?search=${encodeURIComponent(term)}`
                  })}
                >
                  <div className="flex items-center gap-2 flex-1 min-w-0">
                    <Clock className="h-3 w-3 sm:h-4 sm:w-4 text-muted-foreground flex-shrink-0" />
                    <span className="truncate">{term}</span>
                  </div>
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-6 w-6 flex-shrink-0"
                    onClick={(e) => removeRecentSearch(term, e)}
                  >
                    <X className="h-3 w-3" />
                  </Button>
                </div>
              ))}
            </div>
          )}

          {/* Loading state */}
          {isLoading && (
            <div className="p-4 text-center text-sm text-muted-foreground">
              {__('common.searching')}...
            </div>
          )}

          {/* Suggestions */}
          {suggestions.length > 0 && (
            <div className="p-1 sm:p-2">
              {suggestions.map((suggestion, index) => (
                <div
                  key={suggestion.id}
                  className={`flex items-center gap-3 p-2 sm:p-3 rounded-md hover:bg-muted/50 cursor-pointer ${
                    highlightedIndex === index ? 'bg-muted/50' : ''
                  }`}
                  onClick={() => handleItemClick(suggestion)}
                >
                  {/* Image produit */}
                  {suggestion.image && (
                    <div className="w-8 h-8 sm:w-10 sm:h-10 rounded-md overflow-hidden flex-shrink-0 bg-muted">
                      <img 
                        src={suggestion.image} 
                        alt={suggestion.title}
                        className="w-full h-full object-cover"
                      />
                    </div>
                  )}

                  {/* Icône type */}
                  {!suggestion.image && (
                    <div className="flex-shrink-0">
                      {getTypeIcon(suggestion.type)}
                    </div>
                  )}

                  {/* Contenu */}
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between gap-2">
                      <div className="min-w-0 flex-1">
                        <h4 className="text-sm font-medium text-foreground truncate">
                          {suggestion.title}
                        </h4>
                        {suggestion.subtitle && (
                          <p className="text-xs text-muted-foreground truncate">
                            {suggestion.subtitle}
                          </p>
                        )}
                      </div>

                      <div className="flex items-center gap-2 flex-shrink-0">
                        {suggestion.price && (
                          <span className="text-sm font-medium text-primary">
                            {suggestion.price}
                          </span>
                        )}
                        <ArrowUpRight className="h-3 w-3 text-muted-foreground" />
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}

          {/* Aucun résultat */}
          {query.trim() && !isLoading && suggestions.length === 0 && (
            <div className="p-4 text-center text-sm text-muted-foreground">
              <Search className="h-8 w-8 mx-auto mb-2 opacity-50" />
              <p>{__('common.no_results_for')} "{query}"</p>
              <p className="text-xs mt-1">{__('common.try_different_keywords')}</p>
            </div>
          )}
        </div>
      )}
    </div>
  )
}