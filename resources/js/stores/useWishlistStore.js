import { defineStore } from "pinia";
import { computed, ref } from "vue";

export const useWishlistStore = defineStore("wishlist", () => {
    const items = ref([]);

    const wishlistCount = computed(() => items.value.length);

    const syncWishlist = (payload = []) => {
        items.value = Array.isArray(payload) ? [...payload] : [];
    };

    const isWishlisted = (productId) => items.value.some((item) => item.id === productId);

    const toggleWishlist = (product) => {
        const index = items.value.findIndex((item) => item.id === product.id);
        if (index >= 0) {
            items.value.splice(index, 1);
            return false;
        }
        items.value = [{ ...product }, ...items.value];
        return true;
    };

    const clearWishlist = () => {
        items.value = [];
    };

    return {
        items,
        wishlistCount,
        syncWishlist,
        isWishlisted,
        toggleWishlist,
        clearWishlist,
    };
});
