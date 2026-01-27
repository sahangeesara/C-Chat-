import axios from 'axios';
import store from '@/store'; // ✅ Import Vuex store
import { getToken } from '@/services/authsev'; // ✅ Import helper function

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

   async searchUser(query) {
    try {
        // Use the updated route: /searchUser/:name
        const response = await this.http.get(`/getUser/${query}`);
        return response.data;
    } catch (error) {
        console.error('Error fetching user data:', error.response?.data || error.message);
        throw error;
    }
}

    async getUser(){
        try {
            const response = await this.http.get(`/me`);
            return response.data;
        } catch (error) {
            console.error('Error fetching user data:', error.response?.data || error.message);
            throw error;
        }
    }

    async getUserProfile(id){
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
        try {
            const response = await this.http.post('/send', value);
            return response.data;
        } catch (error) {
            console.error('Error sending message:', error.response?.data || error.message);
            throw error;
        }
    }
}
