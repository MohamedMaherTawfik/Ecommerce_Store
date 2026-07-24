<template>
    <section ref="target" class="newsletter-shell" :class="{ 'is-visible': inView }">
        <div class="newsletter-shell__content">
            <Badge variant="gold">{{ newsletter?.eyebrow || 'Stay in the loop' }}</Badge>
            <h2>{{ newsletter?.title }}</h2>
            <p>{{ newsletter?.description }}</p>
        </div>

        <form class="newsletter-shell__form" @submit.prevent="subscribe">
            <input v-model="email" type="email" :placeholder="newsletter?.placeholder || 'you@example.com'" required />
            <Button variant="gold" :loading="loading" type="submit">{{ loading ? 'Joining...' : newsletter?.ctaLabel || 'Subscribe' }}</Button>
            <small v-if="success" class="newsletter-shell__success">You are on the list. Watch for the next drop.</small>
        </form>
    </section>
</template>

<script setup>
import { ref } from "vue";
import Badge from "@/components/ui/Badge.vue";
import Button from "@/components/ui/Button.vue";
import { useIntersectionObserver } from "@/composables/useIntersectionObserver";

defineProps({
    newsletter: { type: Object, default: null },
});

const email = ref("");
const loading = ref(false);
const success = ref(false);

const { target, inView } = useIntersectionObserver({ threshold: 0.12 });

const subscribe = async () => {
    loading.value = true;
    success.value = false;
    await new Promise((resolve) => setTimeout(resolve, 700));
    email.value = "";
    success.value = true;
    loading.value = false;
};
</script>

<style scoped>
.newsletter-shell {
    display: grid;
    grid-template-columns: 1.1fr .9fr;
    gap: 1rem;
    padding: clamp(1.5rem, 4vw, 3rem);
    border-radius: 2rem;
    background:
        radial-gradient(circle at top right, color-mix(in srgb, var(--accent) 12%, transparent), transparent 34%),
        linear-gradient(135deg, var(--hero-from) 0%, var(--hero-to) 100%);
    border: 1px solid rgba(255, 255, 255, .08);
    color: #fff;
    box-shadow: var(--premium-shadow-lg);
}

.newsletter-shell__content {
    display: grid;
    gap: 1rem;
    align-content: center;
}

.newsletter-shell__content h2 {
    margin: 0;
    font-size: clamp(2rem, 4vw, 3.4rem);
    line-height: 1;
    letter-spacing: -.05em;
}

.newsletter-shell__content p {
    margin: 0;
    max-width: 56ch;
    color: rgba(255, 255, 255, .72);
    line-height: 1.75;
}

.newsletter-shell__form {
    display: grid;
    align-content: center;
    gap: .9rem;
}

.newsletter-shell__form input {
    min-height: 52px;
    padding: 0 1rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, .14);
    background: rgba(255, 255, 255, .08);
    color: #fff;
}

.newsletter-shell__form input::placeholder {
    color: rgba(255, 255, 255, .5);
}

.newsletter-shell__success {
    color: #c8f7d4;
    font-weight: 700;
}

@media (max-width: 900px) {
    .newsletter-shell {
        grid-template-columns: 1fr;
    }
}
</style>
