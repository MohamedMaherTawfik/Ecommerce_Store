import api from "../ApiClient";

const ProductService = {
    async getProducts(params = {}) {
        const response = await api.get("/products", { params });
        return response.data;
    },

    async getProduct(id) {
        const response = await api.get(`/products/${id}`);
        return response.data;
    },

    async getRelated(id) {
        const response = await api.get(`/products/${id}/related`);
        return response.data;
    },

    async saveReview(productId, payload) {
        const response = await api.post(
            `/products/${productId}/reviews`,
            payload,
        );
        return response.data;
    },
};

export default ProductService;
