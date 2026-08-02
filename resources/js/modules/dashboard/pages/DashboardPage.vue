<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold text-slate-900 dark:text-white">Dashboard</h1>
                <p class="mt-1 text-slate-500">Storage &amp; volume reports by type, category, department, and user</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Select
                    v-model="selectedOrg"
                    :options="organizations"
                    option-label="name"
                    option-value="id"
                    placeholder="Organization"
                    class="w-56"
                    @change="reload"
                />
                <Select
                    v-model="selectedDays"
                    :options="dayOptions"
                    option-label="label"
                    option-value="value"
                    class="w-40"
                    @change="reload"
                />
                <Button icon="pi pi-refresh" outlined :loading="store.loading" @click="reload" />
            </div>
        </div>

        <div
            v-if="store.error"
            class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300"
        >
            {{ store.error }}
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div v-for="card in kpiCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-950">
                <p class="text-sm text-slate-500">{{ card.label }}</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ card.value }}</p>
                <p v-if="card.hint" class="mt-1 text-xs text-slate-400">{{ card.hint }}</p>
            </div>
        </div>

        <!-- Storage report cards (screenshot-style) -->
        <div class="grid gap-5 xl:grid-cols-3">
            <StorageReportCard
                title="No. &amp; Size of Docs by Document Types"
                label-header="Document Type"
                :rows="reports?.by_document_type || []"
                :center-label="totalSizeLabel(reports?.by_document_type)"
                :colors="palette"
            />

            <div class="space-y-5">
                <StorageReportCard
                    title="Data Usage By Category"
                    label-header="File Categories"
                    :rows="reports?.by_file_category || []"
                    :center-label="totalSizeLabel(reports?.by_file_category)"
                    :colors="categoryColors"
                    view-all
                />
                <DiskUsageCard :disk="reports?.disk" />
            </div>

            <div class="space-y-5">
                <StorageReportCard
                    title="No. &amp; Size of Docs by Department"
                    label-header="Department"
                    :rows="reports?.by_department || []"
                    :center-label="totalSizeLabel(reports?.by_department)"
                    :colors="palette"
                    view-all
                />
                <StorageReportCard
                    title="No. &amp; Size of Docs by Users"
                    label-header="Users"
                    :rows="reports?.by_user || []"
                    :center-label="totalSizeLabel(reports?.by_user)"
                    :colors="palette"
                    view-all
                />
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-950">
                <h2 class="mb-4 font-semibold">Uploads &amp; approvals ({{ selectedDays }} days)</h2>
                <Chart v-if="trendChartData" type="line" :data="trendChartData" :options="lineOptions" class="h-64" />
                <p v-else class="py-12 text-center text-sm text-slate-500">No trend data yet.</p>
            </section>
            <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-950">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-semibold">Recent workflow activity</h2>
                    <RouterLink class="text-sm text-cyan-700 hover:underline dark:text-cyan-300" :to="{ name: 'workflow' }">Open workflow</RouterLink>
                </div>
                <ul v-if="summary?.recent_actions?.length" class="divide-y divide-slate-100 dark:divide-slate-800">
                    <li v-for="item in summary.recent_actions" :key="item.id" class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
                        <div>
                            <p class="font-medium">{{ item.action }} · {{ item.document?.title || 'Document' }}</p>
                            <p class="text-xs text-slate-500">{{ item.actor?.name }} · {{ item.step?.name || '—' }}</p>
                        </div>
                        <span class="text-xs text-slate-500">{{ formatDate(item.acted_at) }}</span>
                    </li>
                </ul>
                <p v-else class="text-sm text-slate-500">No workflow activity yet.</p>
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import Button from 'primevue/button';
import Chart from 'primevue/chart';
import Select from 'primevue/select';
import {
    ArcElement,
    CategoryScale,
    Chart as ChartJS,
    DoughnutController,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';
import api from '@/services/api';
import { useDashboardStore } from '@/modules/dashboard/stores/dashboard';
import StorageReportCard from '@/modules/dashboard/components/StorageReportCard.vue';
import DiskUsageCard from '@/modules/dashboard/components/DiskUsageCard.vue';
import { resolveOrganizationId } from '@/utils/organization';

ChartJS.register(
    ArcElement,
    CategoryScale,
    DoughnutController,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
);

const store = useDashboardStore();
const organizations = ref([]);
const selectedOrg = ref(store.organizationId);
const selectedDays = ref(store.days);
const dayOptions = [
    { label: '7 days', value: 7 },
    { label: '14 days', value: 14 },
    { label: '30 days', value: 30 },
    { label: '60 days', value: 60 },
    { label: '90 days', value: 90 },
];

const summary = computed(() => store.summary);
const reports = computed(() => summary.value?.storage_reports);

const palette = ['#06b6d4', '#22c55e', '#f97316', '#ef4444', '#8b5cf6', '#0ea5e9', '#eab308', '#64748b', '#ec4899', '#14b8a6', '#a855f7', '#f43f5e'];
const categoryColors = {
    Documents: '#f97316',
    'Audio/Video Files': '#22c55e',
    Archives: '#06b6d4',
    Database: '#8b5cf6',
    Backup: '#ef4444',
    Others: '#64748b',
};

const kpiCards = computed(() => {
    const k = summary.value?.kpis || {};
    return [
        { label: 'Documents', value: formatNumber(k.documents_total), hint: formatBytes(k.storage_bytes) },
        { label: 'My pending approvals', value: formatNumber(k.pending_my_approvals), hint: `${formatNumber(k.instances_in_progress)} in progress` },
        { label: 'Under review', value: formatNumber(k.documents_under_review), hint: `${formatNumber(k.documents_draft)} draft` },
        { label: 'Approved', value: formatNumber(k.documents_approved), hint: `${formatNumber(k.documents_rejected)} rejected` },
    ];
});

const trendChartData = computed(() => {
    const trends = summary.value?.trends;
    if (!trends?.labels?.length) return null;
    return {
        labels: trends.labels.map(shortDate),
        datasets: [
            {
                label: 'Uploads',
                data: trends.uploads,
                borderColor: '#0f766e',
                backgroundColor: 'rgba(15, 118, 110, 0.12)',
                fill: true,
                tension: 0.35,
            },
            {
                label: 'Submissions',
                data: trends.submissions,
                borderColor: '#1d4ed8',
                backgroundColor: 'rgba(29, 78, 216, 0.08)',
                fill: true,
                tension: 0.35,
            },
            {
                label: 'Approvals',
                data: trends.approvals,
                borderColor: '#b45309',
                backgroundColor: 'rgba(180, 83, 9, 0.08)',
                fill: true,
                tension: 0.35,
            },
        ],
    };
});

const lineOptions = {
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom' } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
};

function totalSizeLabel(rows) {
    const total = (rows || []).reduce((sum, r) => sum + Number(r.size_bytes || 0), 0);
    return formatBytes(total, true);
}

function formatNumber(value) {
    if (value === undefined || value === null) return '—';
    return Number(value).toLocaleString();
}

function formatBytes(bytes, compact = false) {
    const n = Number(bytes || 0);
    if (!n) return compact ? '0 MB' : '0 B stored';
    const mb = n / (1024 * 1024);
    if (mb >= 1024) {
        return `${(mb / 1024).toFixed(compact ? 1 : 2)} GB${compact ? '' : ' stored'}`;
    }
    return `${mb.toFixed(compact ? 1 : 2)} MB${compact ? '' : ' stored'}`;
}

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleString();
}

function shortDate(value) {
    const d = new Date(value);
    return `${d.getMonth() + 1}/${d.getDate()}`;
}

async function loadOrgs() {
    const { data } = await api.get('/organizations', { params: { per_page: 100 } });
    organizations.value = data.data.data || data.data;
    selectedOrg.value = resolveOrganizationId(organizations.value, selectedOrg.value);
}

async function reload() {
    if (!selectedOrg.value) return;
    store.setOrganization(selectedOrg.value);
    store.days = selectedDays.value;
    try {
        await store.fetchSummary({ days: selectedDays.value });
    } catch {
        // Error message is stored on the dashboard store for the banner.
    }
}

onMounted(async () => {
    await loadOrgs();
    await reload();
});
</script>
