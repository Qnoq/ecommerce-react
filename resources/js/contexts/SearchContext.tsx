// resources/js/contexts/SearchContext.tsx
import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react'

interface SearchContextType {
  recentSearches: string[]
  addRecentSearch: (query: string) => void
  clearRecentSearches: () => void
  isLoading: boolean
}

const SearchContext = createContext<SearchContextType | undefined>(undefined)

interface SearchProviderProps {
  children: ReactNode
}

export function SearchProvider({ children }: SearchProviderProps) {
  const [recentSearches, setRecentSearches] = useState<string[]>([])
  const [isLoading, setIsLoading] = useState(true)

  // Charger les recherches récentes au démarrage
  useEffect(() => {
    const loadRecentSearches = () => {
      try {
        const stored = localStorage.getItem('shoplux_recent_searches')
        if (stored) {
          const parsed = JSON.parse(stored)
          // Validation des données pour éviter les erreurs
          if (Array.isArray(parsed) && parsed.every(item => typeof item === 'string')) {
            setRecentSearches(parsed)
          }
        }
      } catch (error) {
        console.error('Erreur lors du chargement des recherches récentes:', error)
        // En cas d'erreur, on nettoie le localStorage corrompu
        localStorage.removeItem('shoplux_recent_searches')
      } finally {
        setIsLoading(false)
      }
    }

    loadRecentSearches()
  }, [])

  // Fonction pour ajouter une recherche récente
  const addRecentSearch = (query: string) => {
    const trimmedQuery = query.trim()
    
    // Valider que la requête est valide
    if (trimmedQuery.length < 2) return
    
    setRecentSearches(currentSearches => {
      // Créer une nouvelle liste sans doublons
      const newSearches = [
        trimmedQuery,
        ...currentSearches.filter(search => search !== trimmedQuery)
      ].slice(0, 5) // Garder seulement les 5 plus récentes
      
      // Sauvegarder dans localStorage de manière asynchrone
      try {
        localStorage.setItem('shoplux_recent_searches', JSON.stringify(newSearches))
      } catch (error) {
        console.error('Erreur lors de la sauvegarde des recherches récentes:', error)
      }
      
      return newSearches
    })
  }

  // Fonction pour vider les recherches récentes
  const clearRecentSearches = () => {
    setRecentSearches([])
    try {
      localStorage.removeItem('shoplux_recent_searches')
    } catch (error) {
      console.error('Erreur lors du nettoyage des recherches récentes:', error)
    }
  }

  const contextValue: SearchContextType = {
    recentSearches,
    addRecentSearch,
    clearRecentSearches,
    isLoading
  }

  return (
    <SearchContext.Provider value={contextValue}>
      {children}
    </SearchContext.Provider>
  )
}

// Hook personnalisé pour utiliser le contexte
export function useSearchContext() {
  const context = useContext(SearchContext)
  if (context === undefined) {
    throw new Error('useSearchContext doit être utilisé à l\'intérieur d\'un SearchProvider')
  }
  return context
}