<template>
    <div class="space-y-4 p-4 sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                <span class="relative w-full max-w-xs">
                    <i class="pi pi-search pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-slate-400" />
                    <InputText
                        v-model="search"
                        placeholder="Search records..."
                        class="w-full pl-9"
                        @keyup.enter="load"
                    />
                </span>
                <Button
                    icon="pi pi-search"
                    severity="secondary"
                    outlined
                    v-tooltip.bottom="'Search'"
                    @click="load"
                />
            </div>
            <Button
                :label="`Add ${title}`"
                icon="pi pi-plus"
                size="small"
                @click="openCreate"
            />
        </div>

        <DataTable
            :value="rows"
            :loading="loading"
            paginator
            :rows="15"
            size="small"
            class="org-table text-sm"
            :pt="{
                table: { class: 'text-sm' },
                thead: { class: 'bg-slate-50 dark:bg-slate-900/60' },
            }"
        >
            <template #empty>
                <div class="py-10 text-center">
                    <i class="pi pi-inbox mb-2 text-2xl text-slate-300 dark:text-slate-600" />
                    <p class="text-sm text-slate-500">No {{ title.toLowerCase() }} found</p>
                </div>
            </template>

            <Column
                v-for="col in columns"
                :key="col.field"
                :field="col.field"
                :header="col.header"
                :style="col.kind === 'code' ? 'width: 7.5rem' : undefined"
            >
                <template #body="{ data }">
                    <template v-if="col.kind === 'boolean'">
                        <Tag
                            :value="truthy(resolveField(data, col.field)) ? 'Active' : 'Inactive'"
                            :severity="truthy(resolveField(data, col.field)) ? 'success' : 'secondary'"
                            rounded
                            class="text-xs"
                        />
                    </template>
                    <template v-else-if="col.kind === 'status'">
                        <Tag
                            :value="formatStatus(resolveField(data, col.field))"
                            :severity="statusSeverity(resolveField(data, col.field))"
                            rounded
                            class="text-xs capitalize"
                        />
                    </template>
                    <template v-else-if="col.kind === 'code'">
                        <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            {{ resolveField(data, col.field) || '—' }}
                        </span>
                    </template>
                    <template v-else-if="col.kind === 'primary'">
                        <span class="font-medium text-slate-800 dark:text-slate-100">
                            {{ resolveField(data, col.field) || '—' }}
                        </span>
                    </template>
                    <template v-else>
                        <span class="text-slate-600 dark:text-slate-300">
                            {{ resolveField(data, col.field) || '—' }}
                        </span>
                    </template>
                </template>
            </Column>

            <Column header="Actions" style="width: 7rem" align-frozen="right">
                <template #body="{ data }">
                    <div class="flex items-center justify-end gap-0.5">
                        <Button
                            icon="pi pi-pencil"
                            text
                            rounded
                            size="small"
                            severity="secondary"
                            v-tooltip.top="'Edit'"
                            @click="openEdit(data)"
                        />
                        <Button
                            icon="pi pi-trash"
                            text
                            rounded
                            size="small"
                            severity="danger"
                            v-tooltip.top="'Delete'"
                            @click="confirmDelete(data)"
                        />
                    </div>
                </template>
            </Column>
        </DataTable>

        <Dialog
            v-model:visible="dialogVisible"
            modal
            :header="editingId ? `Edit ${title}` : `Create ${title}`"
            class="w-full max-w-2xl"
            :pt="{ header: { class: 'border-b border-slate-100 dark:border-slate-800' } }"
        >
            <div class="grid gap-4 py-1 sm:grid-cols-2">
                <div v-for="field in fields" :key="field.key" :class="field.type === 'textarea' ? 'sm:col-span-2' : ''">
                    <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">
                        {{ field.label }}
                        <span v-if="field.required" class="text-red-500">*</span>
                    </label>

                    <Textarea
                        v-if="field.type === 'textarea'"
                        v-model="form[field.key]"
                        rows="3"
                        class="w-full"
                    />
                    <div v-else-if="field.type === 'boolean'" class="flex items-center gap-2 pt-1">
                        <Checkbox v-model="form[field.key]" binary :input-id="`fld-${field.key}`" />
                        <label :for="`fld-${field.key}`" class="text-sm text-slate-600 dark:text-slate-300">Enabled</label>
                    </div>
                    <InputNumber
                        v-else-if="field.type === 'number'"
                        v-model="form[field.key]"
                        class="w-full"
                        input-class="w-full"
                    />
                    <InputText
                        v-else-if="field.type === 'date'"
                        v-model="form[field.key]"
                        type="date"
                        class="w-full"
                    />
                    <Select
                        v-else-if="field.type === 'select'"
                        v-model="form[field.key]"
                        :options="optionMaps[field.optionsKey] || []"
                        option-label="name"
                        option-value="id"
                        show-clear
                        class="w-full"
                    />
                    <Select
                        v-else-if="field.type === 'status'"
                        v-model="form[field.key]"
                        :options="statusOptions"
                        option-label="label"
                        option-value="value"
                        class="w-full"
                    />
                    <InputText
                        v-else
                        v-model="form[field.key]"
                        class="w-full"
                        :required="field.required"
                    />
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" text severity="secondary" @click="dialogVisible = false" />
                <Button label="Save" icon="pi pi-check" :loading="saving" @click="save" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { useOrganizationStore } from '@/modules/organization/stores/organization';

const props = defineProps({
    resource: { type: String, required: true },
    title: { type: String, required: true },
    fields: { type: Array, required: true },
    columns: { type: Array, required: true },
    organizationId: { type: String, default: null },
});

const orgStore = useOrganizationStore();
const toast = useToast();
const confirm = useConfirm();

const rows = ref([]);
const loading = ref(false);
const saving = ref(false);
const search = ref('');
const dialogVisible = ref(false);
const editingId = ref(null);
const form = reactive({});
const optionMaps = reactive({});

const statusOptions = [
    { label: 'Active', value: 'active' },
    { label: 'On leave', value: 'on_leave' },
    { label: 'Resigned', value: 'resigned' },
    { label: 'Terminated', value: 'terminated' },
    { label: 'Suspended', value: 'suspended' },
];

function resolveField(data, path) {
    return path.split('.').reduce((acc, key) => (acc == null ? '' : acc[key]), data) ?? '';
}

function truthy(value) {
    return value === true || value === 1 || value === '1' || value === 'true';
}

function formatStatus(value) {
    if (!value) return '—';
    return String(value).replaceAll('_', ' ');
}

function statusSeverity(value) {
    const map = {
        active: 'success',
        on_leave: 'warn',
        resigned: 'secondary',
        terminated: 'danger',
        suspended: 'danger',
    };
    return map[value] || 'info';
}

function resetForm(row = null) {
    props.fields.forEach((field) => {
        if (row) {
            form[field.key] = row[field.key] ?? (field.type === 'boolean' ? false : null);
        } else {
            form[field.key] = field.type === 'boolean' ? true : field.type === 'status' ? 'active' : '';
        }
    });
}

async function loadOptions() {
    const keys = [...new Set(props.fields.filter((f) => f.type === 'select').map((f) => f.optionsKey))];
    for (const key of keys) {
        optionMaps[key] = await orgStore.fetchOptions(key, props.organizationId);
        if (key !== 'organizations') {
            optionMaps[key] = (optionMaps[key] || []).map((item) => ({
                ...item,
                name: item.name || `${item.first_name || ''} ${item.last_name || ''}`.trim(),
            }));
        }
    }
}

async function load() {
    loading.value = true;
    try {
        const data = await orgStore.fetchList(props.resource, {
            search: search.value || undefined,
            per_page: 50,
            organization_id: props.resource === 'organizations' ? undefined : props.organizationId,
        });
        rows.value = data.data || data;
    } finally {
        loading.value = false;
    }
}

function openCreate() {
    editingId.value = null;
    resetForm();
    dialogVisible.value = true;
}

function openEdit(row) {
    editingId.value = row.id;
    resetForm(row);
    dialogVisible.value = true;
}

async function save() {
    saving.value = true;
    try {
        const payload = { ...form };
        if (props.resource !== 'organizations') {
            payload.organization_id = props.organizationId;
        }
        if (editingId.value) {
            await orgStore.update(props.resource, editingId.value, payload);
            toast.add({ severity: 'success', summary: 'Updated', life: 2000 });
        } else {
            await orgStore.create(props.resource, payload);
            toast.add({ severity: 'success', summary: 'Created', life: 2000 });
        }
        dialogVisible.value = false;
        await load();
        if (props.resource !== 'employees') {
            await orgStore.loadTree(props.organizationId);
        }
        if (props.resource === 'organizations') {
            await orgStore.loadOrganizations();
        }
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Save failed',
            detail: error.response?.data?.message || 'Validation error',
            life: 4000,
        });
    } finally {
        saving.value = false;
    }
}

function confirmDelete(row) {
    confirm.require({
        message: `Delete this ${props.title.toLowerCase()} record?`,
        header: 'Confirm delete',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            await orgStore.remove(props.resource, row.id);
            toast.add({ severity: 'success', summary: 'Deleted', life: 2000 });
            await load();
            await orgStore.loadTree(props.organizationId);
        },
    });
}

watch(
    () => props.organizationId,
    async () => {
        await loadOptions();
        await load();
    }
);

onMounted(async () => {
    await loadOptions();
    await load();
});
</script>

<style scoped>
.org-table :deep(.p-datatable-thead > tr > th) {
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: rgb(100 116 139);
    padding-top: 0.7rem;
    padding-bottom: 0.7rem;
}

.org-table :deep(.p-datatable-tbody > tr > td) {
    padding-top: 0.65rem;
    padding-bottom: 0.65rem;
    border-color: rgb(241 245 249);
}

:global(.dark) .org-table :deep(.p-datatable-tbody > tr > td) {
    border-color: rgb(30 41 59);
}

.org-table :deep(.p-datatable-tbody > tr:hover) {
    background: rgb(248 250 252);
}

:global(.dark) .org-table :deep(.p-datatable-tbody > tr:hover) {
    background: rgb(15 23 42);
}
</style>
