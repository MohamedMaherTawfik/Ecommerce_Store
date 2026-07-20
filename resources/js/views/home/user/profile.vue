<template>
    <main class="store-shell profile-page">
        <header class="profile-header store-card">
            <div>
                <span class="store-eyebrow">Account</span>
                <h1 class="store-title">My profile</h1>
                <p class="store-subtitle">Manage your account information and password.</p>
            </div>
        </header>

        <section class="profile-panel store-card">
            <div class="tabs">
                <button :class="{ active: currentTab === 'overview' }" @click="currentTab = 'overview'">Overview</button>
                <button :class="{ active: currentTab === 'edit' }" @click="currentTab = 'edit'">Edit profile</button>
                <button :class="{ active: currentTab === 'password' }" @click="currentTab = 'password'">Password</button>
            </div>

            <div v-if="currentTab === 'overview'" class="tab-body">
                <div class="profile-user">
                    <div class="avatar">
                        <img v-if="user.image" :src="getImageUrl(user.image)" alt="avatar" loading="lazy" decoding="async" />
                        <span v-else>{{ avatarInitials }}</span>
                    </div>
                    <div>
                        <h2>{{ user.name || 'User' }}</h2>
                        <p>{{ user.email || '-' }}</p>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-item"><span>Name</span><strong>{{ user.name || '-' }}</strong></div>
                    <div class="info-item"><span>Email</span><strong>{{ user.email || '-' }}</strong></div>
                    <div class="info-item"><span>Phone</span><strong>{{ user.phone || '-' }}</strong></div>
                    <div class="info-item"><span>Role</span><strong>{{ user.role || '-' }}</strong></div>
                </div>
            </div>

            <form v-if="currentTab === 'edit'" class="tab-body form-grid" @submit.prevent="updateProfile">
                <label>
                    Name
                    <input v-model.trim="editData.name" class="store-input form-control" type="text" required />
                </label>

                <label>
                    Email
                    <input v-model.trim="editData.email" class="store-input form-control" type="email" required />
                </label>

                <label>
                    Phone
                    <input v-model.trim="editData.phone" class="store-input form-control" type="text" />
                </label>

                <label>
                    Profile image
                    <input class="store-input form-control" type="file" accept="image/*" @change="handleImageUpload" />
                </label>

                <div class="image-preview" v-if="imagePreview || user.image">
                    <img :src="imagePreview || getImageUrl(user.image)" alt="preview" loading="lazy" decoding="async" />
                </div>

                <div class="actions">
                    <button class="store-btn store-btn--primary" type="submit" :disabled="profileLoading">
                        {{ profileLoading ? 'Saving...' : 'Save changes' }}
                    </button>
                    <button class="store-btn danger-btn" type="button" :disabled="profileLoading" @click="deleteProfile">
                        {{ profileLoading ? 'Deleting...' : 'Delete account' }}
                    </button>
                </div>
            </form>

            <form v-if="currentTab === 'password'" class="tab-body form-grid" @submit.prevent="updatePassword">
                <label>
                    Current password
                    <input v-model="passwordData.current_password" class="store-input form-control" type="password" required />
                </label>

                <label>
                    New password
                    <input v-model="passwordData.new_password" class="store-input form-control" type="password" required />
                </label>

                <label>
                    Confirm password
                    <input v-model="passwordData.confirm_password" class="store-input form-control" type="password" required />
                </label>

                <button class="store-btn store-btn--primary" type="submit" :disabled="passwordLoading">
                    {{ passwordLoading ? 'Updating...' : 'Update password' }}
                </button>
            </form>

            <div v-if="statusMessage" class="status" :class="{ error: statusIsError }">
                {{ statusMessage }}
            </div>
        </section>
    </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSeoMeta } from '@/composables/useSeoMeta';
import api from '@/services/ApiClient';
import authService from '@/services/auth/Authservice';

useSeoMeta({
    title: 'My Profile',
    description: 'Manage your EliteShop account information and password.',
    robots: 'noindex,nofollow'
});

const route = useRoute();
const router = useRouter();
const lang = computed(() => route.params.lang || localStorage.getItem('language') || 'en');

const currentTab = ref('overview');
const user = ref({
    name: '',
    email: '',
    phone: '',
    role: '',
    image: '',
});

const editData = ref({
    name: '',
    email: '',
    phone: '',
    image: null,
});

const imagePreview = ref('');
const profileLoading = ref(false);
const passwordLoading = ref(false);

const passwordData = ref({
    current_password: '',
    new_password: '',
    confirm_password: '',
});

const statusMessage = ref('');
const statusIsError = ref(false);

const showStatus = (message, isError = false) => {
    statusMessage.value = message;
    statusIsError.value = isError;
    setTimeout(() => {
        statusMessage.value = '';
    }, 4000);
};

const avatarInitials = computed(() =>
    (user.value.name || '')
        .split(' ')
        .map((part) => part.charAt(0))
        .slice(0, 2)
        .join('')
        .toUpperCase(),
);

const fillEditData = () => {
    editData.value.name = user.value.name || '';
    editData.value.email = user.value.email || '';
    editData.value.phone = user.value.phone || '';
    editData.value.image = null;
    imagePreview.value = '';
};

const fetchProfile = async () => {
    try {
        const { data } = await api.get('/users/profile');
        const profileUser = data.data?.user || data.data || {};
        user.value = {
            name: profileUser.name || '',
            email: profileUser.email || '',
            phone: profileUser.phone || '',
            role: profileUser.role || '',
            image: profileUser.image || '',
        };
        fillEditData();
    } catch {
        showStatus('Unable to load profile.', true);
    }
};

const handleImageUpload = (event) => {
    const file = event.target.files?.[0] || null;
    editData.value.image = file;
    imagePreview.value = file ? URL.createObjectURL(file) : '';
};

const updateProfile = async () => {
    if (!editData.value.name || !editData.value.email) {
        showStatus('Name and email are required.', true);
        return;
    }

    profileLoading.value = true;
    try {
        const payload = new FormData();
        payload.append('name', editData.value.name);
        payload.append('email', editData.value.email);
        if (editData.value.phone) {
            payload.append('phone', editData.value.phone);
        }
        if (editData.value.image) {
            payload.append('image', editData.value.image);
        }

        const { data } = await api.post('/users/update-profile', payload, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        const updated = data.data?.user || data.data || {};
        user.value = {
            ...user.value,
            ...updated,
        };
        fillEditData();
        showStatus('Profile updated successfully.');
    } catch {
        showStatus('Unable to update profile.', true);
    } finally {
        profileLoading.value = false;
    }
};

const deleteProfile = async () => {
    profileLoading.value = true;
    try {
        await api.delete('/users/delete-account');
        showStatus('Account deleted successfully.');
        await authService.logout({ redirect: false });
        await router.push(`/${lang.value}/auth`);
    } catch {
        showStatus('Unable to delete account.', true);
    } finally {
        profileLoading.value = false;
    }
};

const updatePassword = async () => {
    const { current_password, new_password, confirm_password } = passwordData.value;

    if (!current_password || !new_password || !confirm_password) {
        showStatus('All password fields are required.', true);
        return;
    }

    if (new_password !== confirm_password) {
        showStatus('Passwords do not match.', true);
        return;
    }

    if (new_password.length < 6) {
        showStatus('Password must be at least 6 characters.', true);
        return;
    }

    passwordLoading.value = true;
    try {
        await api.post('/users/password', passwordData.value);
        passwordData.value = { current_password: '', new_password: '', confirm_password: '' };
        showStatus('Password updated successfully.');
    } catch {
        showStatus('Unable to update password.', true);
    } finally {
        passwordLoading.value = false;
    }
};

const getImageUrl = (path) => {
    if (!path) {
        return '';
    }
    if (path.startsWith('http')) {
        return path;
    }
    return `/storage/${path}`;
};

onMounted(fetchProfile);
</script>

<style scoped>
.profile-page {
    display: grid;
    gap: 1rem;
}

.profile-header {
    padding: 1.2rem;
}

.profile-panel {
    padding: 1rem;
}

.tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    margin-bottom: 1rem;
}

.tabs button {
    min-height: 38px;
    padding: 0.45rem 0.8rem;
    border-radius: 0.65rem;
    border: 1px solid var(--sf-border);
    background: var(--sf-surface-soft);
    color: var(--sf-muted);
    font-size: 0.85rem;
    font-weight: 700;
}

.tabs button.active {
    border-color: var(--sf-primary);
    background: var(--sf-primary-soft);
    color: var(--sf-primary);
}

.tab-body {
    display: grid;
    gap: 0.85rem;
}

.profile-user {
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.avatar {
    width: 56px;
    height: 56px;
    border-radius: 999px;
    overflow: hidden;
    border: 1px solid var(--sf-border);
    background: var(--sf-surface-soft);
    color: var(--sf-text);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
}

.avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-user h2 {
    margin: 0;
    color: var(--sf-text);
    font-size: 1.1rem;
}

.profile-user p {
    margin: 0.25rem 0 0;
    color: var(--sf-muted);
    font-size: 0.84rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.7rem;
}

.info-item {
    border: 1px solid var(--sf-border);
    border-radius: 0.75rem;
    padding: 0.75rem;
    background: var(--sf-surface-soft);
}

.info-item span {
    display: block;
    color: var(--sf-muted);
    font-size: 0.78rem;
}

.info-item strong {
    color: var(--sf-text);
    font-size: 0.9rem;
}

.form-grid {
    display: grid;
    gap: 0.75rem;
}

.form-grid label {
    display: grid;
    gap: 0.3rem;
    color: var(--sf-muted);
    font-size: 0.82rem;
    font-weight: 700;
}

.image-preview img {
    width: 86px;
    height: 86px;
    border-radius: 0.7rem;
    object-fit: cover;
    border: 1px solid var(--sf-border);
}

.actions {
    display: flex;
    gap: 0.6rem;
}

.danger-btn {
    background: color-mix(in srgb, var(--sf-danger) 15%, transparent);
    border: 1px solid color-mix(in srgb, var(--sf-danger) 45%, transparent);
    color: var(--sf-danger);
}

.status {
    margin-top: 0.9rem;
    border-radius: 0.7rem;
    padding: 0.65rem 0.75rem;
    background: color-mix(in srgb, var(--sf-success) 14%, transparent);
    color: var(--sf-success);
    font-size: 0.85rem;
}

.status.error {
    background: color-mix(in srgb, var(--sf-danger) 14%, transparent);
    color: var(--sf-danger);
}

@media (max-width: 575.98px) {
    .info-grid {
        grid-template-columns: 1fr;
    }

    .actions {
        flex-direction: column;
    }
}
</style>
