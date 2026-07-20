/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: [
                    'Inter',
                    'ui-sans-serif',
                    'system-ui',
                    'sans-serif',
                    'Apple Color Emoji',
                    'Segoe UI Emoji',
                    'Segoe UI Symbol',
                    'Noto Color Emoji',
                ],
                display: [
                    'Outfit',
                    'Inter',
                    'ui-sans-serif',
                    'system-ui',
                    'sans-serif',
                ],
            },
            colors: {
                premium: {
                    ink: 'var(--premium-ink)',
                    text: 'var(--premium-text)',
                    muted: 'var(--premium-muted)',
                    gold: 'var(--premium-gold)',
                },
            },
            boxShadow: {
                premium: 'var(--premium-shadow-sm)',
                premiumLg: 'var(--premium-shadow-lg)',
            },
            borderRadius: {
                'premium-xl': '1.35rem',
                'premium-2xl': '2rem',
            },
        },
    },
    plugins: [],
}
