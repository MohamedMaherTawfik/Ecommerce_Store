<template>
    <AdminLayout>
        <Transition appear name="page-fade">
            <div class="admin-page">
                <section class="admin-page-header">
                    <div class="admin-page-copy">
                        <div class="admin-page-kicker">
                            <i class="bi bi-pencil-square"></i>
                            <span>Edit Category</span>
                        </div>
                        <h2 class="admin-page-title">Fine-tune category details with less friction</h2>
                        <p class="admin-page-description">
                            A more premium editing surface that keeps the same request flow and existing methods.
                        </p>
                    </div>

                    <div class="admin-page-actions">
                        <RouterLink to="/admin/categories" class="btn-admin btn-admin--soft">
                            <i class="bi bi-arrow-left"></i>
                            <span>Back</span>
                        </RouterLink>
                    </div>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <h3 class="admin-panel__title">Editable Category Fields</h3>
                            <p class="admin-panel__meta">Update the name or optionally replace the image.</p>
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

                            <div class="admin-field admin-field--full">
                                <label for="description" class="admin-label">
                                    <i class="bi bi-card-text"></i>
                                    <span>Description</span>
                                </label>
                                <textarea id="description" v-model="form.description" class="form-control admin-control" rows="4"></textarea>
                            </div>

                            <SeoFields
                                v-model:slug="form.slug"
                                v-model:meta-title="form.meta_title"
                                v-model:meta-description="form.meta_description"
                                v-model:meta-keywords="form.meta_keywords"
                                v-model:og-title="form.og_title"
                                v-model:og-description="form.og_description"
                                v-model:og-image="form.og_image"
                                v-model:canonical-url="form.canonical_url"
                                :fallback-title="form.name"
                                :fallback-description="form.description"
                            />

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
                                    <RouterLink to="/admin/categories" class="btn-admin btn-admin--soft">
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
import SeoFields from "@/components/admin/SeoFields.vue";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import categoryService from "@/services/admin/categories/categoryService";

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const submitting = ref(false);
const form = reactive({
    name: "",
    image: null,
    currentImage: "",
    description: "",
    slug: "",
    meta_title: "",
    meta_description: "",
    meta_keywords: "",
    og_title: "",
    og_description: "",
    og_image: null,
    canonical_url: "",
});

const getRecord = (payload) => payload?.data ?? payload ?? {};

const handleFileChange = (event) => {
    form.image = event.target.files?.[0] ?? null;
};

const fetchCategory = async () => {
    loading.value = true;

    try {
        const response = await categoryService.getCategoryById(route.params.id);
        const category = getRecord(response);

        form.name = category.name ?? "";
        form.currentImage = category.image ?? "";
        form.description = category.description ?? "";
        form.slug = category.slug ?? "";
        form.meta_title = category.meta_title ?? "";
        form.meta_description = category.meta_description ?? "";
        form.meta_keywords = category.meta_keywords ?? "";
        form.og_title = category.og_title ?? "";
        form.og_description = category.og_description ?? "";
        form.og_image = category.og_image ?? null;
        form.canonical_url = category.canonical_url ?? "";
    } catch (error) {
        console.error(`Failed to fetch category ${route.params.id}:`, error);
    } finally {
        loading.value = false;
    }
};

const buildPayload = () => {
    const payload = new FormData();
    payload.append("name", form.name);
    [
        "description",
        "slug",
        "meta_title",
        "meta_description",
        "meta_keywords",
        "og_title",
        "og_description",
        "canonical_url",
    ].forEach((field) => {
        if (form[field]) payload.append(field, form[field]);
    });

    if (form.image) {
        payload.append("image", form.image);
    }
    if (form.og_image instanceof File) {
        payload.append("og_image", form.og_image);
    }

    return payload;
};

const handleSubmit = async () => {
    submitting.value = true;

    try {
        await categoryService.updateCategory(route.params.id, buildPayload());
        await router.push("/admin/categories");
    } catch (error) {
        console.error(`Failed to update category ${route.params.id}:`, error);
    } finally {
        submitting.value = false;
    }
};

onMounted(() => {
    fetchCategory();
});
</script>
