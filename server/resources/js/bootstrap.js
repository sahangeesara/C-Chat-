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

const getAccessToken = () =>
    localStorage.getItem('access_token') ||
    localStorage.getItem('token') ||
    null;

const apiBaseUrl =
    import.meta.env.VITE_API_BASE_URL ||
    import.meta.env.VUE_APP_API_BASE_URL ||
    `${window.location.protocol}//${window.location.hostname}:8001`;

const normalizedApiBase = apiBaseUrl.replace(/\/$/, '');
const scheme = (import.meta.env.VITE_PUSHER_SCHEME || window.location.protocol.replace(':', '') || 'http').toLowerCase();
const host = import.meta.env.VITE_PUSHER_HOST || window.location.hostname;
const port = Number(import.meta.env.VITE_PUSHER_PORT || 8001);
const isSecure = scheme === 'https';

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY || import.meta.env.VUE_APP_PUSHER_APP_KEY || null,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || undefined,
    wsHost: host,
    wsPort: port,
    wssPort: port,
    forceTLS: isSecure,
    enabledTransports: isSecure ? ['wss'] : ['ws'],
    disableStats: true,
    authEndpoint: `${normalizedApiBase}/api/broadcasting/auth`,
    auth: {
        headers: {},
    },
    withCredentials: false,
    authorizer: (channel, options) => ({
        authorize: (socketId, callback) => {
            const token = getAccessToken();

            if (!token) {
                callback(true, { message: 'Missing access token for channel authorization.' });
                return;
            }

            axios
                .post(
                    `${normalizedApiBase}/api/broadcasting/auth`,
                    {
                        socket_id: socketId,
                        channel_name: channel.name,
                    },
                    {
                        headers: {
                            Authorization: `Bearer ${token}`,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    }
                )
                .then((response) => callback(false, response.data))
                .catch((error) => callback(true, error));
        },
    }),
});
