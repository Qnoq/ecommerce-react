import { Link } from '@inertiajs/react';
import { Heart, ShoppingCart, Eye } from 'lucide-react';
import { useState } from 'react';
import type { Product } from '@/types/index.d.ts';

// Interface pour la compatibilité avec l'ancienne version
interface LegacyProduct {
    id: number;
    name: string;
    price: number;
    originalPrice?: number;
    image: string;
    badge?: string;
    rating?: number;
    reviewCount?: number;
    slug?: string;
}

interface Props {
    product: Product | LegacyProduct;
    onAddToCart?: (productId: number | string) => void;
    onToggleWishlist?: (productId: number | string) => void;
    isInWishlist?: boolean;
}

export default function ProductCard({ 
    product, 
    onAddToCart, 
    onToggleWishlist, 
    isInWishlist = false 
}: Props) {
    const [isLoading, setIsLoading] = useState(false);
    const [imageError, setImageError] = useState(false);

    const handleAddToCart = async () => {
        if (!onAddToCart) return;
        
        setIsLoading(true);
        try {
            await onAddToCart(product.id);
        } catch (error) {
            console.error('Erreur lors de l\'ajout au panier:', error);
        } finally {
            setIsLoading(false);
        }
    };

    const handleToggleWishlist = () => {
        if (onToggleWishlist) {
            onToggleWishlist(product.id);
        }
    };

    const discountPercentage = product.originalPrice 
        ? Math.round(((product.originalPrice - product.price) / product.originalPrice) * 100)
        : 0;

    const productUrl = product.slug ? `/products/${product.slug}` : `/products/${product.id || (product as Product).uuid}`;

    return (
        <div className="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 group relative overflow-hidden">
            {/* Badge */}
            {product.badge && (
                <span className={`absolute top-3 left-3 px-2 py-1 text-xs font-semibold rounded-full z-10 ${
                    product.badge === 'Promo' || product.badge === 'Solde' ? 'bg-red-100 text-red-600' :
                    product.badge === 'Nouveau' ? 'bg-green-100 text-green-600' :
                    product.badge === 'Populaire' ? 'bg-blue-100 text-blue-600' :
                    'bg-gray-100 text-gray-600'
                }`}>
                    {product.badge}
                </span>
            )}

            {/* Discount Badge */}
            {discountPercentage > 0 && (
                <span className="absolute top-3 right-3 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full z-10">
                    -{discountPercentage}%
                </span>
            )}

            {/* Image Container */}
            <div className="relative overflow-hidden rounded-t-xl">
                <Link href={productUrl}>
                    {!imageError ? (
                        <img
                            src={(product as LegacyProduct).image || (product as Product).featured_image || ''}
                            alt={product.name}
                            className="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300"
                            onError={() => setImageError(true)}
                            loading="lazy"
                        />
                    ) : (
                        <div className="w-full h-64 bg-gray-200 flex items-center justify-center">
                            <span className="text-gray-400">Image non disponible</span>
                        </div>
                    )}
                </Link>

                {/* Overlay Actions */}
                <div className="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-300 flex items-center justify-center">
                    <div className="flex space-x-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <Link
                            href={productUrl}
                            className="bg-white p-2 rounded-full shadow-md hover:bg-gray-100 transition-colors"
                            title="Voir le produit"
                        >
                            <Eye className="h-4 w-4 text-gray-600" />
                        </Link>
                        <button
                            onClick={handleToggleWishlist}
                            className={`p-2 rounded-full shadow-md transition-colors ${
                                isInWishlist 
                                    ? 'bg-red-500 text-white hover:bg-red-600' 
                                    : 'bg-white text-gray-600 hover:bg-gray-100'
                            }`}
                            title={isInWishlist ? "Retirer de la liste de souhaits" : "Ajouter à la liste de souhaits"}
                        >
                            <Heart className={`h-4 w-4 ${isInWishlist ? 'fill-current' : ''}`} />
                        </button>
                    </div>
                </div>
            </div>

            {/* Product Info */}
            <div className="p-6">
                <Link href={productUrl}>
                    <h3 className="font-semibold text-gray-900 mb-2 line-clamp-2 hover:text-blue-600 transition-colors">
                        {product.name}
                    </h3>
                </Link>
                
                {/* Rating */}
                {product.rating && (
                    <div className="flex items-center mb-2">
                        <div className="flex items-center">
                            {[...Array(5)].map((_, i) => (
                                <svg
                                    key={i}
                                    className={`w-4 h-4 ${
                                        i < Math.floor(product.rating!) 
                                            ? 'text-yellow-400 fill-current' 
                                            : 'text-gray-300'
                                    }`}
                                    viewBox="0 0 20 20"
                                >
                                    <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z" />
                                </svg>
                            ))}
                        </div>
                        {((product as LegacyProduct).reviewCount || (product as Product).review_count) && (
                            <span className="ml-2 text-sm text-gray-500">
                                ({(product as LegacyProduct).reviewCount || (product as Product).review_count} avis)
                            </span>
                        )}
                    </div>
                )}

                {/* Price */}
                <div className="flex items-center justify-between mb-4">
                    <div className="flex items-center space-x-2">
                        <span className="text-2xl font-bold text-blue-600">
                            {product.price.toFixed(2)}€
                        </span>
                        {product.originalPrice && (
                            <span className="text-sm text-gray-500 line-through">
                                {product.originalPrice.toFixed(2)}€
                            </span>
                        )}
                    </div>
                </div>

                {/* Add to Cart Button */}
                <button
                    onClick={handleAddToCart}
                    disabled={isLoading}
                    className={`w-full py-2 px-4 rounded-lg font-medium transition-all duration-200 flex items-center justify-center space-x-2 ${
                        isLoading
                            ? 'bg-gray-400 text-white cursor-not-allowed'
                            : 'bg-blue-600 text-white hover:bg-blue-700 hover:shadow-md'
                    }`}
                >
                    {isLoading ? (
                        <>
                            <div className="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                            <span>Ajout...</span>
                        </>
                    ) : (
                        <>
                            <ShoppingCart className="h-4 w-4" />
                            <span>Ajouter au panier</span>
                        </>
                    )}
                </button>
            </div>
        </div>
    );
}