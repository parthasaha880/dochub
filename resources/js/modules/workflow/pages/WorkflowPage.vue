<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold text-slate-900 dark:text-white">Workflow</h1>
                <p class="mt-1 text-sm text-slate-500">Multi-level sequential approvals, inbox, and history</p>
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

        <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
            <TabView @tab-change="onTabChange">
                <TabPanel header="Approval inbox">
                    <div class="mb-3 flex gap-2">
                        <InputText v-model="inboxSearch" placeholder="Search documents..." class="w-64" @keyup.enter="loadInbox" />
                        <Button icon="pi pi-search" @click="loadInbox" />
                    </div>
                    <DataTable :value="inboxRows" :loading="store.loading" striped-rows paginator :rows="10" size="small">
                        <Column field="document.title" header="Document" />
                        <Column field="workflow.name" header="Workflow" />
                        <Column field="current_step.name" header="Current step" />
                        <Column field="submitter.name" header="Submitted by" />
                        <Column header="Submitted">
                            <template #body="{ data }">{{ formatDate(data.submitted_at) }}</template>
                        </Column>
                        <Column header="Actions" style="width: 12rem">
                            <template #body="{ data }">
                                <Button icon="pi pi-check" text rounded severity="success" v-tooltip="'Approve'" @click="act(data, 'approve')" />
                                <Button icon="pi pi-times" text rounded severity="danger" v-tooltip="'Reject'" @click="act(data, 'reject')" />
                                <Button icon="pi pi-replay" text rounded severity="warn" v-tooltip="'Return'" @click="act(data, 'return')" />
                                <Button icon="pi pi-eye" text rounded @click="openDetail(data)" />
                            </template>
                        </Column>
                    </DataTable>
                </TabPanel>

                <TabPanel header="Workflow definitions">
                    <div class="mb-3 flex justify-end">
                        <Button label="New workflow" icon="pi pi-plus" @click="openCreate" />
                    </div>
                    <DataTable :value="workflowRows" :loading="store.loading" striped-rows paginator :rows="10" size="small">
                        <Column field="name" header="Name" />
                        <Column field="code" header="Code" />
                        <Column header="Steps">
                            <template #body="{ data }">{{ data.steps?.length || 0 }}</template>
                        </Column>
                        <Column header="Default">
                            <template #body="{ data }">
                                <Tag v-if="data.is_default" value="Default" severity="info" />
                                <span v-else class="text-slate-400">—</span>
                            </template>
                        </Column>
                        <Column header="Active">
                            <template #body="{ data }">
                                <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" />
                            </template>
                        </Column>
                        <Column header="Actions" style="width: 8rem">
                            <template #body="{ data }">
                                <Button icon="pi pi-pencil" text rounded @click="openEdit(data)" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="removeWorkflow(data)" />
                            </template>
                        </Column>
                    </DataTable>
                </TabPanel>

                <TabPanel header="History">
                    <DataTable :value="historyRows" striped-rows paginator :rows="10" size="small">
                        <Column field="document.title" header="Document" />
                        <Column field="workflow.name" header="Workflow" />
                        <Column field="status" header="Status" />
                        <Column field="submitter.name" header="Submitter" />
                        <Column header="Submitted">
                            <template #body="{ data }">{{ formatDate(data.submitted_at) }}</template>
                        </Column>
                        <Column header="">
                            <template #body="{ data }">
                                <Button icon="pi pi-eye" text rounded @click="openDetail(data)" />
                            </template>
                        </Column>
                    </DataTable>
                </TabPanel>
            </TabView>
        </div>

        <Dialog v-model:visible="showEditor" modal :header="editingId ? 'Edit workflow' : 'New workflow'" class="w-full max-w-3xl">
            <div class="space-y-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Name</label>
                        <InputText v-model="form.name" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Code</label>
                        <InputText v-model="form.code" class="w-full" :disabled="!!editingId" />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Description</label>
                    <Textarea v-model="form.description" rows="2" class="w-full" />
                </div>
                <div class="flex flex-wrap gap-4">
                    <div class="flex items-center gap-2">
                        <Checkbox v-model="form.is_active" binary input-id="wf-active" />
                        <label for="wf-active" class="text-sm">Active</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox v-model="form.is_default" binary input-id="wf-default" />
                        <label for="wf-default" class="text-sm">Default for organization</label>
                    </div>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-sm font-semibold">Sequential approval steps</h3>
                        <Button label="Add step" size="small" outlined icon="pi pi-plus" @click="addStep" />
                    </div>
                    <div v-for="(step, idx) in form.steps" :key="idx" class="mb-3 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-sm font-medium">Level {{ idx + 1 }}</p>
                            <Button v-if="form.steps.length > 1" icon="pi pi-trash" text rounded severity="danger" size="small" @click="form.steps.splice(idx, 1)" />
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <InputText v-model="step.name" placeholder="Step name" class="w-full" />
                            <Select
                                v-model="step.role_id"
                                :options="roles"
                                option-label="name"
                                option-value="id"
                                placeholder="Approver role (optional)"
                                show-clear
                                class="w-full"
                            />
                        </div>
                        <MultiSelect
                            v-model="step.approver_user_ids"
                            :options="users"
                            option-label="name"
                            option-value="id"
                            placeholder="Specific approver users (optional)"
                            display="chip"
                            class="mt-2 w-full"
                            filter
                        />
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" text @click="showEditor = false" />
                <Button label="Save" :loading="saving" @click="saveWorkflow" />
            </template>
        </Dialog>

        <Dialog v-model:visible="showDetail" modal header="Approval detail" class="w-full max-w-xl">
            <div v-if="detail" class="space-y-3 text-sm">
                <p><span class="text-slate-500">Document:</span> {{ detail.document?.title }}</p>
                <p><span class="text-slate-500">Workflow:</span> {{ detail.workflow?.name }}</p>
                <p><span class="text-slate-500">Status:</span> {{ detail.status }}</p>
                <p><span class="text-slate-500">Current step:</span> {{ detail.current_step?.name || '—' }}</p>
                <div>
                    <p class="mb-2 font-semibold">Timeline</p>
                    <ul class="space-y-2">
                        <li v-for="action in detail.actions || []" :key="action.id" class="rounded-md bg-slate-50 px-3 py-2 dark:bg-slate-900">
                            <p class="font-medium">{{ action.action }} · {{ action.actor?.name }}</p>
                            <p class="text-xs text-slate-500">{{ formatDate(action.acted_at) }} · {{ action.step?.name || '—' }}</p>
                            <p v-if="action.comments" class="mt-1">{{ action.comments }}</p>
                        </li>
                    </ul>
                </div>
            </div>
        </Dialog>

        <Dialog v-model:visible="showAction" modal :header="actionTitle" class="w-full max-w-md">
            <Textarea v-model="actionComments" rows="3" class="w-full" placeholder="Comments (optional)" />
            <template #footer>
                <Button label="Cancel" text @click="showAction = false" />
                <Button :label="actionTitle" :loading="acting" @click="confirmAct" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Select from 'primevue/select';
import TabPanel from 'primevue/tabpanel';
import TabView from 'primevue/tabview';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import api from '@/services/api';
import { useWorkflowStore } from '@/modules/workflow/stores/workflow';

const store = useWorkflowStore();
const toast = useToast();
const confirm = useConfirm();

const organizations = ref([]);
const selectedOrg = ref(store.organizationId);
const inboxRows = ref([]);
const workflowRows = ref([]);
const historyRows = ref([]);
const inboxSearch = ref('');
const roles = ref([]);
const users = ref([]);

const showEditor = ref(false);
const editingId = ref(null);
const saving = ref(false);
const form = ref(emptyForm());

const showDetail = ref(false);
const detail = ref(null);

const showAction = ref(false);
const actionType = ref('approve');
const actionTarget = ref(null);
const actionComments = ref('');
const acting = ref(false);

const actionTitle = ref('Approve');

function emptyForm() {
    return {
        name: '',
        code: '',
        description: '',
        is_active: true,
        is_default: false,
        steps: [{ name: 'Level 1', role_id: null, approver_user_ids: [] }],
    };
}

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleString();
}

async function loadOrgs() {
    const { data } = await api.get('/organizations', { params: { per_page: 100 } });
    organizations.value = data.data.data || data.data;
    if (!selectedOrg.value && organizations.value.length) {
        selectedOrg.value = organizations.value[0].id;
        store.setOrganization(selectedOrg.value);
    }
}

async function loadLookups() {
    const [rolesRes, usersRes] = await Promise.all([
        api.get('/roles', { params: { per_page: 100 } }),
        api.get('/users', { params: { per_page: 100 } }),
    ]);
    roles.value = rolesRes.data.data.data || rolesRes.data.data;
    users.value = usersRes.data.data.data || usersRes.data.data;
}

async function onOrgChange() {
    store.setOrganization(selectedOrg.value);
    await Promise.all([loadInbox(), loadWorkflows(), loadHistory()]);
}

async function loadInbox() {
    const payload = await store.fetchInbox({ search: inboxSearch.value || undefined });
    inboxRows.value = payload.data || payload;
}

async function loadWorkflows() {
    const payload = await store.fetchWorkflows();
    workflowRows.value = payload.data || payload;
}

async function loadHistory() {
    const payload = await store.fetchInstances();
    historyRows.value = payload.data || payload;
}

function onTabChange() {
    // data refreshed on org change; keep lightweight
}

function openCreate() {
    editingId.value = null;
    form.value = emptyForm();
    showEditor.value = true;
}

function openEdit(row) {
    editingId.value = row.id;
    form.value = {
        name: row.name,
        code: row.code,
        description: row.description || '',
        is_active: row.is_active,
        is_default: row.is_default,
        steps: (row.steps || []).map((s) => ({
            name: s.name,
            role_id: s.role_id,
            approver_user_ids: s.approver_user_ids || (s.approvers || []).map((u) => u.id),
        })),
    };
    showEditor.value = true;
}

function addStep() {
    form.value.steps.push({
        name: `Level ${form.value.steps.length + 1}`,
        role_id: null,
        approver_user_ids: [],
    });
}

async function saveWorkflow() {
    saving.value = true;
    try {
        const payload = {
            ...form.value,
            steps: form.value.steps.map((s, idx) => ({
                ...s,
                step_order: idx + 1,
            })),
        };
        if (editingId.value) {
            await store.updateWorkflow(editingId.value, payload);
            toast.add({ severity: 'success', summary: 'Workflow updated', life: 2500 });
        } else {
            await store.createWorkflow(payload);
            toast.add({ severity: 'success', summary: 'Workflow created', life: 2500 });
        }
        showEditor.value = false;
        await loadWorkflows();
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'Save failed',
            detail: e.response?.data?.message || e.message,
            life: 4000,
        });
    } finally {
        saving.value = false;
    }
}

function removeWorkflow(row) {
    confirm.require({
        message: `Delete workflow “${row.name}”?`,
        header: 'Confirm',
        accept: async () => {
            try {
                await store.deleteWorkflow(row.id);
                toast.add({ severity: 'success', summary: 'Deleted', life: 2000 });
                await loadWorkflows();
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

async function openDetail(row) {
    detail.value = await store.showInstance(row.id);
    showDetail.value = true;
}

function act(row, type) {
    actionTarget.value = row;
    actionType.value = type;
    actionComments.value = '';
    actionTitle.value = type === 'approve' ? 'Approve' : type === 'reject' ? 'Reject' : 'Return';
    showAction.value = true;
}

async function confirmAct() {
    acting.value = true;
    try {
        const id = actionTarget.value.id;
        if (actionType.value === 'approve') await store.approve(id, actionComments.value);
        if (actionType.value === 'reject') await store.reject(id, actionComments.value);
        if (actionType.value === 'return') await store.returnInstance(id, actionComments.value);
        toast.add({ severity: 'success', summary: `${actionTitle.value}d`, life: 2500 });
        showAction.value = false;
        await Promise.all([loadInbox(), loadHistory()]);
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'Action failed',
            detail: e.response?.data?.message || e.message,
            life: 4000,
        });
    } finally {
        acting.value = false;
    }
}

onMounted(async () => {
    await loadOrgs();
    await loadLookups();
    if (selectedOrg.value) {
        store.setOrganization(selectedOrg.value);
        await Promise.all([loadInbox(), loadWorkflows(), loadHistory()]);
    }
});
</script>
