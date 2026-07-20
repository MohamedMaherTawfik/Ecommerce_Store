<template>
    <AdminLayout>
        <Transition appear name="page-fade">
            <div class="admin-page">
                <section class="admin-page-header">
                    <div class="admin-page-copy">
                        <div class="admin-page-kicker">
                            <i class="bi bi-eye"></i>
                            <span>Brand Details</span>
                        </div>
                        <h2 class="admin-page-title">Inspect brand metadata in a cleaner layout</h2>
                        <p class="admin-page-description">
                            The same data is now easier to review thanks to richer spacing and polished detail cards.
                        </p>
                    </div>

                    <div class="admin-page-actions">
                        <button type="button" class="btn-admin btn-admin--soft" @click="router.push('/admin/brands')">
                            <i class="bi bi-arrow-left"></i>
                            <span>Back</span>
                        </button>
                        <button
                            type="button"
                            class="btn-admin btn-admin--primary"
                            @click="router.push(`/admin/brands/${route.params.id}/edit`)"
                        >
                            <i class="bi bi-pencil-square"></i>
                            <span>Edit</span>
                        </button>
                    </div>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <h3 class="admin-panel__title">Brand Snapshot</h3>
                            <p class="admin-panel__meta">Quickly scan all available brand fields.</p>
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
import brandService from "@/services/admin/brands/brandService";

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const brand = ref({});

const getRecord = (payload) => payload?.data ?? payload ?? {};

const fields = computed(() => [
    { label: "ID", value: brand.value.id ?? "-" },
    { label: "Name", value: brand.value.name ?? "-" },
    { label: "Slug", value: brand.value.slug ?? "-" },
    { label: "Image", value: brand.value.image ?? "-" },
    { label: "Created At", value: brand.value.created_at ?? "-" },
    { label: "Updated At", value: brand.value.updated_at ?? "-" },
]);

const fetchBrand = async () => {
    loading.value = true;

    try {
        const response = await brandService.getBrandById(route.params.id);
        brand.value = getRecord(response);
    } catch (error) {
        console.error(`Failed to fetch brand ${route.params.id}:`, error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchBrand();
});
</script>
