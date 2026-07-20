import { defineStore } from "pinia";
import { computed, ref } from "vue";
import { fetchHomeMock } from "@/services/mock/mockHomeService";
import { useCategoriesStore } from "@/stores/useCategoriesStore";
import { useProductsStore } from "@/stores/useProductsStore";
import { useSettingsStore } from "@/stores/useSettingsStore";

export const useHomeStore = defineStore("home", () => {
    const payload = ref(null);
    const loading = ref(false);
    const error = ref(null);
    const loaded = ref(false);

    const categoriesStore = useCategoriesStore();
    const productsStore = useProductsStore();
    const settingsStore = useSettingsStore();

    const heroSlides = computed(() => payload.value?.heroSlides || []);
    const trustItems = computed(() => payload.value?.trustItems || []);
    const categories = computed(() => payload.value?.categories || []);
    const brands = computed(() => payload.value?.brands || []);
    const flashSale = computed(() => payload.value?.flashSale || null);
    const promotionalBanner = computed(() => payload.value?.promotionalBanner || null);
    const promiseItems = computed(() => payload.value?.promiseItems || []);
    const testimonials = computed(() => payload.value?.testimonials || []);
    const instagram = computed(() => payload.value?.instagram || []);
    const reels = computed(() => payload.value?.reels || []);
    const newsletter = computed(() => payload.value?.newsletter || null);
    const stats = computed(() => payload.value?.stats || []);

    const loadHome = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetchHomeMock();
            payload.value = response?.data || null;
            loaded.value = true;

            categoriesStore.setCategories(payload.value?.categories || []);
            categoriesStore.setFeaturedBrands(payload.value?.brands || []);
            productsStore.setCollections(payload.value?.products || {});
            productsStore.setFlashSale(payload.value?.flashSale || null);
            settingsStore.setHomeSettings({
                newsletter: payload.value?.newsletter || null,
                promotionalBanner: payload.value?.promotionalBanner || null,
                stats: payload.value?.stats || [],
            });

            return payload.value;
        } catch (exception) {
            error.value = exception instanceof Error ? exception.message : "Unable to load homepage";
            throw exception;
        } finally {
            loading.value = false;
        }
    };

    return {
        payload,
        loading,
        error,
        loaded,
        heroSlides,
        trustItems,
        categories,
        brands,
        flashSale,
        promotionalBanner,
        promiseItems,
        testimonials,
        instagram,
        reels,
        newsletter,
        stats,
        loadHome,
    };
});
