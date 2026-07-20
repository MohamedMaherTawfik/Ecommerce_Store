import api from "../../ApiClient";

const ProfileService = {
    async getProfile() {
        const response = await api.get("/users/profile");
        return response.data;
    },

    async updateProfile(payload) {
        const response = await api.post("/users/update-profile", payload);
        return response.data;
    },

    async updatePassword(payload) {
        const response = await api.post("/users/password", payload);
        return response.data;
    },

    async deleteAccount() {
        const response = await api.delete("/users/delete-account");
        return response.data;
    },

    async getWallet() {
        const response = await api.get("/users/wallet");
        return response.data;
    },
};

export default ProfileService;
