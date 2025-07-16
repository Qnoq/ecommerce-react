import React from 'react'
import { Star, ShoppingBag } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { formatPricePrefix, hasDiscount } from '@/utils/price'
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
  const handleCardClick = () => {
    onNavigate(product.uuid, product.name)
  }

  const handleAddToCart = (e: React.MouseEvent) => {
    e.stopPropagation()
    if (onAddToCart) {
      onAddToCart(product)
    }
  }

  const renderStars = (rating: number) => {
    return [...Array(5)].map((_, i) => (
      <Star
        key={i}
        className={cn(
          "h-3 w-3",
          i < Math.floor(rating) 
            ? "fill-yellow-400 text-yellow-400" 
            : "text-gray-300"
        )}
      />
    ))
  }

  return (
    <div
      className={cn(
        "bg-card border border-border rounded-lg overflow-hidden hover:shadow-md transition-shadow cursor-pointer",
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
        
        {/* Badges produit */}
        <div className="absolute top-2 left-2 space-y-1">
          {product.badges?.map((badge, index) => (
            <Badge key={index} variant="secondary" className="text-xs">
              {badge}
            </Badge>
          ))}
          {product.badge && (
            <Badge variant="secondary" className="text-xs">
              {product.badge}
            </Badge>
          )}
        </div>
      </div>

      {/* Informations produit */}
      <div className="p-3 space-y-2">
        {/* Évaluation */}
        {product.rating && (
          <div className="flex items-center gap-1">
            <div className="flex">
              {renderStars(product.rating)}
            </div>
            <span className="text-xs text-muted-foreground">
              ({product.review_count || product.reviewCount || 0})
            </span>
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
  )
}