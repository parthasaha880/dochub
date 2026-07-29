import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api';

const endpoints = {
    organizations: '/organizations',
    branches: '/branches',
    departments: '/departments',
    sections: '/sections',
    units: '/units',
    offices: '/offices',
    designations: '/designations',
    employees: '/employees',
};

export const useOrganizationStore = defineStore('organization', () => {
    const loading = ref(false);
    const currentOrganizationId = ref(localStorage.getItem('edams_org_id') || null);
    const organizations = ref([]);
    const tree = ref(null);

    function setOrganization(id) {
        currentOrganizationId.value = id;
        if (id) {
            localStorage.setItem('edams_org_id', id);
        } else {
            localStorage.removeItem('edams_org_id');
        }
    }

    async function fetchList(resource, params = {}) {
        loading.value = true;
        try {
            const query = { ...params };
            if (currentOrganizationId.value && resource !== 'organizations' && !query.organization_id) {
                query.organization_id = currentOrganizationId.value;
            }
            const { data } = await api.get(endpoints[resource], { params: query });
            return data.data;
        } finally {
            loading.value = false;
        }
    }

    async function create(resource, payload) {
        const { data } = await api.post(endpoints[resource], payload);
        return data.data;
    }

    async function update(resource, id, payload) {
        const { data } = await api.put(`${endpoints[resource]}/${id}`, payload);
        return data.data;
    }

    async function remove(resource, id) {
        const { data } = await api.delete(`${endpoints[resource]}/${id}`);
        return data;
    }

    async function fetchOptions(resource, organizationId = null) {
        const { data } = await api.get(`${endpoints[resource]}/options/list`, {
            params: { organization_id: organizationId || currentOrganizationId.value },
        });
        return data.data;
    }

    async function loadOrganizations() {
        const result = await fetchList('organizations', { per_page: 100 });
        organizations.value = result.data || result;
        if (!currentOrganizationId.value && organizations.value.length) {
            setOrganization(organizations.value[0].id);
        }
        return organizations.value;
    }

    async function loadTree(organizationId = null) {
        const id = organizationId || currentOrganizationId.value;
        if (!id) {
            tree.value = null;
            return null;
        }
        const { data } = await api.get(`/organizations/${id}/tree`);
        tree.value = data.data;
        return tree.value;
    }

    return {
        loading,
        currentOrganizationId,
        organizations,
        tree,
        setOrganization,
        fetchList,
        create,
        update,
        remove,
        fetchOptions,
        loadOrganizations,
        loadTree,
    };
});
