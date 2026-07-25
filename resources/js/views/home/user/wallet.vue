<template>
    <main class="store-shell wallet-page">
        <header class="wallet-header store-card">
            <div>
                <span class="store-eyebrow">Wallet</span>
                <h1 class="store-title">My wallet</h1>
                <p class="store-subtitle">Track your balance and wallet account details.</p>
            </div>
        </header>

        <section v-if="loading" class="wallet-loading store-card">
            <div class="spinner"></div>
            <p>Loading wallet data...</p>
        </section>

        <section v-else-if="walletData" class="wallet-content">
            <article class="balance-card store-card">
                <p>Current balance</p>
                <h2>{{ formatBalance(walletData.wallet.balance) }} <span>EGP</span></h2>
                <small>{{ walletData.wallet.uuid.slice(0, 8) }}...</small>
            </article>

            <article class="owner-card store-card">
                <div class="avatar">
                    <img v-if="walletData.image" :src="getImageUrl(walletData.image)" alt="avatar" loading="lazy" decoding="async" />
                    <span v-else>{{ getInitials(walletData.name) }}</span>
                </div>
                <div>
                    <h3>{{ walletData.name }}</h3>
                    <p>{{ walletData.email }}</p>
                </div>
                <span class="role-chip">{{ walletData.role || 'user' }}</span>
            </article>

            <article class="wallet-meta store-card">
                <div class="meta-item">
                    <span>Wallet ID</span>
                    <strong>{{ walletData.wallet.uuid }}</strong>
                </div>
                <div class="meta-item">
                    <span>Created</span>
                    <strong>{{ formatDate(walletData.wallet.created_at) }}</strong>
                </div>
                <div class="meta-item">
                    <span>Last update</span>
                    <strong>{{ formatLastSeen(walletData.wallet.updated_at) }}</strong>
                </div>
                <div class="meta-item">
                    <span>Status</span>
                    <strong>{{ walletData.wallet.is_active ? 'Active' : 'Inactive' }}</strong>
                </div>
            </article>

        </section>

        <section v-else class="wallet-error store-card">
            <h3>Unable to load wallet</h3>
            <p>{{ error }}</p>
            <button class="store-btn store-btn--soft" @click="fetchWallet">Try again</button>
        </section>
    </main>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useSeoMeta } from '@/composables/useSeoMeta';
import walletService from '../../../services/profile/walletService';

useSeoMeta({
    title: 'My Wallet',
    description: 'Track your EliteShop wallet balance.',
    robots: 'noindex,nofollow'
});

const walletData = ref(null);
const loading = ref(true);
const error = ref('');

const fetchWallet = async () => {
    loading.value = true;
    error.value = '';

    try {
        const response = await walletService.getWallet();
        const payload = response.data || response;

        if (!payload?.wallet) {
            throw new Error('Invalid wallet payload');
        }

        walletData.value = payload;
    } catch {
        walletData.value = null;
        error.value = 'Failed to load wallet data.';
    } finally {
        loading.value = false;
    }
};

const getImageUrl = (imagePath) => {
    if (!imagePath) {
        return '';
    }
    if (imagePath.startsWith('http')) {
        return imagePath;
    }
    if (imagePath.startsWith('/storage') || imagePath.startsWith('storage')) {
        return imagePath;
    }
    return `/storage/${imagePath}`;
};

const formatBalance = (balance) => new Intl.NumberFormat('en-US').format(Number(balance || 0));

const formatDate = (value) => {
    if (!value) {
        return '-';
    }
    return new Date(value).toLocaleDateString('en-US');
};

const formatLastSeen = (value) => {
    if (!value) {
        return '-';
    }

    const now = new Date();
    const seen = new Date(value);
    const diff = Math.floor((now - seen) / 1000 / 60);

    if (diff < 1) {
        return 'Just now';
    }
    if (diff < 60) {
        return `${diff} minutes ago`;
    }
    if (diff < 1440) {
        return `${Math.floor(diff / 60)} hours ago`;
    }

    return formatDate(value);
};

const getInitials = (name) => (name ? name.charAt(0).toUpperCase() : '?');

onMounted(fetchWallet);
</script>

<style scoped>
.wallet-page {
    display: grid;
    gap: 1rem;
}

.wallet-header {
    padding: 1.2rem;
}

.wallet-loading,
.wallet-error {
    min-height: 220px;
    display: grid;
    place-items: center;
    text-align: center;
    gap: 0.6rem;
    padding: 1rem;
}

.wallet-loading p,
.wallet-error p {
    margin: 0;
    color: var(--sf-muted);
}

.spinner {
    width: 34px;
    height: 34px;
    border: 3px solid var(--sf-border);
    border-top-color: var(--sf-primary);
    border-radius: 999px;
    animation: spin 0.7s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.wallet-content {
    display: grid;
    gap: 0.9rem;
}

.balance-card {
    padding: 1.1rem;
    background: linear-gradient(145deg, color-mix(in srgb, var(--primary) 80%, var(--hero-from)), var(--hero-from));
    border-color: transparent;
    color: #fff;
}

.balance-card p {
    margin: 0;
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.78);
}

.balance-card h2 {
    margin: 0.45rem 0;
    font-size: clamp(1.9rem, 4vw, 2.7rem);
    font-weight: 800;
}

.balance-card h2 span {
    font-size: 1rem;
    font-weight: 500;
    opacity: 0.8;
}

.balance-card small {
    opacity: 0.7;
}

.owner-card {
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.avatar {
    width: 52px;
    height: 52px;
    border-radius: 999px;
    overflow: hidden;
    border: 1px solid var(--sf-border);
    background: var(--sf-surface-soft);
    color: var(--sf-text);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
}

.avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.owner-card h3 {
    margin: 0;
    color: var(--sf-text);
    font-size: 1.03rem;
}

.owner-card p {
    margin: 0.22rem 0 0;
    color: var(--sf-muted);
    font-size: 0.82rem;
}

.role-chip {
    margin-inline-start: auto;
    border-radius: 999px;
    border: 1px solid var(--sf-border);
    background: var(--sf-surface-soft);
    padding: 0.25rem 0.65rem;
    color: var(--sf-text);
    font-size: 0.78rem;
    font-weight: 700;
}

.wallet-meta {
    padding: 1rem;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.7rem;
}

.meta-item {
    border: 1px solid var(--sf-border);
    border-radius: 0.75rem;
    background: var(--sf-surface-soft);
    padding: 0.7rem;
}

.meta-item span {
    display: block;
    font-size: 0.78rem;
    color: var(--sf-muted);
}

.meta-item strong {
    color: var(--sf-text);
    font-size: 0.88rem;
}

@media (max-width: 767.98px) {
    .wallet-meta {
        grid-template-columns: 1fr;
    }

    .owner-card {
        flex-wrap: wrap;
    }

    .role-chip {
        margin-inline-start: 0;
    }
}
</style>
