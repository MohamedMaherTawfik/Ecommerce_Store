/**
 * themeStorage.js
 * ──────────────────────────────────────────────────────────
 * Handles reading and writing the active palette to/from
 * LocalStorage with a 12-hour Time-To-Live (TTL).
 *
 * Stored shape:
 * {
 *   palette: { ...API response data },
 *   expiresAt: 1234567890000   // Unix timestamp ms
 * }
 */

const STORAGE_KEY = 'app_dynamic_theme';
const TTL_MS = 12 * 60 * 60 * 1000; // 12 hours in milliseconds

/**
 * Retrieve a valid (non-expired) cached palette.
 * Returns null if no cache exists or if it has expired.
 * @returns {{ palette: Object } | null}
 */
export function getCachedTheme() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return null;

        const stored = JSON.parse(raw);
        if (!stored?.palette || !stored?.expiresAt) return null;

        const isExpired = Date.now() >= stored.expiresAt;
        if (isExpired) {
            localStorage.removeItem(STORAGE_KEY);
            return null;
        }

        return stored.palette;
    } catch {
        // Corrupt data — clear and treat as cache miss
        localStorage.removeItem(STORAGE_KEY);
        return null;
    }
}

/**
 * Save a palette to LocalStorage with a fresh 12-hour TTL.
 * @param {Object} palette - The full API response data object
 */
export function saveTheme(palette) {
    try {
        const entry = {
            palette,
            expiresAt: Date.now() + TTL_MS,
        };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(entry));
    } catch {
        // LocalStorage may be full or unavailable — fail silently
    }
}

/**
 * Clear the cached theme immediately (useful for debugging).
 */
export function clearTheme() {
    localStorage.removeItem(STORAGE_KEY);
}
