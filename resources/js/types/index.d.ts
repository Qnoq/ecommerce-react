import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

// Product interfaces unifiées
export interface Product {
    id: string;
    uuid: string;
    name: string;
    slug?: string;
    price: number;
    originalPrice?: number;
    featured_image?: string;
    images?: string[];
    rating?: number;
    review_count?: number;
    reviewCount?: number; // Compatibilité avec ProductCard existant
    is_featured?: boolean;
    badges?: string[];
    badge?: string; // Compatibilité avec ProductCard existant
    description?: string;
    short_description?: string;
    category_id?: string;
    category?: Category;
    stock_quantity?: number;
    min_stock?: number; // Stock minimum disponible pour les avertissements
    is_active?: boolean;
    has_variants?: boolean; // Nouveau champ pour les variantes
    created_at?: string;
    updated_at?: string;
}

export interface Category {
    id: string;
    name: string;
    slug: string;
    description?: string;
    parent_id?: string;
    image?: string;
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

export interface PromotionalCard {
    id: string;
    title: string;
    subtitle: string;
    image?: string;
    icon?: string;
    url: string;
    className: string;
}
