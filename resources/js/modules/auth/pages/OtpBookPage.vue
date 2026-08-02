<template>
    <div class="space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="font-display text-2xl font-semibold tracking-tight text-slate-900 dark:text-white sm:text-3xl">OTP Book</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Email-change &amp; password-recovery codes (super admin only)</p>
            </div>
        </div>

        <section class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                <span class="relative min-w-[14rem] flex-1">
                    <i class="pi pi-search pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-slate-400" />
                    <InputText
                        v-model="search"
                        placeholder="Search user, email, or code…"
                        class="w-full pl-9"
                        @keyup.enter="load"
                    />
                </span>
                <Select
                    v-model="type"
                    :options="typeOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Type"
                    show-clear
                    class="w-44"
                    @change="load"
                />
                <Select
                    v-model="status"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Status"
                    show-clear
                    class="w-40"
                    @change="load"
                />
                <Button icon="pi pi-refresh" severity="secondary" outlined :loading="loading" @click="load" />
            </div>

            <div class="p-3 sm:p-4">
                <DataTable :value="rows" :loading="loading" paginator :rows="20" size="small" class="text-sm">
                    <template #empty>
                        <div class="py-10 text-center text-sm text-slate-500">No OTP records found</div>
                    </template>

                    <Column header="Type" style="width: 8.5rem">
                        <template #body="{ data }">
                            <Tag
                                :value="typeLabel(data.type)"
                                :severity="data.type === 'password_reset' ? 'info' : 'warn'"
                                rounded
                                class="text-xs"
                            />
                        </template>
                    </Column>
                    <Column header="User">
                        <template #body="{ data }">
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ data.user?.name || '—' }}</p>
                            <p class="text-xs text-slate-500">{{ data.user?.email || data.email }}</p>
                        </template>
                    </Column>
                    <Column header="Detail">
                        <template #body="{ data }">
                            <span v-if="data.detail" class="text-sm">→ {{ data.detail }}</span>
                            <span v-else class="text-xs text-slate-400">Password recovery</span>
                        </template>
                    </Column>
                    <Column header="OTP" style="width: 7rem">
                        <template #body="{ data }">
                            <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-sm font-semibold tracking-wider text-slate-800 dark:bg-slate-800 dark:text-slate-100">
                                {{ data.code }}
                            </span>
                        </template>
                    </Column>
                    <Column header="Status" style="width: 7rem">
                        <template #body="{ data }">
                            <Tag :value="data.status" :severity="statusSeverity(data.status)" rounded class="text-xs capitalize" />
                        </template>
                    </Column>
                    <Column header="Expires">
                        <template #body="{ data }">
                            <span class="text-xs text-slate-500">{{ formatDate(data.expires_at) }}</span>
                        </template>
                    </Column>
                    <Column header="Created">
                        <template #body="{ data }">
                            <span class="text-xs text-slate-500">{{ formatDate(data.created_at) }}</span>
                        </template>
                    </Column>
                    <Column header="IP" style="width: 8rem">
                        <template #body="{ data }">
                            <span class="text-xs text-slate-400">{{ data.ip_address || '—' }}</span>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </section>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import api from '@/services/api';

const toast = useToast();
const loading = ref(false);
const rows = ref([]);
const search = ref('');
const status = ref(null);
const type = ref(null);

const statusOptions = [
    { label: 'Pending', value: 'pending' },
    { label: 'Used', value: 'used' },
    { label: 'Expired', value: 'expired' },
];

const typeOptions = [
    { label: 'Email change', value: 'email_change' },
    { label: 'Password reset', value: 'password_reset' },
];

function typeLabel(value) {
    return value === 'password_reset' ? 'Password' : 'Email';
}

function statusSeverity(value) {
    return { pending: 'warn', used: 'success', expired: 'secondary' }[value] || 'info';
}

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleString();
}

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/otp-book', {
            params: {
                search: search.value || undefined,
                status: status.value || undefined,
                type: type.value || undefined,
                per_page: 50,
            },
        });
        const payload = data.data;
        rows.value = Array.isArray(payload) ? payload : (payload?.data || []);
    } catch (e) {
        rows.value = [];
        toast.add({
            severity: 'error',
            summary: 'Failed to load OTP book',
            detail: e.response?.data?.message || e.message,
            life: 4000,
        });
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>
