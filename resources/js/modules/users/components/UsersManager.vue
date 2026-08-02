<template>
    <div class="space-y-4 p-2">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <InputText v-model="search" placeholder="Search users..." class="w-56" @keyup.enter="load" />
                <Select
                    v-model="roleFilter"
                    :options="store.roles"
                    option-label="name"
                    option-value="name"
                    placeholder="Filter role"
                    show-clear
                    class="w-48"
                />
                <Button icon="pi pi-search" @click="load" />
            </div>
            <Button label="Add user" icon="pi pi-plus" @click="openCreate" />
        </div>

        <DataTable :value="rows" :loading="loading" striped-rows paginator :rows="15" size="small">
            <Column field="name" header="Name" />
            <Column field="email" header="Email" />
            <Column header="Roles">
                <template #body="{ data }">
                    <span class="text-sm">{{ (data.role_names || []).join(', ') || '—' }}</span>
                </template>
            </Column>
            <Column field="is_active" header="Active">
                <template #body="{ data }">
                    <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'danger'" />
                </template>
            </Column>
            <Column header="Actions" style="width: 10rem">
                <template #body="{ data }">
                    <Button
                        icon="pi pi-envelope"
                        text
                        rounded
                        v-tooltip.top="'Resend welcome email'"
                        :loading="resendingId === data.id"
                        @click="resendWelcome(data)"
                    />
                    <Button icon="pi pi-pencil" text rounded v-tooltip.top="'Edit'" @click="openEdit(data)" />
                    <Button icon="pi pi-trash" text rounded severity="danger" v-tooltip.top="'Delete'" @click="confirmDelete(data)" />
                </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="dialogVisible" modal :header="editingId ? 'Edit user' : 'Create user'" class="w-full max-w-3xl">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Name</label>
                    <InputText v-model="form.name" class="w-full" required />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Email</label>
                    <InputText v-model="form.email" type="email" class="w-full" required />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Username</label>
                    <InputText v-model="form.username" class="w-full" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Phone</label>
                    <InputText v-model="form.phone" class="w-full" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Password</label>
                    <Password v-model="form.password" class="w-full" input-class="w-full" toggle-mask :feedback="false" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Confirm password</label>
                    <Password v-model="form.password_confirmation" class="w-full" input-class="w-full" toggle-mask :feedback="false" />
                </div>
                <div class="flex items-center gap-2">
                    <Checkbox v-model="form.is_active" binary input-id="user-active" />
                    <label for="user-active">Active</label>
                </div>
                <div class="flex items-center gap-2">
                    <Checkbox v-model="form.force_password_change" binary input-id="force-pw" />
                    <label for="force-pw">Force password change</label>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Roles</label>
                    <MultiSelect
                        v-model="form.roles"
                        :options="store.roles"
                        option-label="name"
                        option-value="name"
                        display="chip"
                        class="w-full"
                        placeholder="Assign roles"
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
import { onMounted, reactive, ref } from 'vue';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Password from 'primevue/password';
import Select from 'primevue/select';
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
const resendingId = ref(null);
const search = ref('');
const roleFilter = ref(null);
const dialogVisible = ref(false);
const editingId = ref(null);

const form = reactive({
    name: '',
    email: '',
    username: '',
    phone: '',
    password: '',
    password_confirmation: '',
    is_active: true,
    force_password_change: false,
    roles: [],
});

function resetForm(row = null) {
    form.name = row?.name || '';
    form.email = row?.email || '';
    form.username = row?.username || '';
    form.phone = row?.phone || '';
    form.password = '';
    form.password_confirmation = '';
    form.is_active = row?.is_active ?? true;
    form.force_password_change = row?.force_password_change ?? false;
    form.roles = [...(row?.role_names || [])];
}

async function load() {
    loading.value = true;
    try {
        const data = await store.fetchUsers({
            search: search.value || undefined,
            role: roleFilter.value || undefined,
            per_page: 50,
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
        if (editingId.value && !payload.password) {
            delete payload.password;
            delete payload.password_confirmation;
        }
        if (editingId.value) {
            await store.updateUser(editingId.value, payload);
            toast.add({ severity: 'success', summary: 'User updated', life: 2000 });
        } else {
            await store.createUser(payload);
            toast.add({ severity: 'success', summary: 'User created', life: 2000 });
        }
        dialogVisible.value = false;
        await load();
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
        message: `Delete user ${row.email}?`,
        header: 'Confirm',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await store.deleteUser(row.id);
                toast.add({ severity: 'success', summary: 'User deleted', life: 2000 });
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

async function resendWelcome(row) {
    confirm.require({
        message: `Resend welcome email to ${row.email}?`,
        header: 'Resend welcome email',
        icon: 'pi pi-envelope',
        accept: async () => {
            resendingId.value = row.id;
            try {
                const result = await store.resendWelcomeEmail(row.id);
                toast.add({
                    severity: 'success',
                    summary: 'Email sent',
                    detail: result.message || `Welcome email sent to ${row.email}`,
                    life: 3000,
                });
            } catch (error) {
                const errors = error.response?.data?.errors;
                const detail =
                    errors?.email?.[0] ||
                    error.response?.data?.message ||
                    'Unable to send welcome email';
                toast.add({
                    severity: 'error',
                    summary: 'Resend failed',
                    detail,
                    life: 5000,
                });
            } finally {
                resendingId.value = null;
            }
        },
    });
}

onMounted(async () => {
    await store.loadRoleOptions();
    await load();
});
</script>
