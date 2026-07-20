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
                        <h2 class="admin-page-title">Trashed Categories</h2>
                        <p class="admin-page-description">
                            View and restore deleted categories.
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
                            <h3 class="admin-panel__title">Trashed Categories</h3>
                            <p class="admin-panel__meta">Categories that have been soft-deleted.</p>
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
                                There are no deleted categories in the recycle bin.
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
                                                <button type="button" class="btn-admin btn-admin--primary btn-admin--sm"
                                                    :disabled="restoringId === category.id"
                                                    @click="handleRestore(category.id)">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                    <span>{{ restoringId === category.id ? "Restoring..." : "Restore"
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
const restoringId = ref(null);

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
        const response = await categoryService.getTrashedCategories();
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
const handleRestore = async (id) => {
    if (!window.confirm("Are you sure you want to restore this category?")) {
        return;
    }

    restoringId.value = id;

    try {
        await categoryService.restoreCategory(id);
        alert("Category restored successfully");
        await fetchCategories();
    } catch (error) {
        console.error(`Failed to restore category ${id}:`, error);
        alert("Failed to restore category");
    } finally {
        restoringId.value = null;
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