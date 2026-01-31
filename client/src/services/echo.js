import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import store from '@/store'

window.Pusher = Pusher

export default new Echo({
    broadcaster: 'pusher',

    key: 'd48f36eb19647382a1d0',
    cluster: 'mt1',        // REQUIRED by pusher-js

    wsHost: '127.0.0.1',
    wsPort: 6001,
    wssPort: 6001,
   
    forceTLS: true,     
    encrypted: true,   

    disableStats: true,
    enabledTransports: ['wss', 'ws'],

    authorizer: (channel) => ({
        authorize: (socketId, callback) => {
            fetch('http://127.0.0.1:8000/api/broadcasting/auth', {
                method: 'POST',
                headers: {
                    Authorization: `Bearer ${store.getters.getToken}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'   // 🔥 IMPORTANT FIX
                },
                body: JSON.stringify({
                    socket_id: socketId,
                    channel_name: channel.name
                })
            })
                .then(res => res.json())
                .then(data => callback(null, data))
                .catch(err => callback(err))
        }
    })
})
