import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],

      // ⬇️ Tambahkan bagian ini
    build: {
        outDir: 'build', // hasil build langsung ke /build (bukan public/build)
    },
    base: '/', // penting, supaya URL asset tidak diawali /public/
});
