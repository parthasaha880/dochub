<template>
    <div class="space-y-4 p-2">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex gap-2">
                <InputText v-model="search" placeholder="Search roles..." class="w-56" @keyup.enter="load" />
                <Button icon="pi pi-search" @click="load" />
            </div>
            <Button label="Add role" icon="pi pi-plus" @click="openCreate" />
        </div>

        <DataTable :value="rows" :loading="loading" striped-rows paginator :rows="15" size="small">
            <Column field="name" header="Role" />
            <Column field="description" header="Description" />
            <Column field="hierarchy_level" header="Level" />
            <Column header="System">
                <template #body="{ data }">
                    <Tag :value="data.is_system ? 'Yes' : 'Custom'" :severity="data.is_system ? 'warn' : 'info'" />
                </template>
            </Column>
            <Column header="Permissions">
                <template #body="{ data }">
                    <span class="text-sm">{{ (data.permissions || []).length }} assigned</span>
                </template>
            </Column>
            <Column header="Actions" style="width: 8rem">
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" text rounded @click="openEdit(data)" />
                    <Button
                        icon="pi pi-trash"
                        text
                        rounded
                        severity="danger"
                        :disabled="data.is_system"
                        @click="confirmDelete(data)"
                    />
                </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="dialogVisible" modal :header="editingId ? 'Edit role' : 'Create role'" class="w-full max-w-3xl">
            <div class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Name</label>
                        <InputText v-model="form.name" class="w-full" :disabled="editingIsSystem" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Hierarchy level</label>
                        <InputNumber v-model="form.hierarchy_level" class="w-full" input-class="w-full" :min="1" :max="999" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Description</label>
                        <InputText v-model="form.description" class="w-full" />
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium">Permissions by group</label>
                    <div class="max-h-80 space-y-4 overflow-y-auto rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                        <div v-for="(perms, group) in store.permissionsGrouped" :key="group">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ group }}</p>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <div v-for="perm in perms" :key="perm.id" class="flex items-center gap-2">
                                    <Checkbox
                                        v-model="form.permissions"
                                        :input-id="`perm-${perm.id}`"
                                        :value="perm.name"
                                    />
                                    <label :for="`perm-${perm.id}`" class="text-sm">{{ perm.name }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
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
import { onMounted, reactive, ref } from 'vue';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { useUsersStore } from '@/modules/users/stores/users';

const store = useUsersStore();
const toast = useToast();
const confirm = useConfirm();

const rows = ref([]);
const loading = ref(false);
const saving = ref(false);
const search = ref('');
const dialogVisible = ref(false);
const editingId = ref(null);
const editingIsSystem = ref(false);

const form = reactive({
    name: '',
    description: '',
    hierarchy_level: 100,
    permissions: [],
});

function resetForm(row = null) {
    form.name = row?.name || '';
    form.description = row?.description || '';
    form.hierarchy_level = row?.hierarchy_level ?? 100;
    form.permissions = [...(row?.permissions || [])];
    editingIsSystem.value = !!row?.is_system;
}

async function load() {
    loading.value = true;
    try {
        const data = await store.fetchRoles({ search: search.value || undefined, per_page: 50 });
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
        const payload = {
            name: form.name,
            description: form.description,
            hierarchy_level: form.hierarchy_level,
            permissions: form.permissions,
        };
        if (editingId.value) {
            await store.updateRole(editingId.value, payload);
            toast.add({ severity: 'success', summary: 'Role updated', life: 2000 });
        } else {
            await store.createRole(payload);
            toast.add({ severity: 'success', summary: 'Role created', life: 2000 });
        }
        dialogVisible.value = false;
        await load();
        await store.loadRoleOptions();
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
        message: `Delete role ${row.name}?`,
        header: 'Confirm',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await store.deleteRole(row.id);
                toast.add({ severity: 'success', summary: 'Role deleted', life: 2000 });
                await load();
            } catch (error) {
                toast.add({
                    severity: 'error',
                    summary: 'Delete failed',
                    detail: error.response?.data?.message || 'Unable to delete',
                    life: 4000,
                });
            }
        },
    });
}

onMounted(async () => {
    await store.loadPermissionMeta();
    await load();
});
</script>
