import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import vuetify from './plugins/vuetify';
import i18n from './plugins/i18n';
import { Toast, options } from './plugins/toast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';


createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // Set up CSRF token for axios requests
        const token = props.initialPage.props.csrf_token;
        if (token) {
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        } else {
            console.error('CSRF token not found. Make sure it is shared from HandleInertiaRequests middleware.');
        }

        // Function to update CSRF token
        const updateCsrfToken = (newToken) => {
            if (newToken) {
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken;
            }
        };

        // Listen for Inertia page visits to update CSRF token
        router.on('navigate', (event) => {
            if (event.detail.page.props.csrf_token) {
                updateCsrfToken(event.detail.page.props.csrf_token);
            }
        });

        // Add request interceptor to ensure CSRF token is always included
        window.axios.interceptors.request.use(
            (config) => {
                // Ensure CSRF token is included for all non-GET requests
                if (config.method !== 'get' && !config.headers['X-CSRF-TOKEN']) {
                    const token = document.head.querySelector('meta[name="csrf-token"]');
                    if (token) {
                        config.headers['X-CSRF-TOKEN'] = token.content;
                    }
                }
                return config;
            },
            (error) => Promise.reject(error)
        );

        // Add response interceptor to handle CSRF token refresh
        window.axios.interceptors.response.use(
            (response) => response,
            async (error) => {
                if (error.response && error.response.status === 419) {
                    try {
                        // Try to get a fresh CSRF token
                        const response = await window.axios.get('/csrf-token');
                        if (response.data && response.data.csrf_token) {
                            updateCsrfToken(response.data.csrf_token);
                            // Retry the original request
                            return window.axios.request(error.config);
                        }
                    } catch (refreshError) {
                        console.error('Failed to refresh CSRF token:', refreshError);
                        // If we can't get a new token, the session might be expired
                        if (refreshError.response && refreshError.response.status === 401) {
                            // Redirect to login if unauthorized
                            window.location.href = '/login';
                            return;
                        }
                    }
                    // If all else fails, reload the page
                    window.location.reload();
                }
                return Promise.reject(error);
            }
        );

        app.use(plugin)
            .use(ZiggyVue)
            .use(vuetify)
            .use(i18n)
            .use(Toast, options)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
