import axios from 'axios';

// Create axios instance with custom config
const instance = axios.create({
    baseURL: 'http://localhost:8000',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    }
});

// Add a request interceptor to add the auth token
instance.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('token');
        console.log('Axios interceptor - Current token:', token);
        if (token) {
            config.headers['Authorization'] = `Bearer ${token}`;
            console.log('Axios interceptor - Setting Authorization header:', config.headers['Authorization']);
        }
        return config;
    },
    (error) => {
        console.error('Axios interceptor error:', error);
        return Promise.reject(error);
    }
);

// Add a response interceptor to handle 401 errors
instance.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            console.log('Received 401 response, clearing auth data');
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export default instance; 