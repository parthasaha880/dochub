import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api';

export const useDashboardStore = defineStore('dashboard', () => {
    const loading = ref(false);
    const error = ref(null);
    const organizationId = ref(localStorage.getItem('edams_org_id') || null);
    const days = ref(30);
    const summary = ref(null);

    function setOrganization(id) {
        organizationId.value = id;
        if (id) localStorage.setItem('edams_org_id', id);
    }

    async function fetchSummary(params = {}) {
        if (!organizationId.value) {
            summary.value = null;
            error.value = null;
            return null;
        }

        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/dashboard/summary', {
                params: {
                    organization_id: organizationId.value,
                    days: params.days ?? days.value,
                },
            });
            summary.value = data.data;
            return summary.value;
        } catch (e) {
            summary.value = null;
            error.value = e.response?.data?.message || e.message || 'Failed to load dashboard';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    return {
        loading,
        error,
        organizationId,
        days,
        summary,
        setOrganization,
        fetchSummary,
    };
});
