import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.js',
                'resources/js/main.js',
                'resources/js/config.js',
                'resources/js/ui-toasts.js',
                'resources/js/ui-modals.js',
                'resources/js/form-basic-inputs.js',
                'resources/js/extended-ui-perfect-scrollbar.js',
                'resources/js/ui-popover.js',
                'resources/js/pages-account-settings-account.js',
                'resources/js/dashboards-analytics.js',
                'resources/js/procedimientos/diagrama.js',
                'resources/js/procedimientos/redactorIA.js',
                'resources/js/procedimientos/formulario.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});



