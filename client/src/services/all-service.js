import axios from 'axios';
import store from '@/store'; // ✅ Import Vuex store

const API_BASE_URL = 'http://localhost:8000/api';
// const API_BASE_URL = 'http://192.168.8.182:8000/api';

export default class AllServiceService {
    constructor() {
        this.http = axios.create({
            baseURL: API_BASE_URL,
            headers: {
                'Content-Type': 'application/json',
            },
        });

        this.http.interceptors.request.use((config) => {
            if (config.url.includes('broadcasting/auth')) {
                return config;
            }

            const token = store.getters['auth/getToken'];
            if (token) {
                config.headers.Authorization = `Bearer ${token}`;
            }

            return config;
        });
    }

    async searchUser(query, page = 1) {
        const rawQuery = (query ?? '').toString().trim();

        if (rawQuery.length > 0 && rawQuery.length < 2) {
            return { data: [] };
        }

        const endpointAttempts = rawQuery
            ? [
                {
                    url: '/searchUser',
                    params: {
                        page,
                        query: rawQuery,
                        name: rawQuery,
                        search: rawQuery,
                    },
                },
                {
                    url: '/getUser',
                    params: {
                        page,
                        query: rawQuery,
                        name: rawQuery,
                        search: rawQuery,
                    },
                },
            ]
            : [
                {
                    url: '/getUser',
                    params: { page },
                },
                {
                    url: '/searchUser',
                    params: { page },
                },
            ];

        let lastError = null;

        for (const attempt of endpointAttempts) {
            try {
                const response = await this.http.get(attempt.url, { params: attempt.params });
                return response.data;
            } catch (error) {
                const isNotFound = error?.response?.status === 404;
                if (!isNotFound) {
                    console.error('Error fetching user data:', error.response?.data || error.message);
                    throw error;
                }
                lastError = error;
            }
        }

        // If search routes are not registered in backend, keep UI stable without throwing.
        if (lastError?.response?.status === 404) {
            return { data: [] };
        }

        console.error('Error fetching user data:', lastError?.response?.data || lastError?.message);
        throw lastError;
    }

    async getUser() {
        try {
            const response = await this.http.get(`/me`);
            return response.data;
        } catch (error) {
            console.error('Error fetching user data:', error.response?.data || error.message);
            throw error;
        }
    }

    async getUserProfile(id) {
        try {
            const response = await this.http.get(`/user/${id}`);
            return response.data;
        } catch (error) {
            console.error('Error fetching user data:', error.response?.data || error.message);
            throw error;
        }
    }

    async getMessage(query) {
        try {
            const response = await this.http.get(`/chat/${query}`);
            return response.data;
        } catch (error) {
            console.error('Error fetching user data:', error.response?.data || error.message);
            throw error;
        }
    }

    async sendMessages(value) {
        const toId = value?.user_id ?? value?.to_id ?? value?.receiver_id ?? null;
        const text = (value?.message ?? value?.body ?? value?.text ?? '').toString().trim();

        const payloadAttempts = [];

        if (value && typeof value === 'object') {
            payloadAttempts.push(value);
        }

        if (toId && text) {
            payloadAttempts.push({ user_id: toId, message: text });
            payloadAttempts.push({ to_id: toId, message: text });
            payloadAttempts.push({ user_id: toId, body: text });
            payloadAttempts.push({ to_id: toId, body: text });
        }

        const seen = new Set();
        const uniqueAttempts = payloadAttempts.filter((payload) => {
            const key = JSON.stringify(payload);
            if (seen.has(key)) {
                return false;
            }
            seen.add(key);
            return true;
        });

        let lastError = null;

        try {
            for (const payload of uniqueAttempts) {
                try {
                    const response = await this.http.post('/send', payload);
                    return response.data;
                } catch (error) {
                    lastError = error;
                    const status = error?.response?.status;
                    // Do not retry 500 responses to avoid duplicate DB inserts.
                    const shouldRetry = status === 422;

                    if (!shouldRetry) {
                        throw error;
                    }
                }
            }

            throw lastError || new Error('Unable to send message with available payload formats.');
        } catch (error) {
            console.error('Error sending message:', error.response?.data || error.message);
            throw error;
        }
    }
}
