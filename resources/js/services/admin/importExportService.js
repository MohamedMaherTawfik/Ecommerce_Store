import api from "../AdminApiClient";

const importExportService = {
    async exportProducts(format = "xlsx") {
        const response = await api.get("/admin/export/products", {
            params: { format },
            responseType: "blob",
        });
        return response.data;
    },

    async exportCategories(format = "xlsx") {
        const response = await api.get("/admin/export/categories", {
            params: { format },
            responseType: "blob",
        });
        return response.data;
    },

    async exportOrders(format = "xlsx") {
        const response = await api.get("/admin/export/orders", {
            params: { format },
            responseType: "blob",
        });
        return response.data;
    },

    async importProducts(file, updateExisting = false) {
        const formData = new FormData();
        formData.append("file", file);
        formData.append("update_existing", updateExisting ? "1" : "0");
        const response = await api.post("/admin/import/products", formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        return response.data;
    },

    async importCategories(file, updateExisting = false) {
        const formData = new FormData();
        formData.append("file", file);
        formData.append("update_existing", updateExisting ? "1" : "0");
        const response = await api.post("/admin/import/categories", formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        return response.data;
    },

    async sample(type, format = "xlsx") {
        const response = await api.get(`/admin/import/sample/${type}`, {
            params: { format },
            responseType: "blob",
        });
        return response.data;
    },
};

export default importExportService;
