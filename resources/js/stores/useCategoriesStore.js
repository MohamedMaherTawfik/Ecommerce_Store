import { defineStore } from "pinia";
import { computed, ref } from "vue";

export const useCategoriesStore = defineStore("categories", () => {
    const categories = ref([]);
    const featuredBrands = ref([]);

    const categoryCount = computed(() => categories.value.length);

    const setCategories = (payload = []) => {
        categories.value = Array.isArray(payload) ? [...payload] : [];
    };

    const setFeaturedBrands = (payload = []) => {
        featuredBrands.value = Array.isArray(payload) ? [...payload] : [];
    };

    return {
        categories,
        featuredBrands,
        categoryCount,
        setCategories,
        setFeaturedBrands,
    };
});
