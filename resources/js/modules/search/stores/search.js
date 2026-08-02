import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api';

export const useSearchStore = defineStore('search', () => {
    const loading = ref(false);
    const organizationId = ref(localStorage.getItem('edams_org_id') || null);
    const results = ref([]);
    const meta = ref(null);
    const facets = ref(null);
    const saved = ref([]);

    function setOrganization(id) {
        organizationId.value = id;
        if (id) localStorage.setItem('edams_org_id', id);
    }

    async function search(params = {}) {
        loading.value = true;
        try {
            const { data } = await api.get('/search/documents', {
                params: {
                    organization_id: organizationId.value,
                    ...params,
                },
            });
            const payload = data.data;
            // Laravel Resource::collection(paginator)->getData(true) => { data, links, meta }
            // Guard against double-wrapped shapes from ApiResponse.
            if (Array.isArray(payload)) {
                results.value = payload;
                meta.value = data.meta || null;
            } else if (payload && Array.isArray(payload.data)) {
                results.value = payload.data;
                meta.value = payload.meta || data.meta || null;
            } else {
                results.value = [];
                meta.value = null;
            }
            return payload;
        } finally {
            loading.value = false;
        }
    }

    async function loadFacets() {
        if (!organizationId.value) return null;
        const { data } = await api.get('/search/facets', {
            params: { organization_id: organizationId.value },
        });
        facets.value = data.data;
        return facets.value;
    }

    async function loadSaved() {
        const { data } = await api.get('/search/saved', {
            params: { organization_id: organizationId.value || undefined },
        });
        saved.value = data.data;
        return saved.value;
    }

    async function createSaved(payload) {
        const { data } = await api.post('/search/saved', {
            ...payload,
            organization_id: organizationId.value,
        });
        await loadSaved();
        return data.data;
    }

    async function updateSaved(id, payload) {
        const { data } = await api.put(`/search/saved/${id}`, payload);
        await loadSaved();
        return data.data;
    }

    async function deleteSaved(id) {
        await api.delete(`/search/saved/${id}`);
        await loadSaved();
    }

    return {
        loading,
        organizationId,
        results,
        meta,
        facets,
        saved,
        setOrganization,
        search,
        loadFacets,
        loadSaved,
        createSaved,
        updateSaved,
        deleteSaved,
    };
});
