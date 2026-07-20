import api from "../../ApiClient";

const CategoryService = {
    async getCategories(params = {}) {
        const response = await api.get("/categories", { params });
        return response.data;
    },

    async getCategory(slug) {
        const response = await api.get(`/categories/${slug}`);
        return response.data;
    },
};

export default CategoryService;
