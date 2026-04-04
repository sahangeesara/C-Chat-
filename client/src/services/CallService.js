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

        this.callHistoryStorageKey = 'chatapp.callHistory'
    }

    readLocalCallHistory() {
        try {
            const raw = localStorage.getItem(this.callHistoryStorageKey)
            if (!raw) {
                return []
            }

            const parsed = JSON.parse(raw)
            return Array.isArray(parsed) ? parsed : []
        } catch {
            return []
        }
    }

    writeLocalCallHistory(items) {
        localStorage.setItem(this.callHistoryStorageKey, JSON.stringify(items))
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

    async saveCallHistory(entry) {
        const payload = {
            user_id: entry?.user_id ?? null,
            direction: entry?.direction || 'outgoing',
            status: entry?.status || 'ended',
            started_at: entry?.started_at || new Date().toISOString(),
            ended_at: entry?.ended_at || null,
            duration_seconds: Number(entry?.duration_seconds || 0),
            meta: entry?.meta || null,
        }

        try {
            const response = await this.http.post('/call/history', payload)
            return response.data
        } catch {
            const localItems = this.readLocalCallHistory()
            localItems.unshift({
                id: `local-${Date.now()}-${Math.random().toString(36).slice(2)}`,
                ...payload,
            })
            this.writeLocalCallHistory(localItems.slice(0, 200))
            return payload
        }
    }

    async getCallHistory(userId = null) {
        try {
            const response = await this.http.get('/call/history', {
                params: userId ? { user_id: userId } : {},
            })

            const items = response?.data?.history || response?.data?.data || response?.data || []
            return Array.isArray(items) ? items : []
        } catch {
            const localItems = this.readLocalCallHistory()
            if (!userId) {
                return localItems
            }

            return localItems.filter((item) => Number(item.user_id) === Number(userId))
        }
    }
}

export default new CallService()
