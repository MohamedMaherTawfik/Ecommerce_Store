import api from "../ApiClient";
export default {
    list: () => api.get("/support/tickets").then(r => r.data),
    create: data => api.post("/support/tickets", data).then(r => r.data),
    show: id => api.get(`/support/tickets/${id}`).then(r => r.data),
    reply: (id, message) => api.post(`/support/tickets/${id}/reply`, { message }).then(r => r.data),
    status: (id, status) => api.patch(`/support/tickets/${id}/status`, { status }).then(r => r.data),
};
