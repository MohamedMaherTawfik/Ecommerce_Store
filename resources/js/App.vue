<template>
    <div class="d-flex flex-column min-vh-100">
        <NavbarComponent v-if="showNavbar" />

        <main class="flex-fill">
            <router-view />
        </main>

        <FooterComponent v-if="showFooter" />
    </div>
</template>

<script setup>
import { computed, defineAsyncComponent } from 'vue';
import { useRoute } from 'vue-router';
import NavbarComponent from './components/layouts/Navbar.vue';

const FooterComponent = defineAsyncComponent(() => import('./components/layouts/footer.vue'));
const route = useRoute();

const showNavbar = computed(() => !route.meta.hideNavbar);
const showFooter = computed(() => !route.meta.hideFooter);
</script>
