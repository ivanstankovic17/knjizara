import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // Ulazne tačke: svaka stranica učitava CSS i svoj skript.
            input: [
                'resources/css/app.css',
                'resources/js/catalog.js',
                'resources/js/new-book.js',
                'resources/js/login.js',
                'resources/js/account.js',
                'resources/js/admin-orders.js',
                'resources/js/api-docs.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
