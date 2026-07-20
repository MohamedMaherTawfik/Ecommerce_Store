import api from '../AdminApiClient';

export default {
    getAll() {
        return api.get('/admin/banners');
    },
    get(id) {
        return api.get(`/admin/banners/${id}`);
    },
    create(data) {
        return api.post('/admin/banners/create', data);
    },
    update(id, data) {
        return api.post(`/admin/banners/${id}`, data);
    },
    delete(id) {
        return api.delete(`/admin/banners/${id}`);
    }
};
