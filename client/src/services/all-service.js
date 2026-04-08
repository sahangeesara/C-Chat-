import axios from 'axios';
import store from '@/store';
import { getApiBaseUrl } from '@/services/api-origin';

const API_BASE_URL = getApiBaseUrl();

export default class AllServiceService {
    constructor() {
        this.http = axios.create({
            baseURL: API_BASE_URL,
            headers: {
                'Content-Type': 'application/json',
            },
        });

        this.http.interceptors.request.use((config) => {
            const requestUrl = String(config?.url || '');
            if (requestUrl.includes('broadcasting/auth')) {
                return config;
            }

            // Let the browser set multipart boundaries for FormData uploads.
            if (typeof FormData !== 'undefined' && config.data instanceof FormData) {
                delete config.headers['Content-Type'];
                delete config.headers['content-type'];
            }

            const token =
                store.getters['auth/getToken'] ||
                store.getters['tokens/getToken'] ||
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
                if (!error?.response) {
                    console.error('Network error while calling API:', {
                        method: (error?.config?.method || 'get').toUpperCase(),
                        url: error?.config?.baseURL
                            ? `${String(error.config.baseURL).replace(/\/$/, '')}/${String(error?.config?.url || '').replace(/^\//, '')}`
                            : error?.config?.url,
                        online: typeof navigator !== 'undefined' ? navigator.onLine : true,
                        message: error?.message || 'Network Error',
                    });
                }

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

    isNetworkError(error) {
        // Axios network errors usually have no response object.
        return Boolean(error && !error.response);
    }

    getAuthToken() {
        return (
            store.getters['auth/getToken'] ||
            store.getters['tokens/getToken'] ||
            store.getters.getToken ||
            localStorage.getItem('token') ||
            null
        );
    }

    async postFormDataWithFetch(endpoint, formData) {
        const token = this.getAuthToken();
        const baseUrl = (this.http?.defaults?.baseURL || '').replace(/\/$/, '');
        const normalizedEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
        const url = `${baseUrl}${normalizedEndpoint}`;

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
                Accept: 'application/json',
            },
            body: this.cloneFormData(formData),
        });

        const text = await response.text();
        let parsed;
        try {
            parsed = text ? JSON.parse(text) : {};
        } catch {
            parsed = { message: text || 'Unexpected response format' };
        }

        if (!response.ok) {
            const error = new Error(parsed?.message || `Request failed with status ${response.status}`);
            error.response = { status: response.status, data: parsed };
            throw error;
        }

        return parsed;
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

    buildMessageFormData(value, { includeGroup = false } = {}) {
        if (typeof FormData !== 'undefined' && value instanceof FormData) {
            return this.normalizeSendFormData(value, { includeGroup });
        }

        const formData = new FormData();
        const toId = value?.user_id ?? value?.to_id ?? value?.receiver_id ?? null;
        const groupId = value?.group_id ?? null;
        const text = (value?.message ?? value?.body ?? value?.text ?? '').toString();

        if (toId !== null && toId !== undefined && toId !== '') {
            formData.append('user_id', String(toId));
            formData.append('to_id', String(toId));
            formData.append('receiver_id', String(toId));
        }

        if (includeGroup && groupId !== null && groupId !== undefined && groupId !== '') {
            formData.append('group_id', String(groupId));
            formData.append('conversation_type', 'group');
        }

        formData.append('message', text);
        formData.append('body', text);

        if (value?.group_name) {
            formData.append('group_name', String(value.group_name));
        }

        const attachmentFile = value?.attachment || value?.file || value?.media || value?.document || null;
        if (attachmentFile) {
            formData.append('attachment', attachmentFile);
            formData.append('file', attachmentFile);
            formData.append('media', attachmentFile);
            formData.append('document', attachmentFile);
        }

        ['attachment_name', 'attachment_mime_type', 'attachment_kind', 'attachment_size'].forEach((key) => {
            if (value?.[key] !== undefined && value?.[key] !== null && value?.[key] !== '') {
                formData.append(key, String(value[key]));
            }
        });

        return formData;
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
        const payload = this.buildMessageFormData(value, { includeGroup: false });
        const endpoints = ['/send', '/messages', '/chat/send'];
        let lastError = null;

        for (const endpoint of endpoints) {
            try {
                const response = await this.http.post(endpoint, this.cloneFormData(payload));
                return response.data;
            } catch (error) {
                lastError = error;

                if (this.isNetworkError(error)) {
                    try {
                        return await this.postFormDataWithFetch(endpoint, payload);
                    } catch (fetchError) {
                        lastError = fetchError;
                    }
                }

                const status = lastError?.response?.status;
                if (status !== 404 && status !== 405 && status !== 422) {
                    break;
                }
            }
        }

        console.error('Error sending message:', lastError?.response?.data || lastError?.message);
        throw lastError;
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
        let seedPayload = payload || {};
        if (typeof FormData !== 'undefined' && payload instanceof FormData) {
            seedPayload = this.cloneFormData(payload);
            this.appendIfMissing(seedPayload, 'group_id', String(groupId));
            this.appendIfMissing(seedPayload, 'conversation_type', 'group');
        } else {
            seedPayload = {
                ...seedPayload,
                group_id: groupId,
                conversation_type: seedPayload?.conversation_type || 'group',
            };
        }

        const payloadWithGroup = this.buildMessageFormData(seedPayload, { includeGroup: true });
        const endpoints = [
            `/groups/${groupId}/messages`,
            `/group/${groupId}/messages`,
            '/send',
            '/messages',
        ];

        let lastError = null;

        for (const endpoint of endpoints) {
            try {
                const response = await this.http.post(endpoint, this.cloneFormData(payloadWithGroup));
                return response.data;
            } catch (error) {
                lastError = error;

                if (this.isNetworkError(error)) {
                    try {
                        return await this.postFormDataWithFetch(endpoint, payloadWithGroup);
                    } catch (fetchError) {
                        lastError = fetchError;
                    }
                }

                const status = lastError?.response?.status;
                if (status !== 404 && status !== 405 && status !== 422) {
                    break;
                }
            }
        }

        console.error('Error sending group message:', {
            groupId,
            detail: lastError?.response?.data || lastError?.message,
        });
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
        const endpoints = [`/groups/${groupId}`, `/group/${groupId}`];
        const isForm = typeof FormData !== 'undefined' && payload instanceof FormData;
        let lastError = null;

        for (const endpoint of endpoints) {
            try {
                if (isForm) {
                    const formPayload = this.cloneFormData(payload);
                    this.appendIfMissing(formPayload, '_method', 'PUT');

                    const imageFile = formPayload.get('image') || formPayload.get('group_image') || formPayload.get('avatar');
                    if (imageFile) {
                        this.appendIfMissing(formPayload, 'image', imageFile);
                        this.appendIfMissing(formPayload, 'group_image', imageFile);
                        this.appendIfMissing(formPayload, 'avatar', imageFile);
                    }

                    const response = await this.http.post(endpoint, formPayload);
                    return response.data;
                }

                const response = await this.http.put(endpoint, payload);
                return response.data;
            } catch (error) {
                lastError = error;
                const status = error?.response?.status;
                if (status !== 404 && status !== 405) {
                    break;
                }
            }
        }

        console.error('Error updating group:', lastError?.response?.data || lastError?.message);
        throw lastError;
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
