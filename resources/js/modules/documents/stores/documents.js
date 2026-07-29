import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api';

export const useDocumentsStore = defineStore('documents', () => {
    const loading = ref(false);
    const organizationId = ref(localStorage.getItem('edams_org_id') || null);
    const currentFolderId = ref(null);
    const folderTree = ref([]);
    const showTrash = ref(false);

    function setOrganization(id) {
        organizationId.value = id;
        if (id) localStorage.setItem('edams_org_id', id);
    }

    async function loadFolders() {
        if (!organizationId.value) return [];
        const { data } = await api.get('/folders/tree', {
            params: { organization_id: organizationId.value },
        });
        folderTree.value = data.data;
        return folderTree.value;
    }

    async function createFolder(payload) {
        const { data } = await api.post('/folders', {
            ...payload,
            organization_id: organizationId.value,
        });
        await loadFolders();
        return data.data;
    }

    async function deleteFolder(id) {
        await api.delete(`/folders/${id}`);
        await loadFolders();
    }

    async function fetchDocuments(params = {}) {
        loading.value = true;
        try {
            const endpoint = showTrash.value ? '/documents/trash' : '/documents';
            const { data } = await api.get(endpoint, {
                params: {
                    organization_id: organizationId.value,
                    folder_id: showTrash.value ? undefined : (currentFolderId.value ?? 'root'),
                    ...params,
                },
            });
            return data.data;
        } finally {
            loading.value = false;
        }
    }

    async function uploadDocument(formData) {
        const { data } = await api.post('/documents', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        return data.data;
    }

    async function bulkUpload(formData) {
        const { data } = await api.post('/documents/bulk-upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        return data.data;
    }

    async function updateDocument(id, payload) {
        const { data } = await api.put(`/documents/${id}`, payload);
        return data.data;
    }

    async function deleteDocument(id) {
        await api.delete(`/documents/${id}`);
    }

    async function restoreDocument(id) {
        await api.post(`/documents/${id}/restore`);
    }

    async function forceDeleteDocument(id) {
        await api.delete(`/documents/${id}/force`);
    }

    async function checkOut(id) {
        const { data } = await api.post(`/documents/${id}/check-out`);
        return data.data;
    }

    async function checkIn(id, formData = null) {
        const { data } = await api.post(`/documents/${id}/check-in`, formData, formData ? {
            headers: { 'Content-Type': 'multipart/form-data' },
        } : undefined);
        return data.data;
    }

    async function rename(id, title) {
        const { data } = await api.post(`/documents/${id}/rename`, { title });
        return data.data;
    }

    async function move(id, folderId) {
        const { data } = await api.post(`/documents/${id}/move`, { folder_id: folderId });
        return data.data;
    }

    async function copy(id, folderId = null) {
        const { data } = await api.post(`/documents/${id}/copy`, { folder_id: folderId });
        return data.data;
    }

    function downloadUrl(id) {
        return `/api/v1/documents/${id}/download`;
    }

    return {
        loading,
        organizationId,
        currentFolderId,
        folderTree,
        showTrash,
        setOrganization,
        loadFolders,
        createFolder,
        deleteFolder,
        fetchDocuments,
        uploadDocument,
        bulkUpload,
        updateDocument,
        deleteDocument,
        restoreDocument,
        forceDeleteDocument,
        checkOut,
        checkIn,
        rename,
        move,
        copy,
        downloadUrl,
    };
});
