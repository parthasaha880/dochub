<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-display text-2xl font-semibold">Login activity</h1>
                <p class="text-sm text-slate-500">Audit trail of successful and failed authentication attempts</p>
            </div>
        </div>

        <DataTable :value="rows" :loading="loading" striped-rows paginator :rows="15" class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800">
            <Column field="created_at" header="When">
                <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
            </Column>
            <Column field="status" header="Status" />
            <Column field="ip_address" header="IP" />
            <Column field="device_name" header="Device" />
            <Column field="failure_reason" header="Reason" />
        </DataTable>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { useAuthStore } from '@/modules/auth/stores/auth';

const auth = useAuthStore();
const rows = ref([]);
const loading = ref(false);

function formatDate(value) {
    return value ? new Date(value).toLocaleString() : '—';
}

onMounted(async () => {
    loading.value = true;
    try {
        const data = await auth.fetchLoginActivities();
        rows.value = data.data || data;
    } finally {
        loading.value = false;
    }
});
</script>
