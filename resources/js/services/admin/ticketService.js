import api from "../AdminApiClient";
export default {
    list: (params = {}) => api.get("/admin/tickets", { params }).then(r => r.data),
    show: id => api.get(`/admin/tickets/${id}`).then(r => r.data),
    reply: (id, message) => api.post(`/admin/tickets/${id}/reply`, { message }).then(r => r.data),
    update: (id, data) => api.patch(`/admin/tickets/${id}`, data).then(r => r.data),
};
