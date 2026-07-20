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
};

export default HomeService;
