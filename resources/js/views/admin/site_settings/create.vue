<template>
    <AdminLayout>
        <div class="admin-page">
            <section class="admin-page-header">
                <div class="admin-page-copy">
                    <h2 class="admin-page-title">Create Site Setting</h2>
                </div>
            </section>
            <section class="admin-panel">
                <form @submit.prevent="handleSubmit" class="admin-panel__body">
                    <div class="mb-3">
                        <label>Key</label>
                        <input v-model="form.key" type="text" class="form-control" required />
                        <small class="text-muted">Must be unique (e.g. `navbar_image`, `register_image`)</small>
                    </div>
                    <div class="mb-3" v-if="isImageSetting">
                        <label>Image Value</label>
                        <input type="file" @change="handleFileChange" class="form-control" accept="image/*" />
                        <div v-if="imagePreview" class="mt-3">
                            <img :src="imagePreview" alt="Preview" style="max-height: 150px; border-radius: 8px; border: 1px solid #ccc; padding: 5px; background: #fff;" />
                        </div>
                    </div>
                    <div class="mb-3" v-else>
                        <label>Value</label>
                        <textarea v-model="form.value" class="form-control" rows="4"></textarea>
                    </div>
                    <button type="submit" class="btn-admin btn-admin--primary" :disabled="submitting">
                        {{ submitting ? 'Saving...' : 'Save' }}
                    </button>
                    <RouterLink to="/admin/site-settings" class="btn-admin btn-admin--outline ms-2">Cancel</RouterLink>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { useRouter } from "vue-router";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import siteSettingService from "@/services/admin/siteSettingService";
import toastr from "toastr";

const router = useRouter();
const form = ref({ key: "", value: "" });
const fileValue = ref(null);
const imagePreview = ref(null);
const submitting = ref(false);

const imageKeys = ['navbar_image', 'footer_image', 'register_image', 'tab_icon'];
const isImageSetting = computed(() => imageKeys.includes(form.value.key.trim().toLowerCase()));

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

const handleSubmit = async () => {
    submitting.value = true;
    try {
        let payload;
        if (isImageSetting.value) {
            payload = new FormData();
            payload.append('key', form.value.key);
            if (fileValue.value) {
                payload.append('value', fileValue.value);
            } else {
                payload.append('value', form.value.value || '');
            }
        } else {
            payload = form.value;
        }

        await siteSettingService.create(payload);
        toastr.success("Created successfully");
        router.push("/admin/site-settings");
    } catch (e) {
        toastr.error("Failed to create setting");
    } finally {
        submitting.value = false;
    }
};
</script>
