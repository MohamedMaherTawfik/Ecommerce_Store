import { mount, flushPromises } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import ProductsList from "../Index.vue";

vi.mock("vue-router", () => ({
    RouterLink: {
        props: ["to"],
        template: "<a><slot /></a>",
    },
    useRoute: () => ({ params: { lang: "en" }, query: {} }),
    useRouter: () => ({ push: vi.fn(), replace: vi.fn() }),
}));

vi.mock("@/services/home/ProductService", () => ({
    default: {
        getProducts: vi.fn().mockResolvedValue({
            data: {
                data: [{ id: 1, name: "Essential Cotton Tee", price: 28, stock: 4, average_rating: 4.8, reviews_count: 12 }],
                current_page: 1,
                last_page: 1,
                total: 1,
            },
        }),
    },
}));

vi.mock("@/services/home/categorey/CategoryService", () => ({
    default: {
        getCategories: vi.fn().mockResolvedValue({ data: { data: [{ id: 1, name: "T-Shirts" }] } }),
    },
}));

vi.mock("@/services/home/brand/BrandService", () => ({
    default: {
        getBrands: vi.fn().mockResolvedValue({ data: { data: [{ id: 1, name: "Urban Loom" }] } }),
    },
}));

vi.mock("@/services/home/CartService", () => ({
    default: { add: vi.fn() },
}));

vi.mock("@/services/home/WishlistService", () => ({
    default: { toggle: vi.fn() },
}));

describe("ProductsList", () => {
    it("renders filters and product cards", async () => {
        const wrapper = mount(ProductsList);

        await flushPromises();

        expect(wrapper.text()).toContain("Products");
        expect(wrapper.text()).toContain("T-Shirts");
        expect(wrapper.text()).toContain("Urban Loom");
        expect(wrapper.text()).toContain("Essential Cotton Tee");
    });
});
