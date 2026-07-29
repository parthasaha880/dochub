<template>
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
        <div class="h-1 bg-cyan-500" />
        <div class="p-4">
            <h2 class="mb-3 text-center text-sm font-semibold text-slate-800 dark:text-slate-100">Total Disk Space Usage</h2>

            <div class="relative mx-auto mb-4 h-44 max-w-[220px]">
                <Chart v-if="chartData" type="doughnut" :data="chartData" :options="chartOptions" class="h-44" />
                <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        {{ disk?.quota_gb ?? 10 }}.0 GB
                    </span>
                </div>
            </div>

            <table class="w-full text-left text-xs">
                <thead class="text-slate-500">
                    <tr>
                        <th class="pb-2 font-medium">Space</th>
                        <th class="pb-2 text-right font-medium">Size</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in disk?.rows || []" :key="row.label" class="border-t border-slate-100 dark:border-slate-800">
                        <td class="py-1.5">
                            <span class="inline-flex items-center gap-2">
                                <span
                                    class="inline-block h-2.5 w-2.5 rounded-full border-2"
                                    :style="{ borderColor: row.color, background: row.label === 'Used Space' ? row.color : 'transparent' }"
                                />
                                {{ row.label }}
                            </span>
                        </td>
                        <td class="py-1.5 text-right tabular-nums">{{ formatGb(row.size_bytes) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import Chart from 'primevue/chart';

const props = defineProps({
    disk: { type: Object, default: null },
});

const chartData = computed(() => {
    const rows = props.disk?.rows || [];
    if (!rows.length) {
        return {
            labels: ['Quota'],
            datasets: [{ data: [1], backgroundColor: ['#e2e8f0'], borderWidth: 0 }],
        };
    }

    // Prefer used vs free for the ring; include over as pink slice if present
    const used = Number(props.disk?.used_bytes || 0);
    const free = Number(props.disk?.free_bytes || 0);
    const over = Number(props.disk?.over_bytes || 0);
    const quota = Number(props.disk?.quota_bytes || 0);

    if (over > 0) {
        return {
            labels: ['Used (quota)', 'Over Usage'],
            datasets: [{
                data: [quota, over],
                backgroundColor: ['#ef4444', '#f9a8d4'],
                borderWidth: 0,
            }],
        };
    }

    return {
        labels: ['Used Space', 'Free Space'],
        datasets: [{
            data: [Math.max(used, 0.0001), Math.max(free, 0.0001)],
            backgroundColor: ['#ef4444', '#22c55e'],
            borderWidth: 0,
        }],
    };
});

const chartOptions = {
    cutout: '70%',
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
};

function formatGb(bytes) {
    const n = Number(bytes || 0);
    return `${(n / (1024 * 1024 * 1024)).toFixed(2)} GB`;
}
</script>
