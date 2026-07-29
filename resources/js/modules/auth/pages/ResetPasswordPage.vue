<template>
    <div class="mx-auto w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <h1 class="font-display text-2xl font-semibold">Reset password</h1>
        <p class="mt-2 text-sm text-slate-500">Choose a strong password for your EDAMS account.</p>

        <form class="mt-6 space-y-4" @submit.prevent="onSubmit">
            <div>
                <label class="mb-2 block text-sm font-medium">Email</label>
                <InputText v-model="form.email" type="email" class="w-full" required />
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">New password</label>
                <Password v-model="form.password" class="w-full" input-class="w-full" toggle-mask required />
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium">Confirm password</label>
                <Password v-model="form.password_confirmation" class="w-full" input-class="w-full" :feedback="false" toggle-mask required />
            </div>
            <Button type="submit" label="Update password" class="w-full" :loading="loading" />
        </form>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import { useToast } from 'primevue/usetoast';
import { useAuthStore } from '@/modules/auth/stores/auth';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();
const toast = useToast();
const loading = ref(false);

const form = reactive({
    token: route.query.token || '',
    email: route.query.email || '',
    password: '',
    password_confirmation: '',
});

async function onSubmit() {
    loading.value = true;
    try {
        const data = await auth.resetPassword(form);
        toast.add({ severity: 'success', summary: data.message, life: 4000 });
        router.push({ name: 'login' });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Reset failed',
            detail: error.response?.data?.message || 'Unable to reset password',
            life: 4000,
        });
    } finally {
        loading.value = false;
    }
}
</script>
