import axios from 'axios';
import { getApiOrigin } from '@/services/api-origin';

const sanctum = axios.create({
    baseURL: getApiOrigin(),
    withCredentials: true,
    headers: {
        Accept: 'application/json'
    }
});

export default sanctum;
