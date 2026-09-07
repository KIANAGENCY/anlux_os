import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/ordenes/main.tsx',
                'resources/js/historial/main.tsx',
                'resources/js/login/main.tsx',
                'resources/js/admin/users/main.tsx',
                'resources/js/admin/registro/main.tsx',
                'resources/js/admin/catalogo/main.tsx',
                'resources/js/admin/index/main.tsx',
                'resources/js/admin/seguridad/main.tsx',
                'resources/js/admin/folios/main.tsx',
                'resources/js/admin/integraciones/main.tsx',
                'resources/js/admin/apariencia/main.tsx',
                'resources/js/orden_form/main.tsx',
                'resources/js/shared/nav/main.tsx',
                'resources/js/home/main.tsx',
                'resources/js/auth/forgot_password/main.tsx',
                'resources/js/auth/reset_password/main.tsx',
                'resources/js/auth/confirm_password/main.tsx',
                'resources/js/auth/verify_email/main.tsx',
                'resources/js/maintenance/main.tsx',
                'resources/js/profile/main.tsx',
                'resources/js/legal/main.tsx',
            ],
            refresh: true,
        }),
        react(),
    ],
    build: {
        // Keep prior hashed assets so cached HTML tabs don't 404 CSS/JS after rebuild.
        emptyOutDir: false,
    },
});
