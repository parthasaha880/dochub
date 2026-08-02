<template>
    <div class="flex min-h-screen items-center justify-center bg-[radial-gradient(ellipse_at_top,#1b4f72_0%,#0e2f45_45%,#020617_100%)] px-4 py-10">
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-white/10 bg-white/95 shadow-2xl backdrop-blur dark:bg-slate-950/90">
            <div class="bg-gradient-to-r from-brand-700 to-brand-600 px-6 py-5 text-white">
                <p class="font-display text-lg font-bold tracking-wide">EDAMS</p>
                <h1 class="mt-1 font-display text-2xl font-semibold">Forgot password</h1>
                <p class="mt-1 text-sm text-white/80">Recover access with a one-time email code</p>
            </div>

            <div class="p-6 sm:p-8">
                <!-- Step 1: request OTP -->
                <form v-if="step === 1" class="space-y-4" @submit.prevent="sendOtp">
                    <p class="text-sm text-slate-500">
                        Enter your account email. We’ll send a 6-digit recovery code if the address exists.
                    </p>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Email</label>
                        <InputText v-model="form.email" type="email" class="w-full" required autocomplete="username" />
                    </div>
                    <Button type="submit" label="Send recovery OTP" icon="pi pi-send" class="w-full" :loading="loading" />
                    <RouterLink class="block text-center text-sm font-medium text-brand-600 hover:underline" :to="{ name: 'login' }">
                        Back to sign in
                    </RouterLink>
                </form>

                <!-- Step 2: OTP + new password -->
                <form v-else class="space-y-4" @submit.prevent="resetWithOtp">
                    <div class="rounded-lg border border-brand-100 bg-brand-50 px-3 py-2 text-sm text-brand-700 dark:border-slate-700 dark:bg-slate-900 dark:text-brand-100">
                        Code sent to <strong>{{ form.email }}</strong>.
                        <span v-if="countdown > 0"> Expires in {{ formatCountdown(countdown) }}.</span>
                        <span v-else class="text-red-600 dark:text-red-400"> Code expired — request a new one.</span>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">6-digit OTP</label>
                        <InputText
                            v-model="form.otp"
                            class="w-full tracking-[0.3em]"
                            maxlength="6"
                            inputmode="numeric"
                            placeholder="••••••"
                            required
                        />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">New password</label>
                        <Password v-model="form.password" class="w-full" input-class="w-full" toggle-mask required />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">Confirm password</label>
                        <Password
                            v-model="form.password_confirmation"
                            class="w-full"
                            input-class="w-full"
                            :feedback="false"
                            toggle-mask
                            required
                        />
                    </div>

                    <Button type="submit" label="Reset password" icon="pi pi-lock" class="w-full" :loading="loading" />
                    <div class="flex items-center justify-between gap-2 text-sm">
                        <button type="button" class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-200" @click="backToEmail">
                            Change email
                        </button>
                        <button type="button" class="font-medium text-brand-600 hover:underline" :disabled="loading" @click="sendOtp">
                            Resend OTP
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue';
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
const step = ref(1);
const expiresIn = ref(10);
const countdown = ref(0);
let timer = null;

const form = reactive({
    email: route.query.email || '',
    otp: '',
    password: '',
    password_confirmation: '',
});

function formatCountdown(seconds) {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m}:${String(s).padStart(2, '0')}`;
}

function startCountdown(minutes) {
    stopCountdown();
    countdown.value = Math.max(1, minutes) * 60;
    timer = setInterval(() => {
        if (countdown.value <= 1) {
            countdown.value = 0;
            stopCountdown();
            return;
        }
        countdown.value -= 1;
    }, 1000);
}

function stopCountdown() {
    if (timer) {
        clearInterval(timer);
        timer = null;
    }
}

function backToEmail() {
    step.value = 1;
    form.otp = '';
    form.password = '';
    form.password_confirmation = '';
    stopCountdown();
}

async function sendOtp() {
    loading.value = true;
    try {
        const data = await auth.forgotPassword(form.email);
        expiresIn.value = data.data?.expires_in_minutes || 10;
        step.value = 2;
        form.otp = '';
        startCountdown(expiresIn.value);
        toast.add({
            severity: 'success',
            summary: 'Check your email',
            detail: data.message || 'Recovery code sent if the account exists.',
            life: 4500,
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Request failed',
            detail: error.response?.data?.message || 'Unable to send recovery code',
            life: 4000,
        });
    } finally {
        loading.value = false;
    }
}

async function resetWithOtp() {
    if (form.password !== form.password_confirmation) {
        toast.add({ severity: 'error', summary: 'Passwords do not match', life: 3000 });
        return;
    }
    loading.value = true;
    try {
        const data = await auth.resetPassword({
            email: form.email,
            otp: form.otp,
            password: form.password,
            password_confirmation: form.password_confirmation,
        });
        toast.add({ severity: 'success', summary: data.message || 'Password updated', life: 4000 });
        router.push({ name: 'login' });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Reset failed',
            detail: error.response?.data?.errors?.otp?.[0]
                || error.response?.data?.errors?.password?.[0]
                || error.response?.data?.message
                || 'Unable to reset password',
            life: 4500,
        });
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    if (form.email) {
        // Prefilled from email link — stay on step 1 so user explicitly requests/resends
        step.value = 1;
    }
});

onBeforeUnmount(stopCountdown);
</script>
