import api from "../ApiClient";

const CartService = {
    async getCart() {
        const response = await api.get("/cart/get");
        return response.data;
    },

    async add(productId, payload = {}) {
        const response = await api.post(`/cart/addToCart/${productId}`, payload);
        return response.data;
    },

    async remove(itemId) {
        const response = await api.delete(`/cart/delete/${itemId}`);
        return response.data;
    },

    async updateQuantity(itemId, quantity) {
        const response = await api.put(`/cart/items/${itemId}`, { quantity });
        return response.data;
    },

    async clear() {
        const response = await api.delete("/cart/clearCart");
        return response.data;
    },

    async applyCoupon(code) {
        const response = await api.post("/cart/coupon", { code });
        return response.data;
    },

    async removeCoupon() {
        const response = await api.delete("/cart/coupon");
        return response.data;
    },
};

export default CartService;
