/**
 * themeClient.js
 * ──────────────────────────────────────────────────────────
 * A lightweight, dedicated Axios instance for the palette API.
 * Intentionally isolated from the main ApiClient so it:
 *  - Skips Auth / Bearer tokens (palette is a public endpoint)
 *  - Skips the installMode interceptor
 *  - Silently fails without triggering app-wide notifications
 */

import axios from 'axios';

const apiOrigin = (
    import.meta.env.VITE_API_URL || window.location.origin
).replace(/\/$/, '');

const themeClient = axios.create({
    baseURL: `${apiOrigin}/api/v1`,
    timeout: 8000,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'x-api-key': "sk_test_8d7f2a1b4c6e9f0a123456789abcdef"
    },
});

/**
 * Fetch a palette by numeric ID.
 * @param {1|2|3|4} id
 * @returns {Promise<Object>} raw API response data
 */
export async function fetchPalette(id) {
    const response = await themeClient.get(`/palletes/${id}`);
    return response.data;
}
