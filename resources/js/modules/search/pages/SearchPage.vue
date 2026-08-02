<template>
    <div class="space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="font-display text-2xl font-semibold tracking-tight text-slate-900 dark:text-white sm:text-3xl">Search</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Find documents with keywords, filters, and saved queries</p>
            </div>
            <Select
                v-model="selectedOrg"
                :options="organizations"
                option-label="name"
                option-value="id"
                placeholder="Organization"
                class="w-56"
                @change="onOrgChange"
            />
        </div>

        <div class="grid gap-5 xl:grid-cols-[270px_1fr]">
            <aside class="space-y-4">
                <section class="rounded-xl border border-slate-200/90 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Filters</h2>
                    <div class="space-y-3">
                        <div>
                            <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Approval</label>
                            <Select
                                v-model="filters.approval_status"
                                :options="facetOptions(store.facets?.approval_status)"
                                option-label="label"
                                option-value="value"
                                placeholder="Any"
                                show-clear
                                class="w-full"
                            />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</label>
                            <Select
                                v-model="filters.status"
                                :options="facetOptions(store.facets?.status)"
                                option-label="label"
                                option-value="value"
                                placeholder="Any"
                                show-clear
                                class="w-full"
                            />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Confidentiality</label>
                            <Select
                                v-model="filters.confidentiality_level"
                                :options="facetOptions(store.facets?.confidentiality)"
                                option-label="label"
                                option-value="value"
                                placeholder="Any"
                                show-clear
                                class="w-full"
                            />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Extension</label>
                            <Select
                                v-model="filters.extension"
                                :options="facetOptions(store.facets?.extension)"
                                option-label="label"
                                option-value="value"
                                placeholder="Any"
                                show-clear
                                class="w-full"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">From</label>
                                <InputText v-model="filters.created_from" type="date" class="w-full" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">To</label>
                                <InputText v-model="filters.created_to" type="date" class="w-full" />
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Tags</label>
                            <InputText v-model="tagsInput" class="w-full" placeholder="policy, archive" />
                        </div>
                        <div class="flex gap-2 pt-1">
                            <Button label="Apply" icon="pi pi-filter" class="flex-1" size="small" @click="runSearch" />
                            <Button label="Clear" outlined severity="secondary" size="small" @click="clearFilters" />
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200/90 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Saved searches</h2>
                        <Button
                            icon="pi pi-plus"
                            text
                            rounded
                            size="small"
                            v-tooltip.bottom="'Save current search'"
                            @click="openSave"
                        />
                    </div>
                    <ul v-if="store.saved.length" class="space-y-1">
                        <li
                            v-for="item in store.saved"
                            :key="item.id"
                            class="group flex items-start justify-between gap-2 rounded-lg px-2.5 py-2 transition hover:bg-slate-50 dark:hover:bg-slate-900"
                        >
                            <button type="button" class="min-w-0 flex-1 text-left" @click="applySaved(item)">
                                <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-100">{{ item.name }}</p>
                                <p class="mt-0.5 truncate text-xs text-slate-500">
                                    <i :class="item.is_shared ? 'pi pi-users' : 'pi pi-lock'" class="mr-1 text-[10px]" />
                                    {{ item.is_shared ? 'Shared' : 'Private' }}
                                </p>
                            </button>
                            <Button
                                icon="pi pi-trash"
                                text
                                rounded
                                size="small"
                                severity="danger"
                                class="opacity-0 transition group-hover:opacity-100"
                                @click="removeSaved(item)"
                            />
                        </li>
                    </ul>
                    <div v-else class="rounded-lg bg-slate-50 px-3 py-6 text-center dark:bg-slate-900/50">
                        <i class="pi pi-bookmark mb-2 text-lg text-slate-300 dark:text-slate-600" />
                        <p class="text-xs text-slate-500">No saved searches yet</p>
                    </div>
                </section>
            </aside>

            <section class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
                <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800 sm:px-5">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="relative min-w-[14rem] flex-1">
                            <i class="pi pi-search pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-slate-400" />
                            <InputText
                                v-model="filters.q"
                                placeholder="Search title, reference, keywords…"
                                class="w-full pl-9"
                                @keyup.enter="runSearch"
                            />
                        </span>
                        <Button icon="pi pi-search" label="Search" size="small" :loading="store.loading" @click="runSearch" />
                    </div>
                    <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs text-slate-500">
                            <template v-if="store.loading">Searching…</template>
                            <template v-else-if="resultCount != null">
                                <span class="font-medium text-slate-700 dark:text-slate-200">{{ resultCount.toLocaleString() }}</span>
                                {{ resultCount === 1 ? 'result' : 'results' }}
                                <span v-if="filters.q" class="text-slate-400"> for “{{ filters.q }}”</span>
                            </template>
                            <template v-else>Enter keywords or apply filters</template>
                        </p>
                    </div>
                </div>

                <div class="p-3 sm:p-4">
                    <DataTable
                        :value="store.results"
                        :loading="store.loading"
                        paginator
                        :rows="15"
                        size="small"
                        class="search-table text-sm"
                        :pt="{
                            thead: { class: 'bg-slate-50 dark:bg-slate-900/60' },
                        }"
                    >
                        <template #empty>
                            <div class="py-12 text-center">
                                <i class="pi pi-search mb-3 text-3xl text-slate-300 dark:text-slate-600" />
                                <p class="text-sm font-medium text-slate-600 dark:text-slate-300">No matches found</p>
                                <p class="mt-1 text-xs text-slate-400">Try fewer filters or different keywords</p>
                            </div>
                        </template>

                        <Column field="title" header="Title">
                            <template #body="{ data }">
                                <button type="button" class="max-w-md text-left" @click="openViewer(data)">
                                    <p class="truncate font-medium text-brand-700 hover:underline dark:text-brand-100">
                                        {{ data.title }}
                                    </p>
                                    <p class="mt-0.5 truncate text-xs text-slate-500">
                                        {{ data.reference_no || 'No reference' }}
                                        <span v-if="data.relevance != null" class="text-slate-400">
                                            · {{ Number(data.relevance).toFixed(2) }}
                                        </span>
                                    </p>
                                </button>
                            </template>
                        </Column>
                        <Column field="extension" header="Type" style="width: 5.5rem">
                            <template #body="{ data }">
                                <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-medium uppercase text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                    {{ data.extension || '—' }}
                                </span>
                            </template>
                        </Column>
                        <Column field="approval_status" header="Approval" style="width: 8rem">
                            <template #body="{ data }">
                                <Tag
                                    :value="formatLabel(data.approval_status)"
                                    :severity="approvalSeverity(data.approval_status)"
                                    rounded
                                    class="text-xs"
                                />
                            </template>
                        </Column>
                        <Column field="confidentiality_level" header="Class" style="width: 7rem">
                            <template #body="{ data }">
                                <span class="text-xs capitalize text-slate-600 dark:text-slate-300">
                                    {{ formatLabel(data.confidentiality_level) || '—' }}
                                </span>
                            </template>
                        </Column>
                        <Column header="Folder" style="width: 9rem">
                            <template #body="{ data }">
                                <span class="inline-flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-300">
                                    <i class="pi pi-folder text-[11px] text-slate-400" />
                                    {{ data.folder?.name || 'Root' }}
                                </span>
                            </template>
                        </Column>
                        <Column header="" style="width: 6.5rem">
                            <template #body="{ data }">
                                <div class="flex justify-end gap-0.5">
                                    <Button
                                        icon="pi pi-eye"
                                        text
                                        rounded
                                        size="small"
                                        severity="secondary"
                                        v-tooltip.top="'View'"
                                        @click="openViewer(data)"
                                    />
                                    <Button
                                        icon="pi pi-folder-open"
                                        text
                                        rounded
                                        size="small"
                                        severity="secondary"
                                        v-tooltip.top="'Open in documents'"
                                        @click="goDocuments(data)"
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </section>
        </div>

        <Dialog
            v-model:visible="showSave"
            modal
            header="Save search"
            class="w-full max-w-md"
            :pt="{ header: { class: 'border-b border-slate-100 dark:border-slate-800' } }"
        >
            <div class="space-y-4 py-1">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Name</label>
                    <InputText v-model="saveForm.name" class="w-full" placeholder="e.g. Approved policies" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Description</label>
                    <Textarea v-model="saveForm.description" rows="2" class="w-full" placeholder="Optional notes" />
                </div>
                <div class="flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2.5 dark:bg-slate-900/50">
                    <Checkbox v-model="saveForm.is_shared" binary input-id="share-search" />
                    <label for="share-search" class="text-sm text-slate-600 dark:text-slate-300">Share with organization</label>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" text severity="secondary" @click="showSave = false" />
                <Button label="Save" icon="pi pi-check" :loading="saving" @click="saveCurrent" />
            </template>
        </Dialog>

        <DocumentViewer
            v-model:visible="showViewer"
            :document="viewerDoc"
            :preview-url="viewerDoc ? `/api/v1/documents/${viewerDoc.id}/preview` : ''"
            :downloading="downloading"
            @download="downloadViewerDoc"
        />
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import api from '@/services/api';
import DocumentViewer from '@/modules/documents/components/DocumentViewer.vue';
import { useSearchStore } from '@/modules/search/stores/search';
import { resolveOrganizationId } from '@/utils/organization';

const store = useSearchStore();
const toast = useToast();
const confirm = useConfirm();
const router = useRouter();

const organizations = ref([]);
const selectedOrg = ref(store.organizationId);
const tagsInput = ref('');
const showSave = ref(false);
const saving = ref(false);
const showViewer = ref(false);
const viewerDoc = ref(null);
const downloading = ref(false);
const saveForm = reactive({ name: '', description: '', is_shared: false });
const hasSearched = ref(false);

const filters = reactive({
    q: '',
    approval_status: null,
    status: null,
    confidentiality_level: null,
    extension: null,
    created_from: '',
    created_to: '',
});

const resultCount = computed(() => {
    if (!hasSearched.value) return null;
    if (store.meta?.total != null) return Number(store.meta.total);
    return store.results.length;
});

function facetOptions(rows) {
    return (rows || []).map((r) => ({ label: `${r.label} (${r.value})`, value: r.label }));
}

function formatLabel(value) {
    if (!value) return '';
    return String(value).replaceAll('_', ' ');
}

function approvalSeverity(value) {
    const map = {
        approved: 'success',
        draft: 'secondary',
        under_review: 'warn',
        rejected: 'danger',
        cancelled: 'secondary',
    };
    return map[value] || 'info';
}

function currentCriteria() {
    const tags = tagsInput.value
        .split(',')
        .map((t) => t.trim())
        .filter(Boolean);

    return {
        q: filters.q || undefined,
        approval_status: filters.approval_status || undefined,
        status: filters.status || undefined,
        confidentiality_level: filters.confidentiality_level || undefined,
        extension: filters.extension || undefined,
        created_from: filters.created_from || undefined,
        created_to: filters.created_to || undefined,
        tags: tags.length ? tags : undefined,
    };
}

async function runSearch() {
    try {
        await store.search({ ...currentCriteria(), per_page: 50 });
        hasSearched.value = true;
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'Search failed',
            detail: e.response?.data?.message || e.message,
            life: 4000,
        });
    }
}

function clearFilters() {
    filters.q = '';
    filters.approval_status = null;
    filters.status = null;
    filters.confidentiality_level = null;
    filters.extension = null;
    filters.created_from = '';
    filters.created_to = '';
    tagsInput.value = '';
    runSearch();
}

async function onOrgChange() {
    store.setOrganization(selectedOrg.value);
    await store.loadFacets();
    await store.loadSaved();
    await runSearch();
}

function openSave() {
    saveForm.name = filters.q ? `Search: ${filters.q}` : 'Untitled search';
    saveForm.description = '';
    saveForm.is_shared = false;
    showSave.value = true;
}

async function saveCurrent() {
    saving.value = true;
    try {
        await store.createSaved({
            name: saveForm.name,
            description: saveForm.description || null,
            is_shared: saveForm.is_shared,
            criteria: {
                organization_id: selectedOrg.value,
                ...currentCriteria(),
            },
        });
        toast.add({ severity: 'success', summary: 'Search saved', life: 2500 });
        showSave.value = false;
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'Could not save',
            detail: e.response?.data?.message || e.message,
            life: 4000,
        });
    } finally {
        saving.value = false;
    }
}

function applySaved(item) {
    const c = item.criteria || {};
    filters.q = c.q || '';
    filters.approval_status = c.approval_status || null;
    filters.status = c.status || null;
    filters.confidentiality_level = c.confidentiality_level || null;
    filters.extension = c.extension || null;
    filters.created_from = c.created_from || '';
    filters.created_to = c.created_to || '';
    tagsInput.value = Array.isArray(c.tags) ? c.tags.join(', ') : '';
    runSearch();
}

function removeSaved(item) {
    confirm.require({
        message: `Delete saved search “${item.name}”?`,
        header: 'Confirm delete',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await store.deleteSaved(item.id);
                toast.add({ severity: 'success', summary: 'Deleted', life: 2000 });
            } catch (e) {
                toast.add({
                    severity: 'error',
                    summary: 'Delete failed',
                    detail: e.response?.data?.message || e.message,
                    life: 4000,
                });
            }
        },
    });
}

function goDocuments(row) {
    router.push({ name: 'documents', query: { highlight: row.id } });
}

function openViewer(row) {
    viewerDoc.value = row;
    showViewer.value = true;
}

async function downloadViewerDoc() {
    if (!viewerDoc.value) return;
    downloading.value = true;
    try {
        const token = localStorage.getItem('edams_token');
        const response = await fetch(`/api/v1/documents/${viewerDoc.value.id}/download`, {
            headers: { Authorization: `Bearer ${token}`, Accept: 'application/octet-stream' },
        });
        if (!response.ok) throw new Error('Download failed');
        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = viewerDoc.value.original_name || viewerDoc.value.title;
        a.click();
        URL.revokeObjectURL(url);
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Download failed', detail: e.message, life: 4000 });
    } finally {
        downloading.value = false;
    }
}

async function loadOrgs() {
    const { data } = await api.get('/organizations', { params: { per_page: 100 } });
    organizations.value = data.data.data || data.data;
    selectedOrg.value = resolveOrganizationId(organizations.value, selectedOrg.value);
    if (selectedOrg.value) {
        store.setOrganization(selectedOrg.value);
    }
}

onMounted(async () => {
    await loadOrgs();
    if (selectedOrg.value) {
        store.setOrganization(selectedOrg.value);
        await store.loadFacets();
        await store.loadSaved();
        await runSearch();
    }
});
</script>

<style scoped>
.search-table :deep(.p-datatable-thead > tr > th) {
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: rgb(100 116 139);
    padding-top: 0.7rem;
    padding-bottom: 0.7rem;
}

.search-table :deep(.p-datatable-tbody > tr > td) {
    padding-top: 0.7rem;
    padding-bottom: 0.7rem;
    border-color: rgb(241 245 249);
}

:global(.dark) .search-table :deep(.p-datatable-tbody > tr > td) {
    border-color: rgb(30 41 59);
}

.search-table :deep(.p-datatable-tbody > tr:hover) {
    background: rgb(248 250 252);
}

:global(.dark) .search-table :deep(.p-datatable-tbody > tr:hover) {
    background: rgb(15 23 42);
}
</style>
