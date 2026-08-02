import axios from 'axios';

const api = axios.create({
    baseURL: '/api/v1',
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

let redirectingToLogin = false;

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('edams_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        const url = String(error.config?.url || '');
        const isAuthAttempt = /\/auth\/(login|forgot-password|reset-password)/.test(url);

        if (status === 401 && !isAuthAttempt) {
            localStorage.removeItem('edams_token');
            if (!redirectingToLogin && !window.location.pathname.startsWith('/login')) {
                redirectingToLogin = true;
                window.location.href = '/login';
            }
        }

        return Promise.reject(error);
    }
);

export default api;
