<template>
    <AdminLayout>
        <Transition appear name="page-fade">
            <div class="admin-page">
                <section class="admin-page-header">
                    <div class="admin-page-copy">
                        <div class="admin-page-kicker">
                            <i class="bi bi-eye"></i>
                            <span>Category Details</span>
                        </div>
                        <h2 class="admin-page-title">Read category metadata at a glance</h2>
                        <p class="admin-page-description">
                            Detail cards make the current data easier to review without changing any fetch behavior.
                        </p>
                    </div>

                    <div class="admin-page-actions">
                        <button
                            type="button"
                            class="btn-admin btn-admin--soft"
                            @click="router.push('/admin/categories')"
                        >
                            <i class="bi bi-arrow-left"></i>
                            <span>Back</span>
                        </button>
                        <button
                            type="button"
                            class="btn-admin btn-admin--primary"
                            @click="router.push(`/admin/categories/${route.params.id}/edit`)"
                        >
                            <i class="bi bi-pencil-square"></i>
                            <span>Edit</span>
                        </button>
                    </div>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <h3 class="admin-panel__title">Category Snapshot</h3>
                            <p class="admin-panel__meta">Everything currently returned by the service in one calm layout.</p>
                        </div>
                    </div>

                    <div class="admin-panel__body">
                        <div v-if="loading" class="admin-skeleton-panel">
                            <div class="admin-skeleton-line admin-skeleton-line--lg"></div>
                            <div class="admin-skeleton-line"></div>
                            <div class="admin-skeleton-line admin-skeleton-line--md"></div>
                        </div>

                        <div v-else class="admin-detail-grid">
                            <article class="admin-detail-card" v-for="field in fields" :key="field.label">
                                <div class="admin-detail-card__label">{{ field.label }}</div>
                                <div class="admin-detail-card__value">{{ field.value }}</div>
                            </article>
                        </div>
                    </div>
                </section>
            </div>
        </Transition>
    </AdminLayout>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import categoryService from "@/services/admin/categories/categoryService";

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const category = ref({});

const getRecord = (payload) => payload?.data ?? payload ?? {};

const fields = computed(() => [
    { label: "ID", value: category.value.id ?? "-" },
    { label: "Name", value: category.value.name ?? "-" },
    { label: "Slug", value: category.value.slug ?? "-" },
    { label: "Image", value: category.value.image ?? "-" },
    { label: "Products Count", value: category.value.products?.length ?? 0 },
    { label: "Created At", value: category.value.created_at ?? "-" },
    { label: "Updated At", value: category.value.updated_at ?? "-" },
]);

const fetchCategory = async () => {
    loading.value = true;

    try {
        const response = await categoryService.getCategoryById(route.params.id);
        category.value = getRecord(response);
    } catch (error) {
        console.error(`Failed to fetch category ${route.params.id}:`, error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchCategory();
});
</script>
