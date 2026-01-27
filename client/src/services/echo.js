import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { getToken } from '@/services/authsev' // wherever your token helper is

window.Pusher = Pusher

const echo = new Echo({
    broadcaster: 'pusher',

    key: process.env.VUE_APP_PUSHER_APP_KEY,
    cluster: process.env.VUE_APP_PUSHER_APP_CLUSTER,

    wsHost: process.env.VUE_APP_PUSHER_HOST,
    wsPort: process.env.VUE_APP_PUSHER_PORT,

    forceTLS: false,
    encrypted: false,

    disableStats: true,
    enabledTransports: ['ws', 'wss'],

    authEndpoint: 'http://127.0.0.1:8000/api/broadcasting/auth',

    auth: {
        headers: {
            Authorization: `Bearer ${getToken()}`
        }
    }
})

export default echo
