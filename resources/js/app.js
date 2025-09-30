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

// Function to get CSRF token from meta tag
function getCSRFToken() {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  return token
}

// Function to refresh CSRF token
function refreshCSRFToken() {
  const token = getCSRFToken()
  if (token) {
    // Update Axios default headers if available
    if (window.axios) {
      window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token
    }
    // Update any existing CSRF token inputs in forms
    document.querySelectorAll('input[name="_token"]').forEach(input => {
      input.value = token
    })
  }
}

// Add Inertia router event listeners for CSRF handling
router.on('before', (event) => {
  // Refresh CSRF token before each request
  refreshCSRFToken()
})

router.on('error', (errors) => {
  // Handle 419 CSRF token mismatch errors
  if (errors && (errors[419] || errors['419'] || (typeof errors === 'object' && Object.keys(errors).some(key => key.includes('419'))))) {
    console.warn('CSRF token expired, refreshing page...')
    // Reload the page to get a fresh CSRF token
    window.location.reload()
  }
})

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
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
