/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const token =
    localStorage.getItem('access_token') ||
    localStorage.getItem('token') ||
    null;

const scheme = import.meta.env.VITE_PUSHER_SCHEME || window.location.protocol.replace(':', '') || 'http';
const host = import.meta.env.VITE_PUSHER_HOST || window.location.hostname;
const port = Number(import.meta.env.VITE_PUSHER_PORT || 6001);
const isSecure = scheme === 'https';

window.Echo = new Echo({
    broadcaster: 'pusher',
    key:
        import.meta.env.VITE_PUSHER_APP_KEY ||
        import.meta.env.VUE_APP_PUSHER_APP_KEY ||
        null,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
    wsHost: host,
    wsPort: port,
    wssPort: port,
    forceTLS: isSecure,
    enabledTransports: isSecure ? ['wss'] : ['ws'],
    disableStats: true,
    authEndpoint: `${window.location.origin}/api/broadcasting/auth`,
    auth: {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
    },
    withCredentials: true,
});
