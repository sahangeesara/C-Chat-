import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import store from '@/store';
import.meta.env.VITE_PUSHER_APP_KEY
import.meta.env.VITE_PUSHER_APP_CLUSTER

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    wsHost: import.meta.env.VITE_PUSHER_APP_HOST,
    wsPort: import.meta.env.VITE_PUSHER_APP_PORT,
    forceTLS: false,
    encrypted: false,
    auth: {
        headers: {
            Authorization: `Bearer ${store.getters['auth/getToken']}`,
        },
    },
});

export default echo
