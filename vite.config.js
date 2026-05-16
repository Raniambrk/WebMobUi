import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        vue(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/poll-dashboard.js',
                // Ajout : entrypoint pour la page de vote via token
                'resources/js/poll-vote.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    // Ajout : alias @ pour pointer vers resources/js (utilisé dans les imports Vue)
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    server: {
        host: true,
        hmr: {
            host: 'localhost'
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});