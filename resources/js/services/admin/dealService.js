import api from '../AdminApiClient';

export default {
    getAll() {
        return api.get('/admin/deals');
    },
    get(id) {
        return api.get(`/admin/deals/${id}`);
    },
    create(data) {
        return api.post('/admin/deals/create', data);
    },
    update(id, data) {
        return api.post(`/admin/deals/${id}`, data);
    },
    delete(id) {
        return api.delete(`/admin/deals/${id}`);
    }
};
