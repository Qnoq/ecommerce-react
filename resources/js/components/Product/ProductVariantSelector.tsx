import React from 'react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

interface ProductVariantSelectorProps {
  attributes: Record<string, any>
  selectedVariants: Record<string, string>
  onVariantChange: (type: string, value: string) => void
}

export default function ProductVariantSelector({ 
  attributes, 
  selectedVariants, 
  onVariantChange 
}: ProductVariantSelectorProps) {
  
  // Types de variantes à afficher
  const variantTypes = ['color', 'size', 'storage', 'memory']
  
  const renderVariantSelector = (type: string, values: string[] | string) => {
    const valueArray = Array.isArray(values) ? values : [values]
    
    if (valueArray.length <= 1) return null

    const getDisplayName = (type: string) => {
      switch (type) {
        case 'color': return 'Couleur'
        case 'size': return 'Taille'
        case 'storage': return 'Stockage'
        case 'memory': return 'Mémoire'
        default: return type.charAt(0).toUpperCase() + type.slice(1)
      }
    }

    const renderColorVariant = (value: string, isSelected: boolean) => (
      <button
        key={value}
        onClick={() => onVariantChange(type, value)}
        className={cn(
          "w-8 h-8 rounded-full border-2 relative overflow-hidden transition-all",
          isSelected 
            ? "border-primary ring-2 ring-primary/20 scale-110" 
            : "border-muted-foreground/20 hover:border-muted-foreground/40"
        )}
        style={{
          backgroundColor: getColorValue(value)
        }}
        title={value}
      >
        {isSelected && (
          <div className="absolute inset-0 flex items-center justify-center">
            <div className="w-2 h-2 bg-white rounded-full shadow-sm" />
          </div>
        )}
      </button>
    )

    const renderStandardVariant = (value: string, isSelected: boolean) => (
      <Button
        key={value}
        variant={isSelected ? "default" : "outline"}
        size="sm"
        onClick={() => onVariantChange(type, value)}
        className={cn(
          "transition-all",
          isSelected && "ring-2 ring-primary/20"
        )}
      >
        {value}
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

    return (
      <div key={type} className="space-y-3">
        <div className="flex items-center justify-between">
          <label className="text-sm font-medium">
            {getDisplayName(type)}:
          </label>
          {selectedVariants[type] && (
            <Badge variant="secondary" className="text-xs">
              {selectedVariants[type]}
            </Badge>
          )}
        </div>
        
        <div className={cn(
          "flex flex-wrap gap-2",
          type === 'color' ? "gap-3" : "gap-2"
        )}>
          {valueArray.map((value) => {
            const isSelected = selectedVariants[type] === value
            
            return type === 'color' 
              ? renderColorVariant(value, isSelected)
              : renderStandardVariant(value, isSelected)
          })}
        </div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {variantTypes.map((type) => {
        if (!attributes[type]) return null
        return renderVariantSelector(type, attributes[type])
      })}
    </div>
  )
}