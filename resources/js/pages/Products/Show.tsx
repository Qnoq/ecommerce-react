import React, { useState } from 'react'
import { Head, useForm, router } from '@inertiajs/react'
import EcommerceLayout from '@/layouts/EcommerceLayout'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { 
  Heart, 
  Share2, 
  Star, 
  ShoppingBag, 
  Truck, 
  Shield, 
  RotateCcw,
  ChevronDown,
  ChevronUp,
  Plus,
  Minus
} from 'lucide-react'
import { cn } from '@/lib/utils'
import { formatPricePrefix, hasDiscount, calculateDiscountPercent } from '@/utils/price'
import type { Product } from '@/types/index.d.ts'
import { useCart } from '@/contexts/CartContext'
import { usePage } from '@inertiajs/react'

// Components
import ProductImageGallery from '@/components/Product/ProductImageGallery'
import ProductVariantSelector from '@/components/Product/ProductVariantSelector'
import ProductReviews from '@/components/Product/ProductReviews'
import ProductRecommendations from '@/components/Product/ProductRecommendations'
import ProductSpecifications from '@/components/Product/ProductSpecifications'

interface ProductShowProps {
  product: Product & {
    attributes?: Record<string, any>
    reviews?: any[]
    averageRating?: number
    reviewsCount?: number
  }
  variants: any[]
  availableAttributes: Record<string, any[]>
  relatedProducts: Product[]
  maxStock: number
}

interface SelectedVariants {
  [key: string]: string
}

export default function ProductShow({ product, variants, availableAttributes, relatedProducts, maxStock }: ProductShowProps) {
  // States
  const [selectedVariants, setSelectedVariants] = useState<SelectedVariants>({})
  const [quantity, setQuantity] = useState(1)
  const [isWishlisted, setIsWishlisted] = useState(false)
  const [showDescription, setShowDescription] = useState(true)
  const [showSpecifications, setShowSpecifications] = useState(false)
  const [showShipping, setShowShipping] = useState(false)
  const [isAddingToCart, setIsAddingToCart] = useState(false)


  // Cart context et shared props
  const { cartCount, updateCartCount, refreshCartCount } = useCart()
  const { props } = usePage()

  // Vérifier si toutes les variantes requises sont sélectionnées
  const requiredAttributes = Object.keys(availableAttributes)
  const allVariantsSelected = requiredAttributes.length === 0 || 
    requiredAttributes.every(attr => selectedVariants[attr])

  // Trouver la variante correspondante aux sélections
  const selectedVariant = variants.find(variant => {
    if (requiredAttributes.length === 0) return variant.is_default
    
    return variant.attributes.every(attr => 
      selectedVariants[attr.attribute_name] === attr.attribute_value
    )
  })

  // Calculer le stock disponible pour la variante sélectionnée
  const availableStock = selectedVariant ? selectedVariant.stock_quantity : maxStock

  // Form pour l'ajout au panier
  const { data, setData, post, processing, errors } = useForm({
    product_uuid: product.uuid,
    product_variant_id: null,
    quantity: 1,
    variants: selectedVariants
  })

  // Handlers
  const handleVariantChange = (type: string, value: string) => {
    const newVariants = { ...selectedVariants, [type]: value }
    setSelectedVariants(newVariants)
    setData('variants', newVariants)
  }

  const handleQuantityChange = (delta: number) => {
    const newQuantity = Math.max(1, Math.min(availableStock, quantity + delta))
    setQuantity(newQuantity)
    setData('quantity', newQuantity)
  }

  const handleAddToCart = () => {
    // Éviter les doubles clics
    if (isAddingToCart) return
    
    // Vérifier que toutes les variantes sont sélectionnées
    if (!allVariantsSelected) {
      alert('Veuillez sélectionner toutes les options du produit avant de l\'ajouter au panier.')
      return
    }

    // Recalculer la variante sélectionnée au moment de l'ajout
    const currentSelectedVariant = variants.find(variant => {
      if (requiredAttributes.length === 0) return variant.is_default
      
      return variant.attributes.every(attr => 
        selectedVariants[attr.attribute_name] === attr.attribute_value
      )
    })


    setIsAddingToCart(true)

    const postData = {
      product_uuid: product.uuid,
      product_variant_id: currentSelectedVariant?.id || null,
      quantity: quantity,
      variants: selectedVariants
    }
    

    // Utiliser router.post directement pour éviter les problèmes de sérialisation
    router.post(route('cart.store'), postData, {
      preserveScroll: true,
      onSuccess: (page) => {
        // Mettre à jour le compteur avec la quantité ajoutée
        updateCartCount(cartCount + quantity)
        // Remettre la quantité à 1 pour la prochaine sélection
        setQuantity(1)
        setIsAddingToCart(false)
      },
      onError: (errors) => {
        setIsAddingToCart(false)
      },
      onFinish: () => {
        setIsAddingToCart(false)
      }
    })
  }

  const handleWishlistToggle = () => {
    setIsWishlisted(prev => !prev)
    // TODO: Implémenter la wishlist
  }

  const handleShare = () => {
    if (navigator.share) {
      navigator.share({
        title: product.name,
        text: product.short_description,
        url: window.location.href
      })
    }
  }

  // Compute current price (with variants if applicable)
  const currentPrice = product.price
  const originalPrice = product.original_price
  const productHasDiscount = hasDiscount(originalPrice, currentPrice)
  const discountPercent = calculateDiscountPercent(originalPrice, currentPrice)

  // Product images
  const productImages = Array.isArray(product.images) ? product.images : 
    product.featured_image ? [product.featured_image] : []

  return (
    <EcommerceLayout>
      <Head title={`${product.name} | ShopLux`} />
      
      <div className="min-h-screen bg-background">
        {/* Breadcrumb */}
        <div className="border-b bg-muted/20">
          <div className="container mx-auto px-4 py-3">
            <nav className="flex items-center text-sm text-muted-foreground">
              <a href="/" className="hover:text-foreground">Accueil</a>
              <span className="mx-2">›</span>
              <a href="/products" className="hover:text-foreground">Produits</a>
              <span className="mx-2">›</span>
              <span className="text-foreground font-medium">{product.name}</span>
            </nav>
          </div>
        </div>

        <div className="container mx-auto px-4 py-8">
          <div className="grid lg:grid-cols-2 gap-8 lg:gap-12">
            {/* Image Gallery - Left Column */}
            <div className="space-y-4">
              <ProductImageGallery 
                images={productImages} 
                productName={product.name}
              />
            </div>

            {/* Product Info - Right Column */}
            <div className="space-y-6">
              {/* Product Header */}
              <div className="space-y-4">
                <div className="flex items-start justify-between">
                  <div className="space-y-2">
                    <h1 className="text-2xl lg:text-3xl font-bold leading-tight">
                      {product.name}
                    </h1>
                    <p className="text-muted-foreground text-lg">
                      {product.short_description}
                    </p>
                  </div>
                  
                  <div className="flex gap-2">
                    <Button 
                      variant="outline" 
                      size="icon"
                      onClick={handleWishlistToggle}
                      className={cn(
                        "transition-colors",
                        isWishlisted && "text-red-500 border-red-200 bg-red-50"
                      )}
                    >
                      <Heart className={cn("h-4 w-4", isWishlisted && "fill-current")} />
                    </Button>
                    <Button variant="outline" size="icon" onClick={handleShare}>
                      <Share2 className="h-4 w-4" />
                    </Button>
                  </div>
                </div>

                {/* Reviews */}
                {product.averageRating && (
                  <div className="flex items-center gap-2">
                    <div className="flex items-center">
                      {[...Array(5)].map((_, i) => (
                        <Star 
                          key={i}
                          className={cn(
                            "h-4 w-4",
                            i < Math.floor(product.averageRating!) 
                              ? "fill-yellow-400 text-yellow-400" 
                              : "text-muted-foreground"
                          )}
                        />
                      ))}
                    </div>
                    <span className="text-sm text-muted-foreground">
                      {product.averageRating} ({product.reviewsCount} avis)
                    </span>
                  </div>
                )}
              </div>

              {/* Price */}
              <div className="space-y-2">
                <div className="flex items-center gap-3">
                  <span className="text-3xl font-bold text-primary">
                    {formatPricePrefix(currentPrice)}
                  </span>
                  {productHasDiscount && (
                    <>
                      <span className="text-lg text-muted-foreground line-through">
                        {formatPricePrefix(originalPrice)}
                      </span>
                      <Badge variant="destructive" className="text-xs">
                        -{discountPercent}%
                      </Badge>
                    </>
                  )}
                </div>
                <p className="text-sm text-muted-foreground">
                  Prix TTC, livraison non comprise
                </p>
              </div>

              {/* Variants Selection */}
              {Object.keys(availableAttributes).length > 0 && (
                <ProductVariantSelector
                  attributes={availableAttributes}
                  selectedVariants={selectedVariants}
                  onVariantChange={handleVariantChange}
                />
              )}

              {/* Quantity & Add to Cart */}
              <div className="space-y-4">
                <div className="flex items-center gap-4">
                  <label className="text-sm font-medium">Quantité:</label>
                  <div className="flex items-center border rounded-lg">
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-10 w-10 rounded-none border-r"
                      onClick={() => handleQuantityChange(-1)}
                      disabled={quantity <= 1}
                    >
                      <Minus className="h-4 w-4" />
                    </Button>
                    <span className="w-16 text-center py-2 font-medium">
                      {quantity}
                    </span>
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-10 w-10 rounded-none border-l"
                      onClick={() => handleQuantityChange(1)}
                      disabled={quantity >= availableStock}
                    >
                      <Plus className="h-4 w-4" />
                    </Button>
                  </div>
                  
                  {/* Stock Info */}
                  <div className="text-sm text-muted-foreground">
                    {availableStock > 0 ? (
                      availableStock <= 5 ? (
                        <span className="text-orange-600">
                          Plus que {availableStock} en stock
                        </span>
                      ) : (
                        <span className="text-green-600">
                          En stock ({availableStock} disponibles)
                        </span>
                      )
                    ) : (
                      <span className="text-red-600">
                        Rupture de stock
                      </span>
                    )}
                  </div>
                </div>

                <Button 
                  size="lg" 
                  className={`w-full h-12 text-base font-semibold ${
                    !allVariantsSelected ? 'opacity-50 cursor-not-allowed' : ''
                  }`}
                  onClick={handleAddToCart}
                  disabled={!allVariantsSelected || isAddingToCart || availableStock === 0}
                >
                  <ShoppingBag className="mr-2 h-5 w-5" />
                  {isAddingToCart ? 'Ajout en cours...' : 
                   availableStock === 0 ? 'Rupture de stock' :
                   !allVariantsSelected ? 'Sélectionnez les options' : 'Ajouter au panier'}
                </Button>
                
                {errors.stock && (
                  <p className="text-sm text-red-600 mt-2">{errors.stock}</p>
                )}
                {errors.cart && (
                  <p className="text-sm text-red-600 mt-2">{errors.cart}</p>
                )}
              </div>

              {/* Service Info */}
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div className="flex items-center gap-2 text-sm">
                  <Truck className="h-4 w-4 text-green-600" />
                  <span>Livraison gratuite dès 50€</span>
                </div>
                <div className="flex items-center gap-2 text-sm">
                  <RotateCcw className="h-4 w-4 text-blue-600" />
                  <span>Retour gratuit 30j</span>
                </div>
                <div className="flex items-center gap-2 text-sm">
                  <Shield className="h-4 w-4 text-purple-600" />
                  <span>Paiement sécurisé</span>
                </div>
              </div>
            </div>
          </div>

          {/* Product Details Tabs */}
          <div className="mt-16 space-y-6">
            {/* Description */}
            <Card>
              <CardContent className="p-0">
                <button
                  onClick={() => setShowDescription(prev => !prev)}
                  className="w-full p-6 flex items-center justify-between text-left hover:bg-muted/50 transition-colors"
                >
                  <h3 className="text-lg font-semibold">Description</h3>
                  {showDescription ? (
                    <ChevronUp className="h-5 w-5" />
                  ) : (
                    <ChevronDown className="h-5 w-5" />
                  )}
                </button>
                {showDescription && (
                  <div className="px-6 pb-6">
                    <p className="text-muted-foreground leading-relaxed">
                      {product.description}
                    </p>
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Specifications */}
            {product.attributes && (
              <ProductSpecifications
                attributes={product.attributes}
                isOpen={showSpecifications}
                onToggle={() => setShowSpecifications(prev => !prev)}
              />
            )}

            {/* Shipping Info */}
            <Card>
              <CardContent className="p-0">
                <button
                  onClick={() => setShowShipping(prev => !prev)}
                  className="w-full p-6 flex items-center justify-between text-left hover:bg-muted/50 transition-colors"
                >
                  <h3 className="text-lg font-semibold">Livraison & Retours</h3>
                  {showShipping ? (
                    <ChevronUp className="h-5 w-5" />
                  ) : (
                    <ChevronDown className="h-5 w-5" />
                  )}
                </button>
                {showShipping && (
                  <div className="px-6 pb-6 space-y-4">
                    <div>
                      <h4 className="font-medium mb-2">Livraison</h4>
                      <ul className="text-sm text-muted-foreground space-y-1">
                        <li>• Livraison gratuite dès 50€ d'achat</li>
                        <li>• Livraison standard: 3-5 jours ouvrés (4,99€)</li>
                        <li>• Livraison express: 24-48h (9,99€)</li>
                      </ul>
                    </div>
                    <div>
                      <h4 className="font-medium mb-2">Retours</h4>
                      <ul className="text-sm text-muted-foreground space-y-1">
                        <li>• Retour gratuit sous 30 jours</li>
                        <li>• Échange possible une seule fois</li>
                        <li>• Article en parfait état requis</li>
                      </ul>
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          </div>

          {/* Reviews Section */}
          {product.reviews && (
            <ProductReviews 
              reviews={product.reviews}
              averageRating={product.averageRating}
              reviewsCount={product.reviewsCount}
            />
          )}

          {/* Related Products */}
          {relatedProducts.length > 0 && (
            <ProductRecommendations 
              products={relatedProducts}
              title="Produits similaires"
            />
          )}
        </div>
      </div>
    </EcommerceLayout>
  )
}