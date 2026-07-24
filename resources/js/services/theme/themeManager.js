/**
 * themeManager.js
 * ──────────────────────────────────────────────────────────
 * The single entry-point for the Global Dynamic Theme System.
 *
 * Call `themeManager.init()` ONCE at app startup (in app.js,
 * before mount) and never anywhere else.
 *
 * Flow:
 *  1. Check LocalStorage for a valid (non-expired) cached palette.
 *     → Found: apply it immediately, done.
 *  2. Cache miss / expired: pick a random palette ID (1-4),
 *     call the API, save the result, and apply it.
 *  3. API error: log in dev, fail silently in production.
 *     The CSS fallback variables defined in :root will stay active.
 *
 * The system is self-contained — no page or component should
 * import this file except app.js.
 */

import { fetchPalette } from './themeClient.js';
import { getCachedTheme, saveTheme } from './themeStorage.js';
import { applyTheme } from './themeApplier.js';

/** Number of available palettes in the API */
const PALETTE_COUNT = 4;

/**
 * Pick a cryptographically-simple random integer in [1, PALETTE_COUNT].
 * @returns {number}
 */
function randomPaletteId() {
    return Math.floor(Math.random() * PALETTE_COUNT) + 1;
}

/**
 * Initialise the theme system.
 * Returns a Promise that resolves when the theme has been applied
 * (or silently failed). Always safe to await before mount.
 *
 * @returns {Promise<void>}
 */
async function init() {
    // ── Step 1: Try the cache first (fast path) ──────────────────
    const cached = getCachedTheme();

    if (cached) {
        applyTheme(cached);

        if (import.meta.env.DEV) {
            console.info('[ThemeManager] Using cached palette (cache still valid).');
        }

        return;
    }

    // ── Step 2: Cache miss — fetch a fresh palette ────────────────
    const id = randomPaletteId();

    if (import.meta.env.DEV) {
        console.info(`[ThemeManager] Cache miss — fetching palette #${id} from API.`);
    }

    try {
        const data = await fetchPalette(id);

        if (!data) {
            if (import.meta.env.DEV) {
                console.warn('[ThemeManager] API returned empty data, keeping fallback CSS.');
            }
            return;
        }

        // Save first, then apply — so even if applyTheme throws we don't re-fetch
        saveTheme(data);
        applyTheme(data);

        if (import.meta.env.DEV) {
            console.info(`[ThemeManager] Palette #${id} fetched and applied.`, data);
        }
    } catch (error) {
        // ── Step 3: Silent fail ───────────────────────────────────
        if (import.meta.env.DEV) {
            console.warn('[ThemeManager] Failed to load palette from API. Using CSS fallback.', error);
        }
        // No notification, no redirect, no UI change — fallback CSS vars remain active.
    }
}

const themeManager = { init };

export default themeManager;
