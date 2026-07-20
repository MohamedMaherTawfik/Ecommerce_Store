<template>
    <main class="store-shell contact-page">
        <header class="contact-header store-card">
            <div>
                <span class="store-eyebrow">Support</span>
                <h1 class="store-title">Contact us</h1>
                <p class="store-subtitle">We are here to answer your questions and help with your orders.</p>
            </div>
        </header>

        <section class="contact-layout">
            <article class="contact-info store-card">
                <h2>Contact information</h2>

                <div class="info-item">
                    <i class="bi bi-geo-alt"></i>
                    <div>
                        <strong>Address</strong>
                        <p>Nile Street, Cairo, Egypt</p>
                    </div>
                </div>

                <div class="info-item">
                    <i class="bi bi-telephone"></i>
                    <div>
                        <strong>Phone</strong>
                        <p>+20 123 456 789</p>
                    </div>
                </div>

                <div class="info-item">
                    <i class="bi bi-envelope"></i>
                    <div>
                        <strong>Email</strong>
                        <p>info@example.com</p>
                    </div>
                </div>

                <div>
                    <strong>Follow us</strong>
                    <div class="socials">
                        <a v-for="social in socials" :key="social.name" :href="social.href" :aria-label="social.name">
                            <i :class="`bi ${social.icon}`"></i>
                        </a>
                    </div>
                </div>
            </article>

            <article class="contact-form store-card">
                <h2>Send a message</h2>

                <form @submit.prevent="submitForm">
                    <template v-if="!isLoggedIn">
                        <label>
                            Full name
                            <input v-model="form.name" class="store-input form-control" :class="{ 'is-invalid': errors.name }" type="text" required />
                            <div class="invalid-feedback" v-if="errors.name">{{ errors.name[0] }}</div>
                        </label>

                        <label>
                            Email
                            <input v-model="form.email" class="store-input form-control" :class="{ 'is-invalid': errors.email }" type="email" required />
                            <div class="invalid-feedback" v-if="errors.email">{{ errors.email[0] }}</div>
                        </label>
                    </template>

                    <label>
                        Subject
                        <input v-model="form.subject" class="store-input form-control" :class="{ 'is-invalid': errors.subject }" type="text" required />
                        <div class="invalid-feedback" v-if="errors.subject">{{ errors.subject[0] }}</div>
                    </label>

                    <label>
                        Message
                        <textarea v-model="form.message" class="store-textarea form-control" :class="{ 'is-invalid': errors.message }" rows="4" required></textarea>
                        <div class="invalid-feedback" v-if="errors.message">{{ errors.message[0] }}</div>
                    </label>

                    <button class="store-btn store-btn--primary" type="submit" :disabled="loading">
                        <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Send message
                    </button>
                </form>

                <div v-if="successMessage" class="success-alert">{{ successMessage }}</div>
                <div v-if="errorMessage" class="error-alert">{{ errorMessage }}</div>
            </article>
        </section>
    </main>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useSeoMeta } from '@/composables/useSeoMeta';
import axios from 'axios';
import authService from '@/services/auth/Authservice';

useSeoMeta({
    title: 'Contact Us',
    description: 'Contact EliteShop. We are here to answer your questions and help with your orders.',
});

const isLoggedIn = ref(false);

onMounted(() => {
    isLoggedIn.value = authService.isLoggedIn();
});

const form = ref({
    name: '',
    email: '',
    subject: '',
    message: '',
});

const loading = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const errors = ref({});

const socials = [
    { name: 'facebook', href: '#', icon: 'bi-facebook' },
    { name: 'twitter', href: '#', icon: 'bi-twitter' },
    { name: 'instagram', href: '#', icon: 'bi-instagram' },
    { name: 'telegram', href: '#', icon: 'bi-telegram' },
];

const submitForm = async () => {
    loading.value = true;
    successMessage.value = '';
    errorMessage.value = '';
    errors.value = {};

    try {
        const response = await axios.post('/api/v1/contact-us', form.value);
        successMessage.value = response.data.message || 'Your message has been sent successfully.';
        form.value = { name: '', email: '', subject: '', message: '' };
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
            errorMessage.value = error.response.data.message || 'Validation failed. Please check the fields below.';
        } else {
            errorMessage.value = error.response?.data?.message || 'Failed to send message. Please try again later.';
        }
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.contact-page {
    display: grid;
    gap: 1rem;
}

.contact-header {
    padding: 1.2rem;
}

.contact-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    align-items: start;
}

.contact-info,
.contact-form {
    padding: 1rem;
}

.contact-info h2,
.contact-form h2 {
    margin: 0 0 1rem;
    color: var(--sf-text);
    font-size: 1.15rem;
    font-weight: 800;
}

.info-item {
    display: flex;
    gap: 0.7rem;
    margin-bottom: 0.9rem;
}

.info-item i {
    width: 34px;
    height: 34px;
    border-radius: 0.65rem;
    border: 1px solid var(--sf-border);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--sf-text);
}

.info-item strong {
    color: var(--sf-text);
    font-size: 0.9rem;
}

.info-item p {
    margin: 0.25rem 0 0;
    color: var(--sf-muted);
    font-size: 0.84rem;
}

.socials {
    margin-top: 0.7rem;
    display: flex;
    gap: 0.5rem;
}

.socials a {
    width: 36px;
    height: 36px;
    border-radius: 0.7rem;
    border: 1px solid var(--sf-border);
    background: var(--sf-surface-soft);
    color: var(--sf-text);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}

.contact-form form {
    display: grid;
    gap: 0.7rem;
}

.contact-form label {
    display: grid;
    gap: 0.3rem;
    color: var(--sf-muted);
    font-size: 0.82rem;
    font-weight: 700;
}

.success-alert {
    margin-top: 0.9rem;
    border-radius: 0.7rem;
    padding: 0.65rem 0.75rem;
    background: color-mix(in srgb, var(--sf-success) 14%, transparent);
    color: var(--sf-success);
    font-size: 0.85rem;
}

.error-alert {
    margin-top: 0.9rem;
    border-radius: 0.7rem;
    padding: 0.65rem 0.75rem;
    background: color-mix(in srgb, var(--sf-danger) 14%, transparent);
    color: var(--sf-danger);
    font-size: 0.85rem;
}

@media (max-width: 991.98px) {
    .contact-layout {
        grid-template-columns: 1fr;
    }
}
</style>
