import { useCartStore } from "../stores/cart";

export const syncCartState = (payload) => {
    const store = useCartStore();
    return store.syncCartState(payload);
};

export const setCartCount = (count) => {
    const store = useCartStore();
    store.setCartCount(count);
};

export const resetCartState = () => {
    const store = useCartStore();
    store.resetCartState();
};

export function useCartState() {
    const store = useCartStore();
    return {
        cartCount: store.cartCount,
        cartLoaded: store.cartLoaded,
        syncCartState,
        setCartCount,
        resetCartState,
    };
}
