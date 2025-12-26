import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import vuetify from 'vite-plugin-vuetify';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
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
        vuetify({ autoImport: true }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: '192.168.1.38',
            protocol: 'ws',
        },
        cors: true,
        watch: {
            usePolling: true,
        },
    },
    build: {
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('vuetify')) {
                            return 'vendor-vuetify';
                        }
                        if (id.includes('chart.js')) {
                            return 'vendor-chartjs';
                        }
                        if (id.includes('vue') || id.includes('@inertiajs')) {
                            return 'vendor-vue';
                        }
                        if (id.includes('axios') || id.includes('lodash')) {
                            return 'vendor-utils';
                        }
                        return 'vendor';
                    }
                },
            },
        },
    },
});
