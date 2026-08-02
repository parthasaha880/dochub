<template>
    <div class="flex min-h-screen items-center justify-center bg-[radial-gradient(ellipse_at_top,#1b4f72_0%,#0e2f45_45%,#020617_100%)] px-4 py-10">
        <div class="w-full max-w-md rounded-2xl border border-white/10 bg-white/95 p-8 shadow-2xl dark:bg-slate-950/90">
            <h1 class="font-display text-2xl font-semibold text-slate-900 dark:text-white">Reset password</h1>
            <p class="mt-2 text-sm text-slate-500">
                Password recovery now uses an email OTP. Continue from Forgot password.
            </p>
            <Button
                class="mt-6 w-full"
                label="Go to Forgot password"
                icon="pi pi-arrow-right"
                @click="router.push({ name: 'forgot-password', query: form.email ? { email: form.email } : {} })"
            />
            <RouterLink class="mt-4 block text-center text-sm text-brand-600" :to="{ name: 'login' }">Back to sign in</RouterLink>
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Button from 'primevue/button';

const route = useRoute();
const router = useRouter();
const form = reactive({
    email: route.query.email || '',
});
</script>
