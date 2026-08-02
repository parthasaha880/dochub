<template>
    <span
        class="inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-brand-600 font-semibold text-white shadow-sm dark:border-slate-700"
        :class="sizeClass"
        :aria-label="name || 'User'"
        role="img"
    >
        <img
            v-if="photoUrl"
            :src="photoUrl"
            :alt="name || 'User photo'"
            class="h-full w-full object-cover"
            @error="onImgError"
        />
        <span v-else>{{ initials }}</span>
    </span>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    name: { type: String, default: '' },
    avatarUrl: { type: String, default: null },
    sizeClass: { type: String, default: 'h-9 w-9 text-sm' },
});

const photoUrl = ref(null);

const initials = computed(() => {
    const parts = String(props.name || '?').trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return '?';
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
});

function revokePhoto() {
    if (photoUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(photoUrl.value);
    }
    photoUrl.value = null;
}

async function loadPhoto() {
    revokePhoto();
    if (!props.avatarUrl) return;

    try {
        const token = localStorage.getItem('edams_token');
        const response = await fetch(props.avatarUrl, {
            headers: token ? { Authorization: `Bearer ${token}` } : {},
        });
        if (!response.ok) throw new Error('avatar missing');
        const blob = await response.blob();
        photoUrl.value = URL.createObjectURL(blob);
    } catch {
        photoUrl.value = null;
    }
}

function onImgError() {
    revokePhoto();
}

watch(() => props.avatarUrl, loadPhoto);
onMounted(loadPhoto);
onBeforeUnmount(revokePhoto);
</script>
