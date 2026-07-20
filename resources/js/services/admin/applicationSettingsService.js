import api from "../AdminApiClient";

export default {
    get() {
        return api.get("/admin/settings/application");
    },
    update(data) {
        return api.put("/admin/settings/application", data);
    },
    sendTestMail(data = {}) {
        return api.post("/admin/settings/application/test-mail", data);
    },
};
