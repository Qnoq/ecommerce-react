import { usePage, router } from '@inertiajs/react';

interface SharedData {
    translations: Record<string, string>;
    locale: string;
    available_locales?: string[]; // Ajoutez cette ligne
}

export function useTranslation() {
    const { translations, locale, available_locales = ['fr', 'en'] } = usePage<SharedData>().props;
    
    const __ = (key: string, replacements: Record<string, string> = {}) => {
        let translation = translations[key] || key;
        
        Object.keys(replacements).forEach((r) => {
            translation = translation.replace(`:${r}`, replacements[r]);
        });
        
        return translation;
    };

    const changeLocale = (newLocale: string) => {
        router.post(route('locale.change'), { locale: newLocale }, {
            preserveState: true,
            preserveScroll: true,
        });
    };
    
    return {
        __,
        trans: __,
        locale,
        available_locales,
        changeLocale,
    };
}