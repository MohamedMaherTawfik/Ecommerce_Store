import api from '../ApiClient';
import { readInitialData } from '@/utils/initialData';

let homeData = readInitialData('initial-home-data');
let homeRequest = null;

const HomeService = {
    getInitialHomeData() {
        return homeData;
    },

    async getHomeContent() {
        if (homeData) {
            return { success: true, data: homeData };
        }

        homeRequest ??= api.get('/home-content')
            .then((response) => {
                homeData = response.data?.data || null;
                return response.data;
            })
            .finally(() => {
                homeRequest = null;
            });

        return homeRequest;
    },

    async getLatestProducts() {
        const response = await api.get('/products/latest-four');
        return response.data;
    },

    async getRandomThree() {
        const response = await api.get('/products/random-three');
        return response.data;
    },

    async getRandomFour() {
        const response = await api.get('/products/random-four');
        return response.data;
    },

    async getFeaturedProducts() {
        const response = await api.get('/products/featured');
        return response.data;
    },

    async getCategories() {
        const response = await api.get('/categories');
        return response.data;
    },

    async getBrands() {
        const response = await api.get('/brands');
        return response.data;
    },
};

export default HomeService;
