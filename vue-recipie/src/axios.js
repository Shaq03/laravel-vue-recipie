import axios from 'axios';
import { useStore } from 'vuex';
import router from './router';

const instance = axios.create({
    baseURL: 'http://localhost:8000',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

instance.interceptors.request.use(
    config => {
        const token = localStorage.getItem('token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

instance.interceptors.response.use(
    (response) => response,
    async (error) => {
        const originalRequest = error.config;
        
        if (error.response?.status === 401 && !originalRequest._retry) {
            originalRequest._retry = true;
            
            try {
                const response = await instance.post('/api/v1/refresh-token');
                if (response.data.token) {
                    localStorage.setItem('token', response.data.token);
                    instance.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
                    
                    originalRequest.headers['Authorization'] = `Bearer ${response.data.token}`;
                    return instance(originalRequest);
                }
            } catch (refreshError) {
                console.error('Token refresh failed:', refreshError);
            }
            
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            delete instance.defaults.headers.common['Authorization'];
            
            if (router.currentRoute.value.path !== '/login') {
                router.push('/login');
            }
        }
        return Promise.reject(error);
    }
);

export default instance; 