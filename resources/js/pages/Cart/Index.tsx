import React, { useState, useEffect } from 'react'
import { Head, Link, useForm, router } from '@inertiajs/react'
import EcommerceLayout from '@/layouts/EcommerceLayout'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { 
  ShoppingBag, 
  Plus, 
  Minus, 
  Trash2, 
  ArrowRight,
  ArrowLeft,
  Star
} from 'lucide-react'
import { cn } from '@/lib/utils'
import { formatPricePrefix } from '@/utils/price'
import { useCart } from '@/contexts/CartContext'

interface CartItem {
  product_uuid: string
  item_key: string // Clé unique pour cet item (avec variante)
  quantity: number
  variants: Record<string, string>
  price: string
  added_at: string
  updated_at: string
  subtotal: number
  product: {
    uuid: string
    name: string
    short_description?: string
    featured_image?: string
    slug: string
  }
}

interface CartData {
  items: CartItem[]
  total: number
  quantity: number
  updated_at: string
}

interface CartTotals {
  subtotal: number
  tax: number
  shipping: number
  total: number
}

interface CartIndexProps {
  cart: CartData
  totals: CartTotals
}

export default function CartIndex({ cart, totals }: CartIndexProps) {
  const { updateCartCount } = useCart()
  const { delete: destroy, processing } = useForm()
  
  // State local pour tracker les changements
  const [currentCartQuantity, setCurrentCartQuantity] = useState(cart.quantity)
  
  // Synchroniser le compteur global au chargement de la page
  useEffect(() => {
    updateCartCount(cart.quantity)
  }, [cart.quantity, updateCartCount])
  
  const [deleteDialog, setDeleteDialog] = useState<{
    isOpen: boolean
    itemKey: string | null
    productName: string
    action: 'item' | 'all'
  }>({
    isOpen: false,
    itemKey: null,
    productName: '',
    action: 'item'
  })

  const handleQuantityChange = (itemKey: string, newQuantity: number) => {
    if (newQuantity < 0) return
    
    // Trouver l'item pour calculer la différence
    const item = cart.items.find(item => item.item_key === itemKey)
    if (!item) return
    
    const quantityDiff = newQuantity - item.quantity
    
    // Utiliser router.patch directement avec les données
    router.patch(route('cart.update', encodeURIComponent(itemKey)), {
      quantity: newQuantity
    }, {
      preserveScroll: true,
      onSuccess: () => {
        // Mettre à jour le compteur avec la différence
        const newTotal = currentCartQuantity + quantityDiff
        setCurrentCartQuantity(newTotal)
        updateCartCount(newTotal)
      }
    })
  }

  const handleRemoveItem = (itemKey: string, productName: string) => {
    setDeleteDialog({
      isOpen: true,
      itemKey,
      productName,
      action: 'item'
    })
  }

  const handleClearCart = () => {
    setDeleteDialog({
      isOpen: true,
      itemKey: null,
      productName: '',
      action: 'all'
    })
  }

  const confirmDelete = () => {
    if (deleteDialog.action === 'item' && deleteDialog.itemKey) {
      // Trouver l'item pour connaître sa quantité
      const item = cart.items.find(item => item.item_key === deleteDialog.itemKey)
      const itemQuantity = item ? item.quantity : 0
      
      destroy(route('cart.destroy', encodeURIComponent(deleteDialog.itemKey)), {
        preserveScroll: true,
        onSuccess: () => {
          // Décrémenter le compteur de la quantité de l'item supprimé
          const newTotal = currentCartQuantity - itemQuantity
          setCurrentCartQuantity(newTotal)
          updateCartCount(newTotal)
          setDeleteDialog({ isOpen: false, itemKey: null, productName: '', action: 'item' })
        }
      })
    } else if (deleteDialog.action === 'all') {
      destroy(route('cart.clear'), {
        preserveScroll: true,
        onSuccess: () => {
          // Remettre le compteur à 0
          setCurrentCartQuantity(0)
          updateCartCount(0)
          setDeleteDialog({ isOpen: false, itemKey: null, productName: '', action: 'item' })
        }
      })
    }
  }

  if (!cart.items || cart.items.length === 0) {
    return (
      <EcommerceLayout>
        <Head title="Panier vide | ShopLux" />
        
        <div className="container mx-auto px-4 py-8">
          <div className="text-center py-16">
            <ShoppingBag className="h-24 w-24 mx-auto mb-6 text-muted-foreground" />
            <h1 className="text-3xl font-bold mb-4">Votre panier est vide</h1>
            <p className="text-muted-foreground mb-8 max-w-md mx-auto">
              Découvrez nos produits et ajoutez-les à votre panier pour commencer vos achats.
            </p>
            <Link href={route('products.index')}>
              <Button size="lg" className="px-8">
                Continuer mes achats
              </Button>
            </Link>
          </div>
        </div>
      </EcommerceLayout>
    )
  }

  return (
    <EcommerceLayout>
      <Head title={`Panier (${cart.quantity}) | ShopLux`} />
      
      <div className="container mx-auto px-4 py-8">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold mb-2">Mon panier</h1>
          <p className="text-muted-foreground">
            {cart.quantity} article{cart.quantity > 1 ? 's' : ''} dans votre panier
          </p>
        </div>

        <div className="grid lg:grid-cols-3 gap-8">
          {/* Cart Items */}
          <div className="lg:col-span-2 space-y-4">
            {cart.items.map((item) => (
              <Card key={item.item_key}>
                <CardContent className="p-6">
                  <div className="flex gap-4">
                    {/* Product Image */}
                    <div className="w-24 h-24 flex-shrink-0">
                      <Link href={route('products.show', { slug: item.product.slug, uuid: item.product.uuid })}>
                        {item.product.featured_image ? (
                          <img
                            src={item.product.featured_image}
                            alt={item.product.name}
                            className="w-full h-full object-cover hover:opacity-75 transition-opacity"
                          />
                        ) : (
                          <div className="w-full h-full bg-muted flex items-center justify-center">
                            <ShoppingBag className="h-8 w-8 text-muted-foreground" />
                          </div>
                        )}
                      </Link>
                    </div>

                    {/* Product Info */}
                    <div className="flex-1 min-w-0">
                      <div className="flex justify-between items-start mb-2">
                        <div>
                          <Link 
                            href={route('products.show', { slug: item.product.slug, uuid: item.product.uuid })}
                            className="font-semibold hover:text-primary transition-colors"
                          >
                            {item.product.name}
                          </Link>
                          {item.product.short_description && (
                            <p className="text-sm text-muted-foreground mt-1">
                              {item.product.short_description}
                            </p>
                          )}
                        </div>
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleRemoveItem(item.item_key, item.product.name)}
                          className="text-muted-foreground hover:text-destructive"
                        >
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </div>

                      {/* Variants */}
                      {Object.keys(item.variants).length > 0 && (
                        <div className="flex gap-2 mb-3">
                          {Object.entries(item.variants).map(([key, value]) => (
                            <Badge key={key} variant="secondary" className="text-xs">
                              {key}: {value}
                            </Badge>
                          ))}
                        </div>
                      )}

                      {/* Price and Quantity */}
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                          <span className="text-lg font-bold text-primary">
                            {formatPricePrefix(item.price)}
                          </span>
                          <span className="text-sm text-muted-foreground">
                            × {item.quantity}
                          </span>
                        </div>

                        {/* Quantity Controls */}
                        <div className="flex items-center border">
                          <Button
                            variant="ghost"
                            size="icon"
                            className="h-8 w-8 rounded-none border-r"
                            onClick={() => handleQuantityChange(item.item_key, item.quantity - 1)}
                            disabled={processing || item.quantity <= 1}
                          >
                            <Minus className="h-3 w-3" />
                          </Button>
                          <span className="w-12 text-center py-1 text-sm font-medium">
                            {item.quantity}
                          </span>
                          <Button
                            variant="ghost"
                            size="icon"
                            className="h-8 w-8 rounded-none border-l"
                            onClick={() => handleQuantityChange(item.item_key, item.quantity + 1)}
                            disabled={processing}
                          >
                            <Plus className="h-3 w-3" />
                          </Button>
                        </div>
                      </div>

                      {/* Subtotal */}
                      <div className="mt-3 text-right">
                        <span className="text-lg font-bold">
                          {formatPricePrefix(item.subtotal)}
                        </span>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}

            {/* Cart Actions */}
            <div className="flex justify-between items-center pt-4">
              <Link href={route('products.index')}>
                <Button variant="outline" className="flex items-center gap-2">
                  <ArrowLeft className="h-4 w-4" />
                  Continuer mes achats
                </Button>
              </Link>

              <Button
                variant="outline"
                onClick={handleClearCart}
                className="text-destructive hover:text-destructive"
              >
                Vider le panier
              </Button>
            </div>
          </div>

          {/* Order Summary */}
          <div>
            <Card className="sticky top-8">
              <CardContent className="p-6">
                <h3 className="text-lg font-semibold mb-4">Résumé de la commande</h3>
                
                <div className="space-y-3 mb-6">
                  <div className="flex justify-between">
                    <span>Sous-total</span>
                    <span>{formatPricePrefix(totals.subtotal)}</span>
                  </div>
                  <div className="flex justify-between">
                    <span>TVA (20%)</span>
                    <span>{formatPricePrefix(totals.tax)}</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Livraison</span>
                    <span>
                      {totals.shipping === 0 ? (
                        <span className="text-green-600">Gratuite</span>
                      ) : (
                        formatPricePrefix(totals.shipping)
                      )}
                    </span>
                  </div>
                  <div className="border-t pt-3">
                    <div className="flex justify-between text-lg font-bold">
                      <span>Total</span>
                      <span className="text-primary">{formatPricePrefix(totals.total)}</span>
                    </div>
                  </div>
                </div>

                {totals.shipping === 0 && (
                  <div className="mb-4 p-3 bg-green-50 text-green-700 text-sm">
                    🎉 Livraison gratuite activée !
                  </div>
                )}

                <Button size="lg" className="w-full">
                  Passer la commande
                  <ArrowRight className="ml-2 h-4 w-4" />
                </Button>

                <div className="mt-4 space-y-2 text-sm text-muted-foreground">
                  <div className="flex items-center gap-2">
                    <Star className="h-4 w-4 fill-current text-yellow-400" />
                    <span>Paiement 100% sécurisé</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <ShoppingBag className="h-4 w-4" />
                    <span>Retour gratuit sous 30 jours</span>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>

        {/* Delete Confirmation Dialog */}
        <Dialog open={deleteDialog.isOpen} onOpenChange={(open) => 
          setDeleteDialog({ ...deleteDialog, isOpen: open })
        }>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>
                {deleteDialog.action === 'item' ? 'Supprimer l\'article' : 'Vider le panier'}
              </DialogTitle>
              <DialogDescription>
                {deleteDialog.action === 'item' 
                  ? `Êtes-vous sûr de vouloir supprimer "${deleteDialog.productName}" de votre panier ?`
                  : 'Êtes-vous sûr de vouloir vider entièrement votre panier ? Cette action ne peut pas être annulée.'
                }
              </DialogDescription>
            </DialogHeader>
            <DialogFooter className="space-x-2">
              <Button 
                variant="outline" 
                onClick={() => setDeleteDialog({ ...deleteDialog, isOpen: false })}
              >
                Annuler
              </Button>
              <Button 
                variant="destructive"
                onClick={confirmDelete}
              >
                {deleteDialog.action === 'item' ? 'Supprimer' : 'Vider le panier'}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>
    </EcommerceLayout>
  )
}