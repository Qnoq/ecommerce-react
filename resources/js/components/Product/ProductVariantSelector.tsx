import React from 'react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

interface VariantOption {
  value: string
  display_name: string
  color_code?: string
  sort_order: number
}

interface ProductVariantSelectorProps {
  attributes: Record<string, VariantOption[]>
  selectedVariants: Record<string, string>
  onVariantChange: (type: string, value: string) => void
}

export default function ProductVariantSelector({ 
  attributes, 
  selectedVariants, 
  onVariantChange 
}: ProductVariantSelectorProps) {
  
  const renderVariantSelector = (attributeName: string, options: VariantOption[]) => {
    if (options.length <= 1) return null

    const getDisplayName = (attributeName: string) => {
      const nameMap: Record<string, string> = {
        'couleur': 'Couleur',
        'taille': 'Taille', 
        'pointure': 'Pointure',
        'stockage': 'Stockage',
        'processeur': 'Processeur',
        'ram': 'Mémoire RAM',
        'color': 'Couleur',
        'size': 'Taille',
        'storage': 'Stockage'
      }
      
      return nameMap[attributeName.toLowerCase()] || 
             attributeName.charAt(0).toUpperCase() + attributeName.slice(1)
    }

    const renderColorVariant = (option: VariantOption, isSelected: boolean) => (
      <button
        key={option.value}
        onClick={() => onVariantChange(attributeName, option.value)}
        className={cn(
          "w-8 h-8 rounded-full border-2 relative overflow-hidden transition-all",
          isSelected 
            ? "border-primary ring-2 ring-primary/20 scale-110" 
            : "border-muted-foreground/20 hover:border-muted-foreground/40"
        )}
        style={{
          backgroundColor: option.color_code || getColorValue(option.value)
        }}
        title={option.display_name || option.value}
      >
        {isSelected && (
          <div className="absolute inset-0 flex items-center justify-center">
            <div className="w-2 h-2 bg-white rounded-full shadow-sm" />
          </div>
        )}
      </button>
    )

    const renderStandardVariant = (option: VariantOption, isSelected: boolean) => (
      <Button
        key={option.value}
        variant={isSelected ? "default" : "outline"}
        size="sm"
        onClick={() => onVariantChange(attributeName, option.value)}
        className={cn(
          "transition-all",
          isSelected && "ring-2 ring-primary/20"
        )}
      >
        {option.display_name || option.value}
      </Button>
    )

    const getColorValue = (colorName: string): string => {
      const colorMap: Record<string, string> = {
        'noir': '#000000',
        'black': '#000000',
        'blanc': '#ffffff',
        'white': '#ffffff',
        'rouge': '#dc2626',
        'red': '#dc2626',
        'bleu': '#2563eb',
        'blue': '#2563eb',
        'vert': '#16a34a',
        'green': '#16a34a',
        'jaune': '#eab308',
        'yellow': '#eab308',
        'rose': '#ec4899',
        'pink': '#ec4899',
        'gris': '#6b7280',
        'gray': '#6b7280',
        'argent': '#9ca3af',
        'silver': '#9ca3af',
        'or': '#f59e0b',
        'gold': '#f59e0b',
        'titane': '#64748b',
        'titanium': '#64748b',
        'bordeaux': '#7c2d12',
        'emeraude': '#059669',
        'emerald': '#059669',
        'bleu marine': '#1e3a8a',
        'navy': '#1e3a8a',
        'vert menthe': '#10b981',
        'mint': '#10b981',
        'rose poudré': '#f9a8d4',
        'powder pink': '#f9a8d4',
        'bleu nuit': '#1e1b4b',
        'night blue': '#1e1b4b',
        'gris sidéral': '#374151',
        'space gray': '#374151',
        'titane naturel': '#78716c',
        'natural titanium': '#78716c',
        'titane blanc': '#f3f4f6',
        'white titanium': '#f3f4f6',
        'titane noir': '#111827',
        'black titanium': '#111827',
        'titane désert': '#a3a3a3',
        'desert titanium': '#a3a3a3',
        'vert sarcelle': '#0d9488',
        'teal': '#0d9488',
        'bleu outremer': '#1d4ed8',
        'ultramarine': '#1d4ed8'
      }
      
      return colorMap[colorName.toLowerCase()] || '#6b7280'
    }

    const isColorAttribute = attributeName.toLowerCase().includes('couleur') || 
                             attributeName.toLowerCase().includes('color')

    return (
      <div key={attributeName} className="space-y-3">
        <div className="flex items-center justify-between">
          <label className="text-sm font-medium">
            {getDisplayName(attributeName)}:
          </label>
          {selectedVariants[attributeName] && (
            <Badge variant="secondary" className="text-xs">
              {options.find(opt => opt.value === selectedVariants[attributeName])?.display_name || 
               selectedVariants[attributeName]}
            </Badge>
          )}
        </div>
        
        <div className={cn(
          "flex flex-wrap gap-2",
          isColorAttribute ? "gap-3" : "gap-2"
        )}>
          {options.map((option) => {
            const isSelected = selectedVariants[attributeName] === option.value
            
            return isColorAttribute 
              ? renderColorVariant(option, isSelected)
              : renderStandardVariant(option, isSelected)
          })}
        </div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {Object.entries(attributes).map(([attributeName, options]) => 
        renderVariantSelector(attributeName, options)
      )}
    </div>
  )
}