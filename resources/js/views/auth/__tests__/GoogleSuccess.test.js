import { flushPromises, mount } from "@vue/test-utils";
import { beforeEach, describe, expect, it, vi } from "vitest";

const { push, replace, apiGet, apiClient, routeQuery } = vi.hoisted(() => {
    const push = vi.fn();
    const replace = vi.fn();
    const apiGet = vi.fn();
    const routeQuery = {
        token: "oauth-token-123",
        role: "admin",
    };
    const apiClient = {
        defaults: { headers: { common: {} } },
        get: apiGet,
    };

    return { push, replace, apiGet, apiClient, routeQuery };
});

vi.mock("vue-router", () => ({
    useRoute: () => ({
        query: routeQuery,
    }),
    useRouter: () => ({
        replace,
    }),
}));

vi.mock("@/router", () => ({
    default: {
        push,
    },
}));

vi.mock("@/services/ApiClient", () => ({
    default: apiClient,
}));

import GoogleSuccess from "../GoogleSuccess.vue";

describe("GoogleSuccess", () => {
    beforeEach(() => {
        localStorage.clear();
        push.mockReset();
        replace.mockReset();
        apiGet.mockReset();
        apiClient.defaults.headers.common = {};
        routeQuery.token = "oauth-token-123";
        routeQuery.role = "admin";
    });

    it("stores Google OAuth params and redirects the user", async () => {
        apiGet.mockResolvedValue({
            data: {
                data: {
                    role: "admin",
                },
            },
        });

        mount(GoogleSuccess);
        await flushPromises();

        expect(localStorage.getItem("auth_token")).toBe("oauth-token-123");
        expect(localStorage.getItem("user_role")).toBe("admin");
        expect(apiClient.defaults.headers.common.Authorization).toBe("Bearer oauth-token-123");
        expect(push).toHaveBeenCalledWith("/admin");
    });

    it("redirects to login when token is missing", async () => {
        delete routeQuery.token;
        routeQuery.role = "user";

        mount(GoogleSuccess);
        await flushPromises();

        expect(localStorage.getItem("auth_token")).toBeNull();
        expect(replace).toHaveBeenCalledWith("/en/auth");
    });
});
