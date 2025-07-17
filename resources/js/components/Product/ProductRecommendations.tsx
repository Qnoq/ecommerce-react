import React from 'react'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import { Button } from '@/components/ui/button'
import ProductCardCompact from '@/components/Product/ProductCardCompact'
import type { Product } from '@/types/index.d.ts'

interface ProductRecommendationsProps {
  products: Product[]
  title: string
}

export default function ProductRecommendations({ products, title }: ProductRecommendationsProps) {
  if (products.length === 0) return null

  const scrollContainer = React.useRef<HTMLDivElement>(null)

  const scroll = (direction: 'left' | 'right') => {
    if (!scrollContainer.current) return
    
    const scrollAmount = 320 // Width of one product card + gap
    const newScrollPosition = scrollContainer.current.scrollLeft + 
      (direction === 'left' ? -scrollAmount : scrollAmount)
    
    scrollContainer.current.scrollTo({
      left: newScrollPosition,
      behavior: 'smooth'
    })
  }

  const handleProductClick = (uuid: string, name: string) => {
    // Navigation vers la page produit
    window.location.href = `/products/${uuid}`
  }

  const handleAddToCart = (product: Product) => {
    console.log('Ajout au panier depuis recommandations:', product.name)
    // TODO: Implémenter l'ajout au panier
  }

  return (
    <section className="mt-16">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-2xl font-bold">{title}</h2>
        
        {products.length > 4 && (
          <div className="flex gap-2">
            <Button
              variant="outline"
              size="icon"
              onClick={() => scroll('left')}
              className="h-8 w-8"
            >
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <Button
              variant="outline"
              size="icon"
              onClick={() => scroll('right')}
              className="h-8 w-8"
            >
              <ChevronRight className="h-4 w-4" />
            </Button>
          </div>
        )}
      </div>

      <div className="relative">
        <div
          ref={scrollContainer}
          className="flex gap-4 overflow-x-auto scroll-smooth pb-4 scrollbar-hide"
        >
          {products.map((product) => (
            <div key={product.uuid} className="flex-shrink-0 w-72">
              <ProductCardCompact
                product={product}
                onNavigate={handleProductClick}
                onAddToCart={handleAddToCart}
              />
            </div>
          ))}
        </div>
        
        {/* Gradient fade for scroll indication */}
        {products.length > 4 && (
          <>
            <div className="absolute top-0 left-0 bottom-0 w-8 bg-gradient-to-r from-background to-transparent pointer-events-none" />
            <div className="absolute top-0 right-0 bottom-0 w-8 bg-gradient-to-l from-background to-transparent pointer-events-none" />
          </>
        )}
      </div>

    </section>
  )
}