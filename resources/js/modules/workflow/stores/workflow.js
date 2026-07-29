import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api';

export const useWorkflowStore = defineStore('workflow', () => {
    const loading = ref(false);
    const organizationId = ref(localStorage.getItem('edams_org_id') || null);

    function setOrganization(id) {
        organizationId.value = id;
        if (id) localStorage.setItem('edams_org_id', id);
    }

    async function fetchWorkflows(params = {}) {
        loading.value = true;
        try {
            const { data } = await api.get('/workflows', {
                params: { organization_id: organizationId.value, ...params },
            });
            return data.data;
        } finally {
            loading.value = false;
        }
    }

    async function createWorkflow(payload) {
        const { data } = await api.post('/workflows', {
            ...payload,
            organization_id: organizationId.value,
        });
        return data.data;
    }

    async function updateWorkflow(id, payload) {
        const { data } = await api.put(`/workflows/${id}`, payload);
        return data.data;
    }

    async function deleteWorkflow(id) {
        await api.delete(`/workflows/${id}`);
    }

    async function fetchInbox(params = {}) {
        loading.value = true;
        try {
            const { data } = await api.get('/workflows/inbox', {
                params: { organization_id: organizationId.value, ...params },
            });
            return data.data;
        } finally {
            loading.value = false;
        }
    }

    async function fetchInstances(params = {}) {
        const { data } = await api.get('/workflows/instances', {
            params: { organization_id: organizationId.value, ...params },
        });
        return data.data;
    }

    async function showInstance(id) {
        const { data } = await api.get(`/workflows/instances/${id}`);
        return data.data;
    }

    async function submit(documentId, workflowId = null, note = null) {
        const { data } = await api.post('/workflows/submit', {
            document_id: documentId,
            workflow_id: workflowId,
            note,
        });
        return data.data;
    }

    async function approve(id, comments = null) {
        const { data } = await api.post(`/workflows/instances/${id}/approve`, { comments });
        return data.data;
    }

    async function reject(id, comments = null) {
        const { data } = await api.post(`/workflows/instances/${id}/reject`, { comments });
        return data.data;
    }

    async function returnInstance(id, comments = null) {
        const { data } = await api.post(`/workflows/instances/${id}/return`, { comments });
        return data.data;
    }

    async function cancel(id, comments = null) {
        const { data } = await api.post(`/workflows/instances/${id}/cancel`, { comments });
        return data.data;
    }

    async function fetchStats() {
        if (!organizationId.value) return null;
        const { data } = await api.get('/workflows/stats', {
            params: { organization_id: organizationId.value },
        });
        return data.data;
    }

    async function fetchRecent(limit = 8) {
        if (!organizationId.value) return [];
        const { data } = await api.get('/workflows/recent', {
            params: { organization_id: organizationId.value, limit },
        });
        return data.data;
    }

    return {
        loading,
        organizationId,
        setOrganization,
        fetchWorkflows,
        createWorkflow,
        updateWorkflow,
        deleteWorkflow,
        fetchInbox,
        fetchInstances,
        showInstance,
        submit,
        approve,
        reject,
        returnInstance,
        cancel,
        fetchStats,
        fetchRecent,
    };
});
