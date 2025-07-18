import React, { useState } from 'react'
import { Star, ShoppingBag } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { formatPricePrefix, hasDiscount } from '@/utils/price'
import VariantSheet from './VariantSheet'
import type { Product } from '@/types/index.d.ts'

interface ProductCardCompactProps {
  product: Product
  onNavigate: (uuid: string, name: string) => void
  onAddToCart?: (product: Product) => void
  className?: string
}

export default function ProductCardCompact({
  product,
  onNavigate,
  onAddToCart,
  className
}: ProductCardCompactProps) {
  const [isVariantSheetOpen, setIsVariantSheetOpen] = useState(false)

  const handleCardClick = () => {
    onNavigate(product.uuid, product.name)
  }

  const handleAddToCart = (e: React.MouseEvent) => {
    e.stopPropagation()
    
    // Si le produit a des variantes, ouvrir le VariantSheet
    const hasVariants = product.has_variants || 
                       (product.variants && product.variants.length > 1) ||
                       (product.availableAttributes && Object.keys(product.availableAttributes).length > 0);
    
    // TEMPORAIRE : Forcer l'ouverture du VariantSheet jusqu'à ce que le backend envoie les vraies données
    if (true || hasVariants) {
      setIsVariantSheetOpen(true)
      return
    }
    
    // Sinon, ajout direct (fallback)
    if (onAddToCart) {
      onAddToCart(product)
    }
  }


  return (
    <>
      <div
        className={cn(
          "bg-card border border-border overflow-hidden hover:shadow-md transition-shadow cursor-pointer",
          className
        )}
        onClick={handleCardClick}
      >
        {/* Image du produit */}
        <div className="relative aspect-square">
          {product.featured_image ? (
            <img
              src={product.featured_image}
              alt={product.name}
              className="w-full h-full object-cover"
            />
          ) : (
            <div className="w-full h-full bg-muted flex items-center justify-center">
              <ShoppingBag className="h-8 w-8 text-muted-foreground" />
            </div>
          )}
          
          {/* Badge */}
          {(product.badges?.length > 0 || product.badge) && (
            <div className="absolute top-2 left-2">
              <Badge variant="secondary" className="text-[10px] px-1.5 py-0.5 rounded-none font-medium">
                {product.badges?.[0] || product.badge}
              </Badge>
            </div>
          )}
        </div>

        {/* Informations produit */}
        <div className="p-3 space-y-2">
          {/* Rating style Allbirds - avec une seule étoile */}
          {product.rating && (
            <div className="flex items-center gap-1 text-xs text-muted-foreground">
              <Star className="h-3 w-3 fill-yellow-400 text-yellow-400" />
              <span className="font-medium">{Number(product.rating).toFixed(1)}</span>
              <span>({product.review_count || product.reviewCount || 0})</span>
            </div>
          )}

          {/* Nom du produit */}
          <h3 className="font-medium text-sm line-clamp-2 text-card-foreground">
            {product.name}
          </h3>

          {/* Prix */}
          <div className="flex items-center space-x-2">
            <span className="font-bold text-primary">
              {formatPricePrefix(product.price)}
            </span>
            {hasDiscount(product.original_price, product.price) && (
              <span className="text-xs text-muted-foreground line-through">
                {formatPricePrefix(product.original_price)}
              </span>
            )}
          </div>

          {/* Bouton d'ajout au panier */}
          <Button
            size="sm"
            className="w-full"
            onClick={handleAddToCart}
          >
            <ShoppingBag className="h-3 w-3 mr-1" />
            Ajouter
          </Button>
        </div>
      </div>

      {/* VariantSheet - en dehors du div cliquable */}
      <VariantSheet
        isOpen={isVariantSheetOpen}
        onClose={() => setIsVariantSheetOpen(false)}
        product={product}
        variants={product.variants || []}
        availableAttributes={product.availableAttributes || {}}
      />
    </>
  )
}