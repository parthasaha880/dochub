<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold text-slate-900 dark:text-white">Operations</h1>
                <p class="mt-1 text-sm text-slate-500">Audit, notifications, sharing, retention, and reports</p>
            </div>
            <Select
                v-model="orgId"
                :options="organizations"
                option-label="name"
                option-value="id"
                placeholder="Organization"
                class="w-56"
                @change="reloadAll"
            />
        </div>

        <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
            <TabView>
                <TabPanel header="Audit">
                    <div class="mb-3 flex flex-wrap gap-2">
                        <InputText v-model="auditSearch" placeholder="Search audit…" class="w-64" @keyup.enter="loadAudit" />
                        <Button icon="pi pi-search" @click="loadAudit" />
                    </div>
                    <DataTable :value="auditRows" :loading="loading.audit" size="small" striped-rows paginator :rows="15">
                        <Column field="created_at" header="When">
                            <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
                        </Column>
                        <Column field="module" header="Module" />
                        <Column field="action" header="Action" />
                        <Column field="description" header="Description" />
                        <Column header="User">
                            <template #body="{ data }">{{ data.user?.name || '—' }}</template>
                        </Column>
                        <Column field="ip_address" header="IP" />
                    </DataTable>
                </TabPanel>

                <TabPanel header="Notifications">
                    <div class="mb-3 flex gap-2">
                        <Button label="Mark all read" outlined size="small" @click="markAllRead" />
                        <Tag :value="`${unread} unread`" :severity="unread ? 'warn' : 'success'" />
                    </div>
                    <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                        <li v-for="n in notifications" :key="n.id" class="flex items-start justify-between gap-3 py-3 text-sm">
                            <div>
                                <p class="font-medium" :class="!n.read_at ? 'text-brand-700 dark:text-brand-200' : ''">
                                    {{ n.data?.title || n.type }}
                                </p>
                                <p class="text-slate-500">{{ n.data?.message }}</p>
                                <p class="text-xs text-slate-400">{{ formatDate(n.created_at) }}</p>
                            </div>
                            <Button v-if="!n.read_at" label="Read" size="small" text @click="markRead(n)" />
                        </li>
                    </ul>
                    <p v-if="!notifications.length" class="text-sm text-slate-500">No notifications.</p>
                </TabPanel>

                <TabPanel header="Sharing">
                    <div class="mb-3 flex justify-end">
                        <Button label="New share link" icon="pi pi-link" @click="showShare = true" />
                    </div>
                    <DataTable :value="shares" size="small" striped-rows paginator :rows="10">
                        <Column header="Document">
                            <template #body="{ data }">{{ data.document?.title }}</template>
                        </Column>
                        <Column field="share_type" header="Type" />
                        <Column field="download_count" header="Downloads" />
                        <Column header="Expires">
                            <template #body="{ data }">{{ formatDate(data.expires_at) }}</template>
                        </Column>
                        <Column header="Active">
                            <template #body="{ data }">
                                <Tag :value="data.is_active ? 'Active' : 'Revoked'" :severity="data.is_active ? 'success' : 'secondary'" />
                            </template>
                        </Column>
                        <Column header="Link">
                            <template #body="{ data }">
                                <Button icon="pi pi-copy" text rounded @click="copy(data.url)" />
                                <Button v-if="data.is_active" icon="pi pi-ban" text rounded severity="danger" @click="revokeShare(data)" />
                            </template>
                        </Column>
                    </DataTable>
                </TabPanel>

                <TabPanel header="Retention">
                    <div class="mb-3 flex flex-wrap gap-2">
                        <Button label="New policy" icon="pi pi-plus" @click="showRetention = true" />
                        <Button label="Run now" icon="pi pi-play" outlined @click="runRetention" />
                    </div>
                    <DataTable :value="policies" size="small" striped-rows class="mb-6">
                        <Column field="name" header="Name" />
                        <Column field="code" header="Code" />
                        <Column field="retention_days" header="Days" />
                        <Column field="action_on_expiry" header="Action" />
                        <Column header="Active">
                            <template #body="{ data }">
                                <Tag :value="data.is_active ? 'Yes' : 'Off'" :severity="data.is_active ? 'success' : 'secondary'" />
                            </template>
                        </Column>
                        <Column header="">
                            <template #body="{ data }">
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="deletePolicy(data)" />
                            </template>
                        </Column>
                    </DataTable>
                    <h3 class="mb-2 text-sm font-semibold">Recent runs</h3>
                    <DataTable :value="runs" size="small" striped-rows>
                        <Column header="Started">
                            <template #body="{ data }">{{ formatDate(data.started_at) }}</template>
                        </Column>
                        <Column field="processed" header="Processed" />
                        <Column field="archived" header="Archived" />
                        <Column field="soft_deleted" header="Deleted" />
                        <Column field="flagged" header="Flagged" />
                        <Column field="status" header="Status" />
                    </DataTable>
                </TabPanel>

                <TabPanel header="Reports">
                    <div class="mb-3 flex flex-wrap gap-2">
                        <Select v-model="reportType" :options="reportTypes" option-label="label" option-value="value" class="w-56" />
                        <Button label="Preview" @click="previewReport" />
                        <Button label="Export CSV" outlined @click="exportReport" />
                    </div>
                    <p class="mb-2 text-sm text-slate-500">{{ reportPreview?.count ?? 0 }} rows (showing up to 100)</p>
                    <DataTable :value="reportPreview?.rows || []" size="small" striped-rows paginator :rows="10" scrollable>
                        <Column v-for="col in reportColumns" :key="col" :field="col" :header="col" />
                    </DataTable>
                </TabPanel>
            </TabView>
        </div>

        <Dialog v-model:visible="showShare" modal header="Create share link" class="w-full max-w-md">
            <div class="space-y-3">
                <Select
                    v-model="shareForm.document_id"
                    :options="documents"
                    option-label="title"
                    option-value="id"
                    placeholder="Document"
                    filter
                    class="w-full"
                />
                <Select
                    v-model="shareForm.share_type"
                    :options="[{label:'External',value:'external'},{label:'Internal',value:'internal'}]"
                    option-label="label"
                    option-value="value"
                    class="w-full"
                />
                <InputText v-model="shareForm.label" class="w-full" placeholder="Label (optional)" />
                <InputText v-model="shareForm.password" type="password" class="w-full" placeholder="Password (optional)" />
                <InputText v-model="shareForm.expires_at" type="datetime-local" class="w-full" />
            </div>
            <template #footer>
                <Button label="Cancel" text @click="showShare = false" />
                <Button label="Create" @click="createShare" />
            </template>
        </Dialog>

        <Dialog v-model:visible="showRetention" modal header="Retention policy" class="w-full max-w-md">
            <div class="space-y-3">
                <InputText v-model="policyForm.name" class="w-full" placeholder="Name" />
                <InputText v-model="policyForm.code" class="w-full" placeholder="Code" />
                <InputText v-model.number="policyForm.retention_days" type="number" class="w-full" placeholder="Retention days" />
                <Select
                    v-model="policyForm.action_on_expiry"
                    :options="[{label:'Archive',value:'archive'},{label:'Soft delete',value:'soft_delete'},{label:'Flag',value:'flag'}]"
                    option-label="label"
                    option-value="value"
                    class="w-full"
                />
            </div>
            <template #footer>
                <Button label="Cancel" text @click="showRetention = false" />
                <Button label="Save" @click="createPolicy" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import TabPanel from 'primevue/tabpanel';
import TabView from 'primevue/tabview';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import api from '@/services/api';
import { resolveOrganizationId } from '@/utils/organization';

const toast = useToast();
const organizations = ref([]);
const orgId = ref(localStorage.getItem('edams_org_id') || null);
const loading = reactive({ audit: false });
const auditRows = ref([]);
const auditSearch = ref('');
const notifications = ref([]);
const unread = ref(0);
const shares = ref([]);
const documents = ref([]);
const policies = ref([]);
const runs = ref([]);
const showShare = ref(false);
const showRetention = ref(false);
const shareForm = reactive({
    document_id: null,
    share_type: 'external',
    label: '',
    password: '',
    expires_at: '',
});
const policyForm = reactive({
    name: '',
    code: '',
    retention_days: 365,
    action_on_expiry: 'archive',
});
const reportType = ref('inventory');
const reportTypes = [
    { label: 'Document inventory', value: 'inventory' },
    { label: 'Workflow summary', value: 'workflow' },
    { label: 'Audit trail', value: 'audit' },
    { label: 'Share links', value: 'shares' },
];
const reportPreview = ref(null);
const reportColumns = computed(() => (reportPreview.value?.rows?.[0] ? Object.keys(reportPreview.value.rows[0]) : []));

function formatDate(v) {
    return v ? new Date(v).toLocaleString() : '—';
}

async function loadOrgs() {
    const { data } = await api.get('/organizations', { params: { per_page: 100 } });
    organizations.value = data.data.data || data.data;
    orgId.value = resolveOrganizationId(organizations.value, orgId.value);
    if (orgId.value) {
        localStorage.setItem('edams_org_id', orgId.value);
    }
}

async function loadAudit() {
    loading.audit = true;
    try {
        const { data } = await api.get('/audit-logs', {
            params: { organization_id: orgId.value, search: auditSearch.value || undefined, per_page: 50 },
        });
        auditRows.value = data.data.data || [];
    } finally {
        loading.audit = false;
    }
}

async function loadNotifications() {
    const { data } = await api.get('/notifications', { params: { per_page: 30 } });
    notifications.value = data.data.data || [];
    unread.value = data.data.unread_count || 0;
}

async function markRead(n) {
    await api.post(`/notifications/${n.id}/read`);
    await loadNotifications();
}

async function markAllRead() {
    await api.post('/notifications/read-all');
    await loadNotifications();
}

async function loadShares() {
    const { data } = await api.get('/shares', { params: { organization_id: orgId.value } });
    shares.value = data.data.data || [];
}

async function loadDocuments() {
    const { data } = await api.get('/documents', { params: { organization_id: orgId.value, per_page: 100 } });
    const payload = data.data;
    documents.value = payload.data || payload;
}

async function createShare() {
    try {
        const payload = {
            document_id: shareForm.document_id,
            share_type: shareForm.share_type,
            label: shareForm.label || undefined,
            password: shareForm.password || undefined,
            expires_at: shareForm.expires_at ? new Date(shareForm.expires_at).toISOString() : undefined,
        };
        await api.post('/shares', payload);
        toast.add({ severity: 'success', summary: 'Share created', life: 2500 });
        showShare.value = false;
        await loadShares();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Failed', detail: e.response?.data?.message, life: 4000 });
    }
}

async function revokeShare(row) {
    await api.post(`/shares/${row.id}/revoke`);
    await loadShares();
}

function copy(text) {
    navigator.clipboard.writeText(text);
    toast.add({ severity: 'success', summary: 'Link copied', life: 1500 });
}

async function loadRetention() {
    const [p, r] = await Promise.all([
        api.get('/retention/policies', { params: { organization_id: orgId.value } }),
        api.get('/retention/runs', { params: { organization_id: orgId.value } }),
    ]);
    policies.value = p.data.data.data || p.data.data;
    runs.value = r.data.data;
}

async function createPolicy() {
    try {
        await api.post('/retention/policies', { ...policyForm, organization_id: orgId.value });
        showRetention.value = false;
        await loadRetention();
        toast.add({ severity: 'success', summary: 'Policy created', life: 2000 });
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Failed', detail: e.response?.data?.message, life: 4000 });
    }
}

async function deletePolicy(row) {
    await api.delete(`/retention/policies/${row.id}`);
    await loadRetention();
}

async function runRetention() {
    const { data } = await api.post('/retention/run', { organization_id: orgId.value });
    toast.add({
        severity: 'success',
        summary: 'Retention completed',
        detail: `Processed ${data.data.processed}`,
        life: 3000,
    });
    await loadRetention();
}

async function previewReport() {
    const { data } = await api.get('/reports/preview', {
        params: { organization_id: orgId.value, type: reportType.value, days: 30 },
    });
    reportPreview.value = data.data;
}

async function exportReport() {
    const token = localStorage.getItem('edams_token');
    const qs = new URLSearchParams({
        organization_id: orgId.value,
        type: reportType.value,
        days: '30',
    });
    const res = await fetch(`/api/v1/reports/export?${qs}`, {
        headers: { Authorization: `Bearer ${token}` },
    });
    const blob = await res.blob();
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `edams-${reportType.value}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}

async function reloadAll() {
    if (!orgId.value) return;
    localStorage.setItem('edams_org_id', orgId.value);
    await Promise.all([loadAudit(), loadNotifications(), loadShares(), loadDocuments(), loadRetention()]);
}

onMounted(async () => {
    await loadOrgs();
    await reloadAll();
});
</script>
