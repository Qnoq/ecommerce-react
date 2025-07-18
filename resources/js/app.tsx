import '../css/app.css'

import { createInertiaApp } from '@inertiajs/react'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createRoot } from 'react-dom/client'
import { ThemeProvider } from '@/hooks/use-theme'
import { CartProvider } from '@/contexts/CartContext'

const appName = import.meta.env.VITE_APP_NAME || 'Laravel'

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) =>
    resolvePageComponent(
      `./pages/${name}.tsx`,
      import.meta.glob('./pages/**/*.tsx'),
    ),
  setup({ el, App, props }) {
    const root = createRoot(el)

    root.render(
      <ThemeProvider defaultTheme="system" storageKey="shoplux-theme">
        <CartProvider>
          <App {...props} />
        </CartProvider>
      </ThemeProvider>
    )
  },
  progress: {
    color: '#4F46E5',
  },
})