import react from '@vitejs/plugin-react';
import { resolve } from 'path';
import { defineConfig } from 'vitest/config';

export default defineConfig({
    plugins: [react()],
    test: {
        globals: true,
        environment: 'jsdom',
        setupFiles: ['./resources/js/test/setup.ts'],
        include: ['resources/js/**/*.test.{ts,tsx}'],
        coverage: {
            provider: 'v8',
            include: ['resources/js/**/*.{ts,tsx}'],
            exclude: [
                'resources/js/app.tsx',
                'resources/js/ssr.tsx',
                'resources/js/bootstrap.ts',
                'resources/js/types/**',
                'resources/js/test/**',
                'resources/js/**/*.d.ts',
                // Wayfinder auto-generated files — not hand-authored
                'resources/js/routes/**',
                'resources/js/wayfinder/**',
                'resources/js/actions/**',
                // Standalone welcome page not in scope
                'resources/js/pages/welcome.tsx',
            ],
            thresholds: {
                lines: 90,
                functions: 90,
                branches: 85,
                statements: 90,
            },
            reporter: ['text', 'html'],
        },
    },
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
            '@inertiajs/react': resolve(__dirname, 'resources/js/test/mocks/inertia.tsx'),
        },
    },
});
