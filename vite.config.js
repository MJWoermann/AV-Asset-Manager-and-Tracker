import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        VitePWA({
            registerType: 'autoUpdate',
            includeAssets: ['favicon.ico'],
            manifest: {
                name: 'AV Asset Manager',
                short_name: 'AV Assets',
                description: 'AV equipment stocktake and asset management',
                theme_color: '#00adb7',
                background_color: '#ffffff',
                display: 'standalone',
                start_url: '/dashboard',
                icons: [
                    {
                        src: '/icons/icon-192.png',
                        sizes: '192x192',
                        type: 'image/png',
                    },
                    {
                        src: '/icons/icon-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                    },
                ],
            },
            workbox: {
                navigateFallback: null,
                runtimeCaching: [],
                globPatterns: ['**/*.{js,css,ico,png,svg,woff2}'],
            },
            devOptions: {
                enabled: false,
            },
        }),
    ],
});
