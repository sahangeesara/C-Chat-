import axios from 'axios';
import store from '@/store';

const API_BASE_URL = process.env.VUE_APP_API_BASE_URL
    ? `${process.env.VUE_APP_API_BASE_URL.replace(/\/$/, '')}/api`
    : 'http://127.0.0.1:8000/api';

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

            // Let the browser set multipart boundaries for FormData uploads.
            if (typeof FormData !== 'undefined' && config.data instanceof FormData) {
                delete config.headers['Content-Type'];
                delete config.headers['content-type'];
            }

            const token =
                store.getters['auth/getToken'] ||
                store.getters.getToken ||
                localStorage.getItem('token');

            if (token) {
                config.headers.Authorization = `Bearer ${token}`;
            }

            return config;
        });

        this.http.interceptors.response.use(
            (response) => response,
            (error) => {
                if (error?.response?.status === 401) {
                    store.dispatch('auth/logout');
                    if (window.location.hash !== '#/') {
                        window.location.hash = '#/';
                    }
                }

                return Promise.reject(error);
            }
        );
    }

    cloneFormData(source) {
        const cloned = new FormData();
        source.forEach((value, key) => {
            cloned.append(key, value);
        });
        return cloned;
    }

    appendIfMissing(formData, key, value) {
        if (value === undefined || value === null || value === '') {
            return;
        }
        if (!formData.has(key)) {
            formData.append(key, value);
        }
    }

    normalizeSendFormData(source, { includeGroup = false } = {}) {
        const normalized = this.cloneFormData(source);

        const recipientId = source.get('user_id') || source.get('to_id') || source.get('receiver_id');
        const groupId = source.get('group_id');
        const body = source.get('message') || source.get('body') || source.get('text') || '';
        const attachmentFile = source.get('attachment') || source.get('file') || source.get('media') || source.get('document');

        if (recipientId) {
            this.appendIfMissing(normalized, 'user_id', recipientId);
            this.appendIfMissing(normalized, 'to_id', recipientId);
            this.appendIfMissing(normalized, 'receiver_id', recipientId);
        }

        if (includeGroup && groupId) {
            this.appendIfMissing(normalized, 'group_id', groupId);
            this.appendIfMissing(normalized, 'conversation_type', 'group');
        }

        if (body !== null && body !== undefined) {
            this.appendIfMissing(normalized, 'message', body);
            this.appendIfMissing(normalized, 'body', body);
        }

        if (attachmentFile) {
            this.appendIfMissing(normalized, 'attachment', attachmentFile);
            this.appendIfMissing(normalized, 'file', attachmentFile);
            this.appendIfMissing(normalized, 'media', attachmentFile);
            this.appendIfMissing(normalized, 'document', attachmentFile);
        }

        return normalized;
    }

    async searchUser(query, page = 1) {
        const rawQuery = (query ?? '').toString().trim();


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
                    // Use searchable list endpoint as default.
                    url: '/searchUser',
                    params: { page },
                },
                {
                    // Keep legacy fallback.
                    url: '/getUser',
                    params: { page },
                },
            ];

        let lastError = null;

        const normalizeUserListResponse = (payload) => {
            const users = Array.isArray(payload)
                ? payload
                : (
                    Array.isArray(payload?.data)
                        ? payload.data
                        : (
                            Array.isArray(payload?.users)
                                ? payload.users
                                : (
                                    Array.isArray(payload?.results)
                                        ? payload.results
                                        : (
                                            Array.isArray(payload?.data?.data)
                                                ? payload.data.data
                                                : []
                                        )
                                )
                        )
                );

            return {
                ...((payload && typeof payload === 'object' && !Array.isArray(payload)) ? payload : {}),
                users,
                data: users,
            };
        };

        for (const attempt of endpointAttempts) {
            try {
                const response = await this.http.get(attempt.url, { params: attempt.params });

                // /api/getUser may return auth user object, not list payload.
                if (
                    attempt.url === '/getUser' &&
                    response?.data &&
                    !Array.isArray(response.data) &&
                    !Array.isArray(response?.data?.data) &&
                    !Array.isArray(response?.data?.users) &&
                    !Array.isArray(response?.data?.results)
                ) {
                    continue;
                }

                return normalizeUserListResponse(response.data);
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
            return { users: [], data: [] };
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
        if (typeof FormData !== 'undefined' && value instanceof FormData) {
            const payload = this.normalizeSendFormData(value, { includeGroup: false });
            const payloadVariants = [
                payload,
                (() => {
                    const variant = this.cloneFormData(payload);
                    this.appendIfMissing(variant, '_method', 'POST');
                    return variant;
                })(),
            ];
            const endpoints = ['/send', '/messages', '/chat/send'];

            let lastError = null;

            for (const endpoint of endpoints) {
                for (const variant of payloadVariants) {
                    try {
                        const response = await this.http.post(endpoint, this.cloneFormData(variant));
                        return response.data;
                    } catch (error) {
                        lastError = error;
                        const status = error?.response?.status;
                        if (status !== 404 && status !== 405 && status !== 422) {
                            break;
                        }
                    }
                }

                if (lastError?.response?.status !== 404 && lastError?.response?.status !== 405) {
                    break;
                }
            }

            console.error('Error sending message:', lastError?.response?.data || lastError?.message);
            throw lastError;
        }

        const toId = value?.user_id ?? value?.to_id ?? value?.receiver_id ?? null;
        const text = (value?.message ?? value?.body ?? value?.text ?? '').toString().trim();
        const passthroughFields = {};
        [
            'group_id',
            'group_name',
            'conversation_type',
            'attachment_url',
            'attachment_name',
            'attachment_mime_type',
            'attachment_kind',
            'attachment_size',
        ].forEach((key) => {
            if (value?.[key] !== undefined && value?.[key] !== null && value?.[key] !== '') {
                passthroughFields[key] = value[key];
            }
        });

        const payloadAttempts = [];

        if (value && typeof value === 'object') {
            payloadAttempts.push(value);
        }

        if (toId && text) {
            payloadAttempts.push({ ...passthroughFields, user_id: toId, message: text });
            payloadAttempts.push({ ...passthroughFields, to_id: toId, message: text });
            payloadAttempts.push({ ...passthroughFields, user_id: toId, body: text });
            payloadAttempts.push({ ...passthroughFields, to_id: toId, body: text });
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

    async updateProfile(formData) {
        const requestAttempts = [
            { endpoint: '/user/update-profile', mutate: null },
            { endpoint: '/profile/update', mutate: null },
            { endpoint: '/user/profile', mutate: null },
            {
                endpoint: '/user',
                mutate: (payload) => {
                    this.appendIfMissing(payload, '_method', 'PUT');
                    return payload;
                },
            },
        ];

        let lastError = null;

        for (const attempt of requestAttempts) {
            try {
                const payload = this.cloneFormData(formData);

                // Support multiple backend field names for the profile photo.
                const imageFile = payload.get('image') || payload.get('profile_photo') || payload.get('avatar');
                if (imageFile) {
                    this.appendIfMissing(payload, 'image', imageFile);
                    this.appendIfMissing(payload, 'profile_photo', imageFile);
                    this.appendIfMissing(payload, 'avatar', imageFile);
                }

                const finalPayload = attempt.mutate ? attempt.mutate(payload) : payload;
                const response = await this.http.post(attempt.endpoint, finalPayload);
                return response.data;
            } catch (error) {
                lastError = error;
                const status = error?.response?.status;
                if (status !== 404 && status !== 405) {
                    break;
                }
            }
        }

        console.error('Error updating profile:', lastError?.response?.data || lastError?.message);
        throw lastError;
    }

    async getGroups() {
        try {
            const response = await this.http.get('/groups');
            return response.data;
        } catch (error) {
            console.error('Error fetching groups:', error.response?.data || error.message);
            throw error;
        }
    }

    async getGroup(groupId) {
        try {
            const response = await this.http.get(`/groups/${groupId}`);
            return response.data;
        } catch (error) {
            console.error('Error fetching group details:', error.response?.data || error.message);
            throw error;
        }
    }

    async getGroupMessages(groupId) {
        const endpoints = [
            `/groups/${groupId}/messages`,
            `/group/${groupId}/messages`,
            `/group/messages/${groupId}`,
            `/chat/group/${groupId}`,
        ];

        let lastError = null;

        for (const endpoint of endpoints) {
            try {
                const response = await this.http.get(endpoint);
                return response.data;
            } catch (error) {
                lastError = error;
                if (error?.response?.status !== 404) {
                    break;
                }
            }
        }

        if (lastError?.response?.status === 404) {
            return { messages: [] };
        }

        console.error('Error fetching group messages:', lastError?.response?.data || lastError?.message);
        throw lastError;
    }

    async sendGroupMessage(groupId, payload) {
        const normalizedPayload = typeof FormData !== 'undefined' && payload instanceof FormData
            ? this.normalizeSendFormData(payload, { includeGroup: true })
            : payload;

        const attempts = [
            {
                endpoint: `/groups/${groupId}/messages`,
                payload: normalizedPayload,
            },
            {
                endpoint: `/group/${groupId}/messages`,
                payload: normalizedPayload,
            },
            {
                endpoint: '/send',
                payload: normalizedPayload,
            },
            {
                endpoint: '/messages',
                payload: normalizedPayload,
            },
        ];

        let lastError = null;

        for (const attempt of attempts) {
            try {
                const attemptPayload = (typeof FormData !== 'undefined' && attempt.payload instanceof FormData)
                    ? this.cloneFormData(attempt.payload)
                    : attempt.payload;
                const response = await this.http.post(attempt.endpoint, attemptPayload);
                return response.data;
            } catch (error) {
                lastError = error;
                const status = error?.response?.status;
                if (status !== 404 && status !== 405 && status !== 422) {
                    break;
                }
            }
        }

        console.error('Error sending group message:', lastError?.response?.data || lastError?.message);
        throw lastError;
    }

    async createGroup(payload) {
        const name = (payload?.name || '').toString().trim();
        const userIds = Array.isArray(payload?.user_ids)
            ? payload.user_ids
            : (Array.isArray(payload?.member_ids)
                ? payload.member_ids
                : (Array.isArray(payload?.members) ? payload.members : []));

        const attempts = [
            payload,
            { name, user_ids: userIds },
            { name, member_ids: userIds },
            { name, members: userIds },
            { name, users: userIds },
        ];
        const endpoints = ['/groups', '/group'];

        let lastError = null;

        for (const endpoint of endpoints) {
            for (const attempt of attempts) {
                try {
                    const response = await this.http.post(endpoint, attempt);
                    return response.data;
                } catch (error) {
                    lastError = error;
                    const status = error?.response?.status;
                    const shouldTryNextPayload = status === 422;
                    const shouldTryNextEndpoint = status === 404;

                    if (!shouldTryNextPayload && !shouldTryNextEndpoint) {
                        break;
                    }
                }
            }

            if (lastError?.response?.status !== 404) {
                break;
            }
        }

        console.error('Error creating group:', lastError?.response?.data || lastError?.message);
        throw lastError;
    }

    async updateGroup(groupId, payload) {
        try {
            const response = await this.http.put(`/groups/${groupId}`, payload);
            return response.data;
        } catch (error) {
            console.error('Error updating group:', error.response?.data || error.message);
            throw error;
        }
    }

    async addGroupMembers(groupId, userIds) {
        const ids = Array.isArray(userIds) ? userIds : [];
        const attempts = [
            { user_ids: ids },
            { member_ids: ids },
            { members: ids },
            { users: ids },
        ];

        let lastError = null;

        for (const attempt of attempts) {
            try {
                const response = await this.http.post(`/groups/${groupId}/members`, attempt);
                return response.data;
            } catch (error) {
                lastError = error;
                if (error?.response?.status !== 422) {
                    break;
                }
            }
        }

        console.error('Error adding group members:', lastError?.response?.data || lastError?.message);
        throw lastError;
    }

    async updateGroupMemberRole(groupId, userId, role) {
        try {
            const response = await this.http.patch(`/groups/${groupId}/members/${userId}/role`, { role });
            return response.data;
        } catch (error) {
            console.error('Error updating group role:', error.response?.data || error.message);
            throw error;
        }
    }

    async removeGroupMember(groupId, userId) {
        try {
            const response = await this.http.delete(`/groups/${groupId}/members/${userId}`);
            return response.data;
        } catch (error) {
            console.error('Error removing group member:', error.response?.data || error.message);
            throw error;
        }
    }

    async leaveGroup(groupId) {
        try {
            const response = await this.http.post(`/groups/${groupId}/leave`);
            return response.data;
        } catch (error) {
            console.error('Error leaving group:', error.response?.data || error.message);
            throw error;
        }
    }

    async deleteGroup(groupId) {
        try {
            const response = await this.http.delete(`/groups/${groupId}`);
            return response.data;
        } catch (error) {
            console.error('Error deleting group:', error.response?.data || error.message);
            throw error;
        }
    }
}
