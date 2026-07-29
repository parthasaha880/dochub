<template>
    <div class="space-y-4 p-2">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex gap-2">
                <InputText v-model="search" placeholder="Search..." class="w-56" @keyup.enter="load" />
                <Button icon="pi pi-search" @click="load" />
            </div>
            <Button :label="`Add ${title}`" icon="pi pi-plus" @click="openCreate" />
        </div>

        <DataTable :value="rows" :loading="loading" striped-rows paginator :rows="15" size="small">
            <Column
                v-for="col in columns"
                :key="col.field"
                :field="col.field"
                :header="col.header"
            >
                <template #body="{ data }">
                    <span>{{ resolveField(data, col.field) }}</span>
                </template>
            </Column>
            <Column header="Actions" style="width: 8rem">
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" text rounded @click="openEdit(data)" />
                    <Button icon="pi pi-trash" text rounded severity="danger" @click="confirmDelete(data)" />
                </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="dialogVisible" modal :header="editingId ? `Edit ${title}` : `Create ${title}`" class="w-full max-w-2xl">
            <div class="grid gap-4 sm:grid-cols-2">
                <div v-for="field in fields" :key="field.key" :class="field.type === 'textarea' ? 'sm:col-span-2' : ''">
                    <label class="mb-1 block text-sm font-medium">{{ field.label }}</label>

                    <Textarea
                        v-if="field.type === 'textarea'"
                        v-model="form[field.key]"
                        rows="3"
                        class="w-full"
                    />
                    <Checkbox
                        v-else-if="field.type === 'boolean'"
                        v-model="form[field.key]"
                        binary
                    />
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
                <Button label="Cancel" text @click="dialogVisible = false" />
                <Button label="Save" :loading="saving" @click="save" />
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
        header: 'Confirm',
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
