import type { PromotionalCard } from '@/types/index.d.ts'

export const PROMOTIONAL_CARDS: PromotionalCard[] = [
  {
    id: 'nouveautes',
    title: 'Découvrez nos actualités',
    subtitle: 'Nouveautés',
    image: 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=600&h=300&fit=crop',
    url: '/products?category=nouveautes',
    className: 'bg-gradient-to-r from-blue-500 to-purple-600 text-white'
  },
  {
    id: 'livraison',
    title: 'LIVRAISON GRATUITE',
    subtitle: 'À partir de 50€',
    icon: '🚚',
    url: '/livraison',
    className: 'bg-gradient-to-r from-green-500 to-blue-500 text-white'
  },
  {
    id: 'seconde-main',
    title: 'La SECONDE MAIN des familles',
    subtitle: '100% qualité, 100% style, 100% petits prix.',
    icon: '♻️',
    url: '/seconde-main',
    className: 'bg-gradient-to-r from-purple-500 to-pink-500 text-white'
  }
]

export const POPULAR_CATEGORIES = [
  { name: 'T-shirts', slug: 't-shirts' },
  { name: 'Robes', slug: 'robes' },
  { name: 'Chaussures', slug: 'chaussures' },
  { name: 'Accessoires', slug: 'accessoires' }
]