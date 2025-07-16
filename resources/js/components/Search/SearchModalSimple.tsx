import React from 'react'
import { Search, X } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Sheet, SheetContent, SheetHeader } from '@/components/ui/sheet'
import { useSearchContext } from '@/contexts/SearchContext'
import { useSearchModal } from '@/hooks/useSearchModal'
import { PROMOTIONAL_CARDS, POPULAR_CATEGORIES } from '@/data/promotionalCards'

interface SearchModalSimpleProps {
  isOpen: boolean
  onClose: () => void
  placeholder?: string
}

export default function SearchModalSimple({
  isOpen,
  onClose,
  placeholder = "Recherchez une couleur"
}: SearchModalSimpleProps) {
  const { recentSearches } = useSearchContext()
  const {
    query,
    inputRef,
    handleInputChange,
    handleKeyDown,
    clearQuery,
    navigateToSearchPage,
    navigateToUrl,
    performSearch
  } = useSearchModal({ isOpen, onClose })

  // Pas besoin de useEffect, tout est géré par useSearchModal et SearchContext

  // La logique de recherche est gérée par useSearchModal

  // Utilisation des données externalisées

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

          {/* Bouton de recherche si query non vide */}
          {query.length >= 2 && (
            <Button 
              onClick={() => performSearch(query)}
              className="w-full"
              size="lg"
            >
              <Search className="h-4 w-4 mr-2" />
              Rechercher "{query}"
            </Button>
          )}
        </SheetHeader>

        {/* Contenu scrollable */}
        <div className="flex-1 overflow-y-auto bg-background">
          <div className="p-4 space-y-4">
            {/* Recherches récentes si disponibles */}
            {recentSearches.length > 0 && (
              <div className="space-y-2">
                <h3 className="font-medium text-sm text-muted-foreground uppercase tracking-wide">
                  Recherches récentes
                </h3>
                <div className="flex flex-wrap gap-2">
                  {recentSearches.slice(0, 5).map((search, index) => (
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
              <h3 className="font-medium text-sm text-muted-foreground uppercase tracking-wide">
                Découvrez nos actualités
              </h3>
              
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

            {/* Suggestions de catégories populaires */}
            <div className="space-y-2">
              <h3 className="font-medium text-sm text-muted-foreground uppercase tracking-wide">
                Catégories populaires
              </h3>
              <div className="grid grid-cols-2 gap-3">
                {POPULAR_CATEGORIES.map((category) => (
                  <Button
                    key={category.slug}
                    variant="outline"
                    className="justify-start h-auto p-3"
                    onClick={() => navigateToUrl(`/products?category=${category.slug}`)}
                  >
                    {category.name}
                  </Button>
                ))}
              </div>
            </div>
          </div>
        </div>
      </SheetContent>
    </Sheet>
  )
} 