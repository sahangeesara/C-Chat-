import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import store from '@/store'
import { getApiOrigin } from '@/services/api-origin'

window.Pusher = Pusher

const wsPort = Number(process.env.VUE_APP_WEBSOCKETS_PORT || 6001)
const apiBaseUrl = getApiOrigin()
const wsScheme = (process.env.VUE_APP_WEBSOCKETS_SCHEME || 'http').toLowerCase()
const useTls = wsScheme === 'https' || wsScheme === 'wss'
const isHttpsPage = window.location.protocol === 'https:'
const isLoopbackHost = (host = '') => {
    const normalized = String(host || '').toLowerCase()
    return normalized === 'localhost' || normalized === '127.0.0.1'
}
const apiHostname = (() => {
    try {
        return new URL(apiBaseUrl).hostname
    } catch {
        return window.location.hostname || '127.0.0.1'
    }
})()
const resolvedWsHost = (() => {
    const envHost = process.env.VUE_APP_WEBSOCKETS_HOST || ''
    if (envHost) {
        return envHost
    }

    const pageHost = window.location.hostname || '127.0.0.1'
    if (isLoopbackHost(apiHostname) && !isLoopbackHost(pageHost)) {
        // Remote/tunneled frontend cannot reach loopback websocket host.
        return pageHost
    }

    return apiHostname || pageHost || '127.0.0.1'
})()

const createNoopChannel = () => ({
    listen() {
        return this
    },
    error() {
        return this
    }
})

let echoClient

// Browsers block insecure ws:// from HTTPS pages.
// If the app is opened over HTTPS while websocket scheme is http, avoid infinite retry spam.
if (isHttpsPage && !useTls) {
    console.warn(
        'Realtime websocket disabled: page is HTTPS but websocket scheme is HTTP. Open the app on http://127.0.0.1:8080 or configure WSS.'
    )

    echoClient = {
        private() {
            return createNoopChannel()
        },
        channel() {
            return createNoopChannel()
        },
        leave() {
            // no-op
        }
    }
} else {
    echoClient = new Echo({
        broadcaster: 'pusher',

        key: 'd48f36eb19647382a1d0',
        cluster: 'ap2',        // Match the existing backend / Pusher cluster

        wsHost: resolvedWsHost,
        wsPort,
        wssPort: wsPort,

        forceTLS: useTls,
        encrypted: useTls,

        disableStats: true,
        enabledTransports: useTls ? ['wss'] : ['ws'],

        authorizer: (channel) => ({
            authorize: (socketId, callback) => {
                const token =
                    store.getters['auth/getToken'] ||
                    store.getters['tokens/getToken'] ||
                    store.getters.getToken ||
                    localStorage.getItem('token')

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
}

export default echoClient
