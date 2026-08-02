import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import api from '@/services/api';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const token = ref(localStorage.getItem('edams_token'));
    const loading = ref(false);
    const initialized = ref(false);

    const isAuthenticated = computed(() => !!user.value && !!token.value);
    const isVerified = computed(() => !!user.value?.email_verified_at);

    function setSession(payload) {
        user.value = payload.user;
        token.value = payload.token;
        localStorage.setItem('edams_token', payload.token);
    }

    function clearSession() {
        user.value = null;
        token.value = null;
        localStorage.removeItem('edams_token');
    }

    async function ensureCsrf() {
        await api.get('/sanctum/csrf-cookie', { baseURL: '/' });
    }

    async function login(credentials) {
        loading.value = true;
        try {
            await ensureCsrf();
            const { data } = await api.post('/auth/login', credentials);
            setSession(data.data);
            return data;
        } finally {
            loading.value = false;
        }
    }

    async function logout() {
        try {
            await api.post('/auth/logout');
        } finally {
            clearSession();
        }
    }

    async function fetchMe() {
        if (!token.value) {
            initialized.value = true;
            return null;
        }

        try {
            const { data } = await api.get('/auth/me');
            user.value = data.data;
            return user.value;
        } catch {
            clearSession();
            return null;
        } finally {
            initialized.value = true;
        }
    }

    async function forgotPassword(email) {
        await ensureCsrf();
        const { data } = await api.post('/auth/forgot-password', { email });
        return data;
    }

    async function resetPassword(payload) {
        await ensureCsrf();
        const { data } = await api.post('/auth/reset-password', payload);
        return data;
    }

    async function fetchLoginActivities(params = {}) {
        const { data } = await api.get('/auth/login-activities', { params });
        return data.data;
    }

    async function fetchDevices(params = {}) {
        const { data } = await api.get('/auth/devices', { params });
        return data.data;
    }

    async function revokeDevice(id) {
        const { data } = await api.delete(`/auth/devices/${id}`);
        return data;
    }

    async function logoutOtherDevices(password) {
        const { data } = await api.post('/auth/logout-other-devices', { password });
        return data;
    }

    async function updateProfile(payload) {
        const { data } = await api.put('/auth/me', payload);
        user.value = data.data;
        return user.value;
    }

    async function uploadAvatar(file) {
        const form = new FormData();
        form.append('avatar', file);
        const { data } = await api.post('/auth/me/avatar', form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        user.value = data.data;
        return user.value;
    }

    async function removeAvatar() {
        const { data } = await api.delete('/auth/me/avatar');
        user.value = data.data;
        return user.value;
    }

    return {
        user,
        token,
        loading,
        initialized,
        isAuthenticated,
        isVerified,
        login,
        logout,
        fetchMe,
        forgotPassword,
        resetPassword,
        fetchLoginActivities,
        fetchDevices,
        revokeDevice,
        logoutOtherDevices,
        updateProfile,
        uploadAvatar,
        removeAvatar,
        clearSession,
    };
});
