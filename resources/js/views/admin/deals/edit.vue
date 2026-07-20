<template>
    <AdminLayout>
        <div class="admin-page">
            <section class="admin-page-header">
                <div class="admin-page-copy">
                    <h2 class="admin-page-title">Edit Deal</h2>
                </div>
            </section>
            <section class="admin-panel" v-if="!loading">
                <form @submit.prevent="handleSubmit" class="admin-panel__body">
                    <div class="mb-3">
                        <label>Name</label>
                        <input v-model="form.name" type="text" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label>Category</label>
                        <input v-model="form.category" type="text" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label>Icon</label>
                        <input v-model="form.icon" type="text" class="form-control" />
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label>Original Price</label>
                            <input v-model="form.original_price" type="number" step="0.01" class="form-control" required />
                        </div>
                        <div class="col-md-4">
                            <label>Sale Price</label>
                            <input v-model="form.sale_price" type="number" step="0.01" class="form-control" required />
                        </div>
                        <div class="col-md-4">
                            <label>Discount %</label>
                            <input v-model="form.discount" type="number" class="form-control" required />
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Sold Percent</label>
                            <input v-model="form.sold_percent" type="number" class="form-control" />
                        </div>
                        <div class="col-md-6">
                            <label>Sold Label</label>
                            <input v-model="form.sold_label" type="text" class="form-control" />
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Expires At</label>
                        <input v-model="form.expires_at" type="datetime-local" class="form-control" />
                    </div>
                    <div class="mb-3">
                        <label>Sort Order</label>
                        <input v-model="form.sort_order" type="number" class="form-control" />
                    </div>
                    <div class="mb-3">
                        <label><input type="checkbox" v-model="form.is_active" /> Active</label>
                    </div>
                    <button type="submit" class="btn-admin btn-admin--primary">Update</button>
                    <RouterLink to="/admin/deals" class="btn-admin btn-admin--outline ms-2">Cancel</RouterLink>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import dealService from "@/services/admin/dealService";
import toastr from "toastr";

const router = useRouter();
const route = useRoute();
const form = ref({});
const loading = ref(true);

onMounted(async () => {
    try {
        const res = await dealService.get(route.params.id);
        const data = res.data?.data || res.data;
        if (data.expires_at) {
            // format to datetime-local expected format YYYY-MM-DDThh:mm
            data.expires_at = new Date(data.expires_at).toISOString().slice(0, 16);
        }
        form.value = data;
    } catch (e) {
        router.push("/admin/deals");
    } finally {
        loading.value = false;
    }
});

const handleSubmit = async () => {
    try {
        await dealService.update(route.params.id, form.value);
        toastr.success("Updated successfully");
        router.push("/admin/deals");
    } catch (e) {}
};
</script>
