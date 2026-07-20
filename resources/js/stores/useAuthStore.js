import { defineStore } from "pinia";
import { computed, ref } from "vue";

export const useAuthStore = defineStore("auth", () => {
    const user = ref(null);
    const token = ref(null);

    const isLoggedIn = computed(() => Boolean(user.value || token.value));
    const isGuest = computed(() => !isLoggedIn.value);

    function setUser(payload) {
        user.value = payload || null;
    }

    function setToken(value) {
        token.value = value || null;
    }

    function logout() {
        user.value = null;
        token.value = null;
    }

    return {
        user,
        token,
        isLoggedIn,
        isGuest,
        setUser,
        setToken,
        logout,
    };
});
