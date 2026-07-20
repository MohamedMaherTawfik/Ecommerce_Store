import api from "../ApiClient";
export default {
    list: (params = {}) => api.get("/blog", { params }).then(r => r.data),
    show: slug => api.get(`/blog/${slug}`).then(r => r.data),
    categories: () => api.get("/blog/categories").then(r => r.data),
    tags: () => api.get("/blog/tags").then(r => r.data),
};
