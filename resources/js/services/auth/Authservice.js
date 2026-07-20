import api from "@/services/ApiClient";
import router from "@/router";
import { canAccessAdmin, getAdminHomePath } from "@/config/adminAccess";
import {
    clearSession,
    getRole,
    getToken,
    getUserData,
    setSession,
} from "@/services/auth/authSession";

const apiOrigin = (
    import.meta.env.VITE_API_URL || window.location.origin
).replace(/\/$/, "");

/**
 * Persist auth state and attach Authorization header to the axios instance.
 */
const saveSession = (token, role, userData = null) => {
    const normalizedRole = setSession(token, role, userData);

    if (token) {
        api.defaults.headers.common.Authorization = `Bearer ${token}`;
    } else {
        delete api.defaults.headers.common.Authorization;
    }

    return normalizedRole;
};

const redirectByRole = (role, userData = null) => {
    const user = userData || { role };
    router.push(canAccessAdmin(user) ? getAdminHomePath(user) : "/");
};

const getQueryValue = (query, key) => {
    if (!query) {
        return null;
    }

    if (query instanceof URLSearchParams) {
        return query.get(key);
    }

    const value = query[key];

    if (Array.isArray(value)) {
        return value[0] ?? null;
    }

    return value ?? null;
};

const authService = {
    async login(credentials) {
        const { data } = await api.post("/users/login", credentials);
        const token = data.data?.token;
        const user = data.data?.user;
        const normalizedRole = saveSession(token, user?.role, user);

        redirectByRole(normalizedRole, user);
    },

    async sendOtp(email) {
        await api.post("/users/send-otp", { email });
    },

    async verifyOtp(email, otp) {
        await api.post("/users/verify-otp", { email, otp });
    },

    async register(formData) {
        const payload = new FormData();

        payload.append("name", formData.name);
        payload.append("email", formData.email);
        payload.append("password", formData.password);
        payload.append("password_confirmation", formData.password_confirmation);

        if (formData.phone) payload.append("phone", formData.phone);
        if (formData.image) payload.append("image", formData.image);

        const { data } = await api.post("/users/register", payload, {
            headers: { "Content-Type": "multipart/form-data" },
        });

        const token = data.data?.token;
        const user = data.data?.user;
        const normalizedRole = saveSession(token, user?.role, user);

        redirectByRole(normalizedRole, user);
    },

    async forgotPassword(email) {
        await api.post("/users/forgot-password", { email });
    },

    async resetPassword(payload) {
        await api.post("/users/reset-password", payload);
    },

    getGoogleAuthUrl() {
        return `${apiOrigin}/api/v1/users/google-login`;
    },

    /**
     * Handle the Google OAuth callback.
     *
     * The backend redirects to /auth/google-success?token=TOKEN&role=ROLE.
     * This function reads those params, stores the token, fetches the profile,
     * and then navigates to the correct dashboard.
     *
     * Returns false when token is missing (triggers redirect to login).
     */
    async handleGoogleCallback(query = null) {
        const urlParams = query ?? new URLSearchParams(window.location.search);
        const tokenFromUrl = getQueryValue(urlParams, "token");
        const roleFromUrl = getQueryValue(urlParams, "role");
        const errorMsg =
            getQueryValue(urlParams, "error") ||
            getQueryValue(urlParams, "message");

        if (errorMsg) {
            throw new Error(decodeURIComponent(errorMsg));
        }

        // No token in URL → cannot authenticate.
        if (!tokenFromUrl) {
            return false;
        }

        // Strip sensitive params from browser URL immediately.
        if (typeof window !== "undefined") {
            try {
                const cleanUrl = new URL(window.location.href);
                cleanUrl.search = "";
                window.history.replaceState(
                    {},
                    document.title,
                    cleanUrl.toString(),
                );
            } catch {
                // Non-browser environment (tests) — safe to ignore.
            }
        }

        // Persist token and role immediately so auth state is set even if
        // the profile fetch below fails.
        saveSession(tokenFromUrl, roleFromUrl ?? "user");

        // Attempt to fetch the full profile to get the canonical role.
        try {
            const { data } = await api.get("/users/profile");
            const profile = data.data?.user ?? data.data ?? {};
            const finalRole = profile.role ?? roleFromUrl ?? "user";
            saveSession(tokenFromUrl, finalRole, profile);
            redirectByRole(finalRole, profile);
        } catch {
            // Profile fetch failed (token may already be set) — navigate by
            // the role provided in the redirect URL.
            redirectByRole(roleFromUrl ?? "user");
        }

        return true;
    },

    async logout({ redirect = true } = {}) {
        try {
            await api.post("/users/logout");
        } catch {
            // The local session still gets cleared below.
        } finally {
            clearSession();
            delete api.defaults.headers.common.Authorization;

            if (redirect) {
                const lang = localStorage.getItem("language") || "en";
                await router.push(`/${lang}/auth`);
            }
        }
    },

    getToken() {
        return getToken();
    },

    getRole() {
        return getRole();
    },

    isLoggedIn() {
        return Boolean(getToken() || getRole());
    },

    isAdmin() {
        return canAccessAdmin(getUserData() || { role: getRole() });
    },
};

export default authService;
