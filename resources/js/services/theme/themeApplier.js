/**
 * themeApplier.js
 * ──────────────────────────────────────────────────────────
 * Maps an API palette object to CSS custom properties and
 * injects them onto document.documentElement so they are
 * available globally across all pages via var(--xxx).
 *
 * Supported API fields → CSS Variables:
 *
 *  primary          → --primary
 *  secondary        → --secondary
 *  accent           → --accent
 *  background       → --background
 *  surface          → --surface
 *  border           → --border
 *  text             → --text
 *  text_secondary   → --text-secondary
 *  success          → --success
 *  warning          → --warning
 *  danger           → --danger
 *  info             → --info
 *  hero_from        → --hero-from
 *  hero_to          → --hero-to
 *
 * All keys are optional: missing keys are simply skipped so
 * the CSS fallback values in :root remain in effect.
 */

/**
 * Normalise a raw API key to a CSS variable name.
 * Converts underscores to hyphens and lowercases the string.
 * e.g. "hero_from" → "--hero-from"
 * @param {string} key
 * @returns {string}
 */
function toCssVar(key) {
    return `--${key.toLowerCase().replace(/_/g, '-')}`;
}

/**
 * The canonical mapping of API response fields to CSS variables.
 * Keys are the API field names; values are the CSS variable names.
 * Add new mappings here to support future palette fields without
 * touching any page or component.
 */
const FIELD_MAP = {
    primary:        '--primary',
    secondary:      '--secondary',
    accent:         '--accent',
    background:     '--background',
    surface:        '--surface',
    border:         '--border',
    text:           '--text',
    text_secondary: '--text-secondary',
    success:        '--success',
    warning:        '--warning',
    danger:         '--danger',
    info:           '--info',
    hero_from:      '--hero-from',
    hero_to:        '--hero-to',
};

/**
 * Flatten a possibly-nested palette object.
 * Supports both flat `{ primary: '#fff' }` and
 * nested `{ colors: { primary: '#fff' } }` shapes.
 * @param {Object} raw
 * @returns {Object}
 */
function flattenPalette(raw) {
    if (!raw || typeof raw !== 'object') return {};

    // Try to unwrap common API nesting patterns
    const data = raw.data ?? raw.colors ?? raw.palette ?? raw;

    if (typeof data !== 'object') return {};

    return data;
}

/**
 * Apply a palette to the document root as CSS custom properties.
 * @param {Object} palette - Raw API response (or cached) data
 */
export function applyTheme(palette) {
    const root = document.documentElement;
    const flat = flattenPalette(palette);

    let appliedCount = 0;

    // Apply known mapped fields
    for (const [apiKey, cssVar] of Object.entries(FIELD_MAP)) {
        const value = flat[apiKey];
        if (value && typeof value === 'string' && value.trim()) {
            root.style.setProperty(cssVar, value.trim());
            appliedCount++;
        }
    }

    // Also auto-discover any additional fields using the naming convention
    // (snake_case API field → kebab-case CSS var) for forward compatibility
    for (const [key, value] of Object.entries(flat)) {
        if (key in FIELD_MAP) continue; // already handled above
        if (!value || typeof value !== 'string') continue;

        const cssVar = toCssVar(key);
        root.style.setProperty(cssVar, value.trim());
        appliedCount++;
    }

    if (import.meta.env.DEV && appliedCount > 0) {
        console.info(`[ThemeApplier] Applied ${appliedCount} CSS variables from palette.`);
    }
}

/**
 * Remove all dynamically-set theme variables from the root element,
 * reverting to the CSS fallback values defined in :root.
 */
export function resetTheme() {
    const root = document.documentElement;
    for (const cssVar of Object.values(FIELD_MAP)) {
        root.style.removeProperty(cssVar);
    }
}
