import axios from 'axios';
import { useStore } from 'vuex';

// Create axios instance with custom config
const instance = axios.create({
    baseURL: 'http://localhost:8000',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// Add a request interceptor to include the auth token
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

// Add a response interceptor to handle 401 errors
instance.interceptors.response.use(
    (response) => response,
    async (error) => {
        if (error.response?.status === 401) {
            const store = useStore();
            try {
                // Try to refresh the token
                const refreshed = await store.dispatch('refreshToken');
                if (refreshed) {
                    // Retry the original request
                    error.config.headers['Authorization'] = `Bearer ${store.state.token}`;
                    return instance(error.config);
                }
            } catch (refreshError) {
                console.error('Token refresh failed:', refreshError);
            }
            
            // If refresh failed or not attempted, clear auth data and redirect
            console.log('Authentication failed, clearing auth data');
            store.dispatch('logout');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export default instance; 