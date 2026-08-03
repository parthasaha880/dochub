import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api';
import { resolveOrganizationId } from '@/utils/organization';

export const useArchiveStore = defineStore('archive', () => {
    const loading = ref(false);
    const organizationId = ref(localStorage.getItem('edams_org_id'));

    function setOrganization(id) {
        organizationId.value = id;
        if (id) localStorage.setItem('edams_org_id', id);
    }

    function syncOrganization(organizations) {
        const next = resolveOrganizationId(organizations, organizationId.value);
        setOrganization(next);
        return next;
    }

    async function fetchStats() {
        const { data } = await api.get('/archive/stats', {
            params: { organization_id: organizationId.value },
        });
        return data.data;
    }

    async function fetchLocationTree() {
        const { data } = await api.get('/archive/locations/tree', {
            params: { organization_id: organizationId.value },
        });
        return data.data;
    }

    async function createLocation(payload) {
        const { data } = await api.post('/archive/locations', {
            organization_id: organizationId.value,
            ...payload,
        });
        return data.data;
    }

    async function updateLocation(id, payload) {
        const { data } = await api.put(`/archive/locations/${id}`, payload);
        return data.data;
    }

    async function deleteLocation(id) {
        const { data } = await api.delete(`/archive/locations/${id}`);
        return data;
    }

    async function fetchCategories() {
        const { data } = await api.get('/archive/categories/tree', {
            params: { organization_id: organizationId.value },
        });
        return data.data;
    }

    async function createCategory(payload) {
        const { data } = await api.post('/archive/categories', {
            organization_id: organizationId.value,
            ...payload,
        });
        return data.data;
    }

    async function updateCategory(id, payload) {
        const { data } = await api.put(`/archive/categories/${id}`, payload);
        return data.data;
    }

    async function deleteCategory(id) {
        const { data } = await api.delete(`/archive/categories/${id}`);
        return data;
    }

    async function fetchDigital(params = {}) {
        loading.value = true;
        try {
            const { data } = await api.get('/archive/digital', {
                params: { organization_id: organizationId.value, ...params },
            });
            return data.data;
        } finally {
            loading.value = false;
        }
    }

    async function fetchPhysical(params = {}) {
        loading.value = true;
        try {
            const { data } = await api.get('/archive/physical', {
                params: { organization_id: organizationId.value, ...params },
            });
            return data.data;
        } finally {
            loading.value = false;
        }
    }

    async function fetchHybrid(params = {}) {
        loading.value = true;
        try {
            const { data } = await api.get('/archive/hybrid', {
                params: { organization_id: organizationId.value, ...params },
            });
            return data.data;
        } finally {
            loading.value = false;
        }
    }

    async function lookup(query) {
        const { data } = await api.get('/archive/lookup', {
            params: { organization_id: organizationId.value, query },
        });
        return data.data;
    }

    async function archiveDocument(documentId, locationId = null) {
        const { data } = await api.post(`/archive/documents/${documentId}/archive`, {
            location_id: locationId,
        });
        return data.data;
    }

    async function assignLocation(documentId, payload) {
        const { data } = await api.post(`/archive/documents/${documentId}/assign-location`, payload);
        return data.data;
    }

    return {
        loading,
        organizationId,
        setOrganization,
        syncOrganization,
        fetchStats,
        fetchLocationTree,
        createLocation,
        updateLocation,
        deleteLocation,
        fetchCategories,
        createCategory,
        updateCategory,
        deleteCategory,
        fetchDigital,
        fetchPhysical,
        fetchHybrid,
        lookup,
        archiveDocument,
        assignLocation,
    };
});
