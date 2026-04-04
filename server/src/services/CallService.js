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

    // 📞 Start call (send offer) - returns { status, call_id, call }
    startCall(toId, offer) {
        return this.http.post('/call/start', {
            to_id: toId,
            offer: offer,
        })
    }

    // ✅ Answer call - requires call_id from backend
    answerCall(callId, sdp) {
        return this.http.post('/call/answer', {
            call_id: callId,
            sdp: sdp,
        })
    }

    // ❄ Send ICE candidate
    sendIce(toId, candidate, callId = null) {
        return this.http.post('/call/ice', {
            to_id: toId,
            candidate: candidate,
            call_id: callId,
        })
    }

    // ❌ End call - requires call_id
    endCall(callId, status = 'ended', reason = null) {
        return this.http.post('/call/end', {
            call_id: callId,
            status: status,
            reason: reason,
        })
    }

    // 📋 Get call history with optional filtering
    async getCallHistory(userId = null, perPage = 20) {
        try {
            const params = { per_page: perPage }
            if (userId) {
                params.user_id = userId
            }

            const response = await this.http.get('/call/history', { params })

            // Backend returns paginated response { data: [...], links: {...}, meta: {...} }
            const items = response?.data?.data || response?.data || []
            return Array.isArray(items) ? items : []
        } catch (error) {
            console.error('Failed to fetch call history from backend:', error)
            // Fallback to local storage
            const localItems = this.readLocalCallHistory()
            if (!userId) {
                return localItems
            }

            return localItems.filter((item) => Number(item.user_id) === Number(userId))
        }
    }

    // 📞 Get single call detail
    async getCallDetail(callId) {
        try {
            const response = await this.http.get(`/call/${callId}`)
            return response.data
        } catch (error) {
            console.error(`Failed to fetch call ${callId}:`, error)
            return null
        }
    }

    // 💾 Save call entry to local storage (fallback only, backend handles persistence)
    saveCallLocally(entry) {
        const payload = {
            id: entry?.id || `local-${Date.now()}-${Math.random().toString(36).slice(2)}`,
            caller_id: entry?.caller_id ?? null,
            callee_id: entry?.callee_id ?? null,
            direction: entry?.direction || 'outgoing',
            status: entry?.status || 'ended',
            started_at: entry?.started_at || new Date().toISOString(),
            answered_at: entry?.answered_at || null,
            ended_at: entry?.ended_at || null,
            duration_seconds: Number(entry?.duration_seconds || 0),
            end_reason: entry?.end_reason || null,
        }

        const localItems = this.readLocalCallHistory()
        localItems.unshift(payload)
        this.writeLocalCallHistory(localItems.slice(0, 200))
        return payload
    }
}

export default new CallService()

