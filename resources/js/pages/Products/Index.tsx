import React from 'react'
import { Head, usePage } from '@inertiajs/react'
import EcommerceLayout from '@/layouts/EcommerceLayout'
import ProductCard from '@/components/Product/ProductCard'
import { useSearch } from '@/hooks/useSearch'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { SlidersHorizontal, X, Search } from 'lucide-react'
import { Badge } from '@/components/ui/badge'

interface Product {
  id: number
  uuid: string
  name: string
  description: string
  price: number
  image?: string
  images?: string[]
  stock_quantity: number
  is_active: boolean
  categories: Category[]
  reviews_avg_rating?: number
  reviews_count?: number
}

interface Category {
  id: number
  name: string
  slug: string
}

interface PaginatedProducts {
  data: Product[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  links: Array<{
    url: string | null
    label: string
    active: boolean
  }>
}

interface ProductsIndexProps extends Record<string, any> {
  products: PaginatedProducts
  categories: Category[]
  filters: {
    search?: string
    category?: string
    price_min?: number
    price_max?: number
    sort?: string
    order?: 'asc' | 'desc'
  }
}

export default function ProductsIndex() {
  const { products, categories, filters } = usePage<ProductsIndexProps>().props

  // Hook de recherche Inertia
  const {
    filters: searchFilters,
    setData,
    handleFilterChange,
    clearSearch,
    setSortBy,
    processing
  } = useSearch({
    initialFilters: filters
  })

  // Calculer les filtres actifs
  const activeFiltersCount = Object.values(filters).filter(value => 
    value !== null && value !== undefined && value !== ''
  ).length

  const hasActiveFilters = activeFiltersCount > 0

  return (
    <EcommerceLayout
      title="Produits"
      breadcrumbs={[
        { title: 'Accueil', href: '/' },
        { title: 'Produits', href: '/products' }
      ]}
    >
      <Head title="Catalogue Produits" />

      <div className="container mx-auto px-4 py-8">
        {/* Header avec titre et résultats */}
        <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-foreground mb-2">
              {filters.search ? 
                `Résultats pour "${filters.search}"` : 
                'Catalogue Produits'
              }
            </h1>
            <p className="text-muted-foreground">
              {products.total} produit{products.total > 1 ? 's' : ''} trouvé{products.total > 1 ? 's' : ''}
            </p>
          </div>

          {/* Filtres actifs et tri */}
          <div className="flex items-center space-x-4 mt-4 lg:mt-0">
            {hasActiveFilters && (
              <Button
                variant="outline"
                size="sm"
                onClick={clearSearch}
                className="text-sm"
              >
                <X className="h-4 w-4 mr-2" />
                Effacer les filtres
                <Badge variant="secondary" className="ml-2">
                  {activeFiltersCount}
                </Badge>
              </Button>
            )}

            {/* Tri */}
            <Select
              value={`${searchFilters.sort}-${searchFilters.order}`}
              onValueChange={(value) => {
                const [sort, order] = value.split('-')
                setSortBy(sort, order as 'asc' | 'desc')
              }}
            >
              <SelectTrigger className="w-48">
                <SelectValue placeholder="Trier par..." />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="created_at-desc">Plus récents</SelectItem>
                <SelectItem value="created_at-asc">Plus anciens</SelectItem>
                <SelectItem value="name-asc">Nom A-Z</SelectItem>
                <SelectItem value="name-desc">Nom Z-A</SelectItem>
                <SelectItem value="price-asc">Prix croissant</SelectItem>
                <SelectItem value="price-desc">Prix décroissant</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
          {/* Sidebar Filtres */}
          <div className="lg:col-span-1">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center">
                  <SlidersHorizontal className="h-5 w-5 mr-2" />
                  Filtres
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-6">
                
                {/* Recherche */}
                <div>
                  <Label htmlFor="search">Recherche</Label>
                  <div className="relative mt-2">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                      id="search"
                      type="text"
                      placeholder="Rechercher un produit..."
                      value={searchFilters.search || ''}
                      onChange={(e) => handleFilterChange('search', e.target.value)}
                      className="pl-10"
                    />
                  </div>
                </div>

                {/* Catégorie */}
                <div>
                  <Label htmlFor="category">Catégorie</Label>
                  <Select
                    value={searchFilters.category || ''}
                    onValueChange={(value) => handleFilterChange('category', value || undefined)}
                  >
                    <SelectTrigger className="mt-2">
                      <SelectValue placeholder="Toutes les catégories" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="">Toutes les catégories</SelectItem>
                      {categories.map((category) => (
                        <SelectItem key={category.id} value={category.slug}>
                          {category.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                {/* Prix */}
                <div>
                  <Label>Prix (€)</Label>
                  <div className="grid grid-cols-2 gap-2 mt-2">
                    <div>
                      <Input
                        type="number"
                        placeholder="Min"
                        value={searchFilters.price_min || ''}
                        onChange={(e) => handleFilterChange('price_min', e.target.value ? Number(e.target.value) : undefined)}
                      />
                    </div>
                    <div>
                      <Input
                        type="number"
                        placeholder="Max"
                        value={searchFilters.price_max || ''}
                        onChange={(e) => handleFilterChange('price_max', e.target.value ? Number(e.target.value) : undefined)}
                      />
                    </div>
                  </div>
                </div>

                {/* Filtres appliqués */}
                {hasActiveFilters && (
                  <div>
                    <Label>Filtres actifs</Label>
                    <div className="flex flex-wrap gap-2 mt-2">
                      {filters.search && (
                        <Badge variant="secondary" className="text-xs">
                          Recherche: {filters.search}
                          <Button
                            variant="ghost"
                            size="sm"
                            className="h-auto p-0 ml-1"
                            onClick={() => handleFilterChange('search', '')}
                          >
                            <X className="h-3 w-3" />
                          </Button>
                        </Badge>
                      )}
                      {filters.category && (
                        <Badge variant="secondary" className="text-xs">
                          Catégorie: {categories.find(c => c.slug === filters.category)?.name}
                          <Button
                            variant="ghost"
                            size="sm"
                            className="h-auto p-0 ml-1"
                            onClick={() => handleFilterChange('category', '')}
                          >
                            <X className="h-3 w-3" />
                          </Button>
                        </Badge>
                      )}
                      {(filters.price_min || filters.price_max) && (
                        <Badge variant="secondary" className="text-xs">
                          Prix: {filters.price_min || 0}€ - {filters.price_max || '∞'}€
                          <Button
                            variant="ghost"
                            size="sm"
                            className="h-auto p-0 ml-1"
                            onClick={() => {
                              handleFilterChange('price_min', undefined)
                              handleFilterChange('price_max', undefined)
                            }}
                          >
                            <X className="h-3 w-3" />
                          </Button>
                        </Badge>
                      )}
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          </div>

          {/* Contenu principal */}
          <div className="lg:col-span-3">
            {processing && (
              <div className="text-center py-8">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
                <p className="text-muted-foreground mt-2">Chargement...</p>
              </div>
            )}

            {/* Grille des produits */}
            {products.data.length > 0 ? (
              <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                {products.data.map((product) => (
                  <ProductCard
                    key={product.uuid}
                    product={product as any}
                  />
                ))}
              </div>
            ) : (
              <div className="text-center py-12">
                <Search className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                <h3 className="text-lg font-semibold mb-2">Aucun produit trouvé</h3>
                <p className="text-muted-foreground mb-4">
                  Essayez de modifier vos critères de recherche
                </p>
                {hasActiveFilters && (
                  <Button onClick={clearSearch} variant="outline">
                    Effacer tous les filtres
                  </Button>
                )}
              </div>
            )}

            {/* Pagination */}
            {products.data.length > 0 && products.last_page > 1 && (
              <div className="flex justify-center mt-8">
                <div className="flex space-x-1">
                  {products.links.map((link, index) => (
                    <Button
                      key={index}
                      variant={link.active ? "default" : "outline"}
                      size="sm"
                      disabled={!link.url}
                      onClick={() => link.url && window.location.assign(link.url)}
                      dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </EcommerceLayout>
  )
} 