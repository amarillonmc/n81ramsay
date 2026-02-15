import { defineConfig } from 'vite';
import { resolve } from 'path';
import { builtinModules } from 'module';

export default defineConfig({
    root: '.',
    publicDir: 'public',
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        lib: {
            entry: resolve(__dirname, 'assets/js/replay-player.js'),
            name: 'ReplayPlayer',
            formats: ['es'],
            fileName: () => 'replay-player.bundle.js'
        },
        rollupOptions: {
            external: [],
            output: {
                inlineDynamicImports: true,
                manualChunks: undefined
            }
        },
        target: 'es2020',
        minify: false,
        sourcemap: true
    },
    resolve: {
        alias: {
            'fs': resolve(__dirname, 'assets/js/shims/empty.js'),
            'path': resolve(__dirname, 'assets/js/shims/empty.js'),
            'buffer': resolve(__dirname, 'assets/js/shims/buffer.js'),
            'process': resolve(__dirname, 'assets/js/shims/process.js'),
            'stream': resolve(__dirname, 'assets/js/shims/empty.js'),
            'util': resolve(__dirname, 'assets/js/shims/empty.js'),
            'events': resolve(__dirname, 'assets/js/shims/empty.js')
        }
    },
    define: {
        'process.env.NODE_ENV': '"production"',
        'global': 'globalThis'
    }
});
