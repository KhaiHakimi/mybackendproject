import '../css/app.css'
import 'maplibre-gl/dist/maplibre-gl.css';
import './bootstrap'

import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createApp, h } from 'vue'
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/index.esm.js'

const appName = import.meta.env.VITE_APP_NAME || 'FerryCast'

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)

        app.config.errorHandler = (err, instance, info) => {
            console.error("Global Vue Error:", err, info);
            const errorDiv = document.createElement('div');
            errorDiv.style.position = 'fixed';
            errorDiv.style.top = '0';
            errorDiv.style.left = '0';
            errorDiv.style.width = '100%';
            errorDiv.style.backgroundColor = '#FEE2E2';
            errorDiv.style.color = '#B91C1C';
            errorDiv.style.padding = '20px';
            errorDiv.style.zIndex = '99999';
            errorDiv.style.fontFamily = 'monospace';
            errorDiv.innerHTML = `<strong>Runtime Error:</strong><br/>${err.toString()}<br/><br/><strong>Info:</strong> ${info}`;
            document.body.appendChild(errorDiv);
        };

        app.mount(el)
        return app;
    },
    progress: {
        color: '#4B5563',
    },
})
