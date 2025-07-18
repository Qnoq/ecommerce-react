import { useState, useRef, useEffect, useCallback } from 'react'
import { router } from '@inertiajs/react'
import { useSearchContext } from '@/contexts/SearchContext'
import type { Product, SearchSuggestion } from '@/types/index.d.ts'

interface UseSearchModalOptions {
  isOpen: boolean
  onClose: () => void
  enableLiveSearch?: boolean
}

export function useSearchModal({ isOpen, onClose, enableLiveSearch = false }: UseSearchModalOptions) {
  const [query, setQuery] = useState('')
  const [isSearching, setIsSearching] = useState(false)
  const [suggestions, setSuggestions] = useState<any[]>([])
  const inputRef = useRef<HTMLInputElement>(null)
  const debounceRef = useRef<NodeJS.Timeout | null>(null)
  const saveSearchRef = useRef<NodeJS.Timeout | null>(null)
  
  const { addRecentSearch } = useSearchContext()

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
      setSuggestions([])
      // Nettoyer les timers
      if (debounceRef.current) {
        clearTimeout(debounceRef.current)
      }
      if (saveSearchRef.current) {
        clearTimeout(saveSearchRef.current)
      }
    }
  }, [isOpen])

  // Navigation vers la page de recherche complète
  const navigateToSearchPage = useCallback((searchQuery: string) => {
    const trimmedQuery = searchQuery.trim()
    
    if (trimmedQuery.length >= 2) {
      addRecentSearch(trimmedQuery)
      onClose()
      router.visit(`/s?k=${encodeURIComponent(trimmedQuery)}`, {
        method: 'get',
        preserveScroll: false,
        preserveState: false
      })
    }
  }, [addRecentSearch, onClose])

  // Fonction pour récupérer les suggestions
  const fetchSuggestions = useCallback(async (searchValue: string) => {
    if (searchValue.length >= 2) {
      setIsSearching(true)
      try {
        const response = await fetch(`/products/suggestions?q=${encodeURIComponent(searchValue)}`)
        const data = await response.json()
        setSuggestions(data.suggestions || [])
      } catch (error) {
        console.error('Erreur lors de la récupération des suggestions:', error)
        setSuggestions([])
      } finally {
        setIsSearching(false)
      }
    } else {
      setSuggestions([])
    }
  }, [])

  // Gestion du changement de query
  const handleInputChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value
    setQuery(value)

    // Récupérer les suggestions avec debounce
    if (debounceRef.current) {
      clearTimeout(debounceRef.current)
    }
    
    debounceRef.current = setTimeout(() => {
      fetchSuggestions(value)
    }, 300) // Debounce plus court pour les suggestions
  }, [fetchSuggestions])

  // Navigation vers un produit
  const navigateToProduct = useCallback((product: Product) => {
    if (query.trim().length >= 2) {
      addRecentSearch(query.trim())
      // Annuler le timer de sauvegarde différée puisqu'on sauvegarde immédiatement
      if (saveSearchRef.current) {
        clearTimeout(saveSearchRef.current)
      }
    }
    
    onClose()
    const productUrl = route('products.show', { slug: product.slug || 'product', uuid: product.uuid })
    router.visit(productUrl)
  }, [addRecentSearch, onClose, query])

  // Navigation vers une suggestion
  const navigateToSuggestion = useCallback((suggestion: SearchSuggestion) => {
    addRecentSearch(suggestion.title)
    // Annuler le timer de sauvegarde différée puisqu'on sauvegarde immédiatement
    if (saveSearchRef.current) {
      clearTimeout(saveSearchRef.current)
    }
    onClose()
    router.visit(suggestion.url)
  }, [addRecentSearch, onClose])

  // Navigation vers une URL générique
  const navigateToUrl = useCallback((url: string) => {
    onClose()
    router.visit(url)
  }, [onClose])

  // Gestion de la touche Entrée
  const handleKeyDown = useCallback((e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter') {
      // Toujours naviguer vers la page de recherche avec live search
      navigateToSearchPage(query)
    }
  }, [query, navigateToSearchPage])

  // Effacer la recherche
  const clearQuery = useCallback(() => {
    setQuery('')
    if (inputRef.current) {
      inputRef.current.focus()
    }
  }, [])

  return {
    // État
    query,
    isSearching,
    suggestions,
    inputRef,
    
    // Actions
    handleInputChange,
    handleKeyDown,
    clearQuery,
    navigateToSearchPage,
    navigateToProduct,
    navigateToSuggestion,
    navigateToUrl,
    
    // Utilitaires
    showResults: query.length >= 2,
    performSearch: (searchQuery: string) => navigateToSearchPage(searchQuery)
  }
}