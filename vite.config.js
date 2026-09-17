import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/lean.js'],
            refresh: true,
            fonts: [
                // Brand typeface (self-hosted at build). Swap here and in resources/css/app.css if the brand font changes.
                bunny('Plus Jakarta Sans', {
                    weights: [400, 500, 600, 700, 800],
                    // Only the weights used above the fold (body and headings) are preloaded; the rest load on demand.
                    preload: [{ weight: 400, style: 'normal' }, { weight: 700, style: 'normal' }],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
