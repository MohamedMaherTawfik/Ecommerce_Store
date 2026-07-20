import { defineStore } from "pinia";
import { computed, ref } from "vue";

export const useProductsStore = defineStore("products", () => {
    const featured = ref([]);
    const bestSellers = ref([]);
    const trending = ref([]);
    const spotlight = ref([]);
    const flashSale = ref(null);

    const featuredCount = computed(() => featured.value.length);

    const setCollections = (payload = {}) => {
        featured.value = Array.isArray(payload.featured) ? [...payload.featured] : [];
        bestSellers.value = Array.isArray(payload.bestSellers) ? [...payload.bestSellers] : [];
        trending.value = Array.isArray(payload.trending) ? [...payload.trending] : [];
        spotlight.value = Array.isArray(payload.spotlight) ? [...payload.spotlight] : [];
    };

    const setFlashSale = (payload = null) => {
        flashSale.value = payload ? { ...payload, items: Array.isArray(payload.items) ? [...payload.items] : [] } : null;
    };

    return {
        featured,
        bestSellers,
        trending,
        spotlight,
        flashSale,
        featuredCount,
        setCollections,
        setFlashSale,
    };
});
