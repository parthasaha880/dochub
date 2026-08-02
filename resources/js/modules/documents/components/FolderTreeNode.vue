<template>
    <li>
        <div
            class="group flex items-center gap-1 rounded-md px-2 py-1.5 text-sm hover:bg-slate-100 dark:hover:bg-slate-900"
            :class="activeId === folder.id ? 'bg-brand-50 font-semibold text-brand-700 dark:bg-slate-900 dark:text-brand-100' : ''"
            :style="{ paddingLeft: `${0.5 + depth * 0.75}rem` }"
        >
            <button type="button" class="min-w-0 flex-1 truncate text-left" @click="$emit('select', folder.id)">
                <i v-if="folder.is_locked" class="pi pi-lock mr-1 text-xs text-amber-500" title="Locked" />
                <i v-if="folder.is_hidden" class="pi pi-eye-slash mr-1 text-xs text-slate-400" title="Hidden" />
                <i class="pi pi-folder mr-1 text-xs text-brand-500" />
                {{ folder.name }}
            </button>
            <div class="flex shrink-0 opacity-0 transition group-hover:opacity-100 focus-within:opacity-100">
                <Button
                    icon="pi pi-pencil"
                    text
                    rounded
                    size="small"
                    v-tooltip="'Rename'"
                    :disabled="folder.is_locked"
                    @click.stop="$emit('rename', folder)"
                />
                <Button
                    :icon="folder.is_locked ? 'pi pi-lock-open' : 'pi pi-lock'"
                    text
                    rounded
                    size="small"
                    v-tooltip="folder.is_locked ? 'Unlock' : 'Lock'"
                    @click.stop="$emit('toggle-lock', folder)"
                />
                <Button
                    :icon="folder.is_hidden ? 'pi pi-eye' : 'pi pi-eye-slash'"
                    text
                    rounded
                    size="small"
                    v-tooltip="folder.is_hidden ? 'Unhide' : 'Hide'"
                    @click.stop="$emit('toggle-hide', folder)"
                />
                <Button
                    icon="pi pi-trash"
                    text
                    rounded
                    size="small"
                    severity="danger"
                    v-tooltip="'Delete'"
                    :disabled="folder.is_locked"
                    @click.stop="$emit('delete', folder)"
                />
            </div>
        </div>
        <ul v-if="folder.children?.length" class="space-y-0.5">
            <FolderTreeNode
                v-for="child in folder.children"
                :key="child.id"
                :folder="child"
                :depth="depth + 1"
                :active-id="activeId"
                @select="$emit('select', $event)"
                @rename="$emit('rename', $event)"
                @toggle-lock="$emit('toggle-lock', $event)"
                @toggle-hide="$emit('toggle-hide', $event)"
                @delete="$emit('delete', $event)"
            />
        </ul>
    </li>
</template>

<script setup>
import Button from 'primevue/button';

defineOptions({ name: 'FolderTreeNode' });

defineProps({
    folder: { type: Object, required: true },
    depth: { type: Number, default: 0 },
    activeId: { type: String, default: null },
});

defineEmits(['select', 'rename', 'toggle-lock', 'toggle-hide', 'delete']);
</script>
