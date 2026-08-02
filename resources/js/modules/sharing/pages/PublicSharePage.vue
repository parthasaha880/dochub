<template>
    <div class="mx-auto flex min-h-screen max-w-lg flex-col justify-center px-4 py-10">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">EDAMS shared document</p>
            <h1 class="mt-2 font-display text-2xl font-semibold">{{ info?.document_title || 'Document' }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ info?.extension || 'file' }} · expires {{ formatDate(info?.expires_at) }}</p>

            <div v-if="needsPassword" class="mt-4">
                <InputText v-model="password" type="password" class="w-full" placeholder="Password" />
            </div>

            <div class="mt-6 flex flex-wrap gap-2">
                <Button label="Load / unlock" outlined @click="load" />
                <Button label="View" icon="pi pi-eye" :disabled="!info" @click="openViewer" />
                <Button v-if="info?.allow_download" label="Download" icon="pi pi-download" :disabled="!info" @click="download" />
            </div>
            <p v-if="error" class="mt-3 text-sm text-red-600">{{ error }}</p>
        </div>

        <DocumentViewer
            v-model:visible="showViewer"
            :document="viewerDoc"
            :preview-url="previewUrl"
            :require-auth="false"
            :show-download="!!info?.allow_download"
            :downloading="downloading"
            @download="download"
        />
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import axios from 'axios';
import DocumentViewer from '@/modules/documents/components/DocumentViewer.vue';

const route = useRoute();
const info = ref(null);
const password = ref('');
const error = ref('');
const showViewer = ref(false);
const downloading = ref(false);
const needsPassword = computed(() => info.value?.requires_password);

const viewerDoc = computed(() => {
    if (!info.value) return null;
    return {
        title: info.value.document_title,
        original_name: info.value.original_name || info.value.document_title,
        extension: info.value.extension,
        mime_type: info.value.mime_type,
        size: info.value.size,
    };
});

const previewUrl = computed(() => {
    const token = route.params.token;
    return `/api/v1/public/shares/${token}/preview?password=${encodeURIComponent(password.value || '')}`;
});

function formatDate(v) {
    return v ? new Date(v).toLocaleString() : 'never';
}

async function load() {
    error.value = '';
    try {
        const { data } = await axios.get(`/api/v1/public/shares/${route.params.token}`, {
            params: { password: password.value || undefined },
        });
        info.value = data.data;
    } catch (e) {
        error.value = e.response?.data?.message || 'Unable to open share link';
        if (e.response?.data?.errors?.password) {
            info.value = { ...(info.value || {}), requires_password: true };
        }
    }
}

function openViewer() {
    if (!info.value) return;
    showViewer.value = true;
}

async function download() {
    downloading.value = true;
    try {
        const url = `/api/v1/public/shares/${route.params.token}/download?password=${encodeURIComponent(password.value || '')}`;
        const response = await fetch(url);
        if (!response.ok) throw new Error('Download failed');
        const blob = await response.blob();
        const objectUrl = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = objectUrl;
        a.download = info.value?.original_name || info.value?.document_title || 'document';
        a.click();
        URL.revokeObjectURL(objectUrl);
    } catch (e) {
        error.value = e.message || 'Download failed';
    } finally {
        downloading.value = false;
    }
}

onMounted(load);
</script>
