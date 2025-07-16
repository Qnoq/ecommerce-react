import React from 'react'
import { Card, CardContent } from '@/components/ui/card'
import { ChevronDown, ChevronUp } from 'lucide-react'

interface ProductSpecificationsProps {
  attributes: Record<string, any>
  isOpen: boolean
  onToggle: () => void
}

export default function ProductSpecifications({ 
  attributes, 
  isOpen, 
  onToggle 
}: ProductSpecificationsProps) {
  
  // Filtrer les attributs techniques (exclure les variantes visuelles)
  const technicalAttributes = Object.entries(attributes).filter(([key]) => 
    !['color', 'size'].includes(key)
  )

  if (technicalAttributes.length === 0) return null

  const formatAttributeValue = (value: any): string => {
    if (Array.isArray(value)) {
      return value.join(', ')
    }
    return String(value)
  }

  const getAttributeDisplayName = (key: string): string => {
    const displayNames: Record<string, string> = {
      'brand': 'Marque',
      'material': 'Matière',
      'care': 'Entretien',
      'storage': 'Stockage',
      'memory': 'Mémoire',
      'processor': 'Processeur',
      'screen': 'Écran',
      'battery': 'Batterie',
      'camera': 'Appareil photo',
      'connectivity': 'Connectivité',
      'weight': 'Poids',
      'dimensions': 'Dimensions',
      'warranty': 'Garantie',
      'origin': 'Origine',
      'certification': 'Certification'
    }
    
    return displayNames[key] || key.charAt(0).toUpperCase() + key.slice(1)
  }

  return (
    <Card>
      <CardContent className="p-0">
        <button
          onClick={onToggle}
          className="w-full p-6 flex items-center justify-between text-left hover:bg-muted/50 transition-colors"
        >
          <h3 className="text-lg font-semibold">Caractéristiques</h3>
          {isOpen ? (
            <ChevronUp className="h-5 w-5" />
          ) : (
            <ChevronDown className="h-5 w-5" />
          )}
        </button>
        
        {isOpen && (
          <div className="px-6 pb-6">
            <div className="grid gap-3">
              {technicalAttributes.map(([key, value]) => (
                <div 
                  key={key} 
                  className="flex flex-col sm:flex-row sm:justify-between py-2 border-b border-muted last:border-0"
                >
                  <dt className="font-medium text-sm sm:text-base min-w-0 sm:w-1/3">
                    {getAttributeDisplayName(key)}
                  </dt>
                  <dd className="text-muted-foreground text-sm sm:text-base sm:w-2/3 sm:text-right">
                    {formatAttributeValue(value)}
                  </dd>
                </div>
              ))}
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  )
}