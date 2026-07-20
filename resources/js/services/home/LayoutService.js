import api from '../ApiClient';
import { readInitialData } from '@/utils/initialData';

let layoutData = readInitialData('initial-layout-data');
let layoutRequest = null;

const LayoutService = {
    getInitialLayoutData() {
        return layoutData;
    },

    async getLayout() {
        if (layoutData) {
            return { success: true, data: layoutData };
        }

        layoutRequest ??= api.get('/layout')
            .then((response) => {
                layoutData = response.data?.data || null;
                return response.data;
            })
            .finally(() => {
                layoutRequest = null;
            });

        return layoutRequest;
    },
};

export default LayoutService;
