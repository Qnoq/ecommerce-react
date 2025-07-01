import React, { useState, useRef, useEffect, useCallback } from 'react'
import { Search, X, Clock, TrendingUp } from 'lucide-react'
import { cn } from '@/lib/utils'
import { router } from '@inertiajs/react'

// Types
import type { SearchSuggestion } from './SearchModal'

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
  const [query, setQuery] = useState('')
  const [suggestions, setSuggestions] = useState<SearchSuggestion[]>([])
  const [isOpen, setIsOpen] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const [selectedIndex, setSelectedIndex] = useState(-1)
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

  // Fonction pour récupérer les suggestions
  const fetchSuggestions = useCallback(async (searchQuery: string) => {
    if (searchQuery.length < 2) {
      setSuggestions([])
      setIsLoading(false)
      return
    }

    setIsLoading(true)

    try {
      const response = await fetch(`/products/suggestions?q=${encodeURIComponent(searchQuery)}`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
      })
      
      if (response.ok) {
        const result = await response.json()
        setSuggestions(result.suggestions || [])
      } else {
        setSuggestions([])
      }
    } catch (error) {
      console.error('Erreur lors de la récupération des suggestions:', error)
      setSuggestions([])
    } finally {
      setIsLoading(false)
    }
  }, [])

  // Debounce pour les suggestions
  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value
    setQuery(value)
    setSelectedIndex(-1)

    // Clear previous debounce
    if (debounceRef.current) {
      clearTimeout(debounceRef.current)
    }

    // Debounce les suggestions
    debounceRef.current = setTimeout(() => {
      fetchSuggestions(value)
    }, 300)
  }

  // Effectuer une recherche
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

    // Fermer les suggestions
    setIsOpen(false)
    setSuggestions([])
    
    // Callback personnalisé ou navigation Inertia
    if (onSearch) {
      onSearch(trimmedQuery)
    } else {
      router.get('/products', { search: trimmedQuery })
    }
  }, [onSearch, recentSearches])

  // Navigation vers une suggestion
  const navigateToSuggestion = useCallback((suggestion: SearchSuggestion) => {
    if (suggestion.type !== 'recent') {
      // Sauvegarder dans l'historique
      const newRecent = [
        suggestion.title,
        ...recentSearches.filter(item => item !== suggestion.title)
      ].slice(0, 5)
      
      setRecentSearches(newRecent)
      localStorage.setItem('shoplux_recent_searches', JSON.stringify(newRecent))
    }
    
    setIsOpen(false)
    setSuggestions([])
    router.visit(suggestion.url)
  }, [recentSearches])

  // Gestion du clavier
  const handleKeyDown = (e: React.KeyboardEvent) => {
    const totalItems = suggestions.length + recentSearches.length

    if (e.key === 'ArrowDown') {
      e.preventDefault()
      setSelectedIndex(prev => (prev + 1) % totalItems)
    } else if (e.key === 'ArrowUp') {
      e.preventDefault()
      setSelectedIndex(prev => (prev - 1 + totalItems) % totalItems)
    } else if (e.key === 'Enter') {
      e.preventDefault()
      
      if (selectedIndex >= 0) {
        // Navigation vers la suggestion sélectionnée
        if (selectedIndex < suggestions.length) {
          navigateToSuggestion(suggestions[selectedIndex])
        } else {
          const recentIndex = selectedIndex - suggestions.length
          performSearch(recentSearches[recentIndex])
        }
      } else {
        // Recherche avec la query actuelle
        performSearch(query)
      }
    } else if (e.key === 'Escape') {
      setIsOpen(false)
      inputRef.current?.blur()
    }
  }

  // Suggestions combinées (API + récentes)
  const allSuggestions: SearchSuggestion[] = [
    ...suggestions,
    ...recentSearches.slice(0, 3).map((search, index) => ({
      id: `recent-${index}`,
      type: 'recent' as const,
      title: search,
      subtitle: 'Recherche récente',
      url: `/products?search=${encodeURIComponent(search)}`,
      image: undefined,
      price: undefined
    }))
  ]

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
          onBlur={() => setTimeout(() => setIsOpen(false), 200)}
          placeholder={placeholder}
          autoFocus={autoFocus}
          className="w-full pl-10 pr-10 py-2 border border-input rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
        />
        {query && (
          <button
            type="button"
            onClick={() => {
              setQuery('')
              setSuggestions([])
              inputRef.current?.focus()
            }}
            className="absolute right-3 top-1/2 transform -translate-y-1/2 text-muted-foreground hover:text-foreground"
          >
            <X className="h-4 w-4" />
          </button>
        )}
      </div>

      {/* Suggestions Dropdown */}
      {isOpen && (allSuggestions.length > 0 || isLoading) && (
        <div className="absolute top-full left-0 right-0 mt-1 bg-background border border-border rounded-md shadow-lg z-50 max-h-96 overflow-y-auto">
          {isLoading && (
            <div className="p-4 text-center text-muted-foreground">
              Recherche en cours...
            </div>
          )}
          
          {allSuggestions.map((suggestion, index) => (
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
                {suggestion.image ? (
                  <img
                    src={suggestion.image}
                    alt={suggestion.title}
                    className="w-10 h-10 object-cover rounded"
                  />
                ) : (
                  <div className="w-10 h-10 bg-muted rounded flex items-center justify-center">
                    {suggestion.type === 'recent' ? (
                      <Clock className="h-4 w-4 text-muted-foreground" />
                    ) : suggestion.type === 'trending' ? (
                      <TrendingUp className="h-4 w-4 text-muted-foreground" />
                    ) : (
                      <Search className="h-4 w-4 text-muted-foreground" />
                    )}
                  </div>
                )}
                
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
                
                {suggestion.price && (
                  <div className="text-sm font-medium text-primary">
                    {suggestion.price}
                  </div>
                )}
              </div>
            </button>
          ))}
        </div>
      )}
    </div>
  )
} 