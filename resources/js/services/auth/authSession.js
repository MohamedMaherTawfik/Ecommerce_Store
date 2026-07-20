const STORAGE_KEYS = {
    token: "auth_token",
    role: "user_role",
    userData: "user_data",
};

let runtimeToken = null;

const AUTH_PATH_PREFIXES = [
    "/api/v1/users/profile",
    "/api/v1/users/update-profile",
    "/api/v1/users/password",
    "/api/v1/users/logout",
    "/api/v1/users/delete-account",
    "/api/v1/cart/",
    "/api/v1/pay",
    "/api/v1/order/status/",
    "/api/v1/wishlist/",
    "/api/v1/products/",
    "/api/v1/admin/",
];

const PUBLIC_PATH_PREFIXES = [
    "/api/v1/layout",
    "/api/v1/home-content",
    "/api/v1/categories",
    "/api/v1/brands",
    "/api/v1/products",
    "/api/v1/users/login",
    "/api/v1/users/register",
    "/api/v1/users/send-otp",
    "/api/v1/users/verify-otp",
    "/api/v1/users/forgot-password",
    "/api/v1/users/reset-password",
    "/api/v1/users/google-login",
    "/api/v1/users/google-callback",
    "/api/installer/",
    "/api/v1/paypal/success",
    "/api/v1/paypal/cancel",
    "/api/v1/paypal/webhook",
];

let authRedirectInProgress = false;

/**
 * Read a value from localStorage (primary) with sessionStorage fallback.
 */
const readStorage = (key) => {
    try {
        return localStorage.getItem(key) || sessionStorage.getItem(key);
    } catch {
        return null;
    }
};

/**
 * Write a value to both localStorage (persistent) and sessionStorage.
 */
const writeStorage = (key, value) => {
    try {
        localStorage.setItem(key, value);
        sessionStorage.setItem(key, value);
    } catch {
        // Ignore storage failures in privacy-restricted browsers.
    }
};

const removeStorage = (key) => {
    try {
        sessionStorage.removeItem(key);
        localStorage.removeItem(key);
    } catch {
        // Ignore storage failures in privacy-restricted browsers.
    }
};

const normalizePath = (url = "") => {
    if (!url) {
        return "";
    }

    try {
        return new URL(url, window.location.origin).pathname;
    } catch {
        return String(url);
    }
};

const pathMatches = (path, prefixes) => prefixes.some((prefix) => path === prefix || path.startsWith(prefix));

const getRedirectPath = (requestPath = "") => {
    const currentPath = normalizePath(requestPath);
    const lang = readStorage("language") || "en";

    if (currentPath.startsWith("/api/v1/admin") || window.location.pathname.startsWith("/admin")) {
        return "/admin/auth";
    }

    return `/${lang}/auth`;
};

const shouldHandleUnauthorized = (requestPath = "") => pathMatches(normalizePath(requestPath), AUTH_PATH_PREFIXES);

const isPublicRequest = (requestPath = "") => pathMatches(normalizePath(requestPath), PUBLIC_PATH_PREFIXES);

const isRedirecting = () => authRedirectInProgress;

const markRedirecting = () => {
    authRedirectInProgress = true;
    window.setTimeout(() => {
        authRedirectInProgress = false;
    }, 1000);
};

const clearSession = () => {
    runtimeToken = null;
    removeStorage(STORAGE_KEYS.token);
    removeStorage(STORAGE_KEYS.role);
    removeStorage(STORAGE_KEYS.userData);
};

/**
 * Persist auth state to localStorage + runtime memory.
 * token: Sanctum plaintext token (null for cookie-only flows)
 * role: "user" | "admin" | a custom dashboard role
 * userData: profile object or null
 */
const setSession = (token, role, userData = null) => {
    const normalizedRole = (role ?? "user").toString().toLowerCase().trim();

    runtimeToken = token || null;

    if (token) {
        writeStorage(STORAGE_KEYS.token, token);
    } else {
        removeStorage(STORAGE_KEYS.token);
    }

    writeStorage(STORAGE_KEYS.role, normalizedRole);

    if (userData !== null && userData !== undefined) {
        writeStorage(STORAGE_KEYS.userData, JSON.stringify(userData));
    }

    return normalizedRole;
};

const getToken = () => runtimeToken ?? readStorage(STORAGE_KEYS.token);
const getRole = () => readStorage(STORAGE_KEYS.role);
const getUserData = () => {
    const raw = readStorage(STORAGE_KEYS.userData);

    if (!raw) {
        return null;
    }

    try {
        return JSON.parse(raw);
    } catch {
        return null;
    }
};

const isAuthenticated = () => Boolean(getToken() || getRole());

export {
    clearSession,
    getRedirectPath,
    getRole,
    getToken,
    getUserData,
    isAuthenticated,
    isPublicRequest,
    isRedirecting,
    markRedirecting,
    normalizePath,
    setSession,
    shouldHandleUnauthorized,
};
