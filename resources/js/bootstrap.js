import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Add CSRF token to all requests
const token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
    window.axios.defaults.withCredentials = true;
} else {
    console.error('CSRF token not found');
}

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: window.Laravel.pusher.key,
    cluster: window.Laravel.pusher.cluster,
    forceTLS: true,
    encrypted: true,
    wsHost: window.Laravel.pusher.wsHost,
    wsPort: window.Laravel.pusher.wsPort,
    wssPort: window.Laravel.pusher.wssPort,
    enabledTransports: window.Laravel.pusher.enabledTransports,
    disableStats: true,
    auth: {
        withCredentials: true,
        headers: {
            'X-CSRF-TOKEN': token.content,
            'X-Requested-With': 'XMLHttpRequest',
        }
    },
    authorizer: (channel, options) => {
        return {
            authorize: (socketId, callback) => {
                axios.post('/broadcasting/auth', {
                    socket_id: socketId,
                    channel_name: channel.name
                }, {
                    headers: {
                        'X-CSRF-TOKEN': token.content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    withCredentials: true
                })
                .then(response => {
                    callback(false, response.data);
                })
                .catch(error => {
                    callback(true, error);
                });
            }
        };
    }
});

// Temporarily disable WebSocket and use polling only
// Uncomment the line below to force polling mode permanently:
// window.Echo = null;

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
