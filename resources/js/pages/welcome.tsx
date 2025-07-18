import EcommerceLayout from '@/layouts/EcommerceLayout';
import ProductCard from '@/components/Product/ProductCard';
import { Link } from '@inertiajs/react';
import { Truck, Shield, RotateCcw, Star, ArrowRight, ShoppingBag, TrendingUp } from 'lucide-react';
import { toast } from 'sonner';
import { useTranslation } from '@/hooks/useTranslation';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

interface Product {
    id: number;
    uuid?: string;
    name: string;
    slug?: string;
    price: number;
    original_price?: number;
    originalPrice?: number;
    image?: string;
    featured_image?: string;
    badge?: string;
    badges?: string[];
    rating?: number;
    review_count?: number;
    reviewCount?: number;
    is_featured?: boolean;
    min_stock?: number;
}

interface Props {
    featuredProducts?: Product[];
    categories?: Array<{
        id: number;
        name: string;
        slug: string;
        count: string;
        href: string;
        icon: string;
    }>;
    stats?: {
        total_products: number;
        total_categories: number;
        avg_rating: number;
    };
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

export default function Welcome({ featuredProducts = [], categories = [], stats, user, cartCount = 0, searchResults }: Props) {
    const { __ } = useTranslation();

    // Données de démonstration (à remplacer par les props du contrôleur)
    const defaultProducts: Product[] = [
        {
            id: 1,
            name: "Smartphone Premium",
            price: 699.99,
            originalPrice: 799.99,
            image: "https://via.placeholder.com/400x400/3B82F6/FFFFFF?text=Smartphone",
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

    // Utiliser les catégories du contrôleur ou fallback
    const displayCategories = categories.length > 0 ? categories : [
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
            {/* Hero Section - Responsive avec thèmes */}
            <section className="relative bg-gradient-to-br from-blue-600 via-purple-600 to-indigo-700 dark:from-blue-900 dark:via-purple-900 dark:to-indigo-900 text-white overflow-hidden">
                {/* Décoration de fond */}
                <div className="absolute inset-0 bg-black/20 dark:bg-black/40"></div>
                <div className="absolute top-10 left-10 w-72 h-72 bg-yellow-400/20 rounded-full blur-3xl"></div>
                <div className="absolute bottom-10 right-10 w-96 h-96 bg-pink-400/20 rounded-full blur-3xl"></div>
                
                <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28">
                    <div className="grid lg:grid-cols-2 gap-12 items-center">
                        <div className="space-y-8">
                            <div className="space-y-4">
                                <Badge variant="outline" className="bg-white/10 text-white border-white/20">
                                    ✨ Nouveau sur ShopLux
                                </Badge>
                                <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold leading-tight">
                                    Découvrez l'Shopping
                                    <span className="block text-transparent bg-gradient-to-r from-yellow-400 to-orange-500 bg-clip-text">
                                        Moderne
                                    </span>
                                </h1>
                                <p className="text-xl text-blue-100 dark:text-blue-200 leading-relaxed">
                                    Des milliers de produits de qualité, livrés rapidement. 
                                    Vivez une expérience d'achat exceptionnelle avec notre garantie satisfaction.
                                </p>
                            </div>
                            
                            <div className="flex flex-col sm:flex-row gap-4">
                                <Button asChild size="lg" className="bg-white text-blue-600 hover:bg-gray-100 shadow-lg">
                                    <Link href="/products">
                                        <ShoppingBag className="mr-2 h-5 w-5" />
                                        Explorer nos produits
                                    </Link>
                                </Button>
                                <Button asChild variant="outline" size="lg" className="border-white text-white hover:bg-white hover:text-blue-600">
                                    <Link href="/s">
                                        <TrendingUp className="mr-2 h-5 w-5" />
                                        Tendances
                                    </Link>
                                </Button>
                            </div>
                            
                            {/* Stats */}
                            <div className="grid grid-cols-3 gap-6 pt-8">
                                <div className="text-center">
                                    <div className="text-3xl font-bold text-yellow-400">
                                        {stats?.total_products ? (stats.total_products > 999 ? `${Math.floor(stats.total_products/1000)}k+` : `${stats.total_products}+`) : '2k+'}
                                    </div>
                                    <div className="text-sm text-blue-200">Produits</div>
                                </div>
                                <div className="text-center">
                                    <div className="text-3xl font-bold text-green-400">
                                        {stats?.avg_rating ? `${Math.round(stats.avg_rating * 20)}%` : '98%'}
                                    </div>
                                    <div className="text-sm text-blue-200">Satisfaction</div>
                                </div>
                                <div className="text-center">
                                    <div className="text-3xl font-bold text-pink-400">24h</div>
                                    <div className="text-sm text-blue-200">Livraison</div>
                                </div>
                            </div>
                        </div>
                        
                        <div className="relative">
                            <div className="relative z-10">
                                <img
                                    src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=600&h=400&fit=crop"
                                    alt="Shopping experience"
                                    className="rounded-2xl shadow-2xl"
                                />
                                <div className="absolute -bottom-6 -right-6 bg-white dark:bg-gray-800 rounded-xl p-4 shadow-lg">
                                    <div className="flex items-center gap-2">
                                        <Star className="h-5 w-5 text-yellow-500 fill-current" />
                                        <span className="font-semibold text-gray-900 dark:text-white">4.9/5</span>
                                        <span className="text-gray-500 dark:text-gray-400">avis clients</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Categories - Responsive avec thèmes */}
            <section className="py-16 bg-gray-50 dark:bg-gray-900">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-12">
                        <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                            Catégories Populaires
                        </h2>
                        <p className="text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                            Explorez notre large sélection de produits organisés par catégorie
                        </p>
                    </div>
                    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                        {displayCategories.map((category, index) => (
                            <Link
                                key={category.id || index}
                                href={category.href}
                                className="group text-center p-6 bg-white dark:bg-gray-800 rounded-xl hover:bg-blue-50 dark:hover:bg-gray-700 hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700"
                            >
                                <div className="text-4xl mb-3 group-hover:scale-110 transition-transform duration-300">
                                    {category.icon}
                                </div>
                                <h3 className="font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 mb-1">
                                    {category.name}
                                </h3>
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    {category.count} produits
                                </p>
                            </Link>
                        ))}
                    </div>
                </div>
            </section>

            {/* Featured Products - Responsive avec thèmes */}
            <section className="py-16 bg-white dark:bg-gray-800">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-12 gap-4">
                        <div>
                            <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                                Produits Vedettes
                            </h2>
                            <p className="text-gray-600 dark:text-gray-300">
                                Nos meilleures ventes du moment
                            </p>
                        </div>
                        <Button asChild variant="outline" className="shrink-0">
                            <Link href="/products">
                                Voir tous les produits
                                <ArrowRight className="ml-2 h-4 w-4" />
                            </Link>
                        </Button>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                        {products.map((product) => {
                            // Normaliser les données pour compatibilité avec ProductCard
                            const normalizedProduct = {
                                ...product,
                                originalPrice: product.original_price || product.originalPrice,
                                image: product.featured_image || product.image,
                                reviewCount: product.review_count || product.reviewCount,
                                badge: product.badges?.[0] || product.badge
                            };
                            
                            return (
                                <ProductCard
                                    key={product.id}
                                    product={normalizedProduct}
                                    onAddToCart={handleAddToCart}
                                    onToggleWishlist={handleToggleWishlist}
                                    isInWishlist={false}
                                />
                            );
                        })}
                    </div>
                </div>
            </section>

            {/* Features - Responsive avec thèmes */}
            <section className="py-16 bg-gray-50 dark:bg-gray-900">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-12">
                        <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                            Pourquoi nous choisir ?
                        </h2>
                        <p className="text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                            Nous nous engageons à vous offrir la meilleure expérience d'achat possible
                        </p>
                    </div>
                    <div className="grid md:grid-cols-3 gap-8">
                        <div className="text-center group">
                            <div className="bg-blue-100 dark:bg-blue-900 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                                <Truck className="h-8 w-8 text-blue-600 dark:text-blue-400" />
                            </div>
                            <h3 className="text-xl font-semibold mb-2 text-gray-900 dark:text-white">
                                Livraison Gratuite
                            </h3>
                            <p className="text-gray-600 dark:text-gray-300">
                                Livraison gratuite dès 50€ d'achat. Expédition sous 24h pour la plupart des produits.
                            </p>
                        </div>
                        <div className="text-center group">
                            <div className="bg-green-100 dark:bg-green-900 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                                <Shield className="h-8 w-8 text-green-600 dark:text-green-400" />
                            </div>
                            <h3 className="text-xl font-semibold mb-2 text-gray-900 dark:text-white">
                                Paiement Sécurisé
                            </h3>
                            <p className="text-gray-600 dark:text-gray-300">
                                Tous vos paiements sont protégés par un cryptage SSL de niveau bancaire.
                            </p>
                        </div>
                        <div className="text-center group">
                            <div className="bg-yellow-100 dark:bg-yellow-900 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                                <RotateCcw className="h-8 w-8 text-yellow-600 dark:text-yellow-400" />
                            </div>
                            <h3 className="text-xl font-semibold mb-2 text-gray-900 dark:text-white">
                                Retours 30 jours
                            </h3>
                            <p className="text-gray-600 dark:text-gray-300">
                                Satisfait ou remboursé sous 30 jours. Retour gratuit pour tous vos achats.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {/* Newsletter - Responsive avec thèmes */}
            <section className="py-16 bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-800 dark:to-purple-800">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <div className="space-y-6">
                        <div className="space-y-4">
                            <h2 className="text-3xl font-bold text-white">
                                Restez informé
                            </h2>
                            <p className="text-xl text-blue-100 dark:text-blue-200">
                                Recevez nos dernières offres et nouveautés directement dans votre boîte mail
                            </p>
                        </div>
                        <form onSubmit={handleNewsletterSubmit} className="flex flex-col sm:flex-row gap-4 max-w-md mx-auto">
                            <input
                                type="email"
                                placeholder="Votre adresse email"
                                className="flex-1 px-4 py-3 rounded-lg border-0 focus:ring-2 focus:ring-blue-300 focus:outline-none dark:bg-gray-800 dark:text-white dark:placeholder-gray-400"
                                required
                            />
                            <Button 
                                type="submit"
                                className="bg-yellow-500 text-blue-900 hover:bg-yellow-400 font-semibold px-8 py-3 rounded-lg"
                            >
                                S'abonner
                            </Button>
                        </form>
                        <p className="text-sm text-blue-200 dark:text-blue-300">
                            Pas de spam, désabonnement facile à tout moment
                        </p>
                    </div>
                </div>
            </section>
        </EcommerceLayout>
    );
}