// resources/js/types/search.ts

export interface Product {
    id: string;
    uuid: string;
    name: string;
    slug?: string;
    price: number;
    featured_image?: string;
    images?: string[] | string; // Peut être un JSON string ou un array
    rating?: number;
    review_count?: number;
    is_featured?: boolean;
    badges?: string[];
    sales_count?: number;
    created_at?: string;
    relevance_score?: number;
    min_stock?: number; // Stock minimum disponible pour les avertissements
    has_variants?: boolean; // Indique si le produit a des variantes
  }
  
  export interface Category {
    id: number;
    name: string;
    slug: string;
    is_active?: boolean;
  }
  
  export interface SearchSuggestion {
    id: string;
    type: 'product' | 'category' | 'recent' | 'trending';
    title: string;
    subtitle?: string;
    url: string;
    image?: string;
    price?: string;
  }
  
  export interface PriceRange {
    min: number;
    max: number;
    label: string;
  }
  
  export interface SearchResults {
    products: {
      data: Product[];
      total: number;
      current_page: number;
      last_page: number;
      per_page: number;
    };
    suggestions: SearchSuggestion[];
    executionTime?: number;
    query?: string;
  }
  
  export interface SearchFilters {
    categories: Category[];
    priceRanges: PriceRange[];
  }