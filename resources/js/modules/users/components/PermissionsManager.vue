<template>
    <div class="space-y-4 p-2">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <InputText v-model="search" placeholder="Search permissions..." class="w-56" @keyup.enter="load" />
                <Select
                    v-model="groupFilter"
                    :options="store.permissionGroups"
                    placeholder="Filter group"
                    show-clear
                    class="w-48"
                />
                <Button icon="pi pi-search" @click="load" />
            </div>
            <Button label="Add permission" icon="pi pi-plus" @click="openCreate" />
        </div>

        <DataTable :value="rows" :loading="loading" striped-rows paginator :rows="20" size="small">
            <Column field="name" header="Permission" />
            <Column field="group" header="Group" />
            <Column field="description" header="Description" />
            <Column header="Actions" style="width: 8rem">
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" text rounded @click="openEdit(data)" />
                    <Button icon="pi pi-trash" text rounded severity="danger" @click="confirmDelete(data)" />
                </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="dialogVisible" modal :header="editingId ? 'Edit permission' : 'Create permission'" class="w-full max-w-lg">
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">Name</label>
                    <InputText v-model="form.name" class="w-full" placeholder="module.action" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Group</label>
                    <InputText v-model="form.group" class="w-full" list="perm-groups" />
                    <datalist id="perm-groups">
                        <option v-for="group in store.permissionGroups" :key="group" :value="group" />
                    </datalist>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Description</label>
                    <InputText v-model="form.description" class="w-full" />
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
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
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
const groupFilter = ref(null);
const dialogVisible = ref(false);
const editingId = ref(null);

const form = reactive({
    name: '',
    group: '',
    description: '',
});

function resetForm(row = null) {
    form.name = row?.name || '';
    form.group = row?.group || '';
    form.description = row?.description || '';
}

async function load() {
    loading.value = true;
    try {
        const data = await store.fetchPermissions({
            search: search.value || undefined,
            group: groupFilter.value || undefined,
            per_page: 100,
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
        if (editingId.value) {
            await store.updatePermission(editingId.value, { ...form });
            toast.add({ severity: 'success', summary: 'Permission updated', life: 2000 });
        } else {
            await store.createPermission({ ...form });
            toast.add({ severity: 'success', summary: 'Permission created', life: 2000 });
        }
        dialogVisible.value = false;
        await load();
        await store.loadPermissionMeta();
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
        message: `Delete permission ${row.name}?`,
        header: 'Confirm',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await store.deletePermission(row.id);
                toast.add({ severity: 'success', summary: 'Permission deleted', life: 2000 });
                await load();
                await store.loadPermissionMeta();
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
