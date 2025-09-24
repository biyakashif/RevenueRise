import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Add CSRF token to all requests
function setupCSRF() {
    const token = document.head.querySelector('meta[name="csrf-token"]') || 
                  (window.Laravel && window.Laravel.csrfToken ? { content: window.Laravel.csrfToken } : null) ||
                  (window.page && window.page.props && window.page.props.csrf_token ? { content: window.page.props.csrf_token } : null);

    if (token) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
        window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        window.axios.defaults.withCredentials = true;
    } else {
        console.error('CSRF token not found');
    }
}

// Setup CSRF immediately
setupCSRF();



// Handle 419 CSRF token mismatch
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 419) {
            // Try to refresh CSRF token before reloading
            fetch('/sanctum/csrf-cookie', {
                method: 'GET',
                credentials: 'same-origin'
            }).then(() => {
                setupCSRF();
                window.location.reload();
            }).catch(() => {
                window.location.reload();
            });
        }
        return Promise.reject(error);
    }
);

document.addEventListener('DOMContentLoaded', function() {

    // Only setup Echo if user is authenticated
    if (window.Laravel && window.Laravel.user && window.Laravel.user.id) {
        const token = document.head.querySelector('meta[name="csrf-token"]') || 
                      (window.Laravel && window.Laravel.csrfToken ? { content: window.Laravel.csrfToken } : null);
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: window.Laravel.pusher.key,
            cluster: window.Laravel.pusher.cluster,
            forceTLS: true,
            encrypted: true,
            disableStats: true,
            auth: {
                headers: {
                    'X-CSRF-TOKEN': token.content,
                    'X-Requested-With': 'XMLHttpRequest',
                }
            }
        });

        // Add connection state monitoring
        if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
            window.Echo.connector.pusher.connection.bind('connected', () => {
                console.log('✅ Pusher connected successfully - Live mode active');
            });

            window.Echo.connector.pusher.connection.bind('disconnected', () => {
                console.log('⚠️ Pusher disconnected - Switching to polling');
            });

            window.Echo.connector.pusher.connection.bind('failed', () => {
                console.log('❌ Pusher connection failed - Using polling fallback');
                // Disable WebSocket connections on failure
                window.Echo = null;
            });

            window.Echo.connector.pusher.connection.bind('error', (error) => {
                console.log('❌ Pusher connection error:', error);
            });
        }
    } else if (window.Laravel && window.Laravel.pusher) {
        // Setup Echo for guest users (public channels only)
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: window.Laravel.pusher.key,
            cluster: window.Laravel.pusher.cluster,
            forceTLS: true,
            encrypted: true,
            disableStats: true
        });
        
        console.log('✅ Echo initialized for guest users');
    }
});
