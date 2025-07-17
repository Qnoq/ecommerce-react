import React from 'react'
import { Search, X } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { useSearchContext } from '@/contexts/SearchContext'
import { useSearchModal } from '@/hooks/useSearchModal'
import { PROMOTIONAL_CARDS } from '@/data/promotionalCards'
import type { SearchSuggestion } from '@/types/index.d.ts'

// Types importés depuis @/types

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
  const { recentSearches } = useSearchContext()
  const {
    query,
    isSearching,
    suggestions,
    inputRef,
    handleInputChange,
    handleKeyDown,
    clearQuery,
    navigateToSearchPage,
    navigateToProduct,
    navigateToSuggestion,
    navigateToUrl,
    showResults
  } = useSearchModal({ isOpen, onClose, enableLiveSearch: true })

  // Utilisation des suggestions
  const searchSuggestions: SearchSuggestion[] = suggestions || []

  // Utilisation des données externalisées

  return (
    <Sheet open={isOpen} onOpenChange={onClose}>
      <SheetContent side="top" className="h-full w-full p-0 max-w-none">
        {/* Header avec barre de recherche */}
        <SheetHeader className="border-b bg-background p-4 space-y-4">
          <SheetTitle className="text-lg font-semibold">Recherche</SheetTitle>

          {/* Champ de recherche */}
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
            <input
              ref={inputRef}
              type="text"
              value={query}
              onChange={handleInputChange}
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

          {/* Barre de résultats et actions */}
          {showResults && (
            <div className="flex items-center justify-between">
              <span className="text-sm text-muted-foreground">
                {isSearching ? 'Recherche de suggestions...' : `${searchSuggestions.length} suggestions`}
              </span>
              <div className="flex gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => navigateToSearchPage(query)}
                  className="text-sm"
                >
                  Voir tous les résultats
                </Button>
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
            /* État de recherche avec suggestions */
            <div className="space-y-4">
              {isSearching ? (
                <div className="text-center py-8">
                  <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-2"></div>
                  <p className="text-muted-foreground">Recherche de suggestions...</p>
                </div>
              ) : searchSuggestions.length > 0 ? (
                <div className="p-4 space-y-4">
                  {/* Suggestions de recherche */}
                  <div className="space-y-2">
                    <h3 className="font-medium text-sm text-muted-foreground uppercase tracking-wide">
                      Suggestions de recherche
                    </h3>
                    <div className="space-y-1">
                      {searchSuggestions.map((suggestion, index) => (
                        <Button
                          key={index}
                          variant="ghost"
                          size="sm"
                          onClick={() => navigateToSearchPage(suggestion.title)}
                          className="w-full justify-start text-left h-auto py-3 px-4 hover:bg-muted/50"
                        >
                          <Search className="h-4 w-4 mr-3 text-muted-foreground" />
                          <div className="flex-1">
                            <div className="font-medium">{suggestion.title}</div>
                            {suggestion.description && (
                              <div className="text-xs text-muted-foreground mt-1">
                                {suggestion.description}
                              </div>
                            )}
                          </div>
                        </Button>
                      ))}
                    </div>
                  </div>

                  {/* Bouton pour voir tous les résultats */}
                  <div className="pt-4 border-t">
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => navigateToSearchPage(query)}
                      className="w-full"
                    >
                      Voir tous les résultats pour "{query}"
                    </Button>
                  </div>
                </div>
              ) : query.length >= 2 ? (
                /* Aucune suggestion trouvée */
                <div className="text-center py-8">
                  <Search className="h-12 w-12 text-muted-foreground mx-auto mb-2" />
                  <p className="text-muted-foreground">Aucune suggestion trouvée pour "{query}"</p>
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