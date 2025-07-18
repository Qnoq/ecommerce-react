import React, { useState } from 'react'
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { ShoppingBag, Plus, Minus } from 'lucide-react'
import { router } from '@inertiajs/react'
import { useCart } from '@/contexts/CartContext'
import { toast } from 'sonner'
import { formatPrice } from '@/utils/price'
import ProductVariantSelector from './ProductVariantSelector'
import type { Product } from '@/types/index.d.ts'

interface VariantSheetProps {
  isOpen: boolean
  onClose: () => void
  product: Product
  variants?: any[]
  availableAttributes?: Record<string, any[]>
}

export default function VariantSheet({ 
  isOpen, 
  onClose, 
  product, 
  variants = [], 
  availableAttributes = {} 
}: VariantSheetProps) {
  const [selectedVariants, setSelectedVariants] = useState<Record<string, string>>({})
  const [quantity, setQuantity] = useState(1)
  const [isAdding, setIsAdding] = useState(false)
  const { cartCount, updateCartCount } = useCart()

  // TEMPORAIRE : Données de test pour les variantes
  const testVariants = variants.length > 0 ? variants : [
    { 
      id: 1, 
      name: 'Variant 1', 
      price: product.price, 
      stock_quantity: 10,
      attributes: [
        { attribute_name: 'couleur', attribute_value: 'Rouge', color_code: '#FF0000' },
        { attribute_name: 'taille', attribute_value: 'M' }
      ]
    },
    { 
      id: 2, 
      name: 'Variant 2', 
      price: product.price + 10, 
      stock_quantity: 5,
      attributes: [
        { attribute_name: 'couleur', attribute_value: 'Bleu', color_code: '#0000FF' },
        { attribute_name: 'taille', attribute_value: 'L' }
      ]
    }
  ];

  const testAttributes = Object.keys(availableAttributes).length > 0 ? availableAttributes : {
    'couleur': [
      { value: 'Rouge', color_code: '#dc2626', display_name: 'Rouge', sort_order: 1 },
      { value: 'Bleu', color_code: '#2563eb', display_name: 'Bleu', sort_order: 2 },
      { value: 'Vert', color_code: '#16a34a', display_name: 'Vert', sort_order: 3 }
    ],
    'taille': [
      { value: 'S', display_name: 'Small', sort_order: 1 },
      { value: 'M', display_name: 'Medium', sort_order: 2 },
      { value: 'L', display_name: 'Large', sort_order: 3 },
      { value: 'XL', display_name: 'Extra Large', sort_order: 4 }
    ]
  };

  // Réinitialiser les sélections quand le sheet s'ouvre/ferme
  React.useEffect(() => {
    if (isOpen) {
      setSelectedVariants({})
      setQuantity(1)
    }
  }, [isOpen])

  // Trouver la variante sélectionnée (utiliser les données de test)
  const selectedVariant = testVariants.find(variant => {
    const requiredAttributes = Object.keys(testAttributes)
    if (requiredAttributes.length === 0) return variant.is_default
    
    return variant.attributes?.every((attr: any) => 
      selectedVariants[attr.attribute_name] === attr.attribute_value
    )
  })

  // Vérifier si toutes les variantes requises sont sélectionnées
  const allVariantsSelected = Object.keys(testAttributes).every(
    attrName => selectedVariants[attrName]
  )

  const handleVariantSelect = (attributeName: string, value: string) => {
    setSelectedVariants(prev => ({
      ...prev,
      [attributeName]: value
    }))
  }

  const handleQuantityChange = (newQuantity: number) => {
    if (newQuantity >= 1 && newQuantity <= 99) {
      setQuantity(newQuantity)
    }
  }

  const handleAddToCart = () => {
    if (!allVariantsSelected && Object.keys(testAttributes).length > 0) {
      toast.error('Veuillez sélectionner toutes les options')
      return
    }

    setIsAdding(true)

    const postData = {
      product_uuid: product.uuid,
      product_variant_id: selectedVariant?.id || null,
      quantity: quantity,
      variants: selectedVariants
    }

    router.post(route('cart.store'), postData, {
      preserveScroll: true,
      onSuccess: () => {
        updateCartCount(cartCount + quantity)
        onClose()
        
        // Toast avec bouton annuler
        toast.success(`${product.name} ajouté au panier`, {
          description: `Quantité : ${quantity}`,
          action: {
            label: "Annuler",
            onClick: () => {
              router.delete(route('cart.remove.last'), {
                preserveScroll: true,
                onSuccess: () => {
                  updateCartCount(cartCount)
                  toast.info("Article retiré du panier")
                },
                onError: () => {
                  toast.error("Erreur lors de la suppression")
                }
              })
            }
          }
        })
      },
      onError: () => {
        toast.error("Erreur lors de l'ajout au panier")
        setIsAdding(false)
      },
      onFinish: () => {
        setIsAdding(false)
      }
    })
  }

  return (
    <Sheet open={isOpen} onOpenChange={onClose}>
      <SheetContent side="bottom" className="h-auto max-h-[80vh]">
        <SheetHeader className="text-left">
          {/* Layout Allbirds : Image à gauche, infos à droite */}
          <div className="flex gap-4 items-start">
            {/* Image du produit - petite à gauche */}
            <div className="w-20 h-20 overflow-hidden bg-muted flex-shrink-0">
              {product.featured_image ? (
                <img 
                  src={selectedVariant?.featured_image || product.featured_image} 
                  alt={product.name}
                  className="w-full h-full object-cover"
                />
              ) : (
                <div className="w-full h-full flex items-center justify-center">
                  <ShoppingBag className="h-6 w-6 text-muted-foreground" />
                </div>
              )}
            </div>

            {/* Infos produit à droite */}
            <div className="flex-1 min-w-0">
              <SheetTitle className="text-lg font-medium line-clamp-2">
                {product.name}
              </SheetTitle>
              <p className="text-xl font-bold text-primary mt-1">
                {selectedVariant?.price ? formatPrice(selectedVariant.price) : formatPrice(product.price)}
              </p>
            </div>
          </div>
        </SheetHeader>

        <div className="px-4 py-6 space-y-4">
          {/* Sélection des variantes - Réutilisation du composant existant */}
          <ProductVariantSelector
            attributes={testAttributes}
            selectedVariants={selectedVariants}
            onVariantChange={handleVariantSelect}
          />

          {/* Sélecteur de quantité */}
          <div className="space-y-2">
            <h4 className="font-medium text-sm uppercase tracking-wide text-muted-foreground">
              Quantité
            </h4>
            <div className="flex items-center gap-3">
              <Button
                variant="outline"
                size="icon"
                onClick={() => handleQuantityChange(quantity - 1)}
                disabled={quantity <= 1}
                className="h-10 w-10"
              >
                <Minus className="h-4 w-4" />
              </Button>
              <span className="text-lg font-medium min-w-[3rem] text-center">
                {quantity}
              </span>
              <Button
                variant="outline"
                size="icon"
                onClick={() => handleQuantityChange(quantity + 1)}
                disabled={quantity >= 99}
                className="h-10 w-10"
              >
                <Plus className="h-4 w-4" />
              </Button>
            </div>
          </div>

          {/* Stock info */}
          {selectedVariant?.stock_quantity !== undefined && (
            <div className="flex items-center justify-between text-sm">
              <span className="text-muted-foreground">Stock disponible</span>
              <Badge variant={selectedVariant.stock_quantity > 10 ? "secondary" : "destructive"}>
                {selectedVariant.stock_quantity > 0 
                  ? `${selectedVariant.stock_quantity} en stock`
                  : 'Rupture de stock'
                }
              </Badge>
            </div>
          )}
        </div>

        {/* Bouton d'ajout au panier */}
        <div className="p-4 bg-background border-t mt-4">
          <Button
            onClick={handleAddToCart}
            disabled={isAdding || (!allVariantsSelected && Object.keys(testAttributes).length > 0)}
            className="w-full h-12 text-base font-medium"
            size="lg"
          >
            {isAdding ? (
              <div className="flex items-center gap-2">
                <div className="animate-spin h-4 w-4 border-2 border-white border-t-transparent" />
                Ajout en cours...
              </div>
            ) : (
              <div className="flex items-center gap-2">
                <ShoppingBag className="h-4 w-4" />
                Ajouter au panier - {formatPrice((selectedVariant?.price || product.price) * quantity)}
              </div>
            )}
          </Button>
        </div>
      </SheetContent>
    </Sheet>
  )
}