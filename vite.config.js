import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
            // Expose .env values to your JavaScript
            env: {
                VITE_PUSHER_APP_KEY: process.env.PUSHER_APP_KEY,
                VITE_PUSHER_APP_CLUSTER: process.env.PUSHER_APP_CLUSTER,
            },
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
});
