<template>
    <div>
        <div
            class="flex flex-wrap items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 dark:border-slate-800 dark:bg-slate-950"
            :style="{ marginLeft: `${depth * 1.25}rem` }"
        >
            <Tag :value="node.type" class="capitalize" :severity="typeSeverity" />
            <span class="font-mono text-xs text-slate-500">{{ node.code }}</span>
            <span class="font-medium text-slate-800 dark:text-slate-100">{{ node.name }}</span>
            <span v-if="node.barcode" class="ml-auto hidden text-xs text-slate-400 sm:inline">{{ node.barcode }}</span>
            <div class="ml-auto flex items-center gap-1 sm:ml-0">
                <Button
                    v-if="canAddChild"
                    icon="pi pi-plus"
                    text
                    rounded
                    size="small"
                    v-tooltip.top="`Add ${childLabel}`"
                    @click="$emit('add-child', node)"
                />
                <Button icon="pi pi-pencil" text rounded size="small" @click="$emit('edit', node)" />
                <Button icon="pi pi-trash" text rounded size="small" severity="danger" @click="$emit('remove', node)" />
            </div>
        </div>
        <div v-if="node.children?.length" class="mt-2 space-y-2">
            <LocationNode
                v-for="child in node.children"
                :key="child.id"
                :node="child"
                :depth="depth + 1"
                @add-child="$emit('add-child', $event)"
                @edit="$emit('edit', $event)"
                @remove="$emit('remove', $event)"
            />
        </div>
    </div>
</template>

<script setup>
defineOptions({ name: 'LocationNode' });

import { computed } from 'vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const props = defineProps({
    node: { type: Object, required: true },
    depth: { type: Number, default: 0 },
});

defineEmits(['add-child', 'edit', 'remove']);

const childMap = {
    room: 'rack',
    rack: 'shelf',
    shelf: 'box',
    box: 'file',
};

const canAddChild = computed(() => Boolean(childMap[props.node.type]));
const childLabel = computed(() => childMap[props.node.type] || '');

const typeSeverity = computed(() => {
    const map = { room: 'info', rack: 'secondary', shelf: 'contrast', box: 'warn', file: 'success' };
    return map[props.node.type] || 'secondary';
});
</script>
