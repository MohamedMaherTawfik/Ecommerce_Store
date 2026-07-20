<template>
    <AdminLayout>
        <div class="admin-page">
            <section class="admin-page-header">
                <div class="admin-page-copy">
                    <h2 class="admin-page-title">Edit Site Setting: {{ route.params.key }}</h2>
                </div>
            </section>
            <section class="admin-panel" v-if="!loading">
                <form @submit.prevent="handleSubmit" class="admin-panel__body">
                    <div class="mb-3" v-if="isImageSetting">
                        <label>Image Value</label>
                        <input type="file" @change="handleFileChange" class="form-control" accept="image/*" />
                        <div v-if="imagePreview || form.value" class="mt-3">
                            <img :src="imagePreview || form.value" alt="Preview" style="max-height: 150px; border-radius: 8px; border: 1px solid #ccc; padding: 5px; background: #fff;" />
                        </div>
                    </div>
                    <div class="mb-3" v-else>
                        <label>Value</label>
                        <textarea v-model="form.value" class="form-control" rows="4"></textarea>
                    </div>
                    <button type="submit" class="btn-admin btn-admin--primary" :disabled="submitting">
                        {{ submitting ? 'Updating...' : 'Update' }}
                    </button>
                    <RouterLink to="/admin/site-settings" class="btn-admin btn-admin--outline ms-2">Cancel</RouterLink>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { useRouter, useRoute } from "vue-router";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import siteSettingService from "@/services/admin/siteSettingService";
import toastr from "toastr";

const router = useRouter();
const route = useRoute();
const form = ref({ value: "" });
const fileValue = ref(null);
const imagePreview = ref(null);
const loading = ref(true);
const submitting = ref(false);

const imageKeys = ['navbar_image', 'footer_image', 'register_image', 'tab_icon'];
const isImageSetting = computed(() => imageKeys.includes(route.params.key));

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        fileValue.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

onMounted(async () => {
    try {
        const res = await siteSettingService.get(route.params.key);
        form.value.value = res.data?.data?.value || res.data?.value || "";
    } catch (e) {
        router.push("/admin/site-settings");
    } finally {
        loading.value = false;
    }
});

const handleSubmit = async () => {
    submitting.value = true;
    try {
        let payload;
        if (isImageSetting.value) {
            payload = new FormData();
            payload.append('key', route.params.key);
            if (fileValue.value) {
                payload.append('value', fileValue.value);
            } else {
                // If they submit without changing the file, we can either send nothing or send the existing string path.
                // In this case, we send the existing value.
                payload.append('value', form.value.value || '');
            }
        } else {
            payload = form.value;
        }

        await siteSettingService.update(route.params.key, payload);
        toastr.success("Updated successfully");
        router.push("/admin/site-settings");
    } catch (e) {
        toastr.error("Failed to update setting");
    } finally {
        submitting.value = false;
    }
};
</script>
