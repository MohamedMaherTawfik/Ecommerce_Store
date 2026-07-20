<template>
    <main class="store-shell cart-page">
        <header class="cart-header store-card">
            <div>
                <span class="store-eyebrow">Checkout</span>
                <h1 class="store-title">Your cart</h1>
                <p class="store-subtitle">Review items, apply coupons, and finish your checkout.</p>
            </div>
        </header>

        <div v-if="loading" class="cart-skeleton"></div>

        <div v-else class="cart-layout">
            <section class="cart-items store-card">
                <div v-if="!cart.items?.length" class="empty-cart">
                    <i class="bi bi-bag"></i>
                    <h2>Your cart is empty</h2>
                    <RouterLink :to="`/${lang}/products`" class="store-btn store-btn--primary">Shop products</RouterLink>
                </div>

                <article v-for="item in cart.items" :key="item.id" class="cart-row">
                    <img :src="imageUrl(item.product?.image)" :alt="item.product?.name" width="96" height="128"
                        loading="lazy" decoding="async" />
                    <div class="cart-row__copy">
                        <h2>{{ item.product?.name }}</h2>
                        <p>{{ item.size || 'Standard' }} · {{ item.color || 'Default' }}</p>
                        <strong>{{ money(item.product?.price || 0) }}</strong>
                    </div>
                    <div class="quantity-control">
                        <button type="button" @click="setQuantity(item, item.quantity - 1)" :disabled="busy || item.quantity <= 1">-</button>
                        <input :value="item.quantity" type="number" min="1" @change="setQuantity(item, Number($event.target.value))" />
                        <button type="button" @click="setQuantity(item, item.quantity + 1)" :disabled="busy">+</button>
                    </div>
                    <button class="remove-btn" type="button" :disabled="busy" @click="remove(item.id)">
                        <i class="bi bi-trash"></i>
                    </button>
                </article>
            </section>

            <aside class="summary store-card">
                <h2>Order summary</h2>

                <form class="coupon-form" @submit.prevent="applyCoupon">
                    <input v-model.trim="couponCode" class="store-input form-control" placeholder="Coupon code" />
                    <button class="store-btn store-btn--primary" :disabled="busy || !couponCode">Apply</button>
                </form>

                <button v-if="cart.coupon" class="remove-coupon" type="button" @click="removeCoupon">
                    Remove {{ cart.coupon.code }}
                </button>

                <div class="summary-line"><span>Subtotal</span><strong>{{ money(cart.subtotal) }}</strong></div>
                <div class="summary-line"><span>Discount</span><strong>-{{ money(cart.discount) }}</strong></div>
                <div class="summary-line summary-line--total"><span>Total</span><strong>{{ money(cart.total) }}</strong></div>

                <form class="checkout-form" @submit.prevent="checkout">
                    <input v-model.trim="checkoutForm.phone" class="store-input form-control" placeholder="Phone" required maxlength="50" />
                    <input v-model.trim="checkoutForm.city" class="store-input form-control" placeholder="City" maxlength="100" />
                    <textarea v-model.trim="checkoutForm.address" class="store-textarea form-control" rows="3" placeholder="Address" required maxlength="500"></textarea>
                    <select v-model="checkoutForm.payment_method" class="store-select form-select">
                        <option value="paypal">PayPal</option>
                        <option value="cash_on_delivery">Cash on Delivery</option>
                        <option value="stripe">Stripe</option>
                        <option value="paymob">Paymob</option>
                        <option value="myfatoorah">MyFatoorah</option>
                    </select>
                    <button class="store-btn store-btn--primary" :disabled="busy || !cart.items?.length">
                        {{ busy ? 'Processing...' : 'Checkout' }}
                    </button>
                </form>
            </aside>
        </div>
    </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useSeoMeta } from '@/composables/useSeoMeta';

useSeoMeta({
    title: 'Shopping Cart',
    description: 'Review your cart, apply coupons, and checkout securely on EliteShop.',
    robots: 'noindex,nofollow'
});
import toastr from 'toastr';
import { resetCartState, syncCartState } from '@/composables/useCartState';
import CartService from '@/services/home/CartService';
import OrderService from '@/services/home/OrderService';

const route = useRoute();
const router = useRouter();
const lang = computed(() => route.params.lang || localStorage.getItem('language') || 'en');

const loading = ref(false);
const busy = ref(false);
const couponCode = ref('');
const cart = ref({ items: [], subtotal: 0, discount: 0, total: 0, coupon: null });

const checkoutForm = reactive({
    payment_method: 'paypal',
    phone: '',
    city: '',
    address: '',
});

const fetchCart = async () => {
    loading.value = true;
    try {
        const response = await CartService.getCart();
        cart.value = syncCartState(response) || response.data || cart.value;
    } catch (err) {
        toastr.error(err.response?.data?.message || 'Unable to load cart.');
    } finally {
        loading.value = false;
    }
};

const setQuantity = async (item, quantity) => {
    const nextQuantity = Math.max(1, Number(quantity || 1));
    const oldQuantity = item.quantity;
    item.quantity = nextQuantity;

    busy.value = true;
    try {
        const response = await CartService.updateQuantity(item.id, nextQuantity);
        cart.value = syncCartState(response) || response.data;
    } catch (err) {
        item.quantity = oldQuantity;
        toastr.error(err.response?.data?.message || 'Unable to update quantity.');
    } finally {
        busy.value = false;
    }
};

const applyCoupon = async () => {
    busy.value = true;
    try {
        const response = await CartService.applyCoupon(couponCode.value);
        cart.value = syncCartState(response) || response.data;
        toastr.success('Coupon applied.');
    } catch (err) {
        toastr.error(err.response?.data?.message || 'Unable to apply coupon.');
    } finally {
        busy.value = false;
    }
};

const removeCoupon = async () => {
    try {
        const response = await CartService.removeCoupon();
        cart.value = syncCartState(response) || response.data;
        couponCode.value = '';
    } catch (err) {
        toastr.error(err.response?.data?.message || 'Unable to remove coupon.');
    }
};

const remove = async (id) => {
    busy.value = true;
    try {
        await CartService.remove(id);
        await fetchCart();
        toastr.success('Item removed from cart.');
        if (!cart.value.items?.length) {
            resetCartState();
        }
    } catch (err) {
        toastr.error(err.response?.data?.message || 'Unable to remove item.');
    } finally {
        busy.value = false;
    }
};

const checkout = async () => {
    busy.value = true;
    try {
        const response = await OrderService.checkout(checkoutForm);

        const paymentUrl = response.payment_url || response.approval_url;
        if (paymentUrl) {
            window.location.href = paymentUrl;
            return;
        }

        toastr.success(`Order ${response.order_number} created.`);
        await router.push(`/${lang.value}/orders/${response.order_id}`);
    } finally {
        busy.value = false;
    }
};

const money = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(value || 0));
const imageUrl = (path) => !path ? '/images/categorey.webp' : path.startsWith('http') ? path : `/storage/${path}`;

onMounted(fetchCart);
</script>

<style scoped>
.cart-page {
    display: grid;
    gap: 1rem;
}

.cart-header {
    padding: 1.2rem;
}

.cart-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 360px;
    gap: 1rem;
    align-items: start;
}

.cart-items {
    overflow: hidden;
}

.cart-row {
    display: grid;
    grid-template-columns: 92px minmax(0, 1fr) 128px 42px;
    gap: 0.9rem;
    align-items: center;
    padding: 0.9rem;
    border-bottom: 1px solid var(--sf-border);
}

.cart-row img {
    width: 92px;
    height: 92px;
    object-fit: cover;
    border-radius: 0.75rem;
}

.cart-row h2 {
    margin: 0;
    font-size: 0.98rem;
    font-weight: 800;
    color: var(--sf-text);
}

.cart-row p {
    margin: 0.3rem 0;
    color: var(--sf-muted);
    font-size: 0.84rem;
}

.cart-row strong {
    color: var(--sf-text);
}

.quantity-control {
    display: grid;
    grid-template-columns: 34px 1fr 34px;
    border: 1px solid var(--sf-border);
    border-radius: 0.7rem;
    overflow: hidden;
}

.quantity-control button,
.quantity-control input,
.remove-btn {
    min-height: 36px;
    border: 0;
    background: var(--sf-surface);
    color: var(--sf-text);
    text-align: center;
}

.quantity-control input {
    border-inline: 1px solid var(--sf-border);
}

.remove-btn {
    border-radius: 0.7rem;
    color: var(--sf-danger);
}

.summary {
    display: grid;
    gap: 0.9rem;
    padding: 1rem;
}

.summary h2 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--sf-text);
}

.coupon-form,
.checkout-form {
    display: grid;
    gap: 0.65rem;
}

.coupon-form {
    grid-template-columns: 1fr auto;
}

.remove-coupon {
    border: 0;
    background: transparent;
    color: var(--sf-danger);
    font-weight: 700;
    text-align: left;
    font-size: 0.85rem;
}

.summary-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: var(--sf-muted);
}

.summary-line--total {
    padding-top: 0.8rem;
    border-top: 1px solid var(--sf-border);
    color: var(--sf-text);
    font-size: 1.1rem;
}

.empty-cart {
    display: grid;
    place-items: center;
    gap: 0.65rem;
    text-align: center;
    min-height: 300px;
    padding: 1rem;
}

.empty-cart i {
    font-size: 1.7rem;
    color: var(--sf-muted);
}

.empty-cart h2 {
    margin: 0;
    color: var(--sf-text);
}

.cart-skeleton {
    height: 420px;
    border-radius: 1rem;
    background: linear-gradient(90deg, var(--sf-surface-soft), color-mix(in srgb, var(--sf-surface-soft) 75%, var(--sf-border)), var(--sf-surface-soft));
    background-size: 300% 100%;
    animation: pulse 1.2s linear infinite;
}

@keyframes pulse {
    from {
        background-position: 100% 0;
    }

    to {
        background-position: -100% 0;
    }
}

@media (max-width: 991.98px) {
    .cart-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 575.98px) {
    .cart-row {
        grid-template-columns: 72px minmax(0, 1fr);
    }

    .quantity-control,
    .remove-btn {
        grid-column: 1 / -1;
    }

    .coupon-form {
        grid-template-columns: 1fr;
    }
}
</style>
