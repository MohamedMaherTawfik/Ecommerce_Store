import api from "../../AdminApiClient";

const OrderService = {
    async getOrders(params = {}) {
        const response = await api.get("/admin/orders", { params });
        return response.data;
    },

    async getOrder(id) {
        const response = await api.get(`/admin/orders/${id}`);
        return response.data;
    },

    async updateStatus(id, data) {
        const response = await api.put(`/admin/orders/${id}/status`, data);
        return response.data;
    },

    async deleteOrder(id) {
        const response = await api.delete(`/admin/orders/${id}`);
        return response.data;
    },
};

export default OrderService;
