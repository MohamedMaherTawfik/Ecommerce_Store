<template>
    <AdminLayout>
        <Transition appear name="page-fade">
            <div class="admin-page">
                <section class="admin-page-header">
                    <div class="admin-page-copy">
                        <div class="admin-page-kicker">
                            <i class="bi bi-pencil-square"></i>
                            <span>Edit Brand</span>
                        </div>
                        <h2 class="admin-page-title">Refresh brand presentation with confidence</h2>
                        <p class="admin-page-description">
                            Improve the editing experience while preserving the current update request exactly as-is.
                        </p>
                    </div>

                    <div class="admin-page-actions">
                        <RouterLink to="/admin/brands" class="btn-admin btn-admin--soft">
                            <i class="bi bi-arrow-left"></i>
                            <span>Back</span>
                        </RouterLink>
                    </div>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <h3 class="admin-panel__title">Editable Brand Fields</h3>
                            <p class="admin-panel__meta">Update the name or attach a new image when needed.</p>
                        </div>
                    </div>

                    <div class="admin-panel__body">
                        <div v-if="loading" class="admin-skeleton-panel">
                            <div class="admin-skeleton-line admin-skeleton-line--lg"></div>
                            <div class="admin-skeleton-line admin-skeleton-line--md"></div>
                            <div class="admin-skeleton-line"></div>
                        </div>

                        <form v-else class="admin-form-grid" @submit.prevent="handleSubmit">
                            <div class="admin-field">
                                <label for="name" class="admin-label">
                                    <i class="bi bi-type"></i>
                                    <span>Name</span>
                                </label>
                                <input id="name" v-model="form.name" type="text" class="form-control admin-control" />
                            </div>

                            <div class="admin-field">
                                <label for="image" class="admin-label">
                                    <i class="bi bi-image"></i>
                                    <span>Replace Image</span>
                                </label>
                                <input id="image" type="file" class="form-control admin-control" @change="handleFileChange" />
                                <p class="admin-file-hint">Current image: {{ form.currentImage || "-" }}</p>
                            </div>

                            <div class="admin-field admin-field--full">
                                <div class="admin-actions">
                                    <RouterLink to="/admin/brands" class="btn-admin btn-admin--soft">
                                        <i class="bi bi-x-lg"></i>
                                        <span>Cancel</span>
                                    </RouterLink>
                                    <button type="submit" class="btn-admin btn-admin--primary" :disabled="submitting">
                                        <i class="bi bi-save2"></i>
                                        <span>{{ submitting ? "Saving..." : "Save Changes" }}</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </Transition>
    </AdminLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import brandService from "@/services/admin/brands/brandService";

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const submitting = ref(false);
const form = reactive({
    name: "",
    image: null,
    currentImage: "",
});

const getRecord = (payload) => payload?.data ?? payload ?? {};

const handleFileChange = (event) => {
    form.image = event.target.files?.[0] ?? null;
};

const fetchBrand = async () => {
    loading.value = true;

    try {
        const response = await brandService.getBrandById(route.params.id);
        const brand = getRecord(response);

        form.name = brand.name ?? "";
        form.currentImage = brand.image ?? "";
    } catch (error) {
        console.error(`Failed to fetch brand ${route.params.id}:`, error);
    } finally {
        loading.value = false;
    }
};

const buildPayload = () => {
    const payload = new FormData();
    payload.append("name", form.name);

    if (form.image) {
        payload.append("image", form.image);
    }

    return payload;
};

const handleSubmit = async () => {
    submitting.value = true;

    try {
        await brandService.updateBrand(route.params.id, buildPayload());
        await router.push("/admin/brands");
    } catch (error) {
        console.error(`Failed to update brand ${route.params.id}:`, error);
    } finally {
        submitting.value = false;
    }
};

onMounted(() => {
    fetchBrand();
});
</script>
