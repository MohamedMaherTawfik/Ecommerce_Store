<template>
    <AdminLayout>
        <Transition appear name="page-fade">
            <div class="admin-page">
                <section class="admin-page-header">
                    <div class="admin-page-copy">
                        <div class="admin-page-kicker">
                            <i class="bi bi-award"></i>
                            <span>Brands</span>
                        </div>
                        <h2 class="admin-page-title">Keep brand records polished and organized</h2>
                        <p class="admin-page-description">
                            Scan entries faster with a softer table layout, clearer actions, and improved empty states.
                        </p>
                    </div>

                    <div class="admin-page-actions">
                        <RouterLink to="/admin/brands/create" class="btn-admin btn-admin--primary">
                            <i class="bi bi-plus-lg"></i>
                            <span>Create Brand</span>
                        </RouterLink>
                    </div>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <h3 class="admin-panel__title">Brand Library</h3>
                            <p class="admin-panel__meta">All brands in one premium table view.</p>
                        </div>
                        <span class="admin-pill">
                            <i class="bi bi-collection"></i>
                            <span>{{ brands.length }} visible</span>
                        </span>
                    </div>

                    <div class="admin-panel__body">
                        <div v-if="loading" class="admin-skeleton-panel">
                            <div class="admin-skeleton-line admin-skeleton-line--lg"></div>
                            <div class="admin-skeleton-line"></div>
                            <div class="admin-skeleton-line admin-skeleton-line--md"></div>
                        </div>

                        <div v-else-if="brands.length === 0" class="admin-empty-state">
                            <div class="admin-empty-state__icon">
                                <i class="bi bi-award"></i>
                            </div>
                            <h3 class="admin-empty-state__title">No brands available</h3>
                            <p class="admin-empty-state__text">
                                Add your first brand to start organizing products with better catalog structure.
                            </p>
                        </div>

                        <div v-else class="admin-table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Brand</th>
                                        <th>Image</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="brand in brands" :key="brand.id">
                                        <td class="admin-table__primary">#{{ brand.id }}</td>
                                        <td>
                                            <div class="admin-table__primary">{{ brand.name || "-" }}</div>
                                            <div class="admin-table__secondary">{{ brand.slug || "No slug" }}</div>
                                        </td>
                                        <td>
                                            <div class="admin-image-cell">
                                                <img v-if="brand.image" :src="getImageUrl(brand.image)" alt="brand"
                                                    class="admin-image" />
                                                <span v-else>-</span>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="admin-actions">
                                                <RouterLink :to="`/admin/brands/${brand.id}`"
                                                    class="btn-admin btn-admin--soft btn-admin--sm">
                                                    <i class="bi bi-eye"></i>
                                                    <span>View</span>
                                                </RouterLink>
                                                <RouterLink :to="`/admin/brands/${brand.id}/edit`"
                                                    class="btn-admin btn-admin--outline btn-admin--sm">
                                                    <i class="bi bi-pencil-square"></i>
                                                    <span>Edit</span>
                                                </RouterLink>
                                                <button type="button" class="btn-admin btn-admin--danger btn-admin--sm"
                                                    :disabled="deletingId === brand.id" @click="handleDelete(brand.id)">
                                                    <i class="bi bi-trash3"></i>
                                                    <span>{{ deletingId === brand.id ? "Deleting..." : "Delete"
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
import brandService from "@/services/admin/brands/brandService";

const brands = ref([]);
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

const fetchBrands = async () => {
    loading.value = true;

    try {
        const response = await brandService.getBrands();
        brands.value = getCollection(response);
    } catch (error) {
        console.error("Failed to fetch brands:", error);
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
    if (!window.confirm("Are you sure you want to delete this brand?")) {
        return;
    }

    deletingId.value = id;

    try {
        await brandService.deleteBrand(id);
        await fetchBrands();
    } catch (error) {
        console.error(`Failed to delete brand ${id}:`, error);
    } finally {
        deletingId.value = null;
    }
};

onMounted(() => {
    fetchBrands();
});
</script>

<style scoped>
.admin-image-cell {
    display: flex;
    align-items: center;
    justify-content: flex-start;
}

.admin-image {
    width: 75px;
    height: 75px;
    object-fit: cover;
    border-radius: 10px;
    border: 1px solid #eee;
}
</style>