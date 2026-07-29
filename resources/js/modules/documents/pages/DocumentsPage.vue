<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold text-slate-900 dark:text-white">Documents</h1>
                <p class="mt-1 text-sm text-slate-500">Folders, uploads, versions, check-in/out, and recycle bin</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Select
                    v-model="selectedOrg"
                    :options="organizations"
                    option-label="name"
                    option-value="id"
                    placeholder="Organization"
                    class="w-56"
                    @change="onOrgChange"
                />
                <Button
                    :label="store.showTrash ? 'Back to library' : 'Recycle bin'"
                    :severity="store.showTrash ? 'secondary' : 'danger'"
                    outlined
                    @click="toggleTrash"
                />
                <Button v-if="!store.showTrash" label="New folder" icon="pi pi-folder" outlined @click="showFolderDialog = true" />
                <Button v-if="!store.showTrash" label="Upload" icon="pi pi-upload" @click="showUploadDialog = true" />
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[280px_1fr]">
            <aside v-if="!store.showTrash" class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold">Folders</h2>
                    <Button icon="pi pi-refresh" text rounded size="small" @click="store.loadFolders()" />
                </div>
                <button
                    type="button"
                    class="mb-2 w-full rounded-md px-3 py-2 text-left text-sm hover:bg-slate-100 dark:hover:bg-slate-900"
                    :class="store.currentFolderId === null ? 'bg-brand-50 font-semibold text-brand-700 dark:bg-slate-900' : ''"
                    @click="selectFolder(null)"
                >
                    All / Root
                </button>
                <Tree
                    v-if="treeNodes.length"
                    :value="treeNodes"
                    selection-mode="single"
                    v-model:selection-keys="selectedTreeKeys"
                    class="w-full text-sm"
                    @node-select="onTreeSelect"
                />
                <p v-else class="text-sm text-slate-500">No folders yet.</p>
            </aside>

            <section class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950">
                <div class="mb-4 flex flex-wrap gap-2">
                    <InputText v-model="search" placeholder="Search documents..." class="w-64" @keyup.enter="loadDocuments" />
                    <Button icon="pi pi-search" @click="loadDocuments" />
                </div>

                <DataTable :value="rows" :loading="store.loading" striped-rows paginator :rows="15" size="small">
                    <Column field="title" header="Title" />
                    <Column field="extension" header="Type" />
                    <Column field="version" header="Ver" />
                    <Column field="approval_status" header="Approval" />
                    <Column header="Lock">
                        <template #body="{ data }">
                            <Tag
                                v-if="data.checked_out_by"
                                value="Checked out"
                                severity="warn"
                            />
                            <span v-else class="text-slate-400">—</span>
                        </template>
                    </Column>
                    <Column header="Actions" style="width: 16rem">
                        <template #body="{ data }">
                            <template v-if="store.showTrash">
                                <Button icon="pi pi-replay" text rounded v-tooltip="'Restore'" @click="restore(data)" />
                                <Button icon="pi pi-times" text rounded severity="danger" v-tooltip="'Delete forever'" @click="forceDelete(data)" />
                            </template>
                            <template v-else>
                                <Button
                                    v-if="canSubmit(data)"
                                    icon="pi pi-send"
                                    text
                                    rounded
                                    severity="success"
                                    v-tooltip="'Submit for approval'"
                                    @click="submitApproval(data)"
                                />
                                <Button icon="pi pi-download" text rounded @click="download(data)" />
                                <Button icon="pi pi-lock" text rounded v-tooltip="'Check out'" @click="checkOut(data)" />
                                <Button icon="pi pi-lock-open" text rounded v-tooltip="'Check in'" @click="checkIn(data)" />
                                <Button icon="pi pi-copy" text rounded @click="copyDoc(data)" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="remove(data)" />
                            </template>
                        </template>
                    </Column>
                </DataTable>
            </section>
        </div>

        <Dialog v-model:visible="showUploadDialog" modal header="Upload documents" class="w-full max-w-xl">
            <div
                class="rounded-xl border-2 border-dashed border-slate-300 p-8 text-center dark:border-slate-700"
                @dragover.prevent
                @drop.prevent="onDrop"
            >
                <p class="mb-3 text-sm text-slate-500">Drag & drop files here, or browse</p>
                <input ref="fileInput" type="file" multiple class="hidden" @change="onFilePick" />
                <Button label="Browse files" outlined @click="$refs.fileInput.click()" />
                <ul v-if="pendingFiles.length" class="mt-4 space-y-1 text-left text-sm">
                    <li v-for="(f, idx) in pendingFiles" :key="idx">{{ f.name }} ({{ Math.round(f.size / 1024) }} KB)</li>
                </ul>
            </div>
            <div class="mt-4">
                <label class="mb-1 block text-sm font-medium">Title (single upload)</label>
                <InputText v-model="uploadTitle" class="w-full" placeholder="Optional for multi-file" />
            </div>
            <template #footer>
                <Button label="Cancel" text @click="showUploadDialog = false" />
                <Button label="Upload" :loading="uploading" :disabled="!pendingFiles.length" @click="doUpload" />
            </template>
        </Dialog>

        <Dialog v-model:visible="showFolderDialog" modal header="Create folder" class="w-full max-w-md">
            <InputText v-model="folderName" class="w-full" placeholder="Folder name" />
            <template #footer>
                <Button label="Cancel" text @click="showFolderDialog = false" />
                <Button label="Create" :loading="savingFolder" @click="createFolder" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Tree from 'primevue/tree';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import api from '@/services/api';
import { useDocumentsStore } from '@/modules/documents/stores/documents';

const store = useDocumentsStore();
const toast = useToast();
const confirm = useConfirm();

const organizations = ref([]);
const selectedOrg = ref(store.organizationId);
const rows = ref([]);
const search = ref('');
const showUploadDialog = ref(false);
const showFolderDialog = ref(false);
const pendingFiles = ref([]);
const uploadTitle = ref('');
const uploading = ref(false);
const folderName = ref('');
const savingFolder = ref(false);
const selectedTreeKeys = ref({});
const fileInput = ref(null);

const treeNodes = computed(() => mapFolders(store.folderTree));

function mapFolders(folders) {
    return (folders || []).map((folder) => ({
        key: folder.id,
        label: folder.name,
        data: folder,
        children: mapFolders(folder.children || []),
    }));
}

async function loadOrgs() {
    const { data } = await api.get('/organizations', { params: { per_page: 100 } });
    organizations.value = data.data.data || data.data;
    if (!selectedOrg.value && organizations.value.length) {
        selectedOrg.value = organizations.value[0].id;
        store.setOrganization(selectedOrg.value);
    }
}

async function onOrgChange() {
    store.setOrganization(selectedOrg.value);
    store.currentFolderId = null;
    await store.loadFolders();
    await loadDocuments();
}

function selectFolder(id) {
    store.currentFolderId = id;
    selectedTreeKeys.value = id ? { [id]: true } : {};
    loadDocuments();
}

function onTreeSelect(node) {
    selectFolder(node.key);
}

async function loadDocuments() {
    const data = await store.fetchDocuments({ search: search.value || undefined, per_page: 50 });
    rows.value = data.data || data;
}

function toggleTrash() {
    store.showTrash = !store.showTrash;
    loadDocuments();
}

function onDrop(event) {
    pendingFiles.value = [...event.dataTransfer.files];
}

function onFilePick(event) {
    pendingFiles.value = [...event.target.files];
}

async function doUpload() {
    if (!selectedOrg.value || !pendingFiles.value.length) return;
    uploading.value = true;
    try {
        if (pendingFiles.value.length === 1) {
            const formData = new FormData();
            formData.append('organization_id', selectedOrg.value);
            if (store.currentFolderId) formData.append('folder_id', store.currentFolderId);
            formData.append('title', uploadTitle.value || pendingFiles.value[0].name.replace(/\.[^.]+$/, ''));
            formData.append('file', pendingFiles.value[0]);
            await store.uploadDocument(formData);
        } else {
            const formData = new FormData();
            formData.append('organization_id', selectedOrg.value);
            if (store.currentFolderId) formData.append('folder_id', store.currentFolderId);
            pendingFiles.value.forEach((file) => formData.append('files[]', file));
            await store.bulkUpload(formData);
        }
        toast.add({ severity: 'success', summary: 'Upload complete', life: 2500 });
        showUploadDialog.value = false;
        pendingFiles.value = [];
        uploadTitle.value = '';
        await loadDocuments();
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Upload failed',
            detail: error.response?.data?.message || 'Unable to upload',
            life: 4000,
        });
    } finally {
        uploading.value = false;
    }
}

async function createFolder() {
    savingFolder.value = true;
    try {
        await store.createFolder({
            name: folderName.value,
            parent_id: store.currentFolderId,
        });
        toast.add({ severity: 'success', summary: 'Folder created', life: 2000 });
        showFolderDialog.value = false;
        folderName.value = '';
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Failed', detail: error.response?.data?.message, life: 4000 });
    } finally {
        savingFolder.value = false;
    }
}

async function download(row) {
    const token = localStorage.getItem('edams_token');
    const response = await fetch(store.downloadUrl(row.id), {
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/octet-stream' },
    });
    const blob = await response.blob();
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = row.original_name || row.title;
    a.click();
    URL.revokeObjectURL(url);
}

async function checkOut(row) {
    await store.checkOut(row.id);
    toast.add({ severity: 'success', summary: 'Checked out', life: 2000 });
    await loadDocuments();
}

async function checkIn(row) {
    await store.checkIn(row.id);
    toast.add({ severity: 'success', summary: 'Checked in', life: 2000 });
    await loadDocuments();
}

async function copyDoc(row) {
    await store.copy(row.id, store.currentFolderId);
    toast.add({ severity: 'success', summary: 'Copied', life: 2000 });
    await loadDocuments();
}

function canSubmit(row) {
    return ['draft', 'returned', 'rejected'].includes(row.approval_status);
}

async function submitApproval(row) {
    try {
        await api.post('/workflows/submit', { document_id: row.id });
        toast.add({ severity: 'success', summary: 'Submitted for approval', life: 2500 });
        await loadDocuments();
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Submit failed',
            detail: error.response?.data?.message || 'Unable to submit',
            life: 4000,
        });
    }
}

function remove(row) {
    confirm.require({
        message: `Move "${row.title}" to recycle bin?`,
        header: 'Confirm',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            await store.deleteDocument(row.id);
            toast.add({ severity: 'success', summary: 'Moved to bin', life: 2000 });
            await loadDocuments();
        },
    });
}

async function restore(row) {
    await store.restoreDocument(row.id);
    toast.add({ severity: 'success', summary: 'Restored', life: 2000 });
    await loadDocuments();
}

function forceDelete(row) {
    confirm.require({
        message: `Permanently delete "${row.title}"? This cannot be undone.`,
        header: 'Permanent delete',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            await store.forceDeleteDocument(row.id);
            toast.add({ severity: 'success', summary: 'Deleted forever', life: 2000 });
            await loadDocuments();
        },
    });
}

onMounted(async () => {
    await loadOrgs();
    if (store.organizationId) {
        await store.loadFolders();
        await loadDocuments();
    }
});
</script>
