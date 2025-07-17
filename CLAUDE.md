# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 🎯 PROJET SHOPLUX - CE QUE JE VEUX ACCOMPLIR

### Vision du projet
Je développe **ShopLux**, une **plateforme e-commerce moderne et professionnelle** avec Laravel + Inertia + React. L'objectif est de créer une expérience utilisateur exceptionnelle avec toutes les fonctionnalités d'un vrai e-commerce prêt pour la production. Utilise au maximum Inertia, et non pas api fetch.

### État actuel du projet
- ✅ **Configuration technique** : Docker, Laravel, Inertia, React, PostgreSQL
- ✅ **Page d'accueil** : Welcome.tsx avec hero section et produits mockés
- ✅ **Layout e-commerce** : EcommerceLayout avec navigation
- ✅ **Composant ProductCard** : Carte produit de base
- ✅ **Système de traduction** : Multi-langues (FR/EN) avec Redis cache
- ✅ **Recherche** : SearchPage.tsx basique
- ✅ **Notifications** : Toast système avec Sonner
- ❌ **Backend** : Contrôleurs largement à développer
- ❌ **Base de données** : Migrations et modèles à créer
- ❌ **Pages principales** : Catalogue, détail produit, panier, checkout à faire

### Ce que je veux développer

#### 🗄️ Backend Laravel
- **Modèles** : User, Product, Category, Cart, Order, etc.
- **Migrations** : Structure base de données complète
- **Contrôleurs** : Logique métier pour toutes les fonctionnalités
- **Seeders** : Données de test réalistes
- **API** : Endpoints pour le frontend React

#### 🎨 Frontend React
- **Page catalogue** : Liste produits avec filtres, tri, pagination
- **Page détail produit** : Présentation complète avec variantes, avis
- **Système de panier** : Ajout, modification, calculs automatiques
- **Processus checkout** : Multi-étapes avec validation
- **Espace utilisateur** : Dashboard, commandes, profil, wishlist
- **Interface admin** : Gestion produits, commandes, utilisateurs

#### 🔧 Fonctionnalités e-commerce
- **Gestion stock** : Suivi temps réel, alertes
- **Système de paiement** : Stripe/PayPal intégré
- **Notifications** : Email, SMS pour commandes
- **Promotions** : Coupons, réductions, offres
- **Livraison** : Calculs et suivi
- **Analytics** : Métriques business

### Style de développement souhaité
- **Code professionnel** : Pas de mockups, vraies fonctionnalités
- **Architecture solide** : Scalable, maintenable, performant
- **UX moderne** : Interface fluide, responsive, intuitive
- **TypeScript strict** : Types appropriés partout
- **Composants réutilisables** : Modulaires avec shadcn/ui
- **Validation robuste** : Côté client ET serveur
- **Performance** : Optimisations PostgreSQL, cache Redis

### Priorités actuelles
1. **Remplacer les données mockées** par de vraies données backend
2. **Créer la structure base de données** complète
3. **Développer les contrôleurs** pour l'API
4. **Implémenter les pages principales** (catalogue, détail, panier)
5. **Système de gestion stock** et commandes

### Technologies à utiliser
- **Laravel** : Eloquent ORM, validation, middleware
- **PostgreSQL** : Base de données avec recherche full-text
- **Redis** : Cache et sessions
- **Inertia.js** : Communication Laravel ↔ React
- **Tailwind CSS** : Styling moderne
- **shadcn/ui** : Composants UI de qualité

### Contexte business
ShopLux doit être une **vraie boutique en ligne** capable de :
- Gérer un **catalogue de milliers de produits**
- Traiter des **commandes réelles** avec paiement
- Fournir une **expérience utilisateur fluide**
- Avoir une **interface d'administration** complète
- Être **prêt pour la production** avec de vrais clients

### Ce que je NE veux PAS
- Solutions basiques ou "de démo"
- Composants avec données hardcodées
- Architecture qui ne scale pas
- Code non maintenable
- Fonctionnalités incomplètes

---

## Common Development Commands

### Frontend Development
```bash
# Start development server
npm run dev

# Build for production
npm run build

# Build with SSR
npm run build:ssr

# Linting and formatting
npm run lint          # ESLint with auto-fix
npm run format        # Prettier formatting
npm run format:check  # Check formatting without changes
npm run types         # TypeScript type checking (no emit)
```

### Backend Development
```bash
# Start development environment (Laravel + Queue + Vite)
composer run dev

# Start with SSR support
composer run dev:ssr

# Run tests
composer run test     # Runs php artisan test
php artisan test      # Direct test execution

# Database operations
php artisan migrate
php artisan db:seed
php artisan migrate:refresh --seed
```

### Docker Environment
```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f app

# Access app container
docker exec -it ecommerce-app-1 bash

# Database access
docker exec -it ecommerce-postgres-1 psql -U postgres -d ecommerce
```

### Testing
- Uses **Pest** testing framework (not PHPUnit)
- Test files are in `/tests/Feature/` and `/tests/Unit/`
- Run specific test: `php artisan test --filter=TestName`
- SQLite in-memory database for tests (see phpunit.xml)

## Architecture Overview

### Tech Stack
- **Backend**: Laravel 12 with Inertia.js
- **Frontend**: React 19 + TypeScript + Tailwind CSS 4
- **Database**: PostgreSQL with pgvector extension
- **Cache**: Redis (multiple databases for different purposes)
- **Build Tool**: Vite with Laravel plugin
- **Testing**: Pest + Laravel testing utilities

### Key Architecture Patterns

#### Inertia.js Integration
- Server-side rendering (SSR) supported
- Pages in `resources/js/pages/`
- Layouts in `resources/js/layouts/`
- Uses `@inertiajs/react` for seamless Laravel-React integration

#### Component Structure
- UI components in `resources/js/components/ui/` (shadcn/ui based)
- App-specific components in `resources/js/components/`
- Custom hooks in `resources/js/hooks/`
- TypeScript types in `resources/js/types/`

#### Database Design
- **E-commerce focused**: Products, Categories, Orders, Cart, Wishlist, Reviews
- **Advanced search**: PostgreSQL full-text search with trigram support
- **Optimized indexes**: Comprehensive search indexes already implemented
- **User management**: Laravel Breeze authentication

#### Search System
- **Highly optimized search system** with:
  - PostgreSQL full-text search (French language support)
  - Trigram indexes for fuzzy matching
  - Redis caching for performance
  - React hooks for frontend integration
- Search components in `resources/js/components/Search/`
- Backend search logic in `ProductController`

### Docker Configuration
- **Multi-service setup**: App, PostgreSQL, Redis, pgAdmin, RedisInsight
- **PostgreSQL**: Uses pgvector image for vector search capabilities
- **Redis**: Configured for cache, sessions, and queues
- **Development tools**: pgAdmin on port 5050, RedisInsight on port 5540

### Environment Configuration
- **Database**: PostgreSQL (ecommerce database)
- **Cache**: Redis with multiple databases:
  - DB 0: Default
  - DB 1: Cache
  - DB 2: Sessions  
  - DB 3: Queues
  - DB 4: Search cache
- **Queue**: Redis-based queue system

## Development Guidelines

### Code Style
- **PHP**: Laravel conventions, PSR-12 compliance
- **TypeScript/React**: ESLint + Prettier configuration
- **CSS**: Tailwind CSS with custom component patterns
- **Database**: Snake_case naming, proper indexing

### Component Development
- Follow shadcn/ui patterns for UI components
- Use custom hooks for complex logic
- Implement proper TypeScript types
- Follow Inertia.js best practices for forms and navigation

### Search Implementation
- Leverage existing optimized search indexes
- Use the custom `useSearch` hook for search functionality
- Implement proper debouncing for live search
- Cache search results appropriately

### Testing Strategy
- Feature tests for user workflows
- Unit tests for isolated logic
- Use Laravel's testing utilities
- Pest test framework conventions

## Important File Locations

### Configuration
- `docker-compose.yml`: Docker services configuration
- `vite.config.ts`: Frontend build configuration
- `composer.json`: PHP dependencies and scripts
- `package.json`: Node.js dependencies and scripts

### Core Application
- `app/Http/Controllers/`: Laravel controllers
- `app/Models/`: Eloquent models
- `database/migrations/`: Database schema
- `routes/`: Application routes (web.php, auth.php, settings.php)

### Frontend Resources
- `resources/js/app.tsx`: Main React entry point
- `resources/js/ssr.tsx`: Server-side rendering entry
- `resources/css/app.css`: Tailwind CSS entry

### Search System
- Search optimization docs: `README_SEARCH_OPTIMIZATION.md`, `SEARCH_OPTIMIZATION.md`
- Search components: `resources/js/components/Search/`
- Search implementation: `resources/js/components/Search/README.md`

## Performance Considerations

### Database Optimization
- **Comprehensive indexes** already implemented for search
- **PostgreSQL extensions**: pg_trgm, unaccent, fuzzystrmatch
- **Query optimization**: Use existing search methods in ProductController

### Caching Strategy
- **Redis**: Multi-database setup for different cache types
- **Search cache**: Dedicated Redis database (DB 4)
- **Application cache**: Laravel cache with Redis backend

### Frontend Performance
- **SSR support**: Server-side rendering configured
- **Code splitting**: Vite handles automatic splitting
- **Asset optimization**: Tailwind CSS purging, optimized builds

## Troubleshooting

### Common Issues
- **Docker**: Ensure all services are healthy before starting development
- **Database**: Check PostgreSQL extensions are installed
- **Search**: Verify Redis connection for search caching
- **TypeScript**: Run `npm run types` to check for type errors

### Development Setup
1. Start Docker services: `docker-compose up -d`
2. Install PHP dependencies: `composer install`
3. Install Node dependencies: `npm install`
4. Run migrations: `php artisan migrate`
5. Start development: `composer run dev`

This is a modern, full-stack e-commerce application with advanced search capabilities, proper caching, and a comprehensive development environment.