import api from "../AdminApiClient";

const analyticsService = {
    async getRevenue(params = {}) {
        const response = await api.get("/admin/analytics/revenue", { params });
        return response.data;
    },

    async getSales() {
        const response = await api.get("/admin/analytics/sales");
        return response.data;
    },

    async getTopProducts() {
        const response = await api.get("/admin/analytics/top-products");
        return response.data;
    },

    async getTopCategories() {
        const response = await api.get("/admin/analytics/top-categories");
        return response.data;
    },

    async getTopCustomers() {
        const response = await api.get("/admin/analytics/top-customers");
        return response.data;
    },
};

export default analyticsService;
