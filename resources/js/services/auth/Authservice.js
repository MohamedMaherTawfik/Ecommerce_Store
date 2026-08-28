import api from "@/services/ApiClient";
import router from "@/router";
import { canAccessAdmin, getAdminHomePath } from "@/config/adminAccess";
import {
    clearSession,
    getRole,
    getUserData,
    setSession,
} from "@/services/auth/authSession";

const apiOrigin = (
    import.meta.env.VITE_API_URL || window.location.origin
).replace(/\/$/, "");

const saveSession = (role, userData = null) => setSession(role, userData);

const redirectByRole = (role, userData = null) => {
    const user = userData || { role };
    router.push(canAccessAdmin(user) ? getAdminHomePath(user) : "/");
};

const getQueryValue = (query, key) => {
    if (!query) return null;
    if (query instanceof URLSearchParams) return query.get(key);

    const value = query[key];
    return Array.isArray(value) ? value[0] ?? null : value ?? null;
};

const authService = {
    async login(credentials) {
        const { data } = await api.post("/users/login", credentials);
        const user = data.data?.user;
        const normalizedRole = saveSession(user?.role, user);
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
        const user = data.data?.user;
        const normalizedRole = saveSession(user?.role, user);
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

    async handleGoogleCallback(query = null) {
        const urlParams = query ?? new URLSearchParams(window.location.search);
        const errorMsg =
            getQueryValue(urlParams, "error") ||
            getQueryValue(urlParams, "message");

        if (errorMsg) throw new Error(decodeURIComponent(errorMsg));

        try {
            const { data } = await api.get("/users/profile");
            const profile = data.data?.user ?? data.data ?? {};
            const role = profile.role ?? "user";
            saveSession(role, profile);
            redirectByRole(role, profile);
            return true;
        } catch {
            clearSession();
            return false;
        }
    },

    async logout({ redirect = true } = {}) {
        try {
            await api.post("/users/logout");
        } finally {
            clearSession();

            if (redirect) {
                const lang = localStorage.getItem("language") || "en";
                await router.push(`/${lang}/auth`);
            }
        }
    },

    getRole() {
        return getRole();
    },

    isLoggedIn() {
        return Boolean(getRole());
    },

    isAdmin() {
        return canAccessAdmin(getUserData() || { role: getRole() });
    },
};

export default authService;
