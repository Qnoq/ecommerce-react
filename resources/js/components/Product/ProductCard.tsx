import { Link, useForm } from '@inertiajs/react';
import { ShoppingBag, Star } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { formatPrice } from '@/utils/price';
import VariantSheet from './VariantSheet';
import { useState } from 'react';
import { useCart } from '@/contexts/CartContext';
import { toast } from 'sonner';

interface Props {
    product: any;
    onAddToCart?: (productId: number | string) => void;
}

export default function ProductCard({ product, onAddToCart }: Props) {
    const [isVariantSheetOpen, setIsVariantSheetOpen] = useState(false);
    const { cartCount, updateCartCount } = useCart();
    const { post, processing } = useForm({
        product_uuid: product.uuid,
        quantity: 1,
        variants: {}
    });

    const handleAddToCart = (e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Si le produit a des variantes, ouvrir le VariantSheet
        const hasVariants = product.has_variants || 
                           (product.variants && product.variants.length > 1) ||
                           (product.availableAttributes && Object.keys(product.availableAttributes).length > 0);
        
        // TEMPORAIRE : Forcer l'ouverture du VariantSheet jusqu'à ce que le backend envoie les vraies données
        if (true || hasVariants) {
            setIsVariantSheetOpen(true);
            return;
        }
        
        // Sinon, ajout direct au panier
        post(route('cart.store'), {
            preserveScroll: true,
            onSuccess: () => {
                console.log('🔴 ProductCard - Added to cart successfully');
                updateCartCount(cartCount + 1);
                toast.success(`${product.name} ajouté au panier`, {
                    description: `Quantité : 1`,
                    action: {
                        label: "Annuler",
                        onClick: () => {
                            // Logique d'annulation
                        }
                    }
                });
            },
            onError: (errors) => {
                console.error('🔴 ProductCard - Error adding to cart:', errors);
                toast.error("Erreur lors de l'ajout au panier");
            }
        });
    };

    return (
        <>
            <div className="group relative">
                <Link 
                    href={route('products.show', { slug: product.slug || 'product', uuid: product.uuid })}
                    className="block"
                >
                    {/* Image */}
                    <div className="relative aspect-square overflow-hidden bg-muted">
                        {product.featured_image || product.image ? (
                            <img
                                src={product.featured_image || product.image}
                                alt={product.name}
                                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            />
                        ) : (
                            <div className="w-full h-full flex items-center justify-center">
                                <ShoppingBag className="h-12 w-12 text-muted-foreground" />
                            </div>
                        )}
                        
                        {/* Badge */}
                        {product.badges && product.badges.length > 0 && (
                            <div className="absolute top-2 left-2">
                                <Badge variant="secondary" className="text-[10px] px-1.5 py-0.5 rounded-none font-medium">
                                    {product.badges[0]}
                                </Badge>
                            </div>
                        )}
                    </div>

                    {/* Contenu */}
                    <div className="pt-3 space-y-2">
                        {/* Nom */}
                        <h3 className="font-medium text-sm line-clamp-2 text-foreground group-hover:text-primary transition-colors">
                            {product.name}
                        </h3>

                        {/* Rating style Allbirds - avec une seule étoile */}
                        {product.rating && (
                            <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                <Star className="h-3 w-3 fill-yellow-400 text-yellow-400" />
                                <span className="font-medium">{product.rating.toFixed(1)}</span>
                                <span>({product.review_count || product.reviewCount || 0})</span>
                            </div>
                        )}

                        {/* Prix */}
                        <div className="font-semibold text-base text-foreground">
                            {formatPrice(product.price)}
                        </div>

                        {/* Badge rupture de stock seulement si toutes les variantes sont épuisées */}
                        {product.min_stock === 0 && (
                            <Badge variant="destructive" className="text-[10px] px-1.5 py-0.5 rounded-none font-medium">
                                Rupture de stock
                            </Badge>
                        )}
                    </div>
                </Link>
                
                {/* Bouton panier fixé en bas à droite - style Allbirds */}
                <button
                    onClick={handleAddToCart}
                    disabled={processing}
                    className="absolute bottom-3 right-3 bg-primary text-primary-foreground w-8 h-8 flex items-center justify-center shadow-md hover:shadow-lg transition-all"
                    title={product.has_variants ? "Choisir options" : "Ajouter au panier"}
                >
                    {processing ? (
                        <div className="animate-spin h-3 w-3 border-2 border-primary-foreground border-t-transparent" />
                    ) : (
                        <ShoppingBag className="h-4 w-4" />
                    )}
                </button>
            </div>

            {/* VariantSheet */}
            <VariantSheet
                isOpen={isVariantSheetOpen}
                onClose={() => {
                    console.log('🔴 ProductCard - Closing VariantSheet');
                    setIsVariantSheetOpen(false);
                }}
                product={product}
                variants={product.variants || []}
                availableAttributes={product.availableAttributes || {}}
            />
        </>
    );
}