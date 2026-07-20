import api from "../../AdminApiClient";

const productService = {
    async getProducts(params = {}) {
        const response = await api.get("/admin/products", { params });
        return response.data;
    },

    async getProductCount() {
        const response = await api.get("/admin/products/products/count");
        return response.data;
    },

    async getProductById(id) {
        const response = await api.get(`/admin/products/${id}`);
        return response.data;
    },

    async createProduct(payload) {
        const response = await api.post("/admin/products/create", payload);
        return response.data;
    },

    async updateProduct(id, payload) {
        const response = await api.post(`/admin/products/${id}`, payload);
        return response.data;
    },

    async deleteProduct(id) {
        const response = await api.delete(`/admin/products/${id}`);
        return response.data;
    },

    async getTrashedProducts(params = {}) {
        const response = await api.get("/admin/products/trashed", { params });
        return response.data;
    },

    async restoreProduct(id) {
        const response = await api.post(`/admin/products/${id}/restore`);
        return response.data;
    },
};

export default productService;
