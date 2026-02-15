import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
    root: '.',
    publicDir: 'assets',
    build: {
        outDir: 'dist',
        emptyOutDir: false,
        rollupOptions: {
            input: {
                'replay-player': resolve(__dirname, 'assets/js/replay-player.js')
            },
            output: {
                entryFileNames: 'assets/js/[name]-[hash].js',
                chunkFileNames: 'assets/js/[name]-[hash].js',
                assetFileNames: 'assets/[ext]/[name]-[hash].[ext]'
            }
        }
    },
    server: {
        port: 3000,
        proxy: {
            '/?controller': {
                target: 'http://localhost:80',
                changeOrigin: true
            }
        }
    },
    optimizeDeps: {
        exclude: ['koishipro-core.js']
    },
    resolve: {
        alias: {
            '@': resolve(__dirname, 'assets')
        }
    }
});
