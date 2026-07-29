<template>
    <div class="mx-auto w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <h1 class="font-display text-2xl font-semibold">Forgot password</h1>
        <p class="mt-2 text-sm text-slate-500">We will email you a secure reset link.</p>

        <form class="mt-6 space-y-4" @submit.prevent="onSubmit">
            <div>
                <label class="mb-2 block text-sm font-medium">Email</label>
                <InputText v-model="email" type="email" class="w-full" required />
            </div>
            <Button type="submit" label="Send reset link" class="w-full" :loading="loading" />
            <RouterLink class="block text-center text-sm text-brand-600" :to="{ name: 'login' }">Back to sign in</RouterLink>
        </form>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import { useToast } from 'primevue/usetoast';
import { useAuthStore } from '@/modules/auth/stores/auth';

const auth = useAuthStore();
const toast = useToast();
const email = ref('');
const loading = ref(false);

async function onSubmit() {
    loading.value = true;
    try {
        const data = await auth.forgotPassword(email.value);
        toast.add({ severity: 'success', summary: data.message, life: 4000 });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Request failed',
            detail: error.response?.data?.message || 'Unable to send reset link',
            life: 4000,
        });
    } finally {
        loading.value = false;
    }
}
</script>
