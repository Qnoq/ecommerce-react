import React, { useState } from 'react'
import { router } from '@inertiajs/react'
import { Search, X } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { useTranslation } from '@/hooks/useTranslation'

interface SimpleSearchProps {
  placeholder?: string
  className?: string
  onSearch?: (query: string) => void
  autoFocus?: boolean
  defaultValue?: string
}

export function SimpleSearch({ 
  placeholder,
  className = '',
  onSearch,
  autoFocus = false,
  defaultValue = ''
}: SimpleSearchProps) {
  const { __ } = useTranslation()
  const [query, setQuery] = useState(defaultValue)

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (query.trim()) {
      if (onSearch) {
        onSearch(query.trim())
      } else {
        router.get('/products', { search: query.trim() })
      }
    }
  }

  const clearSearch = () => {
    setQuery('')
  }

  return (
    <form onSubmit={handleSubmit} className={`relative ${className}`}>
      <div className="relative">
        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          type="text"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder={placeholder || __('common.search_products')}
          className="pl-10 pr-10"
          autoComplete="off"
          autoFocus={autoFocus}
        />
        {query && (
          <Button
            type="button"
            variant="ghost"
            size="icon"
            className="absolute right-8 top-1/2 transform -translate-y-1/2 h-6 w-6"
            onClick={clearSearch}
          >
            <X className="h-3 w-3" />
          </Button>
        )}
        <Button
          type="submit"
          variant="ghost"
          size="icon"
          className="absolute right-1 top-1/2 transform -translate-y-1/2 h-6 w-6"
          disabled={!query.trim()}
        >
          <Search className="h-3 w-3" />
        </Button>
      </div>
    </form>
  )
}

export default SimpleSearch 