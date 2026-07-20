import api from "../../ApiClient";

const BrandService = {
    async getBrands(params = {}) {
        const response = await api.get("/brands", { params });
        return response.data;
    },
};

export default BrandService;
