import api from "../AdminApiClient";
export default {
    posts: (params = {}) => api.get("/admin/blog/posts", { params }).then(r => r.data),
    showPost: id => api.get(`/admin/blog/posts/${id}`).then(r => r.data),
    savePost: (id, data) => api.post(id ? `/admin/blog/posts/${id}` : "/admin/blog/posts", data).then(r => r.data),
    deletePost: id => api.delete(`/admin/blog/posts/${id}`),
    categories: () => api.get("/admin/blog/categories").then(r => r.data),
    saveCategory: (id, data) => api[id ? "put" : "post"](id ? `/admin/blog/categories/${id}` : "/admin/blog/categories", data).then(r => r.data),
    deleteCategory: id => api.delete(`/admin/blog/categories/${id}`),
    tags: () => api.get("/admin/blog/tags").then(r => r.data),
    saveTag: (id, data) => api[id ? "put" : "post"](id ? `/admin/blog/tags/${id}` : "/admin/blog/tags", data).then(r => r.data),
    deleteTag: id => api.delete(`/admin/blog/tags/${id}`),
};
