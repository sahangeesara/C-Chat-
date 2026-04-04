import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import store from '@/store'

window.Pusher = Pusher

const wsPort = Number(process.env.VUE_APP_WEBSOCKETS_PORT || 6001)
const apiBaseUrl = process.env.VUE_APP_API_BASE_URL || 'http://localhost:8000'
const isSecurePage = window.location.protocol === 'https:'

export default new Echo({
    broadcaster: 'pusher',

    key: 'd48f36eb19647382a1d0',
    cluster: 'ap2',        // Match the existing backend / Pusher cluster

    wsHost: process.env.VUE_APP_WEBSOCKETS_HOST || window.location.hostname || '127.0.0.1',
    wsPort,
    wssPort: wsPort,

    forceTLS: isSecurePage,
    encrypted: isSecurePage,

    disableStats: true,
    enabledTransports: ['ws', 'wss'],

    authorizer: (channel) => ({
        authorize: (socketId, callback) => {
            const token = store.getters['auth/getToken'] || store.getters.getToken

            if (!token) {
                callback(new Error('Missing auth token for private channel subscription'))
                return
            }

            fetch(`${apiBaseUrl}/api/broadcasting/auth`, {
                method: 'POST',
                headers: {
                    Authorization: `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                body: JSON.stringify({
                    socket_id: socketId,
                    channel_name: channel.name
                })
            })
                .then(async (res) => {
                    const data = await res.json()

                    if (!res.ok) {
                        throw new Error(data?.message || `Broadcast auth failed with status ${res.status}`)
                    }

                    callback(null, data)
                })
                .catch((err) => {
                    console.error('Echo authorizer failed:', err)
                    callback(err)
                })
        }
    })
})
