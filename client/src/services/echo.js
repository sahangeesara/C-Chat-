import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import store from '../store'

window.Pusher = Pusher

const echo = new Echo({
    broadcaster: 'pusher',
    key: 'd48f36eb19647382a1d0', // your Pusher key
    cluster: 'ap2',
    wsHost: window.location.hostname,
    wsPort: 6001,
    forceTLS: false,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],

    // ✅ Your Laravel broadcast auth route
    authEndpoint: 'http://127.0.0.1:8000/api/broadcasting/auth',

    // ✅ Always use latest token from Vuex or localStorage
    auth: {
        headers: {
            Authorization: `Bearer ${store.getters['auth/getToken'] || localStorage.getItem('token')}`,
            Accept: 'application/json',
        },
    },
})

export default echo
