import api from "../AdminApiClient";

const CouponService = {
    async getCoupons(params = {}) {
        const response = await api.get("/admin/coupons", { params });
        return response.data;
    },

    async create(payload) {
        const response = await api.post("/admin/coupons/create", payload);
        return response.data;
    },

    async update(id, payload) {
        const response = await api.put(`/admin/coupons/${id}`, payload);
        return response.data;
    },

    async delete(id) {
        const response = await api.delete(`/admin/coupons/${id}`);
        return response.data;
    },
};

export default CouponService;
