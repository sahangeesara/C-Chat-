import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'pusher',
    key: 'd48f36eb19647382a1d0', // Your Pusher key
    cluster: 'ap2',
    wsHost: window.location.hostname,
    wsPort: 6001,
    forceTLS: false,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: "http://127.0.0.1:8000/api/broadcasting/auth", // Ensure it's correct
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem("token")}`, // Pass API token
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || '',
        },
    },
});

export default echo;
