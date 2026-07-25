import api from '../ApiClient';

const walletService = {
    getWallet: async () => {
        try {
            const response = await api.get('/users/wallet');
            return response.data;
        } catch (error) {
            throw error;
        }
    }
};

export default walletService;
