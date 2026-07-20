import { describe, expect, it, vi } from "vitest";
import api from "../ApiClient";
import ProductService from "../ProductService";

vi.mock("../ApiClient", () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
    },
}));

describe("ProductService", () => {
    it("fetches products through the shared ApiClient", async () => {
        api.get.mockResolvedValueOnce({ data: { data: [] } });

        await ProductService.getProducts({ sort: "rating" });

        expect(api.get).toHaveBeenCalledWith("/products", { params: { sort: "rating" } });
    });

    it("saves reviews through the shared ApiClient", async () => {
        api.post.mockResolvedValueOnce({ data: { status: "success" } });

        await ProductService.saveReview(7, { rating: 5, comment: "Excellent" });

        expect(api.post).toHaveBeenCalledWith("/products/7/reviews", { rating: 5, comment: "Excellent" });
    });
});
