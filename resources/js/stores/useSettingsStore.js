import { defineStore } from "pinia";
import { computed, ref } from "vue";

export const useSettingsStore = defineStore("settings", () => {
    const appName = ref(import.meta.env.VITE_APP_NAME || "EliteShop");
    const currency = ref("USD");
    const social = ref([]);
    const home = ref({});

    const siteLabel = computed(() => appName.value);

    const setHomeSettings = (payload = {}) => {
        home.value = { ...payload };
    };

    const setSocialLinks = (payload = []) => {
        social.value = Array.isArray(payload) ? [...payload] : [];
    };

    return {
        appName,
        currency,
        social,
        home,
        siteLabel,
        setHomeSettings,
        setSocialLinks,
    };
});
