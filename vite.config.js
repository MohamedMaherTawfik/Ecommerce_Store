import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import { fileURLToPath, URL } from "node:url";

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), "");
    const apiTarget = (env.VITE_API_URL || "http://localhost:8000").replace(/\/$/, "");

    return {
        plugins: [
            laravel({
                input: [
                    "resources/css/app.css",
                    "resources/css/icons.css",
                    "resources/js/app.js",
                ],
                refresh: true,
            }),

            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],

        resolve: {
            alias: {
                "@": fileURLToPath(new URL("./resources/js", import.meta.url)),
            },
        },

        test: {
            environment: "jsdom",
            globals: true,
        },

        server: {
            host: "localhost",
            port: 5173,
            strictPort: true,
            watch: {
                ignored: ["**/.env", "**/.env.*"],
            },
            hmr: {
                host: "localhost",
            },
            proxy: {
                "/api": {
                    target: apiTarget,
                    changeOrigin: true,
                    secure: false,
                },
                "/storage": {
                    target: apiTarget,
                    changeOrigin: true,
                    secure: false,
                },
            },
        },

        build: {
            sourcemap: false,
            cssCodeSplit: true,
            minify: "esbuild",
        },
    };
});
