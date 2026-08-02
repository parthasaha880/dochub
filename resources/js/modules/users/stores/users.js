import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api';

export const useUsersStore = defineStore('users', () => {
    const loading = ref(false);
    const roles = ref([]);
    const permissionsGrouped = ref({});
    const permissionGroups = ref([]);

    async function fetchUsers(params = {}) {
        loading.value = true;
        try {
            const { data } = await api.get('/users', { params });
            return data.data;
        } finally {
            loading.value = false;
        }
    }

    async function createUser(payload) {
        const { data } = await api.post('/users', payload);
        return data.data;
    }

    async function updateUser(id, payload) {
        const { data } = await api.put(`/users/${id}`, payload);
        return data.data;
    }

    async function deleteUser(id) {
        const { data } = await api.delete(`/users/${id}`);
        return data;
    }

    async function resendWelcomeEmail(id) {
        const { data } = await api.post(`/users/${id}/resend-welcome`);
        return data;
    }

    async function unlockUser(id) {
        const { data } = await api.post(`/users/${id}/unlock`);
        return data;
    }

    async function fetchRoles(params = {}) {
        loading.value = true;
        try {
            const { data } = await api.get('/roles', { params });
            return data.data;
        } finally {
            loading.value = false;
        }
    }

    async function createRole(payload) {
        const { data } = await api.post('/roles', payload);
        return data.data;
    }

    async function updateRole(id, payload) {
        const { data } = await api.put(`/roles/${id}`, payload);
        return data.data;
    }

    async function deleteRole(id) {
        const { data } = await api.delete(`/roles/${id}`);
        return data;
    }

    async function fetchPermissions(params = {}) {
        loading.value = true;
        try {
            const { data } = await api.get('/permissions', { params });
            return data.data;
        } finally {
            loading.value = false;
        }
    }

    async function createPermission(payload) {
        const { data } = await api.post('/permissions', payload);
        return data.data;
    }

    async function updatePermission(id, payload) {
        const { data } = await api.put(`/permissions/${id}`, payload);
        return data.data;
    }

    async function deletePermission(id) {
        const { data } = await api.delete(`/permissions/${id}`);
        return data;
    }

    async function loadRoleOptions() {
        const { data } = await api.get('/roles/options/list');
        roles.value = data.data;
        return roles.value;
    }

    async function loadPermissionMeta() {
        const [grouped, groups] = await Promise.all([
            api.get('/permissions/grouped'),
            api.get('/permissions/groups'),
        ]);
        permissionsGrouped.value = grouped.data.data;
        permissionGroups.value = groups.data.data;
        return { grouped: permissionsGrouped.value, groups: permissionGroups.value };
    }

    return {
        loading,
        roles,
        permissionsGrouped,
        permissionGroups,
        fetchUsers,
        createUser,
        updateUser,
        deleteUser,
        resendWelcomeEmail,
        unlockUser,
        fetchRoles,
        createRole,
        updateRole,
        deleteRole,
        fetchPermissions,
        createPermission,
        updatePermission,
        deletePermission,
        loadRoleOptions,
        loadPermissionMeta,
    };
});
