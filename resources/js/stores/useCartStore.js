import { defineStore } from "pinia";
import { computed, ref } from "vue";

const countItems = (items = []) => items.reduce((sum, item) => sum + Number(item?.quantity || 0), 0);

const normalizeCartResource = (payload) => {
    if (!payload) return null;
    if (Array.isArray(payload.items)) return payload;
    if (payload.data && Array.isArray(payload.data.items)) return payload.data;
    if (payload.data?.data && Array.isArray(payload.data.data.items)) {
        return payload.data.data;
    }
    return null;
};

export const useCartStore = defineStore("cart", () => {
    const items = ref([]);
    const count = ref(0);
    const loaded = ref(false);

    const cartCount = computed(() => count.value);
    const cartLoaded = computed(() => loaded.value);
    const cartItems = computed(() => items.value);

    function syncCartState(payload) {
        const cart = normalizeCartResource(payload);
        if (!cart) return null;

        items.value = Array.isArray(cart.items) ? cart.items : [];
        count.value = countItems(items.value);
        loaded.value = true;

        return cart;
    }

    function setCartCount(newCount) {
        count.value = Math.max(0, Number(newCount || 0));
        loaded.value = true;
    }

    function addItem(product, quantity = 1) {
        const existing = items.value.find((item) => item.id === product.id);
        if (existing) {
            existing.quantity += quantity;
        } else {
            items.value.push({ ...product, quantity });
        }
        count.value = countItems(items.value);
        loaded.value = true;
    }

    function removeItem(productId) {
        items.value = items.value.filter((item) => item.id !== productId);
        count.value = countItems(items.value);
    }

    function resetCartState() {
        items.value = [];
        count.value = 0;
        loaded.value = false;
    }

    return {
        items,
        count,
        loaded,
        cartCount,
        cartLoaded,
        cartItems,
        syncCartState,
        setCartCount,
        addItem,
        removeItem,
        resetCartState,
    };
});
