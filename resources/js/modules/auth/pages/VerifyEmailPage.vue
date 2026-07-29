<template>
    <div class="mx-auto max-w-lg rounded-xl border border-slate-200 bg-white p-8 dark:border-slate-800 dark:bg-slate-950">
        <h1 class="font-display text-2xl font-semibold">Verify your email</h1>
        <p class="mt-2 text-sm text-slate-500">
            A verification link was sent to <strong>{{ auth.user?.email }}</strong>.
        </p>
        <Button class="mt-6" label="Resend verification email" :loading="loading" @click="resend" />
    </div>
</template>

<script setup>
import { ref } from 'vue';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import api from '@/services/api';
import { useAuthStore } from '@/modules/auth/stores/auth';

const auth = useAuthStore();
const toast = useToast();
const loading = ref(false);

async function resend() {
    loading.value = true;
    try {
        await api.post('/auth/email/verification-notification');
        toast.add({ severity: 'success', summary: 'Verification email sent', life: 3000 });
    } finally {
        loading.value = false;
    }
}
</script>
