<template>
    <div class="google-success-page">
        <div class="google-success-card">
            <div class="spinner-wrap" v-if="isLoading">
                <div class="spinner"></div>
                <h3>Signing you in...</h3>
                <p>Please wait while we complete Google login.</p>
            </div>

            <div v-else-if="error" class="error-wrap">
                <h3>Authentication Failed</h3>
                <p>{{ error }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import authService from "@/services/auth/Authservice";

const route = useRoute();
const router = useRouter();
const error = ref("");
const isLoading = ref(true);

const loginPath = () => `/${localStorage.getItem("language") || "en"}/auth`;

onMounted(async () => {
    try {
        const handled = await authService.handleGoogleCallback(route.query);

        if (!handled) {
            await router.replace(loginPath());
            return;
        }
    } catch (err) {
        error.value =
            err?.message ||
            err?.response?.data?.message ||
            "Google login failed. Please try again.";

        setTimeout(async () => {
            await router.replace(loginPath());
        }, 1500);
    } finally {
        isLoading.value = false;
    }
});
</script>

<style scoped>
.google-success-page {
    min-height: 100vh;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #f4f7fb 0%, #e9eef7 100%);
    padding: 24px;
}

.google-success-card {
    width: 100%;
    max-width: 460px;
    background: #fff;
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 10px 35px rgba(15, 23, 42, 0.08);
    text-align: center;
}

.spinner-wrap h3,
.error-wrap h3 {
    margin: 10px 0 8px;
    font-size: 1.2rem;
    color: #0f172a;
}

.spinner-wrap p,
.error-wrap p {
    margin: 0;
    color: #475569;
}

.spinner {
    width: 42px;
    height: 42px;
    margin: 0 auto;
    border: 4px solid #dbeafe;
    border-top-color: #2563eb;
    border-radius: 9999px;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
