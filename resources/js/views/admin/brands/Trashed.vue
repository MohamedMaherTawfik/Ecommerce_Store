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
                        <h2 class="admin-page-title">Trashed Brands</h2>
                        <p class="admin-page-description">
                            View and restore deleted brands.
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
                            <h3 class="admin-panel__title">Trashed Brands</h3>
                            <p class="admin-panel__meta">Brands that have been soft-deleted.</p>
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
                                There are no deleted brands in the recycle bin.
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
                                                <button type="button" class="btn-admin btn-admin--primary btn-admin--sm"
                                                    :disabled="restoringId === brand.id"
                                                    @click="handleRestore(brand.id)">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                    <span>{{ restoringId === brand.id ? "Restoring..." : "Restore"
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

const fetchBrands = async () => {
    loading.value = true;

    try {
        const response = await brandService.getTrashedBrands();
        brands.value = getCollection(response);
        console.log(brands.value);
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

const handleRestore = async (id) => {
    if (!window.confirm("Are you sure you want to restore this brand?")) {
        return;
    }

    restoringId.value = id;

    try {
        await brandService.restoreBrand(id);
        alert("Brand restored successfully");
        await fetchBrands();
    } catch (error) {
        console.error(`Failed to restore brand ${id}:`, error);
        alert("Failed to restore brand");
    } finally {
        restoringId.value = null;
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
