import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        // Set up CSRF token for all requests
        const token = document.head.querySelector('meta[name="csrf-token"]')?.content;
        if (token) {
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        }
        
        // Make CSRF token globally available
        window.csrfToken = token || props.initialPage.props.csrf_token;
        // Handle 419 CSRF errors globally
        router.on('error', (event) => {
            // Check if it's a 419 CSRF error
            if (event.detail && event.detail.response && event.detail.response.status === 419) {
                console.warn('CSRF token mismatch detected, reloading page...');
                window.location.reload();
            }
        });
        
        // Also handle Inertia visit errors
        router.on('exception', (event) => {
            if (event.detail && event.detail.response && event.detail.response.status === 419) {
                console.warn('CSRF token mismatch detected, reloading page...');
                window.location.reload();
            }
        });
        // Set window.Laravel for broadcasting
        if (props.initialPage.props.auth && props.initialPage.props.auth.user) {
            window.Laravel = {
                user: props.initialPage.props.auth.user,
                pusher: {
                    key: import.meta.env.VITE_PUSHER_APP_KEY,
                    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
                    wsHost: import.meta.env.VITE_PUSHER_HOST || `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusherapp.com`,
                    wsPort: import.meta.env.VITE_PUSHER_PORT || 80,
                    wssPort: import.meta.env.VITE_PUSHER_PORT || 443,
                    enabledTransports: ['ws', 'wss'],
                }
            };
        }

        // Make page props globally available
        window.page = props.initialPage;
        
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
