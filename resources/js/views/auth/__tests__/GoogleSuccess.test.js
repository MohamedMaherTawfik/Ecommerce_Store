import { flushPromises, mount } from "@vue/test-utils";
import { beforeEach, describe, expect, it, vi } from "vitest";

const { push, replace, apiGet, apiClient, routeQuery } = vi.hoisted(() => {
    const push = vi.fn();
    const replace = vi.fn();
    const apiGet = vi.fn();
    const routeQuery = {};
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
        delete routeQuery.message;
    });

    it("uses the HttpOnly cookie profile and redirects the user", async () => {
        apiGet.mockResolvedValue({
            data: {
                data: {
                    role: "admin",
                },
            },
        });

        mount(GoogleSuccess);
        await flushPromises();

        expect(localStorage.getItem("auth_token")).toBeNull();
        expect(localStorage.getItem("user_role")).toBe("admin");
        expect(apiClient.defaults.headers.common.Authorization).toBeUndefined();
        expect(push).toHaveBeenCalledWith("/admin");
    });

    it("redirects to login when the cookie profile is unavailable", async () => {
        apiGet.mockRejectedValue(new Error("Unauthenticated"));

        mount(GoogleSuccess);
        await flushPromises();

        expect(localStorage.getItem("auth_token")).toBeNull();
        expect(replace).toHaveBeenCalledWith("/en/auth");
    });
});
