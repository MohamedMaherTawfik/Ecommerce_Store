import api from "../../AdminApiClient";

const brandService = {
    async getBrands(params = {}) {
        const response = await api.get("/admin/brands", { params });
        return response.data;
    },

    async getAllBrands(params = {}) {
        const response = await api.get("/admin/brands/all/brands", { params });
        return response.data;
    },

    async getBrandCount() {
        const response = await api.get("/admin/brands/brand/count");
        return response.data;
    },

    async getBrandById(id) {
        const response = await api.get(`/admin/brands/${id}`);
        return response.data;
    },

    async getBrandProducts(id, params = {}) {
        const response = await api.get(`/admin/brands/${id}/products`, {
            params,
        });
        return response.data;
    },

    async createBrand(payload) {
        const response = await api.post("/admin/brands/create", payload);
        return response.data;
    },

    async updateBrand(id, payload) {
        const response = await api.post(`/admin/brands/${id}`, payload);
        return response.data;
    },

    async deleteBrand(id) {
        const response = await api.delete(`/admin/brands/${id}`);
        return response.data;
    },

    async getTrashedBrands(params = {}) {
        const response = await api.get("/admin/brands/trashed", { params });
        return response.data;
    },

    async restoreBrand(id) {
        const response = await api.post(`/admin/brands/${id}/restore`);
        return response.data;
    },
};

export default brandService;
