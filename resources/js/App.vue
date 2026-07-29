<template>
    <Toast />
    <ConfirmDialog />
    <router-view />
</template>

<script setup>
import { onMounted } from 'vue';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import { useTheme } from '@/composables/useTheme';

const { initTheme } = useTheme();

onMounted(() => {
    initTheme();
});
</script>
