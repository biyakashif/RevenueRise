import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Add CSRF token to all requests
function setupCSRF() {
    const token = document.head.querySelector('meta[name="csrf-token"]')?.content;
    
    if (token) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        window.axios.defaults.withCredentials = true;
    }
}

// Setup CSRF immediately
setupCSRF();



// Handle 419 CSRF token mismatch
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 419) {
            window.location.reload();
        }
        return Promise.reject(error);
    }
);

// Initialize Echo immediately when Laravel config is available
function initializeEcho() {
    if (window.Laravel && window.Laravel.pusher && !window.Echo) {
        const token = document.head.querySelector('meta[name="csrf-token"]');
        
        const echoConfig = {
            broadcaster: 'pusher',
            key: window.Laravel.pusher.key,
            cluster: window.Laravel.pusher.cluster,
            forceTLS: true,
            encrypted: true,
            disableStats: true,
        };
        
        // Add auth headers for authenticated users
        if (window.Laravel.user && token) {
            echoConfig.auth = {
                headers: {
                    'X-CSRF-TOKEN': token.content,
                    'X-Requested-With': 'XMLHttpRequest',
                }
            };
        }
        
        try {
            window.Echo = new Echo(echoConfig);
            
            // Add connection state monitoring
            if (window.Echo.connector && window.Echo.connector.pusher) {
                window.Echo.connector.pusher.connection.bind('connected', () => {
                    console.log('✅ Pusher connected - Real-time chat active');
                });

                window.Echo.connector.pusher.connection.bind('disconnected', () => {
                    console.log('⚠️ Pusher disconnected - Using polling fallback');
                });

                window.Echo.connector.pusher.connection.bind('failed', () => {
                    console.log('❌ Pusher failed - Chat will use polling only');
                });
            }
            
            console.log('✅ Echo initialized successfully');
        } catch (error) {
            console.error('❌ Echo initialization failed:', error);
            window.Echo = null;
        }
    }
}

// Try to initialize immediately
initializeEcho();

// Also try on DOM ready as fallback
document.addEventListener('DOMContentLoaded', initializeEcho);
