<template>
  <article class="product-card">
    <RouterLink :to="`/${lang}/products/${product.slug || product.id}`" class="product-card__image-wrap">
      <img
        :src="imageUrl(product.image, 640)"
        :srcset="imageSrcset(product.image, [320, 480, 640])"
        sizes="(max-width: 520px) 100vw, (max-width: 1100px) 50vw, 25vw"
        :alt="product.name"
        class="product-card__image"
        loading="lazy"
        decoding="async"
        width="600"
        height="800"
      />
      <div class="product-card__overlay">
        <button
          class="product-card__quick-add"
          type="button"
          @click.prevent="$emit('add-to-cart', product)"
          :disabled="addingId === product.id"
          aria-label="Add to cart"
        >
          <i class="bi bi-bag-plus"></i>
          {{ addingId === product.id ? $t('cart.adding') : $t('cart.add_to_cart') }}
        </button>
      </div>
      <button class="product-card__wishlist" type="button" aria-label="Wishlist">
        <i class="bi bi-heart"></i>
      </button>
    </RouterLink>

    <div class="product-card__body">
      <p class="product-card__meta">{{ product.brand?.name || product.category?.name || 'Fashion' }}</p>
      <h3 class="product-card__name">{{ product.name }}</h3>

      <div v-if="showRating" class="product-card__stars">
        <i
          v-for="s in 5"
          :key="s"
          :class="['bi', s <= stars(product.average_rating) ? 'bi-star-fill' : 'bi-star']"
        ></i>
        <span>{{ Number(product.average_rating || 0).toFixed(1) }} / 5 · {{ Number(product.reviews_count || 0) }} reviews</span>
      </div>

      <div class="product-card__foot">
        <strong class="product-card__price">{{ money(product.price) }}</strong>
        <button
          class="product-card__cart-btn"
          type="button"
          @click="$emit('add-to-cart', product)"
          :disabled="addingId === product.id"
          aria-label="Add to cart"
        >
          <i class="bi bi-bag-plus"></i>
        </button>
      </div>
    </div>
  </article>
</template>

<script>
import { imageSrcset, imageUrl } from "@/utils/image";

export default {
  name: "ProductCard",
  props: {
    product: Object,
    lang: String,
    addingId: [String, Number, null],
    showRating: Boolean,
  },
  emits: ["add-to-cart"],
  methods: {
    imageUrl,
    imageSrcset,
    money(value) {
      return new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(Number(value || 0));
    },
    stars(rating) {
      return Math.round(Number(rating || 0));
    },
  },
};
</script>
