import api from '../AdminApiClient';

export default {
    getAll() {
        return api.get('/admin/nav-links');
    },
    get(id) {
        return api.get(`/admin/nav-links/${id}`);
    },
    create(data) {
        return api.post('/admin/nav-links/create', data);
    },
    update(id, data) {
        return api.post(`/admin/nav-links/${id}`, data);
    },
    delete(id) {
        return api.delete(`/admin/nav-links/${id}`);
    }
};
