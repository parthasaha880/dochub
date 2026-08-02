<template>
    <div class="flex min-h-screen items-center justify-center bg-[radial-gradient(ellipse_at_top,#1b4f72_0%,#0e2f45_45%,#020617_100%)] px-4 py-10">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.04\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40" />

        <div class="relative grid w-full max-w-5xl overflow-hidden rounded-2xl border border-white/10 bg-white/95 shadow-2xl backdrop-blur dark:bg-slate-950/90 lg:grid-cols-2">
            <section class="hidden flex-col justify-between bg-gradient-to-br from-brand-700 via-brand-600 to-accent-500 p-10 text-white lg:flex">
                <div>
                    <p class="font-display text-3xl font-bold tracking-tight">EDAMS</p>
                    <p class="mt-2 text-sm text-white/80">Enterprise Document Archiving & Records Management</p>
                </div>
                <div>
                    <h1 class="font-display text-3xl font-semibold leading-tight">Secure records. Trusted access.</h1>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/85">
                        Government-grade authentication with session control, device tracking, and complete login activity auditing.
                    </p>
                </div>
                <p class="text-xs text-white/70">Built for Banks, Hospitals, Universities & Public Sector</p>
            </section>

            <section class="p-8 sm:p-10">
                <div class="mb-8 lg:hidden">
                    <p class="font-display text-2xl font-bold text-brand-700 dark:text-brand-100">EDAMS</p>
                    <p class="text-sm text-slate-500">Sign in to continue</p>
                </div>

                <h2 class="font-display text-2xl font-semibold text-slate-900 dark:text-white">Sign in</h2>
                <p class="mt-1 text-sm text-slate-500">Use your organization credentials</p>

                <form class="mt-8 space-y-5" @submit.prevent="onSubmit">
                    <div>
                        <label class="mb-2 block text-sm font-medium">Email</label>
                        <InputText v-model="form.email" type="email" class="w-full" autocomplete="username" required />
                        <small v-if="fieldError('email')" class="mt-1 block text-red-600">{{ fieldError('email') }}</small>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Password</label>
                        <Password
                            v-model="form.password"
                            class="w-full"
                            input-class="w-full"
                            :feedback="false"
                            toggle-mask
                            autocomplete="current-password"
                            required
                        />
                        <small v-if="fieldError('password')" class="mt-1 block text-red-600">{{ fieldError('password') }}</small>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Checkbox v-model="form.remember" binary input-id="remember" />
                            <label for="remember" class="text-sm">Remember me</label>
                        </div>
                        <RouterLink class="text-sm font-medium text-brand-600 hover:underline" :to="{ name: 'forgot-password' }">
                            Forgot password?
                        </RouterLink>
                    </div>

                    <Button type="submit" label="Sign in" class="w-full" :loading="auth.loading" />
                </form>
            </section>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import { useAuthStore } from '@/modules/auth/stores/auth';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();
const toast = useToast();

const form = reactive({
    email: '',
    password: '',
    remember: true,
});

const errors = ref({});

function fieldError(key) {
    const value = errors.value?.[key];
    if (!value) return '';
    return Array.isArray(value) ? value[0] : String(value);
}

function normalizeEmail(email) {
    let value = String(email || '').trim().toLowerCase();
    // Common autofill / typo fixes for email TLDs
    value = value
        .replace(/\.ocm$/i, '.com')
        .replace(/\.con$/i, '.com')
        .replace(/\.cpm$/i, '.com')
        .replace(/\.comm$/i, '.com');
    return value;
}

async function onSubmit() {
    errors.value = {};
    form.email = normalizeEmail(form.email);
    try {
        await auth.login(form);
        toast.add({ severity: 'success', summary: 'Welcome back', life: 2500 });
        router.push(route.query.redirect || { name: 'dashboard' });
    } catch (error) {
        const payload = error.response?.data || {};
        errors.value = payload.errors || {};
        toast.add({
            severity: 'error',
            summary: 'Login failed',
            detail: fieldError('email') || payload.message || 'Unable to sign in',
            life: 4000,
        });
    }
}
</script>
