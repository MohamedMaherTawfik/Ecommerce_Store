import api from "../ApiClient";

const OrderService = {
    async checkout(payload) {
        const response = await api.post("/pay", payload);
        return response.data?.data || response.data;
    },

    async paymentMethods() {
        const response = await api.get("/payment-methods");
        return response.data?.data || response.data;
    },

    async status(id) {
        const response = await api.get(`/order/status/${id}`);
        return response.data?.data || response.data;
    },

    async list(params = {}) {
        const response = await api.get("/orders", { params });
        return response.data?.data || response.data;
    },

    async show(id) {
        const response = await api.get(`/orders/${id}`);
        return response.data?.data || response.data;
    },

    invoiceUrl(orderId) {
        return `/api/v1/orders/${orderId}/invoice/download`;
    },

    async returns() {
        const response = await api.get("/returns");
        return response.data?.data || response.data;
    },

    async returnDetails(id) {
        const response = await api.get(`/returns/${id}`);
        return response.data?.data || response.data;
    },

    async createReturn(orderId, payload) {
        const response = await api.post(`/orders/${orderId}/returns`, payload);
        return response.data?.data || response.data;
    },

    async cancelReturn(id) {
        const response = await api.post(`/returns/${id}/cancel`);
        return response.data?.data || response.data;
    },
};

export default OrderService;
