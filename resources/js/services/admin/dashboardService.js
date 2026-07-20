import api from "../AdminApiClient";

export default {
    async statistics(params = {}) {
        const response = await api.get("/admin/dashboard/statistics", { params });
        return response.data;
    },
};
