import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react'

interface CartContextType {
  cartCount: number
  updateCartCount: (count: number) => void
  refreshCartCount: () => void
}

const CartContext = createContext<CartContextType | undefined>(undefined)

export function useCart() {
  const context = useContext(CartContext)
  if (context === undefined) {
    throw new Error('useCart must be used within a CartProvider')
  }
  return context
}

interface CartProviderProps {
  children: ReactNode
  initialCount?: number
}

export function CartProvider({ children, initialCount = 0 }: CartProviderProps) {
  const [cartCount, setCartCount] = useState(initialCount)

  const updateCartCount = (count: number) => {
    setCartCount(count)
  }

  const refreshCartCount = async () => {
    try {
      const response = await fetch(route('cart.count'), {
        credentials: 'include', // Inclure les cookies
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        }
      })
      if (response.ok) {
        const data = await response.json()
        setCartCount(data.count)
      }
    } catch (error) {
      console.error('Error fetching cart count:', error)
    }
  }

  useEffect(() => {
    refreshCartCount()
  }, [])

  const contextValue: CartContextType = {
    cartCount,
    updateCartCount,
    refreshCartCount
  }

  return (
    <CartContext.Provider value={contextValue}>
      {children}
    </CartContext.Provider>
  )
}