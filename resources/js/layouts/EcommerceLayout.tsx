import React, { useState } from 'react'
import { Link, router } from '@inertiajs/react'
import { 
  Search, 
  ShoppingCart, 
  User, 
  Menu, 
  Heart,
  Mail,
  Instagram,
  Facebook,
  Twitter,
  ChevronDown,
  Truck,
  Shield,
  RotateCcw,
  CreditCard,
  X
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Sheet,
  SheetContent,
  SheetTitle,
  SheetTrigger,
} from '@/components/ui/sheet'
import { ThemeToggle } from '@/components/theme-toggle'
import { LanguageSwitcher } from '@/components/LanguageSwitcher'
import { useTranslation } from '@/hooks/useTranslation'
import { Breadcrumbs } from '@/components/breadcrumbs'
import { Toaster } from '@/components/ui/sonner'
import { SearchWithSuggestions, SearchModalLive } from '@/components/Search'
import { SearchProvider } from '@/contexts/SearchContext'
import { usePage } from '@inertiajs/react'

interface EcommerceLayoutProps {
  children: React.ReactNode
  title?: string
  user?: {
    id: number
    name: string
    email: string
  }
  cartCount?: number
  categories?: Array<{
    id: number
    name: string
    slug: string
  }>
  breadcrumbs?: Array<{
    title: string
    href: string
  }>
}

export default function EcommerceLayout({ 
  children, 
  title, 
  user, 
  cartCount: propCartCount = 0,
  categories = [],
  breadcrumbs = []
}: EcommerceLayoutProps) {
  const { __ } = useTranslation()
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false)
  const [isSearchModalOpen, setIsSearchModalOpen] = useState(false)
  
  // Utiliser le cartCount des props Inertia shared
  const { props } = usePage()
  const cartCount = (props as any).cartCount || 0

  const handleSearch = (query: string) => {
    // Navigation vers la page de recherche unifiée (style Amazon)
    router.visit(`/s?k=${encodeURIComponent(query)}`, {
      method: 'get',
      preserveScroll: false,
      preserveState: false,
    })
  }

  const mainCategories = [
    { name: __('ecommerce.electronic'), slug: 'electronique' },
    { name: __('ecommerce.fashion'), slug: 'mode' },
    { name: __('ecommerce.home'), slug: 'maison' },
    { name: __('ecommerce.sport'), slug: 'sport' },
    { name: __('ecommerce.beauty'), slug: 'beaute' },
  ]

  return (
    <SearchProvider>
      <div className="min-h-screen bg-background">
      {/* Skip link for accessibility */}
      <a 
        href="#main-content" 
        className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-primary text-primary-foreground px-4 py-2 rounded-md z-[200]"
      >
        {__('accessibility.skip_to_content')}
      </a>

      {/* Top Bar */}
      <div className="bg-muted/50 border-b">
        <div className="container mx-auto px-4 py-2">
          <div className="flex items-center justify-between text-sm">
            <div className="hidden md:flex items-center space-x-6 text-muted-foreground">
              <div className="flex items-center space-x-1">
                <Mail className="h-3 w-3" />
                <span>{__('company.contact_email')}</span>
              </div>
            </div>
            
            <div className="flex items-center space-x-4">
              <span className="text-muted-foreground">
                {__('ecommerce.free_shipping_from', { amount: '50' })}
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* Main Header */}
      <header className="bg-background border-b sticky top-0 z-50 backdrop-blur-sm bg-background/95">
        <div className="container mx-auto px-4">
          <div className="flex items-center justify-between h-16">
            
            {/* Logo */}
            <Link href="/" className="flex items-center space-x-2 flex-shrink-0">
              <div className="w-8 h-8 bg-gradient-to-br from-primary to-primary/70 rounded-lg flex items-center justify-center">
                <span className="text-primary-foreground font-bold text-sm">SL</span>
              </div>
              <span className="text-lg sm:text-xl font-bold bg-gradient-to-r from-primary to-primary/70 bg-clip-text text-transparent">
                {__('company.company_name')}
              </span>
            </Link>

            {/* Search Bar - Desktop uniquement */}
            <div className="hidden md:flex flex-1 max-w-lg mx-8">
              <SearchWithSuggestions 
                onSearch={handleSearch}
                placeholder={__('common.search_products')}
                className="w-full"
              />
            </div>

            {/* Right Actions */}
            <div className="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
              
              {/* Search Button - Mobile uniquement */}
              <Button 
                variant="ghost" 
                size="icon" 
                className="md:hidden" 
                aria-label={__('common.search_products')}
                onClick={() => setIsSearchModalOpen(true)}
              >
                <Search className="h-5 w-5" />
              </Button>

              {/* Language Switcher - Desktop uniquement */}
              <div className="hidden md:block">
                <LanguageSwitcher />
              </div>
              
              {/* Theme Toggle - Desktop uniquement */}
              <div className="hidden md:block">
                <ThemeToggle />
              </div>

              {/* Wishlist - Desktop uniquement */}
              <Button variant="ghost" size="icon" className="hidden lg:flex" aria-label={__('common.my_wishlist')}>
                <Heart className="h-5 w-5" />
              </Button>

              {/* Cart - Toujours visible */}
              <Button 
                variant="ghost" 
                size="icon" 
                className="relative" 
                aria-label={__('common.cart')}
                asChild
              >
                <Link href={route('cart.index')}>
                  <ShoppingCart className="h-5 w-5" />
                  {cartCount > 0 && (
                    <Badge 
                      variant="destructive" 
                      className="absolute -top-2 -right-2 h-5 w-5 rounded-full p-0 flex items-center justify-center text-xs"
                    >
                      {cartCount > 9 ? '9+' : cartCount}
                    </Badge>
                  )}
                </Link>
              </Button>

              {/* User Menu - Desktop */}
              {user ? (
                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="icon" className="hidden md:flex" aria-label={__('common.my_profile')}>
                      <User className="h-5 w-5" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end" className="w-56">
                    <DropdownMenuLabel>
                      <div className="flex flex-col space-y-1">
                        <p className="text-sm font-medium">{user.name}</p>
                        <p className="text-xs text-muted-foreground">{user.email}</p>
                      </div>
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem asChild>
                      <Link href="/profile">{__('common.my_profile')}</Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                      <Link href="/orders">{__('common.my_orders')}</Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                      <Link href="/wishlist">{__('common.my_wishlist')}</Link>
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem asChild>
                      <Link href="/logout" method="post">{__('common.logout')}</Link>
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              ) : (
                <div className="hidden md:flex items-center space-x-2">
                  <Button variant="ghost" asChild>
                    <Link href="/login">{__('common.login')}</Link>
                  </Button>
                  <Button asChild>
                    <Link href="/register">{__('common.register')}</Link>
                  </Button>
                </div>
              )}

              {/* User Button - Mobile (si non connecté) */}
              {!user && (
                <Button variant="ghost" size="icon" className="md:hidden" asChild aria-label={__('common.login')}>
                  <Link href="/login">
                    <User className="h-5 w-5" />
                  </Link>
                </Button>
              )}

              {/* Mobile Menu - Toujours visible sur mobile */}
              <Sheet open={isMobileMenuOpen} onOpenChange={setIsMobileMenuOpen}>
                <SheetTrigger asChild>
                  <Button variant="ghost" size="icon" className="md:hidden" aria-label={__('common.menu')}>
                    <Menu className="h-5 w-5" />
                  </Button>
                </SheetTrigger>
                <SheetContent side="right" className="w-full sm:w-96 p-0">
                  <SheetTitle className="sr-only">{__('common.menu')}</SheetTitle>
                  {/* Header personnalisé */}
                  <div className="flex items-center justify-between p-6 border-b bg-muted/50">
                    <h2 className="text-lg font-semibold">{__('common.menu')}</h2>
                  </div>

                  <div className="flex flex-col h-full">
                    <div className="flex-1 px-6 py-4 space-y-6 overflow-y-auto">
                      {/* User Section - Si connecté */}
                      {user && (
                        <div className="pb-6 border-b">
                          <div className="flex items-center space-x-3 mb-4">
                            <div className="w-10 h-10 bg-primary rounded-full flex items-center justify-center">
                              <User className="h-5 w-5 text-primary-foreground" />
                            </div>
                            <div className="min-w-0 flex-1">
                              <p className="font-medium truncate">{user.name}</p>
                              <p className="text-sm text-muted-foreground truncate">{user.email}</p>
                            </div>
                          </div>
                          <div className="space-y-1">
                            <Link 
                              href="/profile" 
                              className="block py-2 text-sm hover:text-primary transition-colors"
                              onClick={() => setIsMobileMenuOpen(false)}
                            >
                              {__('common.my_profile')}
                            </Link>
                            <Link 
                              href="/orders" 
                              className="block py-2 text-sm hover:text-primary transition-colors"
                              onClick={() => setIsMobileMenuOpen(false)}
                            >
                              {__('common.my_orders')}
                            </Link>
                            <Link 
                              href="/wishlist" 
                              className="block py-2 text-sm hover:text-primary transition-colors"
                              onClick={() => setIsMobileMenuOpen(false)}
                            >
                              {__('common.my_wishlist')}
                            </Link>
                          </div>
                        </div>
                      )}

                      {/* Auth Section - Si non connecté */}
                      {!user && (
                        <div className="pb-6 border-b space-y-3">
                          <Button className="w-full" asChild>
                            <Link href="/login" onClick={() => setIsMobileMenuOpen(false)}>
                              {__('common.login')}
                            </Link>
                          </Button>
                          <Button variant="outline" className="w-full" asChild>
                            <Link href="/register" onClick={() => setIsMobileMenuOpen(false)}>
                              {__('common.register')}
                            </Link>
                          </Button>
                        </div>
                      )}

                      {/* Navigation */}
                      <nav className="space-y-1">
                        <Link 
                          href="/" 
                          className="block py-3 text-base font-medium hover:text-primary transition-colors"
                          onClick={() => setIsMobileMenuOpen(false)}
                        >
                          {__('navigation.home')}
                        </Link>
                        {mainCategories.map((category) => (
                          <Link
                            key={category.slug}
                            href={`/categories/${category.slug}`}
                            className="block py-3 text-base font-medium hover:text-primary transition-colors"
                            onClick={() => setIsMobileMenuOpen(false)}
                          >
                            {category.name}
                          </Link>
                        ))}
                        <Link 
                          href="/deals" 
                          className="block py-3 text-base font-medium hover:text-primary transition-colors"
                          onClick={() => setIsMobileMenuOpen(false)}
                        >
                          {__('navigation.deals')}
                        </Link>
                        <Link 
                          href="/new" 
                          className="block py-3 text-base font-medium hover:text-primary transition-colors"
                          onClick={() => setIsMobileMenuOpen(false)}
                        >
                          {__('navigation.new')}
                        </Link>
                        <Link 
                          href="/bestsellers" 
                          className="block py-3 text-base font-medium hover:text-primary transition-colors"
                          onClick={() => setIsMobileMenuOpen(false)}
                        >
                          {__('navigation.bestsellers')}
                        </Link>
                        <Link 
                          href="/about" 
                          className="block py-3 text-base font-medium hover:text-primary transition-colors"
                          onClick={() => setIsMobileMenuOpen(false)}
                        >
                          {__('navigation.about')}
                        </Link>
                        <Link 
                          href="/contact" 
                          className="block py-3 text-base font-medium hover:text-primary transition-colors"
                          onClick={() => setIsMobileMenuOpen(false)}
                        >
                          {__('navigation.contact')}
                        </Link>
                      </nav>

                      {/* Settings Section */}
                      <div className="pt-6 border-t space-y-4">
                        <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                          {__('common.settings')}
                        </h3>
                        
                        {/* Language Switcher */}
                        <div className="flex items-center justify-between">
                          <span className="text-sm font-medium">{__('common.language')}</span>
                          <LanguageSwitcher />
                        </div>

                        {/* Theme Toggle */}
                        <div className="flex items-center justify-between">
                          <span className="text-sm font-medium">{__('common.theme')}</span>
                          <ThemeToggle />
                        </div>
                      </div>
                    </div>

                    {/* Footer avec Logout */}
                    {user && (
                      <div className="p-6 border-t bg-muted/30">
                        <Button 
                          variant="outline" 
                          className="w-full" 
                          asChild
                        >
                          <Link 
                            href="/logout" 
                            method="post"
                            onClick={() => setIsMobileMenuOpen(false)}
                          >
                            {__('common.logout')}
                          </Link>
                        </Button>
                      </div>
                    )}
                  </div>
                </SheetContent>
              </Sheet>
            </div>
          </div>
        </div>
      </header>

      {/* Navigation Bar */}
      <nav className="hidden md:block bg-muted/30 border-b">
        <div className="container mx-auto px-4">
          <div className="flex items-center justify-between h-12">
            <div className="flex items-center space-x-8">
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" className="flex items-center space-x-1">
                    <Menu className="h-4 w-4" />
                    <span>{__('common.categories')}</span>
                    <ChevronDown className="h-4 w-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent className="w-56">
                  {mainCategories.map((category) => (
                    <DropdownMenuItem key={category.slug} asChild>
                      <Link href={`/categories/${category.slug}`}>
                        {category.name}
                      </Link>
                    </DropdownMenuItem>
                  ))}
                </DropdownMenuContent>
              </DropdownMenu>

              <Link href="/deals" className="text-sm font-medium hover:text-primary transition-colors">
                {__('navigation.deals')}
              </Link>
              <Link href="/new" className="text-sm font-medium hover:text-primary transition-colors">
                {__('navigation.new')}
              </Link>
              <Link href="/bestsellers" className="text-sm font-medium hover:text-primary transition-colors">
                {__('navigation.bestsellers')}
              </Link>
            </div>

            <div className="flex items-center space-x-6 text-sm text-muted-foreground">
              <div className="flex items-center space-x-1">
                <Truck className="h-4 w-4" />
                <span>{__('ecommerce.free_shipping')}</span>
              </div>
              <div className="flex items-center space-x-1">
                <RotateCcw className="h-4 w-4" />
                <span>{__('ecommerce.returns_30d')}</span>
              </div>
              <div className="flex items-center space-x-1">
                <Shield className="h-4 w-4" />
                <span>{__('ecommerce.secure_payment')}</span>
              </div>
            </div>
          </div>
        </div>
      </nav>

      {/* Breadcrumb */}
      {breadcrumbs.length > 0 && (
        <div className="bg-muted/30 border-b py-3">
          <div className="container mx-auto px-4">
            <Breadcrumbs breadcrumbs={breadcrumbs} />
          </div>
        </div>
      )}

      {/* Main Content */}
      <main className="flex-1" id="main-content">
        {children}
      </main>

      {/* Footer */}
      <footer className="bg-muted/50 border-t mt-20">
        <div className="container mx-auto px-4 py-12">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            
            {/* Company Info */}
            <div className="space-y-4">
              <div className="flex items-center space-x-2">
                <div className="w-6 h-6 bg-gradient-to-br from-primary to-primary/70 rounded flex items-center justify-center">
                  <span className="text-primary-foreground font-bold text-xs">SL</span>
                </div>
                <span className="text-lg font-bold">{__('company.company_name')}</span>
              </div>
              <p className="text-sm text-muted-foreground">
                {__('company.company_description')}
              </p>
              <div className="flex space-x-3">
                <Button variant="ghost" size="icon" className="h-8 w-8">
                  <Facebook className="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" className="h-8 w-8">
                  <Instagram className="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" className="h-8 w-8">
                  <Twitter className="h-4 w-4" />
                </Button>
              </div>
            </div>

            {/* Quick Links */}
            <div className="space-y-4">
              <h4 className="text-sm font-semibold">{__('navigation.quick_links')}</h4>
              <div className="space-y-2">
                <Link href="/about" className="block text-sm text-muted-foreground hover:text-primary transition-colors">
                  {__('navigation.about')}
                </Link>
                <Link href="/contact" className="block text-sm text-muted-foreground hover:text-primary transition-colors">
                  {__('navigation.contact')}
                </Link>
                <Link href="/faq" className="block text-sm text-muted-foreground hover:text-primary transition-colors">
                  {__('navigation.faq')}
                </Link>
                <Link href="/blog" className="block text-sm text-muted-foreground hover:text-primary transition-colors">
                  {__('navigation.blog')}
                </Link>
              </div>
            </div>

            {/* Customer Service */}
            <div className="space-y-4">
              <h4 className="text-sm font-semibold">{__('navigation.customer_service')}</h4>
              <div className="space-y-2">
                <Link href="/support" className="block text-sm text-muted-foreground hover:text-primary transition-colors">
                  {__('navigation.help_support')}
                </Link>
                <Link href="/shipping" className="block text-sm text-muted-foreground hover:text-primary transition-colors">
                  {__('navigation.shipping')}
                </Link>
                <Link href="/returns" className="block text-sm text-muted-foreground hover:text-primary transition-colors">
                  {__('navigation.returns')}
                </Link>
                <Link href="/warranty" className="block text-sm text-muted-foreground hover:text-primary transition-colors">
                  {__('navigation.warranty')}
                </Link>
              </div>
            </div>

            {/* Newsletter */}
            <div className="space-y-4">
              <h4 className="text-sm font-semibold">{__('common.newsletter')}</h4>
              <p className="text-sm text-muted-foreground">
                {__('common.newsletter_description')}
              </p>
              <form className="space-y-2">
                <input
                  type="email"
                  placeholder={__('common.your_email')}
                  className="w-full h-9 px-3 py-1 text-sm bg-background border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                />
                <Button className="w-full h-9">
                  {__('common.subscribe')}
                </Button>
              </form>
            </div>
          </div>

          {/* Bottom Bar */}
          <div className="border-t mt-8 pt-8 flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0">
            <div className="text-sm text-muted-foreground">
              © 2025 {__('company.company_name')}. {__('common.all_rights_reserved')}.
            </div>
            <div className="flex items-center space-x-6 text-sm text-muted-foreground">
              <Link href="/privacy" className="hover:text-primary transition-colors">
                {__('navigation.privacy')}
              </Link>
              <Link href="/terms" className="hover:text-primary transition-colors">
                {__('navigation.terms')}
              </Link>
              <Link href="/cookies" className="hover:text-primary transition-colors">
                {__('navigation.cookies')}
              </Link>
            </div>
            <div className="flex items-center space-x-2">
              <CreditCard className="h-4 w-4 text-muted-foreground" />
              <span className="text-sm text-muted-foreground">{__('ecommerce.secure_payments')}</span>
            </div>
          </div>
        </div>
      </footer>

      {/* Search Modal */}
      <SearchModalLive 
        isOpen={isSearchModalOpen}
        onClose={() => setIsSearchModalOpen(false)}
        placeholder={__('common.search_placeholder')}
      />

      {/* Toast Container */}
      <Toaster />
      </div>
    </SearchProvider>
  )
}