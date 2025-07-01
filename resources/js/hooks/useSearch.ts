import { useForm } from '@inertiajs/react'
import { useState, useCallback } from 'react'

interface SearchFilters {
  search?: string
  category?: string
  price_min?: number
  price_max?: number
  sort?: string
  order?: 'asc' | 'desc'
  [key: string]: any
}

interface UseSearchOptions {
  initialFilters?: SearchFilters
  preserveState?: boolean
  preserveScroll?: boolean
}

export function useSearch(options: UseSearchOptions = {}) {
  const {
    initialFilters = {},
    preserveState = true,
    preserveScroll = true
  } = options

  // État du formulaire Inertia
  const { data, setData, get, processing } = useForm<SearchFilters>({
    search: '',
    category: '',
    price_min: undefined,
    price_max: undefined,
    sort: 'created_at',
    order: 'desc',
    ...initialFilters
  })

  // État pour les suggestions en temps réel
  const [suggestions, setSuggestions] = useState<any[]>([])
  const [isLoadingSuggestions, setIsLoadingSuggestions] = useState(false)

  // Recherche principale avec Inertia
  const handleSearch = useCallback(() => {
    get(route('products.index'), {
      preserveState,
      preserveScroll,
      only: ['products', 'filters']
    })
  }, [get, preserveState, preserveScroll])

  // Recherche avec debounce pour les filtres
  const handleFilterChange = useCallback((key: keyof SearchFilters, value: any) => {
    setData(key, value)
    
    // Debounce pour éviter trop de requêtes
    setTimeout(() => {
      get(route('products.index'), {
        preserveState,
        preserveScroll,
        only: ['products', 'filters']
      })
    }, 300)
  }, [setData, get, preserveState, preserveScroll])

  // Recherche instantanée pour les suggestions (fetch API)
  const fetchSuggestions = useCallback(async (query: string) => {
    if (query.length < 2) {
      setSuggestions([])
      return
    }

    setIsLoadingSuggestions(true)
    
    try {
      const response = await fetch(`/products/suggestions?q=${encodeURIComponent(query)}`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
      })
      
      if (response.ok) {
        const result = await response.json()
        setSuggestions(result.suggestions || [])
      }
    } catch (error) {
      console.error('Erreur lors de la récupération des suggestions:', error)
      setSuggestions([])
    } finally {
      setIsLoadingSuggestions(false)
    }
  }, [])

  // Méthodes utilitaires
  const clearSearch = useCallback(() => {
    setData({
      search: '',
      category: '',
      price_min: undefined,
      price_max: undefined,
      sort: 'created_at',
      order: 'desc'
    })
    handleSearch()
  }, [setData, handleSearch])

  const setSearchQuery = useCallback((query: string) => {
    setData('search', query)
  }, [setData])

  const setSortBy = useCallback((sort: string, order: 'asc' | 'desc' = 'desc') => {
    setData(data => ({...data, sort, order}))
    setTimeout(() => handleSearch(), 100)
  }, [setData, handleSearch])

  return {
    // État du formulaire
    filters: data,
    setData,
    processing,
    
    // Actions principales
    handleSearch,
    handleFilterChange,
    clearSearch,
    setSearchQuery,
    setSortBy,
    
    // Suggestions en temps réel
    suggestions,
    isLoadingSuggestions,
    fetchSuggestions,
    
    // Méthodes Inertia natives
    get
  }
} 