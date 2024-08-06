import { defineConfig } from "vite";

export default defineConfig({
    build: {
        outDir: './public/client/dist',
        copyPublicDir: false,
        modulePreload: {
          polyfill: false,
        },
        // generate .vite/manifest.json in outDir
        manifest: true,
        rollupOptions: {
            // overwrite default .html entry
            input: '/src/js/main.js',
        },
    },
})