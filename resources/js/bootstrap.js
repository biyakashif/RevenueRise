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
    forceTLS: window.Laravel.pusher.forceTLS,
    encrypted: window.Laravel.pusher.encrypted,
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
    }
});
