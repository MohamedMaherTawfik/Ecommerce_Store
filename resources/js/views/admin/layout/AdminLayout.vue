<template>
    <div class="admin-shell">
        <div class="admin-shell__backdrop"></div>

        <div class="admin-shell__body">
            <Sidebar />

            <main class="admin-main">
                <div class="admin-content">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import Sidebar from "./Sidebar.vue";
import adminAuthService from "@/services/adminAuthService";

const handleLogout = () => {
    adminAuthService.logout();
};
</script>

<style>
:root {
    --admin-bg: #f4f7fb;
    --admin-surface: rgba(255, 255, 255, 0.84);
    --admin-surface-strong: #ffffff;
    --admin-border: rgba(15, 23, 42, 0.08);
    --admin-border-strong: rgba(15, 23, 42, 0.12);
    --admin-text: #0f172a;
    --admin-muted: #64748b;
    --admin-primary: #2563eb;
    --admin-primary-soft: rgba(37, 99, 235, 0.12);
    --admin-success-soft: rgba(14, 165, 233, 0.1);
    --admin-danger-soft: rgba(239, 68, 68, 0.12);
    --admin-warning-soft: rgba(249, 115, 22, 0.12);
    --admin-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
    --admin-shadow-soft: 0 12px 30px rgba(15, 23, 42, 0.06);
    --admin-radius-xl: 28px;
    --admin-radius-lg: 22px;
    --admin-radius-md: 16px;
    --admin-radius-sm: 12px;
}

.admin-shell {
    position: relative;
    min-height: 100vh;
    background:
        radial-gradient(circle at top left, rgba(59, 130, 246, 0.15), transparent 32%),
        radial-gradient(circle at bottom right, rgba(14, 165, 233, 0.12), transparent 28%),
        var(--admin-bg);
    color: var(--admin-text);
    overflow: hidden;
}

.admin-shell__backdrop {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255, 255, 255, 0.3) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.3) 1px, transparent 1px);
    background-size: 28px 28px;
    mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.12), transparent 70%);
    pointer-events: none;
}

.admin-shell__body {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: minmax(268px, 300px) minmax(0, 1fr);
    min-height: 100vh;
}

.admin-main {
    padding: 28px 28px 36px;
}

.admin-content {
    animation: pageRise 0.5s ease;
}

.admin-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1.1rem 1.25rem;
    border: 1px solid rgba(255, 255, 255, 0.55);
    background: rgba(255, 255, 255, 0.55);
    border-radius: 20px;
    backdrop-filter: blur(18px);
    box-shadow: var(--admin-shadow-soft);
}

.admin-topbar__eyebrow {
    margin: 0 0 0.25rem;
    color: var(--admin-muted);
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.admin-topbar__title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
}

.admin-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    animation: pageRise 0.5s ease;
}

.admin-page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.5rem 1.6rem;
    border-radius: var(--admin-radius-xl);
    border: 1px solid rgba(255, 255, 255, 0.6);
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.88), rgba(255, 255, 255, 0.68));
    backdrop-filter: blur(18px);
    box-shadow: var(--admin-shadow-soft);
}

.admin-page-copy {
    max-width: 680px;
}

.admin-page-kicker {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    margin-bottom: 0.8rem;
    padding: 0.45rem 0.8rem;
    border-radius: 999px;
    background: var(--admin-primary-soft);
    color: var(--admin-primary);
    font-size: 0.82rem;
    font-weight: 700;
}

.admin-page-title {
    margin: 0;
    font-size: clamp(1.6rem, 1.2rem + 1vw, 2.25rem);
    font-weight: 800;
    letter-spacing: -0.03em;
}

.admin-page-description {
    margin: 0.65rem 0 0;
    color: var(--admin-muted);
    font-size: 1rem;
    line-height: 1.7;
}

.admin-page-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    justify-content: flex-end;
}

.admin-panel,
.admin-stat-card,
.admin-detail-card,
.admin-empty-state,
.admin-skeleton-panel {
    border-radius: var(--admin-radius-xl);
    border: 1px solid var(--admin-border);
    background: var(--admin-surface);
    backdrop-filter: blur(16px);
    box-shadow: var(--admin-shadow-soft);
}

.admin-panel {
    overflow: hidden;
}

.admin-panel__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.25rem 1.4rem;
    border-bottom: 1px solid rgba(15, 23, 42, 0.05);
}

.admin-panel__title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
}

.admin-panel__meta {
    margin: 0.35rem 0 0;
    color: var(--admin-muted);
    font-size: 0.92rem;
}

.admin-panel__body {
    padding: 1.4rem;
}

.admin-grid {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 1rem;
}

.admin-grid__item--4 {
    grid-column: span 4;
}

.admin-grid__item--6 {
    grid-column: span 6;
}

.admin-grid__item--8 {
    grid-column: span 8;
}

.admin-grid__item--12 {
    grid-column: span 12;
}

.admin-stat-card {
    position: relative;
    padding: 1.35rem;
    overflow: hidden;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.admin-stat-card::after {
    content: "";
    position: absolute;
    inset: auto -40px -40px auto;
    width: 110px;
    height: 110px;
    border-radius: 50%;
    background: rgba(37, 99, 235, 0.08);
}

.admin-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--admin-shadow);
}

.admin-stat-card__label {
    color: var(--admin-muted);
    font-size: 0.88rem;
    font-weight: 600;
}

.admin-stat-card__value {
    margin-top: 0.65rem;
    font-size: clamp(1.7rem, 1.35rem + 0.8vw, 2.5rem);
    font-weight: 800;
    letter-spacing: -0.03em;
}

.admin-stat-card__hint {
    margin-top: 0.85rem;
    color: var(--admin-muted);
    font-size: 0.88rem;
}

.admin-table-wrap {
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    min-width: 720px;
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
}

.admin-table thead th {
    position: sticky;
    top: 0;
    z-index: 1;
    padding: 1rem 1.1rem;
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    background: rgba(248, 250, 252, 0.92);
    color: var(--admin-muted);
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.admin-table tbody td {
    padding: 1rem 1.1rem;
    vertical-align: middle;
    border-bottom: 1px solid rgba(15, 23, 42, 0.05);
}

.admin-table tbody tr {
    transition: transform 0.2s ease, background-color 0.2s ease;
}

.admin-table tbody tr:hover {
    background: rgba(255, 255, 255, 0.72);
    transform: translateY(-1px);
}

.admin-table__primary {
    font-weight: 700;
}

.admin-table__secondary {
    margin-top: 0.2rem;
    color: var(--admin-muted);
    font-size: 0.88rem;
}

.admin-actions {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 0.55rem;
}

.admin-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.38rem 0.75rem;
    border-radius: 999px;
    background: rgba(148, 163, 184, 0.12);
    color: #334155;
    font-size: 0.82rem;
    font-weight: 700;
}

.admin-pill--success {
    background: var(--admin-success-soft);
    color: #0369a1;
}

.admin-pill--danger {
    background: var(--admin-danger-soft);
    color: #b91c1c;
}

.admin-pill--warning {
    background: var(--admin-warning-soft);
    color: #c2410c;
}

.admin-form-grid {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 1rem;
}

.admin-field {
    grid-column: span 6;
}

.admin-field--full {
    grid-column: span 12;
}

.admin-label {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    margin-bottom: 0.6rem;
    color: #334155;
    font-size: 0.88rem;
    font-weight: 700;
}

.admin-control,
.admin-control.form-control,
.admin-control.form-select {
    min-height: 54px;
    padding: 0.9rem 1rem;
    border-radius: var(--admin-radius-md);
    border: 1px solid rgba(148, 163, 184, 0.26);
    background: rgba(255, 255, 255, 0.92);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65);
    transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.admin-control:focus {
    border-color: rgba(37, 99, 235, 0.45);
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    transform: translateY(-1px);
}

textarea.admin-control {
    min-height: 128px;
    resize: vertical;
}

.admin-file-hint,
.admin-helper-text {
    margin-top: 0.55rem;
    color: var(--admin-muted);
    font-size: 0.84rem;
}

.admin-switch-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.9rem;
}

.admin-switch {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    min-width: 180px;
    padding: 1rem 1.05rem;
    border-radius: var(--admin-radius-lg);
    border: 1px solid rgba(148, 163, 184, 0.18);
    background: rgba(255, 255, 255, 0.75);
}

.admin-switch .form-check-input {
    margin-top: 0;
    width: 2.4rem;
    height: 1.35rem;
}

.admin-detail-grid {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 1rem;
}

.admin-detail-card {
    grid-column: span 6;
    padding: 1.2rem;
    transition: transform 0.22s ease, box-shadow 0.22s ease;
}

.admin-detail-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--admin-shadow);
}

.admin-detail-card__label {
    margin-bottom: 0.45rem;
    color: var(--admin-muted);
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.admin-detail-card__value {
    color: var(--admin-text);
    font-weight: 700;
    line-height: 1.7;
    word-break: break-word;
}

.admin-empty-state {
    padding: 2.8rem 1.2rem;
    text-align: center;
}

.admin-empty-state__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 68px;
    height: 68px;
    margin-bottom: 1rem;
    border-radius: 20px;
    background: var(--admin-primary-soft);
    color: var(--admin-primary);
    font-size: 1.45rem;
}

.admin-empty-state__title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
}

.admin-empty-state__text {
    max-width: 460px;
    margin: 0.7rem auto 0;
    color: var(--admin-muted);
    line-height: 1.7;
}

.admin-skeleton-panel {
    padding: 1.4rem;
}

.admin-skeleton-card,
.admin-skeleton-line {
    position: relative;
    overflow: hidden;
    background: rgba(148, 163, 184, 0.18);
}

.admin-skeleton-card::after,
.admin-skeleton-line::after {
    content: "";
    position: absolute;
    inset: 0;
    transform: translateX(-100%);
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.55), transparent);
    animation: shimmer 1.5s infinite;
}

.admin-skeleton-card {
    height: 130px;
    border-radius: var(--admin-radius-xl);
}

.admin-skeleton-line {
    height: 14px;
    border-radius: 999px;
}

.admin-skeleton-line+.admin-skeleton-line {
    margin-top: 0.75rem;
}

.admin-skeleton-line--lg {
    width: 48%;
    height: 18px;
}

.admin-skeleton-line--md {
    width: 68%;
}

.admin-skeleton-line--sm {
    width: 36%;
}

.admin-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.admin-pagination__meta {
    color: var(--admin-muted);
    font-size: 0.92rem;
}

.btn-admin {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.55rem;
    min-height: 46px;
    padding: 0.78rem 1.15rem;
    border: 1px solid transparent;
    border-radius: 14px;
    font-weight: 700;
    text-decoration: none;
    transition: transform 0.22s ease, box-shadow 0.22s ease, background-color 0.22s ease, color 0.22s ease, border-color 0.22s ease;
}

.btn-admin:hover {
    transform: translateY(-1px);
}

.btn-admin:disabled {
    opacity: 0.68;
    cursor: not-allowed;
    transform: none;
}

.btn-admin--primary {
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    color: #ffffff;
    box-shadow: 0 10px 24px rgba(37, 99, 235, 0.22);
}

.btn-admin--primary:hover {
    color: #ffffff;
    box-shadow: 0 14px 28px rgba(37, 99, 235, 0.26);
}

.btn-admin--soft {
    background: rgba(255, 255, 255, 0.85);
    border-color: rgba(148, 163, 184, 0.2);
    color: #334155;
}

.btn-admin--soft:hover {
    color: #0f172a;
    background: #ffffff;
}

.btn-admin--outline {
    border-color: rgba(37, 99, 235, 0.22);
    background: rgba(37, 99, 235, 0.06);
    color: var(--admin-primary);
}

.btn-admin--outline:hover {
    background: rgba(37, 99, 235, 0.12);
    color: #1d4ed8;
}

.btn-admin--danger {
    border-color: rgba(239, 68, 68, 0.18);
    background: rgba(239, 68, 68, 0.08);
    color: #b91c1c;
}

.btn-admin--danger:hover {
    background: rgba(239, 68, 68, 0.14);
    color: #991b1b;
}

.btn-admin--sm {
    min-height: 38px;
    padding: 0.55rem 0.9rem;
    border-radius: 12px;
    font-size: 0.84rem;
}

.page-fade-enter-active,
.page-fade-leave-active {
    transition: opacity 0.32s ease, transform 0.32s ease;
}

.page-fade-enter-from,
.page-fade-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

@keyframes shimmer {
    100% {
        transform: translateX(100%);
    }
}

@keyframes pageRise {
    from {
        opacity: 0;
        transform: translateY(14px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 1199.98px) {
    .admin-shell__body {
        grid-template-columns: 250px minmax(0, 1fr);
    }
}

@media (max-width: 991.98px) {
    .admin-shell__body {
        grid-template-columns: 1fr;
    }

    .admin-main {
        padding: 18px 16px 28px;
    }
}

@media (max-width: 767.98px) {

    .admin-topbar,
    .admin-page-header,
    .admin-panel__header {
        flex-direction: column;
        align-items: flex-start;
    }

    .admin-page-actions,
    .admin-actions {
        width: 100%;
        justify-content: stretch;
    }

    .admin-page-actions>*,
    .admin-actions>* {
        flex: 1 1 auto;
    }

    .admin-grid__item--4,
    .admin-grid__item--6,
    .admin-grid__item--8,
    .admin-field,
    .admin-detail-card {
        grid-column: span 12;
    }

    .admin-table {
        min-width: 640px;
    }
}
</style>
