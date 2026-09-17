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
                // Placeholder brand typeface; swap here and in resources/css/app.css when approved.
                bunny('Instrument Sans', {
                    weights: [400, 500, 600, 700],
                    // Only the weights used above the fold (body and headings) are preloaded; 500/700 load on demand.
                    preload: [{ weight: 400, style: 'normal' }, { weight: 600, style: 'normal' }],
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
