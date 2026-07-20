import api from '../AdminApiClient';

export default {
    getAll() {
        return api.get('/admin/testimonials');
    },
    get(id) {
        return api.get(`/admin/testimonials/${id}`);
    },
    create(data) {
        return api.post('/admin/testimonials/create', data);
    },
    update(id, data) {
        return api.post(`/admin/testimonials/${id}`, data);
    },
    delete(id) {
        return api.delete(`/admin/testimonials/${id}`);
    }
};
