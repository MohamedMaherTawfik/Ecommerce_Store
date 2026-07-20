import { defineStore } from "pinia";
import { computed, ref } from "vue";

const applyTheme = (theme) => {
    document.documentElement.setAttribute("data-theme", theme);
    document.documentElement.setAttribute("data-bs-theme", theme);
    localStorage.setItem("theme", theme);
};

export const useThemeStore = defineStore("theme", () => {
    const theme = ref(localStorage.getItem("theme") || "light");

    const isDark = computed(() => theme.value === "dark");

    const initTheme = () => {
        applyTheme(theme.value);
    };

    const setTheme = (value) => {
        theme.value = value === "dark" ? "dark" : "light";
        applyTheme(theme.value);
    };

    const toggleTheme = () => {
        setTheme(isDark.value ? "light" : "dark");
    };

    return {
        theme,
        isDark,
        initTheme,
        setTheme,
        toggleTheme,
    };
});
