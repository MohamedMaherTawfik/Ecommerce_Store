import { createI18n } from 'vue-i18n';
import en from './en.json';
import ar from './ar.json';

/**
 * Supported locales — used to validate before setting.
 */
export const SUPPORTED_LOCALES = ['en', 'ar'];

/**
 * Validate and return a supported locale, falling back to 'en'.
 */
export function resolveLocale(candidate) {
    if (!candidate) {
        return 'en';
    }

    const normalized = String(candidate).toLowerCase().trim().slice(0, 2);
    return SUPPORTED_LOCALES.includes(normalized) ? normalized : 'en';
}

const savedLang = resolveLocale(localStorage.getItem('language'));

const i18n = createI18n({
    legacy: false,
    globalInjection: true,
    locale: savedLang,
    fallbackLocale: 'en',
    messages: { en, ar },

    // ─── Suppress warnings in production ───
    // Missing keys gracefully fall back to 'en' without console noise.
    missingWarn: import.meta.env.DEV,
    fallbackWarn: import.meta.env.DEV,
});

export default i18n;
