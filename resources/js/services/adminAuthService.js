import api from "./AdminApiClient";
import { getAdminHomePath } from "@/config/adminAccess";
import {
    clearSession,
    getRole,
    getToken,
    getUserData,
    setSession,
} from "@/services/auth/authSession";

const adminAuthService = {
    async login(credentials) {
        const response = await api.post("/admin/login", credentials);
        const data = response.data;
        const authData = data?.data ?? {};
        const token = authData?.token;
        const user = authData?.user;

        if (token) {
            setSession(token, user?.role ?? "admin", user ?? null);
        }

        return data;
    },

    logout() {
        clearSession();
        window.location.href = "/admin/auth";
    },

    isAuthenticated() {
        return Boolean(getToken());
    },

    getRole() {
        return getRole();
    },

    dashboardPath() {
        return getAdminHomePath(getUserData() || {});
    },
};

export default adminAuthService;
