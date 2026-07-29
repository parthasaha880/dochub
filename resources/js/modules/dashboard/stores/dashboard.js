import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api';

export const useDashboardStore = defineStore('dashboard', () => {
    const loading = ref(false);
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
            return null;
        }

        loading.value = true;
        try {
            const { data } = await api.get('/dashboard/summary', {
                params: {
                    organization_id: organizationId.value,
                    days: params.days ?? days.value,
                },
            });
            summary.value = data.data;
            return summary.value;
        } finally {
            loading.value = false;
        }
    }

    return {
        loading,
        organizationId,
        days,
        summary,
        setOrganization,
        fetchSummary,
    };
});
