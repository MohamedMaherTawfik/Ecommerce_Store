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
        return response.data;
    },
};

export default OrderService;
