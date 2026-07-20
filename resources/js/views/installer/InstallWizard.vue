<template>
    <div class="min-h-screen flex justify-center items-center bg-gradient-to-br from-purple-500 via-purple-600 to-purple-700 p-4">
        <div class="w-full max-w-2xl bg-white shadow-2xl rounded-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-blue-600 text-white p-6">
                <h3 class="text-2xl font-bold text-center mb-2 flex justify-center items-center gap-2">
                    <i class="bi bi-rocket-takeoff"></i> System Installer
                </h3>
                <div class="flex justify-center gap-2 mt-3">
                    <span
                        v-for="s in totalSteps"
                        :key="s"
                        class="step-dot"
                        :class="{ 'step-dot--active': s === step, 'step-dot--done': s < step }"
                    ></span>
                </div>
            </div>

            <!-- Body -->
            <div class="p-8 relative">
                <!-- Loading Overlay -->
                <div
                    v-if="loading"
                    class="absolute inset-0 bg-white bg-opacity-75 flex flex-col justify-center items-center z-10"
                >
                    <div class="animate-spin rounded-full h-12 w-12 border-b-4 border-blue-600 mb-2"></div>
                    <small class="text-gray-500">{{ loadingMessage }}</small>
                </div>

                <!-- Error Alert -->
                <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 flex gap-3">
                    <i class="bi bi-exclamation-triangle-fill text-red-600 flex-shrink-0 mt-0.5"></i>
                    <div class="flex-1">
                        <strong class="text-red-800">Error:</strong>
                        <p class="text-red-700">{{ error }}</p>
                        <button
                            type="button"
                            class="mt-2 px-3 py-1 text-sm border border-red-300 text-red-600 hover:bg-red-50 rounded"
                            @click="error = ''"
                        >
                            Dismiss
                        </button>
                    </div>
                </div>

                <!-- Step 1: Welcome -->
                <div v-if="step === 1" class="text-center">
                    <div class="text-6xl mb-6">🚀</div>
                    <h4 class="text-2xl font-bold mb-4">Welcome to the Installation Wizard!</h4>
                    <p class="text-gray-600 mb-6">
                        This wizard will guide you through setting up your application.
                        <br />The installer will configure <strong>SQLite automatically</strong> and run migrations for you.
                    </p>
                    <button
                        class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-full text-lg transition"
                        @click="checkRequirements"
                    >
                        <i class="bi bi-play-fill me-1"></i> Start Installation
                    </button>
                </div>

                <!-- Step 2: Requirements -->
                <div v-if="step === 2">
                    <h4 class="text-2xl font-bold text-center mb-6">Server Requirements</h4>
                    <ul class="space-y-2 mb-6">
                        <!-- PHP Version -->
                        <li class="flex justify-between items-center p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <div>
                                PHP >= 8.2.0 <small class="text-gray-500 ml-2">(v{{ reqs.php_version || '?' }})</small>
                            </div>
                            <span :class="reqs.requirements.php ? 'text-green-600' : 'text-red-600'">
                                <i :class="reqs.requirements.php ? 'bi bi-check-circle-fill' : 'bi bi-x-circle-fill'"></i>
                            </span>
                        </li>

                        <!-- Extensions -->
                        <li
                            v-for="(status, ext) in reqs.requirements"
                            :key="ext"
                            v-show="ext !== 'php'"
                            class="flex justify-between items-center p-4 bg-gray-50 border border-gray-200 rounded-lg"
                        >
                            <span>Extension: {{ ext }}</span>
                            <span :class="status ? 'text-green-600' : 'text-red-600'">
                                <i :class="status ? 'bi bi-check-circle-fill' : 'bi bi-x-circle-fill'"></i>
                            </span>
                        </li>

                        <!-- Permissions -->
                        <li
                            v-for="(status, perm) in reqs.permissions"
                            :key="perm"
                            class="flex justify-between items-center p-4 bg-gray-50 border border-gray-200 rounded-lg"
                        >
                            <span>Directory: {{ perm }} (Writable)</span>
                            <span :class="status ? 'text-green-600' : 'text-red-600'">
                                <i :class="status ? 'bi bi-check-circle-fill' : 'bi bi-x-circle-fill'"></i>
                            </span>
                        </li>
                    </ul>

                    <div class="flex justify-between gap-3">
                        <button class="px-6 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg transition" @click="step = 1">
                            Back
                        </button>
                        <button
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="!reqs.ready"
                            @click="step = 3"
                        >
                            Next Step <i class="bi bi-arrow-right ml-1"></i>
                        </button>
                    </div>

                    <div v-if="!reqs.ready" class="text-red-600 text-center text-sm mt-3">
                        Please fix the requirements marked with <i class="bi bi-x-circle-fill"></i> to proceed.
                    </div>
                </div>

                <!-- Step 3: Configuration -->
                <div v-if="step === 3">
                    <h4 class="text-2xl font-bold text-center mb-6">Final Configuration</h4>
                    <form @submit.prevent="processInstall" class="space-y-6">
                        <!-- SQLite Info -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
                            <i class="bi bi-info-circle me-1"></i>
                            SQLite will be prepared automatically at <strong>{{ sqlitePathHint }}</strong>.
                        </div>

                        <!-- Application Settings -->
                        <div>
                            <h6 class="text-blue-600 font-semibold mb-4 flex items-center gap-2">
                                <i class="bi bi-gear"></i> Application Settings
                            </h6>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-semibold text-sm text-gray-700 mb-2">App Name</label>
                                    <input
                                        v-model="form.APP_NAME"
                                        type="text"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="My Store"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="block font-semibold text-sm text-gray-700 mb-2">App URL</label>
                                    <input
                                        v-model="form.APP_URL"
                                        type="url"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="https://example.com"
                                        required
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Admin Account -->
                        <div>
                            <h6 class="text-blue-600 font-semibold mb-4 flex items-center gap-2">
                                <i class="bi bi-person-badge"></i> Admin Account
                            </h6>
                            <div class="space-y-4">
                                <div>
                                    <label class="block font-semibold text-sm text-gray-700 mb-2">Name</label>
                                    <input
                                        v-model="form.ADMIN_NAME"
                                        type="text"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="Admin User"
                                        required
                                    />
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block font-semibold text-sm text-gray-700 mb-2">Email</label>
                                        <input
                                            v-model="form.ADMIN_EMAIL"
                                            type="email"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="admin@example.com"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-sm text-gray-700 mb-2">Password</label>
                                        <input
                                            v-model="form.ADMIN_PASSWORD"
                                            type="password"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            minlength="8"
                                            placeholder="Min 8 characters"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Warning Alert -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <strong>Important:</strong> This step creates or updates the admin account, runs migrations on SQLite, and marks the app as installed.
                        </div>

                        <!-- Buttons -->
                        <div class="flex justify-between gap-3 pt-4">
                            <button
                                type="button"
                                class="px-6 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg transition"
                                @click="step = 2"
                            >
                                Back
                            </button>
                            <button
                                type="submit"
                                class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                                :disabled="loading"
                            >
                                <i class="bi bi-download"></i> Finish Installation
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 4: Success -->
                <div v-if="step === 4" class="text-center py-8">
                    <div class="text-7xl text-green-600 mb-6">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h4 class="text-2xl font-bold mb-4">Installation Successful!</h4>
                    <p class="text-gray-600 mb-2">Your application has been successfully configured and installed.</p>
                    <p class="text-gray-500 text-sm mb-6">Redirecting to home page in {{ countdown }} seconds...</p>
                    <a
                        :href="homeUrl"
                        class="inline-block px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-full transition flex items-center gap-2 justify-center"
                        @click.prevent="goToHome"
                    >
                        <i class="bi bi-house-door"></i> Go to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onUnmounted, ref } from "vue";
import axios from "axios";
import toastr from "toastr";

const step = ref(1);
const totalSteps = 4;
const loading = ref(false);
const loadingMessage = ref("Please wait...");
const error = ref("");
const countdown = ref(5);
const sqlitePathHint = `${window.location.origin.replace(/\/$/, "")}/database/database.sqlite`;

let countdownTimer = null;

const reqs = ref({ requirements: {}, permissions: {}, ready: false, php_version: "" });
const homeUrl = `/${localStorage.getItem("language") || document.documentElement.lang || "ar"}/`;

const form = ref({
    APP_NAME: "My Application",
    APP_URL: window.location.origin,
    ADMIN_NAME: "",
    ADMIN_EMAIL: "",
    ADMIN_PASSWORD: "",
});

const checkRequirements = async () => {
    loading.value = true;
    loadingMessage.value = "Checking server requirements...";
    error.value = "";

    try {
        const res = await axios.get("/api/installer/requirements");
        reqs.value = res.data.data;
        step.value = 2;
    } catch (e) {
        error.value = e.response?.data?.message || "Could not fetch requirements.";
        toastr.error(error.value);
    } finally {
        loading.value = false;
    }
};

const processInstall = async () => {
    if (loading.value) return;

    loading.value = true;
    loadingMessage.value = "Preparing SQLite and finalizing installation...";
    error.value = "";

    const payload = {
        APP_NAME: form.value.APP_NAME,
        APP_URL: form.value.APP_URL,
        ADMIN_NAME: form.value.ADMIN_NAME,
        ADMIN_EMAIL: form.value.ADMIN_EMAIL,
        ADMIN_PASSWORD: form.value.ADMIN_PASSWORD,
    };

    try {
        const res = await axios.post("/api/installer/finish", payload, {
            timeout: 120000,
        });
        console.info("[InstallerWizard] finish response", res.data);
        toastr.success(res.data.message || "Installed successfully!");
        step.value = 4;
        startCountdown();
    } catch (e) {
        const apiMessage = e.response?.data?.error || e.response?.data?.message || "Installation failed.";
        const apiHint = e.response?.data?.hint || "";
        error.value = [apiMessage, apiHint].filter(Boolean).join(" ");
        toastr.error(error.value);
    } finally {
        loading.value = false;
    }
};

const startCountdown = () => {
    countdown.value = 5;
    countdownTimer = setInterval(() => {
        countdown.value--;
        if (countdown.value <= 0) {
            clearInterval(countdownTimer);
            goToHome();
        }
    }, 1000);
};

const goToHome = () => {
    console.info("[InstallerWizard] redirecting to home after successful installation", {
        target: homeUrl,
    });
    window.location.href = homeUrl;
};

onUnmounted(() => {
    if (countdownTimer) {
        clearInterval(countdownTimer);
    }
});
</script>

<style scoped>
.step-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transition: all 0.3s ease;
}

.step-dot--active {
    background: #fff;
    transform: scale(1.3);
    box-shadow: 0 0 8px rgba(255, 255, 255, 0.5);
}

.step-dot--done {
    background: rgba(255, 255, 255, 0.7);
}
</style>
