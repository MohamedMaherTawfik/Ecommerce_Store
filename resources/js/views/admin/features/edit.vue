<template>
    <AdminLayout>
        <div class="admin-page">
            <section class="admin-page-header">
                <div class="admin-page-copy">
                    <h2 class="admin-page-title">Edit Feature</h2>
                </div>
            </section>
            <section class="admin-panel" v-if="!loading">
                <form @submit.prevent="handleSubmit" class="admin-panel__body">
                    <div class="mb-3">
                        <label>Icon</label>
                        <input v-model="form.icon" type="text" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label>Label</label>
                        <input v-model="form.label" type="text" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label>Text</label>
                        <textarea v-model="form.text" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Sort Order</label>
                        <input v-model="form.sort_order" type="number" class="form-control" />
                    </div>
                    <div class="mb-3">
                        <label><input type="checkbox" v-model="form.is_active" /> Active</label>
                    </div>
                    <button type="submit" class="btn-admin btn-admin--primary">Update</button>
                    <RouterLink to="/admin/features" class="btn-admin btn-admin--outline ms-2">Cancel</RouterLink>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import featureService from "@/services/admin/featureService";
import toastr from "toastr";

const router = useRouter();
const route = useRoute();
const form = ref({});
const loading = ref(true);

onMounted(async () => {
    try {
        const res = await featureService.get(route.params.id);
        form.value = res.data?.data || res.data;
    } catch (e) {
        router.push("/admin/features");
    } finally {
        loading.value = false;
    }
});

const handleSubmit = async () => {
    try {
        await featureService.update(route.params.id, form.value);
        toastr.success("Updated successfully");
        router.push("/admin/features");
    } catch (e) {}
};
</script>
