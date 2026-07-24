<template>
    <article class="testimonial-card">
        <Rating :value="testimonial.rating" />
        <p class="testimonial-card__quote">“{{ testimonial.quote }}”</p>
        <footer class="testimonial-card__footer">
            <div class="testimonial-card__avatar">{{ initials }}</div>
            <div>
                <strong>{{ testimonial.name }}</strong>
                <span>{{ testimonial.role }}</span>
            </div>
        </footer>
    </article>
</template>

<script setup>
import { computed } from "vue";
import Rating from "@/components/ui/Rating.vue";

const props = defineProps({
    testimonial: { type: Object, required: true },
});

const initials = computed(() =>
    (props.testimonial.name || "U")
        .split(" ")
        .map((part) => part[0])
        .join("")
        .slice(0, 2)
        .toUpperCase(),
);
</script>

<style scoped>
.testimonial-card {
    display: grid;
    gap: 1rem;
    padding: 1.25rem;
    border-radius: 1.35rem;
    border: 1px solid var(--premium-border);
    background: var(--premium-surface);
    box-shadow: var(--premium-shadow-sm);
}

.testimonial-card__quote {
    margin: 0;
    color: var(--premium-text);
    line-height: 1.75;
    font-size: .98rem;
}

.testimonial-card__footer {
    display: flex;
    align-items: center;
    gap: .85rem;
    padding-top: 1rem;
    border-top: 1px solid var(--premium-border);
}

.testimonial-card__avatar {
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    border-radius: 999px;
    background: linear-gradient(135deg, var(--hero-from), var(--hero-to));
    color: #fff;
    font-weight: 800;
}

.testimonial-card__footer strong {
    display: block;
    color: var(--premium-ink);
}

.testimonial-card__footer span {
    color: var(--premium-muted);
    font-size: .82rem;
}
</style>
