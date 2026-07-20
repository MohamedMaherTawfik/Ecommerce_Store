import api from "../AdminApiClient";

export default {
    get() {
        return api.get("/admin/settings/database");
    },
    test(data) {
        return api.post("/admin/settings/database/test", data);
    },
    update(data) {
        return api.put("/admin/settings/database", data);
    },
};
