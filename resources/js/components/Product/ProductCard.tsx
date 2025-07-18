import { Link, useForm } from '@inertiajs/react';
import { ShoppingBag, Eye, Star } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { formatPrice } from '@/utils/price';
import { cn } from '@/lib/utils';

interface Props {
    product: any;
    onAddToCart?: (productId: number | string) => void;
}

export default function ProductCard({ product, onAddToCart }: Props) {
    const { post, processing } = useForm({
        product_uuid: product.uuid,
        quantity: 1,
        variants: {}
    });

    const handleAddToCart = (e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();
        
        if (onAddToCart) {
            onAddToCart(product.id);
            return;
        }
        
        // Si le produit a des variantes, aller à la page produit
        if (product.has_variants) {
            window.location.href = route('products.show', { slug: product.slug || 'product', uuid: product.uuid });
            return;
        }
        
        // Sinon, ajout direct au panier
        post(route('cart.store'), {
            preserveScroll: true,
            onSuccess: () => {
                // Produit ajouté avec succès
            }
        });
    };

    return (
        <Link 
            href={route('products.show', { slug: product.slug || 'product', uuid: product.uuid })}
            className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden hover:shadow-md transition-shadow group relative block"
        >
            {/* Image */}
            <div className="relative aspect-square">
                {product.featured_image || product.image ? (
                    <img
                        src={product.featured_image || product.image}
                        alt={product.name}
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    />
                ) : (
                    <div className="w-full h-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                        <ShoppingBag className="h-12 w-12 text-gray-400 dark:text-gray-500" />
                    </div>
                )}
                
                {/* Badges */}
                {product.badges && product.badges.length > 0 && (
                    <div className="absolute top-2 left-2 space-y-1">
                        {product.badges.slice(0, 2).map((badge: string, index: number) => (
                            <Badge key={index} variant="secondary" className="text-xs">
                                {badge}
                            </Badge>
                        ))}
                    </div>
                )}

                {/* Bouton panier - visible sur mobile, hover sur desktop */}
                <button
                    onClick={handleAddToCart}
                    disabled={processing}
                    className="absolute bottom-2 right-2 bg-white/90 backdrop-blur-sm text-gray-700 p-2 rounded-full shadow-md hover:bg-white hover:shadow-lg transition-all md:opacity-0 md:group-hover:opacity-100"
                    title={product.has_variants ? "Voir les options" : "Ajouter au panier"}
                >
                    {processing ? (
                        <div className="animate-spin rounded-full h-3 w-3 border-2 border-gray-700 border-t-transparent" />
                    ) : product.has_variants ? (
                        <Eye className="h-3 w-3" />
                    ) : (
                        <ShoppingBag className="h-3 w-3" />
                    )}
                </button>
            </div>

            {/* Contenu */}
            <div className="p-4 space-y-3">
                {/* Rating */}
                {product.rating && (
                    <div className="flex items-center gap-1">
                        <div className="flex">
                            {[...Array(5)].map((_, i) => (
                                <Star
                                    key={i}
                                    className={cn(
                                        "h-3 w-3",
                                        i < Math.floor(product.rating!) 
                                            ? "fill-yellow-400 text-yellow-400" 
                                            : "text-gray-300"
                                    )}
                                />
                            ))}
                        </div>
                        <span className="text-xs text-gray-500 dark:text-gray-400">
                            ({product.review_count || product.reviewCount || 0})
                        </span>
                    </div>
                )}

                {/* Nom */}
                <h3 className="font-medium text-sm line-clamp-2 text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                    {product.name}
                </h3>

                {/* Prix et stock */}
                <div className="flex items-center justify-between">
                    <div className="font-bold text-lg text-blue-600 dark:text-blue-400">
                        {formatPrice(product.price)}
                    </div>
                    
                    {/* Badge stock faible - bien visible à droite du prix */}
                    {product.min_stock !== undefined && product.min_stock > 0 && product.min_stock <= 5 && (
                        <Badge variant="outline" className="text-xs bg-orange-100 text-orange-600 border-orange-200 font-medium">
                            {product.min_stock === 1 ? 'Dernière pièce' : `Plus que ${product.min_stock}`}
                        </Badge>
                    )}
                </div>
            </div>
        </Link>
    );
}