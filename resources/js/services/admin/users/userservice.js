import api from "../../AdminApiClient";

const userService = {
    async getUsers(params = {}) {
        const response = await api.get("/admin/users", { params });
        return response.data;
    },

    async getAllUsers(params = {}) {
        const response = await api.get("/admin/users/all/get", { params });
        return response.data;
    },

    async getUserCount() {
        const response = await api.get("/admin/users/user/count");
        return response.data;
    },

    async getUserById(id) {
        const response = await api.get(`/admin/users/${id}`);
        return response.data;
    },

    async createUser(payload) {
        const response = await api.post("/admin/users/create", payload);
        return response.data;
    },

    async updateUser(id, payload) {
        const response = await api.post(`/admin/users/${id}`, payload);
        return response.data;
    },

    async deleteUser(id) {
        const response = await api.delete(`/admin/users/${id}`);
        return response.data;
    },
};

export default userService;
