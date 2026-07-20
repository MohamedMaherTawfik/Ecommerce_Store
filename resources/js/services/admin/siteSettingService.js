import api from '../AdminApiClient';

export default {
    getAll() {
        return api.get('/admin/site-settings');
    },
    get(key) {
        return api.get(`/admin/site-settings/${key}`);
    },
    create(data) {
        return api.post('/admin/site-settings/create', data);
    },
    update(key, data) {
        return api.post(`/admin/site-settings/${key}`, data);
    },
    batchUpdate(data) {
        return api.post('/admin/site-settings/batch', data);
    },
    delete(key) {
        return api.delete(`/admin/site-settings/${key}`);
    }
};
