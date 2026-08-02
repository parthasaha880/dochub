<template>
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <h1 class="font-display text-3xl font-semibold text-slate-900 dark:text-white">Profile</h1>
            <p class="mt-1 text-sm text-slate-500">Manage your photo, account details, and email</p>
        </div>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
            <div class="flex flex-wrap items-center gap-5">
                <UserAvatar
                    :name="form.name"
                    :avatar-url="auth.user?.avatar_url"
                    size-class="h-20 w-20 text-xl"
                />
                <div class="min-w-0 flex-1">
                    <p class="font-medium text-slate-900 dark:text-white">{{ auth.user?.name }}</p>
                    <p class="text-sm text-slate-500">{{ auth.user?.email }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFilePick" />
                        <Button label="Upload photo" icon="pi pi-camera" size="small" :loading="uploading" @click="fileInput?.click()" />
                        <Button
                            v-if="auth.user?.avatar_url"
                            label="Remove"
                            icon="pi pi-times"
                            size="small"
                            severity="secondary"
                            outlined
                            :loading="removing"
                            @click="removePhoto"
                        />
                    </div>
                    <p class="mt-2 text-xs text-slate-400">JPG, PNG, or WEBP up to 2 MB</p>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Account</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Full name</label>
                    <InputText v-model="form.name" class="w-full" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Phone</label>
                    <InputText v-model="form.phone" class="w-full" placeholder="Optional" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Timezone</label>
                    <InputText v-model="form.timezone" class="w-full" placeholder="e.g. Asia/Dhaka" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Locale</label>
                    <InputText v-model="form.locale" class="w-full" placeholder="e.g. en" />
                </div>
            </div>
            <div class="mt-5 flex justify-end">
                <Button label="Save changes" icon="pi pi-check" :loading="saving" @click="saveProfile" />
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
            <h2 class="mb-1 text-sm font-semibold uppercase tracking-wide text-slate-500">Change email</h2>
            <p class="mb-4 text-sm text-slate-500">
                A one-time code will be sent to your <strong>current</strong> email
                (<span class="text-slate-700 dark:text-slate-200">({{ auth.user?.email }})</span>.
                The code expires in {{ otpExpiresIn || 10 }} minutes.
            </p>

            <div v-if="!otpSent" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">New email address</label>
                    <InputText v-model="emailForm.newEmail" type="email" class="w-full" placeholder="new@example.com" />
                </div>
                <div class="flex justify-end">
                    <Button
                        label="Send OTP"
                        icon="pi pi-send"
                        :loading="sendingOtp"
                        :disabled="!emailForm.newEmail"
                        @click="sendOtp"
                    />
                </div>
            </div>

            <div v-else class="space-y-4">
                <div class="rounded-lg border border-brand-100 bg-brand-50 px-3 py-2 text-sm text-brand-700 dark:border-slate-700 dark:bg-slate-900 dark:text-brand-100">
                    Code sent to {{ auth.user?.email }}. Changing to <strong>{{ emailForm.newEmail }}</strong>.
                    <span v-if="otpCountdown > 0">Expires in {{ formatCountdown(otpCountdown) }}.</span>
                    <span v-else class="text-red-600 dark:text-red-400">Code expired — request a new one.</span>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Enter 6-digit OTP</label>
                    <InputText
                        v-model="emailForm.otp"
                        class="w-full tracking-[0.3em]"
                        maxlength="6"
                        placeholder="••••••"
                        inputmode="numeric"
                    />
                </div>
                <div class="flex flex-wrap justify-end gap-2">
                    <Button label="Cancel" text severity="secondary" @click="resetEmailChange" />
                    <Button label="Resend OTP" outlined severity="secondary" :loading="sendingOtp" :disabled="otpCountdown > 0 && otpCountdown > (otpExpiresIn * 60 - 30)" @click="sendOtp" />
                    <Button label="Verify & change email" icon="pi pi-check" :loading="confirmingOtp" :disabled="emailForm.otp.length !== 6" @click="confirmOtp" />
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import { useToast } from 'primevue/usetoast';
import UserAvatar from '@/components/UserAvatar.vue';
import { useAuthStore } from '@/modules/auth/stores/auth';

const auth = useAuthStore();
const toast = useToast();
const fileInput = ref(null);
const saving = ref(false);
const uploading = ref(false);
const removing = ref(false);
const sendingOtp = ref(false);
const confirmingOtp = ref(false);
const otpSent = ref(false);
const otpExpiresIn = ref(10);
const otpCountdown = ref(0);
let countdownTimer = null;

const form = reactive({
    name: '',
    phone: '',
    timezone: '',
    locale: '',
});

const emailForm = reactive({
    newEmail: '',
    otp: '',
});

function syncForm() {
    form.name = auth.user?.name || '';
    form.phone = auth.user?.phone || '';
    form.timezone = auth.user?.timezone || '';
    form.locale = auth.user?.locale || '';
}

function formatCountdown(seconds) {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m}:${String(s).padStart(2, '0')}`;
}

function startCountdown(minutes) {
    stopCountdown();
    otpCountdown.value = Math.max(1, minutes) * 60;
    countdownTimer = setInterval(() => {
        if (otpCountdown.value <= 1) {
            otpCountdown.value = 0;
            stopCountdown();
            return;
        }
        otpCountdown.value -= 1;
    }, 1000);
}

function stopCountdown() {
    if (countdownTimer) {
        clearInterval(countdownTimer);
        countdownTimer = null;
    }
}

function resetEmailChange() {
    otpSent.value = false;
    emailForm.otp = '';
    emailForm.newEmail = '';
    stopCountdown();
    otpCountdown.value = 0;
}

async function saveProfile() {
    saving.value = true;
    try {
        await auth.updateProfile({
            name: form.name,
            phone: form.phone || null,
            timezone: form.timezone || null,
            locale: form.locale || null,
        });
        toast.add({ severity: 'success', summary: 'Profile saved', life: 2500 });
        syncForm();
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'Save failed',
            detail: e.response?.data?.message || e.message,
            life: 4000,
        });
    } finally {
        saving.value = false;
    }
}

async function sendOtp() {
    sendingOtp.value = true;
    try {
        const result = await auth.requestEmailChange(emailForm.newEmail);
        otpExpiresIn.value = result.expires_in_minutes || 10;
        otpSent.value = true;
        emailForm.otp = '';
        startCountdown(otpExpiresIn.value);
        toast.add({
            severity: 'success',
            summary: 'OTP sent',
            detail: `Check ${auth.user?.email}`,
            life: 4000,
        });
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'Could not send OTP',
            detail: e.response?.data?.errors?.email?.[0] || e.response?.data?.message || e.message,
            life: 4500,
        });
    } finally {
        sendingOtp.value = false;
    }
}

async function confirmOtp() {
    confirmingOtp.value = true;
    try {
        await auth.confirmEmailChange(emailForm.otp);
        toast.add({ severity: 'success', summary: 'Email updated', life: 3000 });
        resetEmailChange();
        syncForm();
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'Verification failed',
            detail: e.response?.data?.errors?.otp?.[0] || e.response?.data?.message || e.message,
            life: 4500,
        });
    } finally {
        confirmingOtp.value = false;
    }
}

async function onFilePick(event) {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file) return;
    uploading.value = true;
    try {
        await auth.uploadAvatar(file);
        toast.add({ severity: 'success', summary: 'Photo updated', life: 2500 });
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'Upload failed',
            detail: e.response?.data?.message || e.message,
            life: 4000,
        });
    } finally {
        uploading.value = false;
    }
}

async function removePhoto() {
    removing.value = true;
    try {
        await auth.removeAvatar();
        toast.add({ severity: 'success', summary: 'Photo removed', life: 2500 });
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'Remove failed',
            detail: e.response?.data?.message || e.message,
            life: 4000,
        });
    } finally {
        removing.value = false;
    }
}

onMounted(async () => {
    if (!auth.user) await auth.fetchMe();
    syncForm();
});

onBeforeUnmount(stopCountdown);
</script>
