import React from 'react'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Globe } from 'lucide-react'
import { useTranslation } from '@/hooks/useTranslation'

const languages = {
  fr: { name: 'Français', flag: '🇫🇷' },
  en: { name: 'English', flag: '🇬🇧' },
  es: { name: 'Español', flag: '🇪🇸' },
  de: { name: 'Deutsch', flag: '🇩🇪' },
}

export function LanguageSwitcher() {
  const { locale, available_locales, changeLocale } = useTranslation()
  const currentLanguage = languages[locale as keyof typeof languages]

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" size="icon" className="relative">
          <Globe className="h-4 w-4" />
          <span className="sr-only">Change language</span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="min-w-[120px]">
        {available_locales.map((lang) => {
          const language = languages[lang as keyof typeof languages]
          if (!language) return null
          
          return (
            <DropdownMenuItem
              key={lang}
              onClick={() => changeLocale(lang)}
              className={`cursor-pointer ${locale === lang ? 'bg-accent' : ''}`}
            >
              <span className="mr-2">{language.flag}</span>
              <span className="text-sm">{language.name}</span>
              {locale === lang && (
                <span className="ml-auto text-xs text-primary">✓</span>
              )}
            </DropdownMenuItem>
          )
        })}
      </DropdownMenuContent>
    </DropdownMenu>
  )
}