import api from "../AdminApiClient";
export default {
    list: () => api.get("/admin/email-templates").then(r => r.data),
    save: (id, data) => api[id ? "put" : "post"](id ? `/admin/email-templates/${id}` : "/admin/email-templates", data).then(r => r.data),
    remove: id => api.delete(`/admin/email-templates/${id}`),
    preview: (id, variables = {}) => api.post(`/admin/email-templates/${id}/preview`, { variables }).then(r => r.data),
    test: (id, email, variables = {}) => api.post(`/admin/email-templates/${id}/test-send`, { email, variables }).then(r => r.data),
};
