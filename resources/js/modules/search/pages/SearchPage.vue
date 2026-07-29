<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold text-slate-900 dark:text-white">Search</h1>
                <p class="mt-1 text-sm text-slate-500">FULLTEXT document search, structured filters, and saved queries</p>
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

        <div class="grid gap-6 xl:grid-cols-[280px_1fr]">
            <aside class="space-y-4">
                <section class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950">
                    <h2 class="mb-3 text-sm font-semibold">Filters</h2>
                    <div class="space-y-3">
                        <div>
                            <label class="mb-1 block text-xs text-slate-500">Approval</label>
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
                            <label class="mb-1 block text-xs text-slate-500">Status</label>
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
                            <label class="mb-1 block text-xs text-slate-500">Confidentiality</label>
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
                            <label class="mb-1 block text-xs text-slate-500">Extension</label>
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
                        <div>
                            <label class="mb-1 block text-xs text-slate-500">Created from</label>
                            <InputText v-model="filters.created_from" type="date" class="w-full" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-slate-500">Created to</label>
                            <InputText v-model="filters.created_to" type="date" class="w-full" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-slate-500">Tags (comma)</label>
                            <InputText v-model="tagsInput" class="w-full" placeholder="policy, archive" />
                        </div>
                        <Button label="Apply filters" class="w-full" @click="runSearch" />
                        <Button label="Clear" class="w-full" outlined severity="secondary" @click="clearFilters" />
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold">Saved searches</h2>
                        <Button icon="pi pi-plus" text rounded size="small" v-tooltip="'Save current'" @click="openSave" />
                    </div>
                    <ul v-if="store.saved.length" class="space-y-2">
                        <li
                            v-for="item in store.saved"
                            :key="item.id"
                            class="flex items-start justify-between gap-2 rounded-md px-2 py-2 hover:bg-slate-50 dark:hover:bg-slate-900"
                        >
                            <button type="button" class="min-w-0 flex-1 text-left text-sm" @click="applySaved(item)">
                                <p class="truncate font-medium">{{ item.name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ item.is_shared ? 'Shared' : 'Private' }}</p>
                            </button>
                            <Button icon="pi pi-trash" text rounded size="small" severity="danger" @click="removeSaved(item)" />
                        </li>
                    </ul>
                    <p v-else class="text-sm text-slate-500">No saved searches yet.</p>
                </section>
            </aside>

            <section class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950">
                <div class="mb-4 flex flex-wrap gap-2">
                    <InputText
                        v-model="filters.q"
                        placeholder="Search title, reference, keywords…"
                        class="min-w-[16rem] flex-1"
                        @keyup.enter="runSearch"
                    />
                    <Button icon="pi pi-search" label="Search" :loading="store.loading" @click="runSearch" />
                </div>

                <DataTable :value="store.results" :loading="store.loading" striped-rows paginator :rows="15" size="small">
                    <Column field="title" header="Title">
                        <template #body="{ data }">
                            <div>
                                <p class="font-medium">{{ data.title }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ data.reference_no || '—' }}
                                    <span v-if="data.relevance != null"> · score {{ Number(data.relevance).toFixed(2) }}</span>
                                </p>
                            </div>
                        </template>
                    </Column>
                    <Column field="extension" header="Type" />
                    <Column field="approval_status" header="Approval" />
                    <Column field="confidentiality_level" header="Class" />
                    <Column header="Folder">
                        <template #body="{ data }">{{ data.folder?.name || 'Root' }}</template>
                    </Column>
                    <Column header="Actions" style="width: 6rem">
                        <template #body="{ data }">
                            <Button
                                icon="pi pi-folder-open"
                                text
                                rounded
                                v-tooltip="'Open in documents'"
                                @click="goDocuments(data)"
                            />
                        </template>
                    </Column>
                </DataTable>
                <p v-if="!store.loading && !store.results.length" class="mt-4 text-sm text-slate-500">No matches. Try fewer filters or different keywords.</p>
            </section>
        </div>

        <Dialog v-model:visible="showSave" modal header="Save search" class="w-full max-w-md">
            <div class="space-y-3">
                <InputText v-model="saveForm.name" class="w-full" placeholder="Name" />
                <Textarea v-model="saveForm.description" rows="2" class="w-full" placeholder="Description (optional)" />
                <div class="flex items-center gap-2">
                    <Checkbox v-model="saveForm.is_shared" binary input-id="share-search" />
                    <label for="share-search" class="text-sm">Share with organization</label>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" text @click="showSave = false" />
                <Button label="Save" :loading="saving" @click="saveCurrent" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import api from '@/services/api';
import { useSearchStore } from '@/modules/search/stores/search';

const store = useSearchStore();
const toast = useToast();
const confirm = useConfirm();
const router = useRouter();

const organizations = ref([]);
const selectedOrg = ref(store.organizationId);
const tagsInput = ref('');
const showSave = ref(false);
const saving = ref(false);
const saveForm = reactive({ name: '', description: '', is_shared: false });

const filters = reactive({
    q: '',
    approval_status: null,
    status: null,
    confidentiality_level: null,
    extension: null,
    created_from: '',
    created_to: '',
});

function facetOptions(rows) {
    return (rows || []).map((r) => ({ label: `${r.label} (${r.value})`, value: r.label }));
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
        header: 'Confirm',
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

async function loadOrgs() {
    const { data } = await api.get('/organizations', { params: { per_page: 100 } });
    organizations.value = data.data.data || data.data;
    if (!selectedOrg.value && organizations.value.length) {
        selectedOrg.value = organizations.value[0].id;
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
