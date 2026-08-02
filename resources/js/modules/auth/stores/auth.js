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
        const rawUser = payload?.user;
        user.value = rawUser?.data ?? rawUser ?? null;
        token.value = payload?.token || null;
        if (token.value) {
            localStorage.setItem('edams_token', token.value);
        } else {
            localStorage.removeItem('edams_token');
        }
        initialized.value = true;
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

    async function verifyPasswordResetOtp(payload) {
        await ensureCsrf();
        const { data } = await api.post('/auth/forgot-password/verify-otp', payload);
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

    async function requestEmailChange(email) {
        const { data } = await api.post('/auth/me/email-change', { email });
        return data.data;
    }

    async function confirmEmailChange(otp) {
        const { data } = await api.post('/auth/me/email-change/confirm', { otp });
        user.value = data.data;
        return user.value;
    }

    function hasPermission(name) {
        if (!user.value) return false;
        if (user.value.roles?.includes('super_admin')) return true;
        return (user.value.permissions || []).includes(name);
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
        verifyPasswordResetOtp,
        resetPassword,
        fetchLoginActivities,
        fetchDevices,
        revokeDevice,
        logoutOtherDevices,
        updateProfile,
        uploadAvatar,
        removeAvatar,
        requestEmailChange,
        confirmEmailChange,
        hasPermission,
        clearSession,
    };
});
