import axios from 'axios'
import store from '@/store'
import { getApiBaseUrl } from '@/services/api-origin'

const API_BASE_URL = getApiBaseUrl()

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
            const requestUrl = String(config?.url || '')
            if (!requestUrl.includes('broadcasting/auth')) {
                const token =
                    store.getters['auth/getToken'] ||
                    store.getters['tokens/getToken'] ||
                    store.getters.getToken ||
                    localStorage.getItem('token')
                if (token) {
                    config.headers.Authorization = `Bearer ${token}`
                }
            }
            return config
        })

        this.http.interceptors.response.use(
            response => response,
            error => {
                if (error?.response?.status === 401) {
                    store.dispatch('logout')
                }

                return Promise.reject(error)
            }
        )

        this.callHistoryStorageKey = 'chatapp.callHistory'
    }

    async postFirstAvailable(endpoints, payload) {
        let lastError = null

        for (const endpoint of endpoints) {
            try {
                const response = await this.http.post(endpoint, payload)
                return response.data
            } catch (error) {
                lastError = error
                const status = error?.response?.status
                if (status !== 404 && status !== 405) {
                    break
                }
            }
        }

        throw lastError
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
        const normalizedOffer = typeof offer === 'string' ? { type: 'offer', sdp: offer } : (offer || {})
        return this.postFirstAvailable(['/call/start', '/calls/start'], {
            to_id: toId,
            offer: normalizedOffer,
            sdp: normalizedOffer?.sdp || '',
            sdp_offer: normalizedOffer?.sdp || '',
            type: normalizedOffer?.type || 'offer',
            call_type: normalizedOffer?.call_type || normalizedOffer?.type_hint || 'audio',
        })
    }

    // ✅ Answer call
    answerCall(toId, answer) {
        const normalizedAnswer = typeof answer === 'string' ? { type: 'answer', sdp: answer } : (answer || {})
        return this.postFirstAvailable(['/call/answer', '/calls/answer'], {
            to_id: toId,
            sdp: normalizedAnswer?.sdp || '',
            sdp_answer: normalizedAnswer?.sdp || '',
            answer: normalizedAnswer,
            type: normalizedAnswer?.type || 'answer',
            call_type: normalizedAnswer?.call_type || normalizedAnswer?.type_hint || 'audio',
        })
    }

    // ❄ Send ICE candidate
    sendIce(toId, candidate) {
        return this.postFirstAvailable(['/call/ice', '/calls/ice'], {
            to_id: toId,
            candidate: candidate,
        })
    }

    // ❌ End call (optional)
    endCall(toId) {
        return this.postFirstAvailable(['/call/end', '/calls/end'], {
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
