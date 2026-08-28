import axios from "axios";
import toastr from "toastr";
import "toastr/build/toastr.min.css";
import {
    clearSession,
    getRedirectPath,
    isPublicRequest,
    isRedirecting,
    markRedirecting,
    shouldHandleUnauthorized,
} from "@/services/auth/authSession";

toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: "toast-top-right",
    timeOut: 3000,
};

const api = axios.create({
    baseURL: "/api",
    withCredentials: true,
    headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
    },
});

api.interceptors.request.use(
    (config) => {
        config.headers.delete("Authorization");
        return config;
    },
    (error) => Promise.reject(error),
);

api.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        const url = error.config?.url || "";
        const requestPath = String(url);
        const isAuthProtectedRequest = shouldHandleUnauthorized(requestPath);
        const isPublic = isPublicRequest(requestPath);

        if (!error.response) {
            toastr.error("There was a problem connecting to the server.");
            return Promise.reject(error);
        }

        switch (status) {
            case 400:
                toastr.warning(error.response?.data?.message || "Invalid request.");
                break;

            case 401:
                if (isPublic) {
                    return Promise.reject(error);
                }

                if (isAuthProtectedRequest && !isRedirecting()) {
                    markRedirecting();
                    clearSession();
                    toastr.error("Session expired. Please sign in again.");
                    window.location.replace(getRedirectPath(requestPath));
                }

                break;

            case 403:
                if (error.response?.data?.action === "install") {
                    if (window.location.pathname !== "/install" && window.location.pathname !== "/installer") {
                        window.location.replace("/install");
                    }
                } else {
                    toastr.error(error.response?.data?.message || "You are not allowed to access this resource.");
                }
                break;

            case 404:
                toastr.error(error.response?.data?.message || "The requested resource was not found.");
                break;

            case 422: {
                const errors = error.response?.data?.errors;

                if (errors) {
                    Object.values(errors).forEach((fieldErrors) => {
                        fieldErrors.forEach((message) => toastr.error(message));
                    });
                } else {
                    toastr.error(error.response?.data?.message || "Invalid data.");
                }
                break;
            }

            case 429:
                toastr.warning(error.response?.data?.message || "Too many requests. Please try again later.");
                break;

            case 500:
                toastr.error(error.response?.data?.message || "Server error.");
                break;

            case 502:
            case 503:
            case 504:
                toastr.error(error.response?.data?.message || "The server is temporarily unavailable.");
                break;

            default:
                toastr.error(error.response?.data?.message || "An unexpected error occurred.");
        }

        return Promise.reject(error);
    },
);

export default api;
