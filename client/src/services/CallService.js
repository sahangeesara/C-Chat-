import axios from 'axios'
import store from '@/store'

const API_BASE_URL = 'http://localhost:8000/api'

class CallService {
    constructor() {
        this.http = axios.create({
            baseURL: API_BASE_URL,
            headers: {
                'Content-Type': 'application/json',
            },
        })

        // 🔐 Attach JWT token
        this.http.interceptors.request.use(config => {
            if (!config.url.includes('broadcasting/auth')) {
                const token = store.getters['auth/getToken']
                if (token) {
                    config.headers.Authorization = `Bearer ${token}`
                }
            }
            return config
        })
    }

    // 📞 Start call (send offer)
    startCall(toId, offer) {
        return this.http.post('/call/start', {
            to_id: toId,
            offer: offer,
        })
    }

    // ✅ Answer call
    answerCall(toId, answer) {
        return this.http.post('/call/answer', {
            to_id: toId,
            sdp: answer,
        })
    }

    // ❄ Send ICE candidate
    sendIce(toId, candidate) {
        return this.http.post('/call/ice', {
            to_id: toId,
            candidate: candidate,
        })
    }

    // ❌ End call (optional)
    endCall(toId) {
        return this.http.post('/call/end', {
            to_id: toId,
        })
    }
}

export default new CallService()
