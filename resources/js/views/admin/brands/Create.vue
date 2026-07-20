<template>
    <AdminLayout>
        <Transition appear name="page-fade">
            <div class="admin-page">
                <section class="admin-page-header">
                    <div class="admin-page-copy">
                        <div class="admin-page-kicker">
                            <i class="bi bi-plus-circle"></i>
                            <span>Create Brand</span>
                        </div>
                        <h2 class="admin-page-title">Introduce a new brand elegantly</h2>
                        <p class="admin-page-description">
                            The same create flow, wrapped in a cleaner card-based form with better spacing.
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
                            <h3 class="admin-panel__title">Brand Information</h3>
                            <p class="admin-panel__meta">Add the basic information and an optional image.</p>
                        </div>
                    </div>

                    <div class="admin-panel__body">
                        <form class="admin-form-grid" @submit.prevent="handleSubmit">
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
                                    <span>Image</span>
                                </label>
                                <input id="image" type="file" class="form-control admin-control" @change="handleFileChange" />
                                <p class="admin-file-hint">Upload a brand image if available.</p>
                            </div>

                            <div class="admin-field admin-field--full">
                                <div class="admin-actions">
                                    <RouterLink to="/admin/brands" class="btn-admin btn-admin--soft">
                                        <i class="bi bi-x-lg"></i>
                                        <span>Cancel</span>
                                    </RouterLink>
                                    <button type="submit" class="btn-admin btn-admin--primary" :disabled="submitting">
                                        <i class="bi bi-check2-circle"></i>
                                        <span>{{ submitting ? "Saving..." : "Create Brand" }}</span>
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
import { reactive, ref } from "vue";
import { RouterLink, useRouter } from "vue-router";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import brandService from "@/services/admin/brands/brandService";

const router = useRouter();
const submitting = ref(false);
const form = reactive({
    name: "",
    image: null,
});

const handleFileChange = (event) => {
    form.image = event.target.files?.[0] ?? null;
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
        await brandService.createBrand(buildPayload());
        await router.push("/admin/brands");
    } catch (error) {
        console.error("Failed to create brand:", error);
    } finally {
        submitting.value = false;
    }
};
</script>
