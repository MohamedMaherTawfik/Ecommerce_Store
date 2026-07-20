<template>
    <section ref="target" class="section-shell" :class="{ 'is-visible': inView }">
        <SectionTitle eyebrow="Social proof" title="What customers say" description="Voice-of-customer signals that reinforce trust and the premium feel." />

        <div class="testimonial-grid">
            <TestimonialCard v-for="testimonial in testimonials" :key="testimonial.id" :testimonial="testimonial" />
        </div>
    </section>
</template>

<script setup>
import SectionTitle from "@/components/ui/SectionTitle.vue";
import TestimonialCard from "@/components/cards/TestimonialCard.vue";
import { useIntersectionObserver } from "@/composables/useIntersectionObserver";

defineProps({
    testimonials: { type: Array, default: () => [] },
});

const { target, inView } = useIntersectionObserver({ threshold: 0.12 });
</script>

<style scoped>
.testimonial-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
}

@media (max-width: 1000px) {
    .testimonial-grid {
        grid-template-columns: 1fr;
    }
}
</style>
