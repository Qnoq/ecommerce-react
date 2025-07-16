/**
 * Utility functions for price formatting
 */

/**
 * Safely parse any price value to a number
 */
export const parsePrice = (price: any): number => {
  if (typeof price === 'number') return price
  if (typeof price === 'string') return parseFloat(price) || 0
  return 0
}

/**
 * Format price as currency string
 */
export const formatPrice = (price: any, currency: string = '€'): string => {
  const numericPrice = parsePrice(price)
  return `${numericPrice.toFixed(2)} ${currency}`
}

/**
 * Format price with currency symbol before (e.g., €12.99)
 */
export const formatPricePrefix = (price: any, currency: string = '€'): string => {
  const numericPrice = parsePrice(price)
  return `${currency}${numericPrice.toFixed(2)}`
}

/**
 * Calculate discount percentage
 */
export const calculateDiscountPercent = (originalPrice: any, currentPrice: any): number => {
  const original = parsePrice(originalPrice)
  const current = parsePrice(currentPrice)
  
  if (original <= 0 || current <= 0 || current >= original) return 0
  
  return Math.round(((original - current) / original) * 100)
}

/**
 * Check if there's a valid discount
 */
export const hasDiscount = (originalPrice: any, currentPrice: any): boolean => {
  const original = parsePrice(originalPrice)
  const current = parsePrice(currentPrice)
  
  return original > 0 && current > 0 && original > current
}