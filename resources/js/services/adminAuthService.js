import api from "./AdminApiClient";
import { getAdminHomePath } from "@/config/adminAccess";
import {
    clearSession,
    getRole,
    getUserData,
    setSession,
} from "@/services/auth/authSession";

const adminAuthService = {
    async login(credentials) {
        const response = await api.post("/admin/login", credentials);
        const data = response.data;
        const authData = data?.data ?? {};
        const user = authData?.user;

        if (user) {
            setSession(user?.role ?? "admin", user);
        }

        return data;
    },

    logout() {
        clearSession();
        window.location.href = "/admin/auth";
    },

    isAuthenticated() {
        return Boolean(getRole());
    },

    getRole() {
        return getRole();
    },

    dashboardPath() {
        return getAdminHomePath(getUserData() || {});
    },
};

export default adminAuthService;
