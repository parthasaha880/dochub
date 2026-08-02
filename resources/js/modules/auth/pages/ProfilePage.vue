<template>
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <h1 class="font-display text-3xl font-semibold text-slate-900 dark:text-white">Profile</h1>
            <p class="mt-1 text-sm text-slate-500">Manage your photo and account details</p>
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
                    <label class="mb-1 block text-sm font-medium">Email</label>
                    <InputText :model-value="auth.user?.email" class="w-full" disabled />
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
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
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

const form = reactive({
    name: '',
    phone: '',
    timezone: '',
    locale: '',
});

function syncForm() {
    form.name = auth.user?.name || '';
    form.phone = auth.user?.phone || '';
    form.timezone = auth.user?.timezone || '';
    form.locale = auth.user?.locale || '';
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
</script>
