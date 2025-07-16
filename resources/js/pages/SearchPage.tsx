// resources/js/pages/SearchPage.tsx
import React, { useState, useEffect } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import EcommerceLayout from '@/layouts/EcommerceLayout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Search, Filter, Star, ShoppingBag, ArrowLeft, ArrowRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import { formatPrice } from '@/utils/price';

// Import des types depuis le fichier centralisé
import type { Product, SearchResults, SearchFilters } from '@/types/search';

interface SearchPageProps {
    searchQuery: string;
    searchResults: SearchResults;
    filters: SearchFilters;
}

export default function SearchPage({ 
    searchQuery, 
    searchResults, 
    filters 
}: SearchPageProps) {
    // Props de recherche reçues
    
    const [showFilters, setShowFilters] = useState(false);
    const [isLiveSearching, setIsLiveSearching] = useState(false);
    
    // Extraire les données de façon sécurisée
    const products = searchResults?.products?.data || [];
    const totalResults = searchResults?.products?.total || 0;
    const currentPage = searchResults?.products?.current_page || 1;
    const lastPage = searchResults?.products?.last_page || 1;
    const suggestions = searchResults?.suggestions || [];
    const executionTime = searchResults?.executionTime || 0;

    // Surveiller les changements d'URL pour la recherche live
    useEffect(() => {
        const handlePopState = () => {
            const urlParams = new URLSearchParams(window.location.search);
            const newQuery = urlParams.get('k') || '';
            
            if (newQuery && newQuery !== searchQuery) {
                setIsLiveSearching(true);
                router.visit(`/s?k=${encodeURIComponent(newQuery)}`, {
                    method: 'get',
                    preserveState: true,
                    preserveScroll: true,
                    only: ['searchResults'],
                    onFinish: () => setIsLiveSearching(false)
                });
            }
        };

        window.addEventListener('popstate', handlePopState);
        return () => window.removeEventListener('popstate', handlePopState);
    }, [searchQuery]);
    
    // Fonction pour effectuer une nouvelle recherche
    const handleSearch = (query: string) => {
        if (query.trim().length < 2) return;
        
        // Navigation vers page de recherche
        
        // CORRECTION: Utiliser l'URL directe comme dans SearchWithSuggestions
        router.visit(`/s?k=${encodeURIComponent(query.trim())}`, {
            method: 'get',
            preserveState: false, // Nouvelle page complète
            preserveScroll: false, // Retour en haut
        });
    };
    
    // Navigation vers une page spécifique
    const handlePageChange = (page: number) => {
        // Navigation vers page de résultats
        
        // CORRECTION: Utiliser l'URL directe pour éviter les conflits
        router.visit(`/s?k=${encodeURIComponent(searchQuery)}&page=${page}`, {
            method: 'get',
            preserveState: false,
            preserveScroll: false,
        });
    };
    
    // Générer les pages de pagination
    const generatePaginationPages = () => {
        const pages = [];
        const showPages = 5; // Nombre de pages à afficher
        const halfShow = Math.floor(showPages / 2);
        
        let startPage = Math.max(1, currentPage - halfShow);
        let endPage = Math.min(lastPage, currentPage + halfShow);
        
        // Ajuster si on est près du début ou de la fin
        if (endPage - startPage + 1 < showPages) {
            if (startPage === 1) {
                endPage = Math.min(lastPage, startPage + showPages - 1);
            } else {
                startPage = Math.max(1, endPage - showPages + 1);
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            pages.push(i);
        }
        
        return pages;
    };
    
    return (
        <EcommerceLayout>
            <Head title={searchQuery ? `Recherche: ${searchQuery} | ShopLux` : 'Recherche | ShopLux'} />
            
            <div className="container mx-auto px-4 py-8">
                
                {/* Contenu principal */}
                {searchQuery ? (
                    <div className="space-y-6">
                        {/* En-tête des résultats */}
                        <div className="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                            <div>
                                <h1 className="text-2xl md:text-3xl font-bold mb-2">
                                    Résultats pour "{searchQuery}"
                                </h1>
                                <div className="flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                                    <span>{totalResults} produits trouvés</span>
                                    {isLiveSearching && (
                                        <span className="flex items-center gap-1">
                                            <div className="animate-spin h-3 w-3 border border-primary border-t-transparent rounded-full"></div>
                                            Recherche en cours...
                                        </span>
                                    )}
                                    {!isLiveSearching && executionTime > 0 && (
                                        <span>en {executionTime}ms</span>
                                    )}
                                </div>
                            </div>
                            
                            <div className="flex gap-2">
                                <Button 
                                    variant="outline" 
                                    size="sm"
                                    onClick={() => setShowFilters(!showFilters)}
                                >
                                    <Filter className="h-4 w-4 mr-2" />
                                    Filtres
                                </Button>
                            </div>
                        </div>
                        
                        {/* Suggestions si peu de résultats */}
                        {suggestions.length > 0 && totalResults < 5 && (
                            <div className="bg-muted/50 p-4 rounded-lg">
                                <h3 className="font-medium mb-3">
                                    Essayez aussi ces recherches :
                                </h3>
                                <div className="flex flex-wrap gap-2">
                                    {suggestions.slice(0, 6).map((suggestion, index) => (
                                        <Button 
                                            key={`suggestion-top-${suggestion.id || suggestion.title || index}`}
                                            variant="outline" 
                                            size="sm"
                                            onClick={() => handleSearch(suggestion.title)}
                                        >
                                            {suggestion.title}
                                        </Button>
                                    ))}
                                </div>
                            </div>
                        )}
                        
                        {/* Grille de produits */}
                        {products.length > 0 ? (
                            <>
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                    {products.map((product) => (
                                        <ProductCard 
                                            key={product.uuid} 
                                            product={product} 
                                        />
                                    ))}
                                </div>
                                
                                {/* Pagination */}
                                {lastPage > 1 && (
                                    <div className="flex justify-center items-center space-x-2 mt-8">
                                        {/* Bouton précédent */}
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => handlePageChange(currentPage - 1)}
                                            disabled={currentPage <= 1}
                                        >
                                            <ArrowLeft className="h-4 w-4" />
                                        </Button>
                                        
                                        {/* Pages */}
                                        {generatePaginationPages().map((page) => (
                                            <Button
                                                key={page}
                                                variant={currentPage === page ? "default" : "outline"}
                                                size="sm"
                                                onClick={() => handlePageChange(page)}
                                            >
                                                {page}
                                            </Button>
                                        ))}
                                        
                                        {/* Bouton suivant */}
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => handlePageChange(currentPage + 1)}
                                            disabled={currentPage >= lastPage}
                                        >
                                            <ArrowRight className="h-4 w-4" />
                                        </Button>
                                    </div>
                                )}
                            </>
                        ) : (
                            <div className="text-center py-12">
                                <Search className="h-16 w-16 mx-auto mb-4 text-muted-foreground" />
                                <h3 className="text-xl font-semibold mb-2">
                                    Aucun produit trouvé
                                </h3>
                                <p className="text-muted-foreground mb-6">
                                    Essayez des termes différents ou plus généraux
                                </p>
                                
                                {/* Suggestions alternatives */}
                                {suggestions.length > 0 && (
                                    <div className="max-w-md mx-auto">
                                        <p className="text-sm text-muted-foreground mb-3">
                                            Suggestions :
                                        </p>
                                        <div className="flex flex-wrap gap-2 justify-center">
                                            {suggestions.slice(0, 4).map((suggestion, index) => (
                                                <Button 
                                                    key={`suggestion-bottom-${suggestion.id || suggestion.title || index}`}
                                                    variant="outline" 
                                                    size="sm"
                                                    onClick={() => handleSearch(suggestion.title)}
                                                >
                                                    {suggestion.title}
                                                </Button>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                ) : (
                    /* Page vide - état initial */
                    <div className="text-center py-12">
                        <Search className="h-20 w-20 mx-auto mb-6 text-muted-foreground" />
                        <h2 className="text-2xl font-semibold mb-4">
                            Rechercher dans notre catalogue
                        </h2>
                        <p className="text-muted-foreground mb-8 max-w-md mx-auto">
                            Découvrez nos milliers de produits en utilisant la barre de recherche ci-dessus
                        </p>
                        
                        {/* Catégories populaires */}
                        {filters.categories.length > 0 && (
                            <div className="max-w-2xl mx-auto">
                                <h3 className="text-lg font-medium mb-4">
                                    Catégories populaires
                                </h3>
                                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                    {filters.categories.slice(0, 8).map((category, index) => (
                                        <Link
                                            key={`category-${category.id || category.slug || index}`}
                                            href={route('products.index', { category: category.slug })}
                                            className="p-4 border border-border rounded-lg hover:bg-muted/50 transition-colors text-center"
                                        >
                                            <span className="font-medium text-sm">
                                                {category.name}
                                            </span>
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </EcommerceLayout>
    );
}

// Composant ProductCard réutilisable (si pas déjà défini ailleurs)
function ProductCard({ product }: { product: Product }) {
    return (
        <Link 
            href={route('products.show', product.uuid)}
            className="bg-card border border-border rounded-lg overflow-hidden hover:shadow-md transition-shadow group"
        >
            {/* Image */}
            <div className="relative aspect-square">
                {product.featured_image ? (
                    <img
                        src={product.featured_image}
                        alt={product.name}
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    />
                ) : (
                    <div className="w-full h-full bg-muted flex items-center justify-center">
                        <ShoppingBag className="h-12 w-12 text-muted-foreground" />
                    </div>
                )}
                
                {/* Badges */}
                {product.badges && product.badges.length > 0 && (
                    <div className="absolute top-2 left-2 space-y-1">
                        {product.badges.slice(0, 2).map((badge, index) => (
                            <Badge key={index} variant="secondary" className="text-xs">
                                {badge}
                            </Badge>
                        ))}
                    </div>
                )}
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
                        <span className="text-xs text-muted-foreground">
                            ({product.review_count || 0})
                        </span>
                    </div>
                )}

                {/* Nom */}
                <h3 className="font-medium text-sm line-clamp-2 group-hover:text-primary transition-colors">
                    {product.name}
                </h3>

                {/* Prix */}
                <div className="font-bold text-lg text-primary">
                    {formatPrice(product.price)}
                </div>
            </div>
        </Link>
    );
}