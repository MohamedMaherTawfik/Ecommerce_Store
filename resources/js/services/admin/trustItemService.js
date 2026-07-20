import api from '../AdminApiClient';

export default {
    getAll() {
        return api.get('/admin/trust-items');
    },
    get(id) {
        return api.get(`/admin/trust-items/${id}`);
    },
    create(data) {
        return api.post('/admin/trust-items/create', data);
    },
    update(id, data) {
        return api.post(`/admin/trust-items/${id}`, data);
    },
    delete(id) {
        return api.delete(`/admin/trust-items/${id}`);
    }
};
