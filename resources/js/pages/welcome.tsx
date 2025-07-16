import EcommerceLayout from '@/layouts/EcommerceLayout';
import ProductCard from '@/components/Product/ProductCard';
import { Link } from '@inertiajs/react';
import { Truck, Shield, RotateCcw } from 'lucide-react';
import { toast } from 'sonner';
import { useTranslation } from '@/hooks/useTranslation';

interface Product {
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
    featuredProducts?: Product[];
    user?: {
        id: number;
        name: string;
        email: string;
    };
    cartCount?: number;
    searchResults?: {
        products: {
            data: any[];
            total: number;
            query?: string;
        };
        suggestions: any[];
    };
}

export default function Welcome({ featuredProducts = [], user, cartCount = 0, searchResults }: Props) {
    const { __ } = useTranslation();

    // Données de démonstration (à remplacer par les props du contrôleur)
    const defaultProducts: Product[] = [
        {
            id: 1,
            name: "Smartphone Premium",
            price: 699.99,
            originalPrice: 799.99,
            image: "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&h=400&fit=crop",
            badge: "Promo",
            rating: 4.5,
            reviewCount: 128
        },
        {
            id: 2,
            name: "Casque Audio Sans Fil",
            price: 199.99,
            image: "https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=400&fit=crop",
            badge: "Nouveau",
            rating: 4.8,
            reviewCount: 89
        },
        {
            id: 3,
            name: "Montre Connectée",
            price: 299.99,
            image: "https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400&h=400&fit=crop",
            badge: "Populaire",
            rating: 4.3,
            reviewCount: 156
        },
        {
            id: 4,
            name: "Ordinateur Portable",
            price: 1299.99,
            image: "https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=400&h=400&fit=crop",
            badge: "",
            rating: 4.7,
            reviewCount: 203
        }
    ];

    const products = featuredProducts.length > 0 ? featuredProducts : defaultProducts;

    const categories = [
        { name: __('ecommerce.electronic'), icon: "📱", count: "120+", href: "/categories/electronics" },
        { name: __('ecommerce.fashion'), icon: "👕", count: "250+", href: "/categories/fashion" },
        { name: __('ecommerce.home'), icon: "🏠", count: "180+", href: "/categories/home" },
        { name: __('ecommerce.sport'), icon: "⚽", count: "90+", href: "/categories/sports" },
        { name: __('ecommerce.beauty'), icon: "💄", count: "150+", href: "/categories/beauty" },
        { name: "Livres", icon: "📚", count: "300+", href: "/categories/books" }
    ];

    // 🔔 NOUVELLES FONCTIONNALITÉS - Gestion avec Toasts Sonner
    const handleAddToCart = async (productId: number | string) => {
        try {
            // Simuler un appel API
            await new Promise(resolve => setTimeout(resolve, 500));
            
            const product = products.find(p => p.id === productId);
            
            // ✅ Toast de succès avec action
            toast.success(__('cart.product_added'), {
                description: `${product?.name} ${__('cart.added_to_cart')}`,
                action: {
                    label: __('common.view_cart'),
                    onClick: () => window.location.href = '/cart'
                }
            });
            
        } catch (error) {
            // ❌ Toast d'erreur
            toast.error(__('cart.error'), {
                description: __('cart.add_failed')
            });
        }
    };

    const handleToggleWishlist = async (productId: number | string) => {
        try {
            await new Promise(resolve => setTimeout(resolve, 300));
            
            const product = products.find(p => p.id === productId);
            
            // ✅ Toast avec action vers la wishlist
            toast.success(__('wishlist.added'), {
                description: `${product?.name} ${__('wishlist.added_to_wishlist')}`,
                action: {
                    label: __('wishlist.view'),
                    onClick: () => window.location.href = '/wishlist'
                }
            });
            
        } catch (error) {
            toast.error(__('wishlist.error'), {
                description: __('wishlist.add_failed')
            });
        }
    };

    const handleNewsletterSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        
        try {
            // Simuler un appel API
            await new Promise(resolve => setTimeout(resolve, 800));
            
            toast.success('Inscription réussie !', {
                description: 'Vous recevrez bientôt nos meilleures offres par email',
                action: {
                    label: 'Voir mes préférences',
                    onClick: () => window.location.href = '/profile'
                }
            });
            
        } catch (error) {
            toast.error('Erreur d\'inscription', {
                description: 'Veuillez réessayer plus tard'
            });
        }
    };

    // 🗺️ BREADCRUMBS - Pour la page d'accueil (optionnel)
    const breadcrumbs = [
        { title: __('navigation.home'), href: '' } // Pas de href = page actuelle
    ];

    return (
        <EcommerceLayout 
            user={user}
            cartCount={cartCount}
            // breadcrumbs={breadcrumbs} // Optionnel pour la page d'accueil
            title={`${__('navigation.home')} - ${__('company.company_name')}`}
        >
            {/* Hero Section */}
            <section className="bg-gradient-to-r from-blue-600 to-purple-600 text-white">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
                    <div className="grid md:grid-cols-2 gap-12 items-center">
                        <div>
                            <h1 className="text-4xl md:text-6xl font-bold mb-6">
                                Découvrez nos
                                <span className="block text-yellow-300">Produits Exceptionnels</span>
                            </h1>
                            <p className="text-xl mb-8 text-blue-100">
                                {__('company.company_description')} 
                                Livraison rapide et garantie satisfait ou remboursé.
                            </p>
                            <div className="flex flex-col sm:flex-row gap-4">
                                <Link
                                    href="/products"
                                    className="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors text-center"
                                >
                                    {__('navigation.products')}
                                </Link>
                                <Link
                                    href="/deals"
                                    className="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition-colors text-center"
                                >
                                    {__('navigation.deals')}
                                </Link>
                            </div>
                        </div>
                        <div className="relative">
                            <img
                                src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=600&h=400&fit=crop"
                                alt="Shopping experience"
                                className="rounded-lg shadow-2xl"
                            />
                        </div>
                    </div>
                </div>
            </section>

            {/* Categories */}
            <section className="py-16 bg-white">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 className="text-3xl font-bold text-center mb-12 text-gray-900">
                        {__('common.categories')} Populaires
                    </h2>
                    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                        {categories.map((category, index) => (
                            <Link
                                key={index}
                                href={category.href}
                                className="group text-center p-6 bg-gray-50 rounded-xl hover:bg-blue-50 hover:shadow-lg transition-all duration-300"
                            >
                                <div className="text-4xl mb-3">{category.icon}</div>
                                <h3 className="font-semibold text-gray-900 group-hover:text-blue-600 mb-1">
                                    {category.name}
                                </h3>
                                <p className="text-sm text-gray-500">{category.count} produits</p>
                            </Link>
                        ))}
                    </div>
                </div>
            </section>

            {/* Featured Products */}
            <section className="py-16 bg-gray-50">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center mb-12">
                        <h2 className="text-3xl font-bold text-gray-900">{__('navigation.bestsellers')}</h2>
                        <Link
                            href="/products"
                            className="text-blue-600 hover:text-blue-700 font-semibold"
                        >
                            Voir tous les produits →
                        </Link>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                        {products.map((product) => (
                            <ProductCard
                                key={product.id}
                                product={product}
                                onAddToCart={handleAddToCart}
                                onToggleWishlist={handleToggleWishlist}
                                isInWishlist={false} // TODO: Vérifier si le produit est dans la wishlist
                            />
                        ))}
                    </div>
                </div>
            </section>

            {/* Features */}
            <section className="py-16 bg-white">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="grid md:grid-cols-3 gap-8">
                        <div className="text-center">
                            <div className="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                <Truck className="h-8 w-8 text-blue-600" />
                            </div>
                            <h3 className="text-xl font-semibold mb-2">{__('ecommerce.free_shipping')}</h3>
                            <p className="text-gray-600">
                                {__('ecommerce.free_shipping_from', { amount: '50' })}. Expédition sous 24h pour la plupart des produits.
                            </p>
                        </div>
                        <div className="text-center">
                            <div className="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                <Shield className="h-8 w-8 text-green-600" />
                            </div>
                            <h3 className="text-xl font-semibold mb-2">{__('ecommerce.secure_payment')}</h3>
                            <p className="text-gray-600">
                                Tous vos paiements sont protégés par un cryptage SSL de niveau bancaire.
                            </p>
                        </div>
                        <div className="text-center">
                            <div className="bg-yellow-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                <RotateCcw className="h-8 w-8 text-yellow-600" />
                            </div>
                            <h3 className="text-xl font-semibold mb-2">{__('ecommerce.returns_30d')}</h3>
                            <p className="text-gray-600">
                                Satisfait ou remboursé sous 30 jours. Retour gratuit pour tous vos achats.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {/* Newsletter */}
            <section className="py-16 bg-blue-600">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 className="text-3xl font-bold text-white mb-4">
                        {__('common.newsletter')}
                    </h2>
                    <p className="text-xl text-blue-100 mb-8">
                        {__('common.newsletter_description')}
                    </p>
                    <form onSubmit={handleNewsletterSubmit} className="flex flex-col sm:flex-row gap-4 max-w-md mx-auto">
                        <input
                            type="email"
                            placeholder={__('common.your_email')}
                            className="flex-1 px-4 py-3 rounded-lg border-0 focus:ring-2 focus:ring-blue-300 focus:outline-none"
                            required
                        />
                        <button 
                            type="submit"
                            className="bg-yellow-500 text-blue-900 px-8 py-3 rounded-lg font-semibold hover:bg-yellow-400 transition-colors"
                        >
                            {__('common.subscribe')}
                        </button>
                    </form>
                </div>
            </section>
        </EcommerceLayout>
    );
}