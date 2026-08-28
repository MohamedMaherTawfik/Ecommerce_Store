import axios from "axios";
import { notify } from "@/services/notifications";
import {
    clearSession,
    getRedirectPath,
    isPublicRequest,
    isRedirecting,
    markRedirecting,
    shouldHandleUnauthorized,
} from "@/services/auth/authSession";

const apiOrigin = (
    import.meta.env.VITE_API_URL || window.location.origin
).replace(/\/$/, "");
const api = axios.create({
    baseURL: `${apiOrigin}/api/v1`,
    withCredentials: true,
    headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",

    },
});

let installMode = false;

export function setInstallMode(value) {
    installMode = Boolean(value);
}

export function isInstallMode() {
    return installMode;
}

api.interceptors.request.use(
    (config) => {
        if (installMode) {
            const controller = new AbortController();
            controller.abort();
            config.signal = controller.signal;
            return config;
        }

        config.headers.delete("Authorization");
        return config;
    },
    (error) => Promise.reject(error),
);

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (axios.isCancel(error) || error.code === "ERR_CANCELED") {
            return Promise.reject(error);
        }

        const status = error.response?.status;
        const url = error.config?.url || "";
        const requestPath = String(url);
        const isAuthProtectedRequest = shouldHandleUnauthorized(requestPath);
        const isPublic = isPublicRequest(requestPath);

        if (!error.response) {
            notify("error", "There was a problem connecting to the server.");
            return Promise.reject(error);
        }

        switch (status) {
            case 400:
                notify("warning",
                    error.response?.data?.message || "Invalid request.",
                );
                break;

            case 401:
                if (isPublic) {
                    return Promise.reject(error);
                }

                if (isAuthProtectedRequest && !isRedirecting()) {
                    markRedirecting();
                    clearSession();
                    notify("error", "Session expired. Please sign in again.");
                    window.location.replace(getRedirectPath(requestPath));
                }

                break;

            case 403:
                if (error.response?.data?.action === "install") {
                    if (
                        window.location.pathname !== "/install" &&
                        window.location.pathname !== "/installer"
                    ) {
                        window.location.replace("/install");
                    }
                } else {
                    notify("error",
                        error.response?.data?.message ||
                            "You are not allowed to access this resource.",
                    );
                }
                break;

            case 404:
                notify("error",
                    error.response?.data?.message ||
                        "The requested resource was not found.",
                );
                break;

            case 422: {
                const errors = error.response?.data?.errors;

                if (errors) {
                    Object.values(errors).forEach((fieldErrors) => {
                        fieldErrors.forEach((message) => notify("error", message));
                    });
                } else {
                    notify("error",
                        error.response?.data?.message || "Invalid data.",
                    );
                }
                break;
            }

            case 429:
                notify("warning",
                    error.response?.data?.message ||
                        "Too many requests. Please try again later.",
                );
                break;

            case 500:
                notify("error", error.response?.data?.message || "Server error.");
                break;

            case 502:
            case 504:
                notify("error", "The server is temporarily unavailable.");
                break;

            case 503:
                if (error.response?.data?.action === "install") {
                    installMode = true;
                    if (
                        window.location.pathname !== "/install" &&
                        window.location.pathname !== "/installer"
                    ) {
                        window.location.replace("/install");
                    }
                } else if (!installMode) {
                    notify("error",
                        error.response?.data?.message ||
                            "The server is temporarily unavailable.",
                    );
                }
                break;

            default:
                notify("error",
                    error.response?.data?.message ||
                        "An unexpected error occurred.",
                );
        }

        return Promise.reject(error);
    },
);

export default api;
