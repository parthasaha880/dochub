<template>
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
        <div class="h-1 bg-cyan-500" />
        <div class="p-4">
            <h2 class="mb-3 text-center text-sm font-semibold text-slate-800 dark:text-slate-100">{{ title }}</h2>

            <div class="relative mx-auto mb-4 h-44 max-w-[220px]">
                <Chart v-if="chartData" type="doughnut" :data="chartData" :options="chartOptions" class="h-44" />
                <div
                    v-if="chartData"
                    class="pointer-events-none absolute inset-0 flex items-center justify-center"
                >
                    <span class="px-2 text-center text-sm font-semibold text-slate-700 dark:text-slate-200">{{ centerLabel }}</span>
                </div>
                <p v-else class="flex h-full items-center justify-center text-sm text-slate-400">No data</p>
            </div>

            <div class="max-h-72 overflow-auto">
                <table class="w-full text-left text-xs">
                    <thead class="sticky top-0 bg-white text-slate-500 dark:bg-slate-950">
                        <tr>
                            <th class="pb-2 font-medium">{{ labelHeader }}</th>
                            <th class="pb-2 text-right font-medium">No.</th>
                            <th class="pb-2 text-right font-medium">Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, idx) in rows" :key="row.label" class="border-t border-slate-100 dark:border-slate-800">
                            <td class="py-1.5 pr-2">
                                <span class="inline-flex items-center gap-2">
                                    <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full" :style="{ background: colorAt(idx, row.label) }" />
                                    <span class="truncate">{{ row.label }}</span>
                                </span>
                            </td>
                            <td class="py-1.5 text-right tabular-nums">{{ row.count ?? '—' }}</td>
                            <td class="py-1.5 text-right tabular-nums">{{ formatMb(row.size_bytes) }}</td>
                        </tr>
                        <tr v-if="!rows.length">
                            <td colspan="3" class="py-4 text-center text-slate-400">No documents yet</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="viewAll" class="mt-3 text-center">
                <RouterLink class="text-sm text-cyan-700 hover:underline dark:text-cyan-300" :to="{ name: 'documents' }">
                    View All Documents
                </RouterLink>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import Chart from 'primevue/chart';

const props = defineProps({
    title: { type: String, required: true },
    rows: { type: Array, default: () => [] },
    centerLabel: { type: String, default: '0 MB' },
    colors: { type: [Array, Object], default: () => [] },
    viewAll: { type: Boolean, default: false },
    labelHeader: { type: String, default: 'Name' },
});

const chartData = computed(() => {
    if (!props.rows?.length) return null;
    return {
        labels: props.rows.map((r) => r.label),
        datasets: [{
            data: props.rows.map((r) => Number(r.size_bytes) || Number(r.count) || 0),
            backgroundColor: props.rows.map((r, i) => colorAt(i, r.label)),
            borderWidth: 0,
            hoverOffset: 4,
        }],
    };
});

const chartOptions = {
    cutout: '62%',
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label(ctx) {
                    const row = props.rows[ctx.dataIndex];
                    if (!row) return ctx.formattedValue;
                    return `${row.label}: ${row.count ?? 0} · ${formatMb(row.size_bytes)}`;
                },
            },
        },
    },
};

function colorAt(idx, label) {
    if (Array.isArray(props.colors)) {
        return props.colors[idx % props.colors.length];
    }
    return props.colors[label] || '#94a3b8';
}

function formatMb(bytes) {
    const n = Number(bytes || 0);
    if (!n) return '0 MB';
    const mb = n / (1024 * 1024);
    if (mb >= 1024) return `${(mb / 1024).toFixed(2)} GB`;
    return `${mb.toFixed(2)} MB`;
}
</script>
