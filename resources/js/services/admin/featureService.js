import api from '../AdminApiClient';

export default {
    getAll() {
        return api.get('/admin/features');
    },
    get(id) {
        return api.get(`/admin/features/${id}`);
    },
    create(data) {
        return api.post('/admin/features/create', data);
    },
    update(id, data) {
        return api.post(`/admin/features/${id}`, data);
    },
    delete(id) {
        return api.delete(`/admin/features/${id}`);
    }
};
