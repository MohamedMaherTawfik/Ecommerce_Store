<template>
    <AdminLayout>
        <Transition appear name="page-fade">
            <div class="admin-page">
                <section class="admin-page-header">
                    <div class="admin-page-copy">
                        <div class="admin-page-kicker">
                            <i class="bi bi-diagram-3"></i>
                            <span>Categories</span>
                        </div>
                        <h2 class="admin-page-title">Organize categories with less visual noise</h2>
                        <p class="admin-page-description">
                            Review categories in a cleaner table with improved hover states and action grouping.
                        </p>
                    </div>

                    <div class="admin-page-actions">
                        <RouterLink to="/admin/categories/create" class="btn-admin btn-admin--primary">
                            <i class="bi bi-plus-lg"></i>
                            <span>Create Category</span>
                        </RouterLink>
                    </div>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <h3 class="admin-panel__title">Category List</h3>
                            <p class="admin-panel__meta">Maintain a clean product taxonomy from one place.</p>
                        </div>
                        <span class="admin-pill">
                            <i class="bi bi-layers"></i>
                            <span>{{ categories.length }} visible</span>
                        </span>
                    </div>

                    <div class="admin-panel__body">
                        <div v-if="loading" class="admin-skeleton-panel">
                            <div class="admin-skeleton-line admin-skeleton-line--lg"></div>
                            <div class="admin-skeleton-line"></div>
                            <div class="admin-skeleton-line admin-skeleton-line--md"></div>
                        </div>

                        <div v-else-if="categories.length === 0" class="admin-empty-state">
                            <div class="admin-empty-state__icon">
                                <i class="bi bi-folder-plus"></i>
                            </div>
                            <h3 class="admin-empty-state__title">No categories found</h3>
                            <p class="admin-empty-state__text">
                                Create a category to start structuring the catalog in a more manageable way.
                            </p>
                        </div>

                        <div v-else class="admin-table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Category</th>
                                        <th>Image</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="category in categories" :key="category.id">
                                        <td class="admin-table__primary">#{{ category.id }}</td>
                                        <td>
                                            <div class="admin-table__primary">{{ category.name || "-" }}</div>
                                            <div class="admin-table__secondary">{{ category.slug || "No slug" }}</div>
                                        </td>
                                        <td>
                                            <div class="admin-image-cell">
                                                <img v-if="category.image" :src="getImageUrl(category.image)"
                                                    alt="category" class="admin-image" />
                                                <span v-else>-</span>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="admin-actions">
                                                <RouterLink :to="`/admin/categories/${category.id}`"
                                                    class="btn-admin btn-admin--soft btn-admin--sm">
                                                    <i class="bi bi-eye"></i>
                                                    <span>View</span>
                                                </RouterLink>
                                                <RouterLink :to="`/admin/categories/${category.id}/edit`"
                                                    class="btn-admin btn-admin--outline btn-admin--sm">
                                                    <i class="bi bi-pencil-square"></i>
                                                    <span>Edit</span>
                                                </RouterLink>
                                                <button type="button" class="btn-admin btn-admin--danger btn-admin--sm"
                                                    :disabled="deletingId === category.id"
                                                    @click="handleDelete(category.id)">
                                                    <i class="bi bi-trash3"></i>
                                                    <span>{{ deletingId === category.id ? "Deleting..." : "Delete"
                                                        }}</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </Transition>
    </AdminLayout>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { RouterLink } from "vue-router";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import categoryService from "@/services/admin/categories/categoryService";

const categories = ref([]);
const loading = ref(false);
const deletingId = ref(null);

const getCollection = (payload) => {
    if (Array.isArray(payload?.data?.data)) {
        return payload.data.data;
    }

    if (Array.isArray(payload?.data)) {
        return payload.data;
    }

    return [];
};

const fetchCategories = async () => {
    loading.value = true;

    try {
        const response = await categoryService.getCategories();
        categories.value = getCollection(response);
    } catch (error) {
        console.error("Failed to fetch categories:", error);
    } finally {
        loading.value = false;
    }
};
const getImageUrl = (path) => {
    if (!path) return "";

    if (path.startsWith("http")) return path;

    return `http://127.0.0.1:8000/storage/${path}`;
};
const handleDelete = async (id) => {
    if (!window.confirm("Are you sure you want to delete this category?")) {
        return;
    }

    deletingId.value = id;

    try {
        await categoryService.deleteCategory(id);
        await fetchCategories();
    } catch (error) {
        console.error(`Failed to delete category ${id}:`, error);
    } finally {
        deletingId.value = null;
    }
};

onMounted(() => {
    fetchCategories();
});
</script>
<style scoped>
.admin-image-cell {
    display: flex;
    align-items: center;
}

.admin-image {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 10px;
    border: 1px solid #eee;
}
</style>