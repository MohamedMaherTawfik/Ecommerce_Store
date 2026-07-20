import api from "../ApiClient";

const WishlistService = {
    async getWishlist(params = {}) {
        const response = await api.get("/wishlist", { params });
        return response.data;
    },

    async toggle(productId) {
        const response = await api.post(`/wishlist/${productId}`);
        return response.data;
    },

    async remove(productId) {
        const response = await api.delete(`/wishlist/${productId}`);
        return response.data;
    },
};

export default WishlistService;
