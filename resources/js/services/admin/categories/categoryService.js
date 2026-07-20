import api from "../../AdminApiClient";

const categoryService = {
    async getCategories(params = {}) {
        const response = await api.get("/admin/categories", { params });
        return response.data;
    },

    async getAllCategories(params = {}) {
        const response = await api.get("/admin/categories/all/categories", {
            params,
        });
        return response.data;
    },

    async getCategoryCount() {
        const response = await api.get("/admin/categories/category/count");
        return response.data;
    },

    async getCategoryById(id) {
        const response = await api.get(`/admin/categories/${id}`);
        return response.data;
    },

    async getCategoryProducts(id, params = {}) {
        const response = await api.get(`/admin/categories/${id}/products`, {
            params,
        });
        return response.data;
    },

    async createCategory(payload) {
        const response = await api.post("/admin/categories/create", payload);
        return response.data;
    },

    async updateCategory(id, payload) {
        const response = await api.post(`/admin/categories/${id}`, payload);
        return response.data;
    },

    async deleteCategory(id) {
        const response = await api.delete(`/admin/categories/${id}`);
        return response.data;
    },

    async getTrashedCategories(params = {}) {
        const response = await api.get("/admin/categories/trashed", { params });
        return response.data;
    },

    async restoreCategory(id) {
        const response = await api.post(`/admin/categories/${id}/restore`);
        return response.data;
    },
};

export default categoryService;
