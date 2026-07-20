<template>
    <AdminLayout>
        <div class="admin-page">
            <section class="admin-page-header">
                <div class="admin-page-copy">
                    <h2 class="admin-page-title">Edit Banner</h2>
                </div>
            </section>
            <section class="admin-panel" v-if="!loading">
                <form @submit.prevent="handleSubmit" class="admin-panel__body">
                    <div class="mb-3">
                        <label>Type</label>
                        <select v-model="form.type" class="form-control" required>
                            <option value="hero">Hero</option>
                            <option value="promo">Promo</option>
                            <option value="newsletter">Newsletter</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Eyebrow</label>
                        <input v-model="form.eyebrow" type="text" class="form-control" />
                    </div>
                    <div class="mb-3">
                        <label>Title</label>
                        <input v-model="form.title" type="text" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label>Subtitle</label>
                        <textarea v-model="form.subtitle" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label>CTA Text</label>
                        <input v-model="form.cta_text" type="text" class="form-control" />
                    </div>
                    <div class="mb-3">
                        <label>CTA Link</label>
                        <input v-model="form.cta_link" type="text" class="form-control" />
                    </div>
                    <div class="mb-3">
                        <label>Image URL</label>
                        <input v-model="form.image" type="text" class="form-control" />
                    </div>
                    <div class="mb-3">
                        <label>Sort Order</label>
                        <input v-model="form.sort_order" type="number" class="form-control" />
                    </div>
                    <div class="mb-3">
                        <label><input type="checkbox" v-model="form.is_active" /> Active</label>
                    </div>
                    <button type="submit" class="btn-admin btn-admin--primary">Update</button>
                    <RouterLink to="/admin/banners" class="btn-admin btn-admin--outline ms-2">Cancel</RouterLink>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import bannerService from "@/services/admin/bannerService";
import toastr from "toastr";

const router = useRouter();
const route = useRoute();
const form = ref({});
const loading = ref(true);

onMounted(async () => {
    try {
        const res = await bannerService.get(route.params.id);
        form.value = res.data?.data || res.data;
    } catch (e) {
        router.push("/admin/banners");
    } finally {
        loading.value = false;
    }
});

const handleSubmit = async () => {
    try {
        await bannerService.update(route.params.id, form.value);
        toastr.success("Updated successfully");
        router.push("/admin/banners");
    } catch (e) {}
};
</script>
