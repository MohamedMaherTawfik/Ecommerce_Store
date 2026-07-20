import api from "../AdminApiClient";
export default {
    list: () => api.get("/admin/permissions").then(r => r.data),
    update: (role, permissions) => api.put(`/admin/permissions/roles/${role}`, { permissions }).then(r => r.data),
};
