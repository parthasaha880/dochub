<template>
    <div class="space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="font-display text-2xl font-semibold tracking-tight text-slate-900 dark:text-white sm:text-3xl">Documents</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Browse folders, preview files, and manage versions</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Select
                    v-model="selectedOrg"
                    :options="organizations"
                    option-label="name"
                    option-value="id"
                    placeholder="Organization"
                    class="w-52"
                    @change="onOrgChange"
                />
                <Button
                    :label="store.showTrash ? 'Library' : 'Recycle bin'"
                    :icon="store.showTrash ? 'pi pi-folder' : 'pi pi-trash'"
                    :severity="store.showTrash ? 'secondary' : 'danger'"
                    outlined
                    size="small"
                    @click="toggleTrash"
                />
                <Button v-if="!store.showTrash" label="New folder" icon="pi pi-folder-plus" outlined size="small" @click="showFolderDialog = true" />
                <Button v-if="!store.showTrash" label="Upload" icon="pi pi-upload" size="small" @click="showUploadDialog = true" />
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-[260px_1fr]">
            <aside
                v-if="!store.showTrash"
                class="rounded-xl border border-slate-200/90 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-950"
            >
                <div class="mb-2 flex items-center justify-between gap-2 px-1">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Folders</h2>
                    <div class="flex items-center">
                        <Button
                            :icon="store.showHiddenFolders ? 'pi pi-eye' : 'pi pi-eye-slash'"
                            text
                            rounded
                            size="small"
                            v-tooltip="store.showHiddenFolders ? 'Hide hidden folders' : 'Show hidden folders'"
                            @click="toggleShowHidden"
                        />
                        <Button icon="pi pi-refresh" text rounded size="small" @click="store.loadFolders()" />
                    </div>
                </div>
                <button
                    type="button"
                    class="mb-1 flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-900"
                    :class="store.currentFolderId === null ? 'bg-brand-50 font-semibold text-brand-700 dark:bg-slate-900 dark:text-brand-100' : ''"
                    @click="selectFolder(null)"
                >
                    <i class="pi pi-home text-xs text-slate-400" />
                    All / Root
                </button>
                <ul v-if="store.folderTree.length" class="space-y-0.5">
                    <FolderTreeNode
                        v-for="folder in store.folderTree"
                        :key="folder.id"
                        :folder="folder"
                        :active-id="store.currentFolderId"
                        @select="selectFolder"
                        @rename="openRenameFolder"
                        @toggle-lock="toggleFolderLock"
                        @toggle-hide="toggleFolderHide"
                        @delete="confirmDeleteFolder"
                    />
                </ul>
                <p v-else class="px-3 py-4 text-center text-sm text-slate-400">No folders yet</p>
            </aside>

            <section class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
                <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                    <span class="relative min-w-[14rem] flex-1">
                        <i class="pi pi-search pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-slate-400" />
                        <InputText
                            v-model="search"
                            placeholder="Search by title or file name…"
                            class="w-full !pl-9"
                            @keyup.enter="loadDocuments"
                        />
                    </span>
                    <Button icon="pi pi-search" label="Search" size="small" @click="loadDocuments" />
                    <span class="ml-auto text-xs text-slate-400">{{ rows.length }} item{{ rows.length === 1 ? '' : 's' }}</span>
                </div>

                <DataTable
                    :value="rows"
                    :loading="store.loading"
                    paginator
                    :rows="15"
                    size="small"
                    row-hover
                    class="docs-table"
                    :pt="{
                        table: { class: 'text-sm' },
                        thead: { class: 'bg-slate-50/80 dark:bg-slate-900/50' },
                        bodyRow: { class: 'align-middle' },
                    }"
                >
                    <Column header="Document" class="min-w-[16rem]">
                        <template #body="{ data }">
                            <div class="flex items-start gap-3 py-1">
                                <div
                                    class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-[10px] font-bold uppercase tracking-wide"
                                    :class="typeTone(data.extension)"
                                >
                                    {{ (data.extension || 'file').slice(0, 4) }}
                                </div>
                                <div class="min-w-0">
                                    <button
                                        type="button"
                                        class="block max-w-full truncate text-left font-medium text-slate-800 hover:text-brand-600 dark:text-slate-100 dark:hover:text-brand-100"
                                        :title="data.title"
                                        @click="openViewer(data)"
                                    >
                                        {{ data.title }}
                                    </button>
                                    <p class="mt-0.5 truncate text-xs text-slate-400">
                                        {{ data.original_name || '—' }}
                                        <span v-if="data.size"> · {{ formatBytes(data.size) }}</span>
                                    </p>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column header="Version" style="width: 5.5rem">
                        <template #body="{ data }">
                            <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                v{{ data.version || 1 }}
                            </span>
                        </template>
                    </Column>
                    <Column header="Approval" style="width: 8.5rem">
                        <template #body="{ data }">
                            <Tag
                                :value="humanize(data.approval_status)"
                                :severity="approvalSeverity(data.approval_status)"
                                class="!text-xs"
                            />
                        </template>
                    </Column>
                    <Column header="Status" style="width: 7.5rem">
                        <template #body="{ data }">
                            <Tag
                                v-if="data.checked_out_by"
                                value="Checked out"
                                severity="warn"
                                class="!text-xs"
                            />
                            <Tag
                                v-else-if="data.is_locked"
                                value="Locked"
                                severity="danger"
                                class="!text-xs"
                            />
                            <Tag
                                v-else-if="data.is_hidden"
                                value="Hidden"
                                severity="secondary"
                                class="!text-xs"
                            />
                            <span v-else class="text-xs text-slate-400">Available</span>
                        </template>
                    </Column>
                    <Column header="" style="width: 9rem">
                        <template #body="{ data }">
                            <div class="flex flex-nowrap items-center justify-end gap-0.5">
                                <template v-if="store.showTrash">
                                    <Button icon="pi pi-replay" text rounded size="small" v-tooltip.top="'Restore'" @click="restore(data)" />
                                    <Button icon="pi pi-times" text rounded size="small" severity="danger" v-tooltip.top="'Delete forever'" @click="forceDelete(data)" />
                                </template>
                                <template v-else>
                                    <Button icon="pi pi-eye" text rounded size="small" v-tooltip.top="'View'" @click="openViewer(data)" />
                                    <Button icon="pi pi-download" text rounded size="small" v-tooltip.top="'Download'" @click="download(data)" />
                                    <Button
                                        icon="pi pi-ellipsis-v"
                                        text
                                        rounded
                                        size="small"
                                        v-tooltip.top="'More actions'"
                                        aria-haspopup="true"
                                        aria-controls="doc_actions_menu"
                                        @click="openDocMenu($event, data)"
                                    />
                                </template>
                            </div>
                        </template>
                    </Column>
                    <template #empty>
                        <div class="px-4 py-12 text-center text-sm text-slate-400">
                            {{ store.showTrash ? 'Recycle bin is empty.' : 'No documents in this folder.' }}
                        </div>
                    </template>
                </DataTable>
            </section>
        </div>

        <Menu id="doc_actions_menu" ref="docMenu" :model="docMenuItems" popup />

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

        <Dialog v-model:visible="showRenameFolderDialog" modal header="Rename folder" class="w-full max-w-md">
            <InputText v-model="renameFolderName" class="w-full" placeholder="Folder name" @keyup.enter="confirmRenameFolder" />
            <template #footer>
                <Button label="Cancel" text @click="showRenameFolderDialog = false" />
                <Button label="Rename" :loading="savingFolder" :disabled="!renameFolderName.trim()" @click="confirmRenameFolder" />
            </template>
        </Dialog>

        <Dialog v-model:visible="showMoveDialog" modal header="Move to folder" class="w-full max-w-md">
            <p class="mb-3 text-sm text-slate-500">
                Move <span class="font-medium text-slate-800 dark:text-slate-200">{{ moveDoc?.title }}</span> to:
            </p>
            <Select
                v-model="moveTargetFolder"
                :options="folderOptions"
                option-label="label"
                option-value="value"
                option-disabled="disabled"
                placeholder="Select folder"
                class="w-full"
            />
            <template #footer>
                <Button label="Cancel" text @click="showMoveDialog = false" />
                <Button label="Move" icon="pi pi-folder" :loading="moving" @click="confirmMove" />
            </template>
        </Dialog>

        <DocumentViewer
            v-model:visible="showViewer"
            :document="viewerDoc"
            :preview-url="viewerDoc ? store.previewUrl(viewerDoc.id) : ''"
            :downloading="downloading"
            @download="download(viewerDoc)"
        />
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from 'vue';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Menu from 'primevue/menu';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import api from '@/services/api';
import DocumentViewer from '@/modules/documents/components/DocumentViewer.vue';
import FolderTreeNode from '@/modules/documents/components/FolderTreeNode.vue';
import { useDocumentsStore } from '@/modules/documents/stores/documents';
import { resolveOrganizationId } from '@/utils/organization';

const store = useDocumentsStore();
const toast = useToast();
const confirm = useConfirm();

const organizations = ref([]);
const selectedOrg = ref(store.organizationId);
const rows = ref([]);
const search = ref('');
const showUploadDialog = ref(false);
const showFolderDialog = ref(false);
const showRenameFolderDialog = ref(false);
const showMoveDialog = ref(false);
const showViewer = ref(false);
const viewerDoc = ref(null);
const moveDoc = ref(null);
const moveTargetFolder = ref('__root__');
const moving = ref(false);
const downloading = ref(false);
const pendingFiles = ref([]);
const uploadTitle = ref('');
const uploading = ref(false);
const folderName = ref('');
const renameFolderName = ref('');
const renameFolderTarget = ref(null);
const savingFolder = ref(false);
const fileInput = ref(null);
const docMenu = ref(null);
const menuDoc = ref(null);

const folderOptions = computed(() => {
    const options = [{ label: 'All / Root', value: '__root__' }];
    const walk = (folders, depth = 0) => {
        for (const folder of folders || []) {
            const lock = folder.is_locked ? ' 🔒' : '';
            options.push({
                label: `${'— '.repeat(depth)}${folder.name}${lock}`,
                value: folder.id,
                disabled: !!folder.is_locked,
            });
            walk(folder.children || [], depth + 1);
        }
    };
    walk(store.folderTree);
    return options;
});

const docMenuItems = computed(() => {
    const data = menuDoc.value;
    if (!data) return [];
    const locked = isFolderLockedDoc(data);
    return [
        {
            label: 'Submit for approval',
            icon: 'pi pi-send',
            visible: canSubmit(data),
            command: () => submitApproval(data),
        },
        {
            label: 'Move to folder',
            icon: 'pi pi-folder',
            disabled: locked,
            command: () => openMove(data),
        },
        {
            label: 'Check out',
            icon: 'pi pi-lock',
            disabled: locked,
            command: () => checkOut(data),
        },
        {
            label: 'Check in',
            icon: 'pi pi-lock-open',
            command: () => checkIn(data),
        },
        {
            label: 'Copy',
            icon: 'pi pi-copy',
            disabled: locked,
            command: () => copyDoc(data),
        },
        { separator: true },
        {
            label: 'Delete',
            icon: 'pi pi-trash',
            disabled: locked,
            class: 'text-red-600',
            command: () => remove(data),
        },
    ];
});

function humanize(value) {
    if (!value) return '—';
    return String(value)
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());
}

function approvalSeverity(status) {
    switch (status) {
        case 'approved':
            return 'success';
        case 'under_review':
        case 'pending':
            return 'warn';
        case 'rejected':
            return 'danger';
        case 'returned':
            return 'info';
        case 'draft':
            return 'secondary';
        default:
            return 'secondary';
    }
}

function typeTone(ext) {
    const e = String(ext || '').toLowerCase();
    if (e === 'pdf') return 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300';
    if (['doc', 'docx'].includes(e)) return 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300';
    if (['xls', 'xlsx', 'csv'].includes(e)) return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300';
    if (['ppt', 'pptx'].includes(e)) return 'bg-orange-50 text-orange-700 dark:bg-orange-950/40 dark:text-orange-300';
    if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(e)) return 'bg-violet-50 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300';
    if (['mp4', 'webm', 'mov'].includes(e)) return 'bg-cyan-50 text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-300';
    return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300';
}

function formatBytes(bytes) {
    const n = Number(bytes) || 0;
    if (n < 1024) return `${n} B`;
    if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`;
    return `${(n / (1024 * 1024)).toFixed(1)} MB`;
}

async function openDocMenu(event, data) {
    menuDoc.value = data;
    await nextTick();
    docMenu.value?.toggle(event);
}
async function loadOrgs() {
    const { data } = await api.get('/organizations', { params: { per_page: 100 } });
    organizations.value = data.data.data || data.data;
    selectedOrg.value = resolveOrganizationId(organizations.value, selectedOrg.value);
    if (selectedOrg.value) {
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
    loadDocuments();
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

async function toggleShowHidden() {
    store.showHiddenFolders = !store.showHiddenFolders;
    await store.loadFolders();
}

function openRenameFolder(folder) {
    renameFolderTarget.value = folder;
    renameFolderName.value = folder.name;
    showRenameFolderDialog.value = true;
}

async function confirmRenameFolder() {
    if (!renameFolderTarget.value || !renameFolderName.value.trim()) return;
    savingFolder.value = true;
    try {
        await store.renameFolder(renameFolderTarget.value.id, renameFolderName.value.trim());
        toast.add({ severity: 'success', summary: 'Folder renamed', life: 2000 });
        showRenameFolderDialog.value = false;
        renameFolderTarget.value = null;
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Rename failed',
            detail: error.response?.data?.message || error.response?.data?.errors?.folder?.[0] || 'Unable to rename',
            life: 4000,
        });
    } finally {
        savingFolder.value = false;
    }
}

async function toggleFolderLock(folder) {
    try {
        if (folder.is_locked) {
            await store.unlockFolder(folder.id);
            toast.add({ severity: 'success', summary: 'Folder unlocked', detail: 'Documents unlocked too', life: 2500 });
        } else {
            await store.lockFolder(folder.id);
            toast.add({ severity: 'success', summary: 'Folder locked', detail: 'All files in this folder are locked', life: 2500 });
        }
        if (store.currentFolderId === folder.id) {
            await loadDocuments();
        }
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Failed',
            detail: error.response?.data?.message || 'Unable to update lock',
            life: 4000,
        });
    }
}

async function toggleFolderHide(folder) {
    try {
        if (folder.is_hidden) {
            await store.unhideFolder(folder.id);
            toast.add({ severity: 'success', summary: 'Folder unhidden', detail: 'Documents unhidden too', life: 2500 });
        } else {
            await store.hideFolder(folder.id);
            toast.add({ severity: 'success', summary: 'Folder hidden', detail: 'All files in this folder are hidden', life: 2500 });
        }
        if (store.currentFolderId === folder.id || store.currentFolderId === null) {
            await loadDocuments();
        }
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Failed',
            detail: error.response?.data?.message || 'Unable to update visibility',
            life: 4000,
        });
    }
}

function confirmDeleteFolder(folder) {
    confirm.require({
        message: `Delete folder "${folder.name}"? It must be empty.`,
        header: 'Delete folder',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await store.deleteFolder(folder.id);
                toast.add({ severity: 'success', summary: 'Folder deleted', life: 2000 });
                await loadDocuments();
            } catch (error) {
                toast.add({
                    severity: 'error',
                    summary: 'Delete failed',
                    detail: error.response?.data?.message
                        || error.response?.data?.errors?.folder?.[0]
                        || 'Unable to delete folder',
                    life: 4500,
                });
            }
        },
    });
}

async function download(row) {
    if (!row) return;
    downloading.value = true;
    try {
        const token = localStorage.getItem('edams_token');
        const response = await fetch(store.downloadUrl(row.id), {
            headers: { Authorization: `Bearer ${token}`, Accept: 'application/octet-stream' },
        });
        if (!response.ok) {
            throw new Error('Download failed');
        }
        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = row.original_name || row.title;
        a.click();
        URL.revokeObjectURL(url);
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Download failed',
            detail: error.message || 'Unable to download',
            life: 4000,
        });
    } finally {
        downloading.value = false;
    }
}

function openViewer(row) {
    viewerDoc.value = row;
    showViewer.value = true;
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

function openMove(row) {
    moveDoc.value = row;
    moveTargetFolder.value = row.folder_id || '__root__';
    showMoveDialog.value = true;
}

async function confirmMove() {
    if (!moveDoc.value) return;
    moving.value = true;
    try {
        const folderId = moveTargetFolder.value === '__root__' ? null : moveTargetFolder.value;
        await store.move(moveDoc.value.id, folderId);
        toast.add({ severity: 'success', summary: 'Moved', detail: 'Document moved to selected folder', life: 2500 });
        showMoveDialog.value = false;
        moveDoc.value = null;
        await loadDocuments();
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Move failed',
            detail: error.response?.data?.message || 'Unable to move document',
            life: 4000,
        });
    } finally {
        moving.value = false;
    }
}

function canSubmit(row) {
    return ['draft', 'returned', 'rejected'].includes(row.approval_status) && !isFolderLockedDoc(row);
}

function isFolderLockedDoc(row) {
    return !!row.is_locked && !row.checked_out_by;
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

<style scoped>
.docs-table :deep(.p-datatable-tbody > tr > td) {
    padding-top: 0.55rem;
    padding-bottom: 0.55rem;
    vertical-align: middle;
}

.docs-table :deep(.p-datatable-thead > tr > th) {
    padding-top: 0.65rem;
    padding-bottom: 0.65rem;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #64748b;
}

.docs-table :deep(.p-datatable-tbody > tr > td .p-button) {
    width: 2rem;
    height: 2rem;
}
</style>
