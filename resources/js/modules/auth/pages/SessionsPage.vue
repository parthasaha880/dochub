<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-display text-2xl font-semibold">Trusted devices</h1>
                <p class="text-sm text-slate-500">Review and revoke devices that have accessed your account</p>
            </div>
            <Button label="Logout other devices" severity="danger" outlined @click="showLogoutOthers = true" />
        </div>

        <DataTable :value="rows" :loading="loading" striped-rows class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800">
            <Column field="device_name" header="Device" />
            <Column field="ip_address" header="IP" />
            <Column field="last_used_at" header="Last used">
                <template #body="{ data }">{{ formatDate(data.last_used_at) }}</template>
            </Column>
            <Column header="Actions">
                <template #body="{ data }">
                    <Button icon="pi pi-trash" severity="danger" text rounded @click="revoke(data.id)" />
                </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="showLogoutOthers" header="Confirm password" modal class="w-full max-w-md">
            <Password v-model="password" class="w-full" input-class="w-full" :feedback="false" toggle-mask />
            <template #footer>
                <Button label="Cancel" text @click="showLogoutOthers = false" />
                <Button label="Confirm" severity="danger" :loading="revoking" @click="logoutOthers" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Password from 'primevue/password';
import { useToast } from 'primevue/usetoast';
import { useAuthStore } from '@/modules/auth/stores/auth';

const auth = useAuthStore();
const toast = useToast();
const rows = ref([]);
const loading = ref(false);
const showLogoutOthers = ref(false);
const password = ref('');
const revoking = ref(false);

function formatDate(value) {
    return value ? new Date(value).toLocaleString() : '—';
}

async function load() {
    loading.value = true;
    try {
        const data = await auth.fetchDevices();
        rows.value = data.data || data;
    } finally {
        loading.value = false;
    }
}

async function revoke(id) {
    await auth.revokeDevice(id);
    toast.add({ severity: 'success', summary: 'Device revoked', life: 2500 });
    await load();
}

async function logoutOthers() {
    revoking.value = true;
    try {
        await auth.logoutOtherDevices(password.value);
        toast.add({ severity: 'success', summary: 'Other devices signed out', life: 2500 });
        showLogoutOthers.value = false;
        password.value = '';
        await load();
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Failed',
            detail: error.response?.data?.message || 'Unable to logout other devices',
            life: 4000,
        });
    } finally {
        revoking.value = false;
    }
}

onMounted(load);
</script>
