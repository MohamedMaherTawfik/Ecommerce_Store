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

    async updateOrderStatus(id, data) {
        const response = await api.patch(`/admin/orders/${id}/order-status`, data);
        return response.data;
    },

    async updatePaymentStatus(id, data) {
        const response = await api.patch(`/admin/orders/${id}/payment-status`, data);
        return response.data;
    },

    async updateShippingStatus(id, data) {
        const response = await api.patch(`/admin/orders/${id}/shipping-status`, data);
        return response.data;
    },

    async createShipment(id, data = {}) {
        const response = await api.post(`/admin/orders/${id}/shipment/create`, data);
        return response.data;
    },

    async buyLabel(id) {
        const response = await api.post(`/admin/orders/${id}/shipment/buy-label`);
        return response.data;
    },

    async trackShipment(id) {
        const response = await api.get(`/admin/orders/${id}/shipment/track`);
        return response.data;
    },

    async updateShipmentStatus(id, data) {
        const response = await api.patch(`/admin/orders/${id}/shipment/status`, data);
        return response.data;
    },

    invoiceUrl(orderId) {
        return `/api/admin/orders/${orderId}/invoice/download`;
    },

    async deleteOrder(id) {
        const response = await api.delete(`/admin/orders/${id}`);
        return response.data;
    },
};

export default OrderService;
