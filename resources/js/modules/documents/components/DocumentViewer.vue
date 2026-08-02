<template>
    <Dialog
        :visible="visible"
        modal
        maximizable
        :header="document?.title || document?.original_name || 'Document viewer'"
        class="w-full max-w-6xl"
        content-class="!p-0"
        @update:visible="onVisible"
    >
        <div class="flex min-h-[60vh] flex-col bg-slate-950">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-800 bg-slate-900 px-4 py-2">
                <div class="min-w-0 text-sm text-slate-300">
                    <span class="font-medium text-white">{{ document?.original_name || document?.title }}</span>
                    <span v-if="document?.extension" class="ml-2 uppercase text-slate-500">.{{ document.extension }}</span>
                    <span v-if="document?.size" class="ml-2 text-slate-500">{{ formatSize(document.size) }}</span>
                </div>
                <div class="flex gap-2">
                    <Button
                        v-if="showDownload"
                        label="Download"
                        icon="pi pi-download"
                        size="small"
                        outlined
                        :loading="downloading"
                        @click="emit('download')"
                    />
                    <Button icon="pi pi-times" text rounded severity="secondary" @click="close" />
                </div>
            </div>

            <div class="relative flex flex-1 items-center justify-center overflow-auto p-4">
                <div v-if="loading" class="flex flex-col items-center gap-3 text-slate-400">
                    <i class="pi pi-spin pi-spinner text-3xl" />
                    <p class="text-sm">Loading preview…</p>
                </div>

                <div v-else-if="error" class="max-w-md text-center text-slate-300">
                    <i class="pi pi-exclamation-circle mb-3 text-3xl text-amber-400" />
                    <p class="mb-4 text-sm">{{ error }}</p>
                    <Button label="Download instead" icon="pi pi-download" :disabled="!showDownload" @click="emit('download')" />
                </div>

                <iframe
                    v-else-if="kind === 'pdf' && objectUrl"
                    :src="objectUrl"
                    title="PDF preview"
                    class="h-[70vh] w-full rounded-md border-0 bg-white"
                />

                <img
                    v-else-if="kind === 'image' && objectUrl"
                    :src="objectUrl"
                    :alt="document?.title || 'Image preview'"
                    class="max-h-[70vh] max-w-full rounded-md object-contain shadow-lg"
                />

                <video
                    v-else-if="kind === 'video' && objectUrl"
                    :src="objectUrl"
                    controls
                    class="max-h-[70vh] w-full max-w-4xl rounded-md bg-black"
                />

                <audio
                    v-else-if="kind === 'audio' && objectUrl"
                    :src="objectUrl"
                    controls
                    class="w-full max-w-xl"
                />

                <pre
                    v-else-if="kind === 'text'"
                    class="max-h-[70vh] w-full overflow-auto rounded-md bg-slate-900 p-4 text-left text-sm leading-relaxed text-slate-100 whitespace-pre-wrap break-words"
                >{{ textContent }}</pre>

                <div
                    v-else-if="kind === 'docx'"
                    class="docx-preview max-h-[70vh] w-full overflow-auto rounded-md bg-white p-6 text-left text-slate-900 [&_h1]:mb-3 [&_h1]:text-2xl [&_h1]:font-semibold [&_h2]:mb-2 [&_h2]:text-xl [&_h2]:font-semibold [&_p]:mb-2 [&_ul]:mb-2 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:mb-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_table]:w-full [&_table]:border-collapse [&_td]:border [&_td]:border-slate-200 [&_td]:p-2 [&_th]:border [&_th]:border-slate-200 [&_th]:p-2"
                    v-html="docxHtml"
                />

                <div v-else class="max-w-md text-center text-slate-300">
                    <i class="pi pi-eye-slash mb-3 text-3xl text-slate-500" />
                    <p class="mb-2 text-sm font-medium text-white">Preview not available for this file type</p>
                    <p class="mb-4 text-sm text-slate-400">Download the file to open it in a desktop application.</p>
                    <Button v-if="showDownload" label="Download" icon="pi pi-download" @click="emit('download')" />
                </div>
            </div>
        </div>
    </Dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import { previewKind } from '@/utils/filePreview';

const props = defineProps({
    visible: { type: Boolean, default: false },
    document: { type: Object, default: null },
    previewUrl: { type: String, default: '' },
    downloading: { type: Boolean, default: false },
    /** When false, fetch without Bearer token (public share links). */
    requireAuth: { type: Boolean, default: true },
    showDownload: { type: Boolean, default: true },
});

const emit = defineEmits(['update:visible', 'download']);

const loading = ref(false);
const error = ref('');
const objectUrl = ref(null);
const textContent = ref('');
const docxHtml = ref('');

const kind = computed(() => (props.document ? previewKind(props.document) : 'unsupported'));

function formatSize(bytes) {
    const n = Number(bytes) || 0;
    if (n < 1024) return `${n} B`;
    if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`;
    return `${(n / (1024 * 1024)).toFixed(1)} MB`;
}

function revoke() {
    if (objectUrl.value) {
        URL.revokeObjectURL(objectUrl.value);
        objectUrl.value = null;
    }
    textContent.value = '';
    docxHtml.value = '';
    error.value = '';
}

function close() {
    emit('update:visible', false);
}

function onVisible(value) {
    emit('update:visible', value);
}

async function loadPreview() {
    revoke();
    if (!props.visible || !props.document || !props.previewUrl) return;

    if (kind.value === 'unsupported') {
        loading.value = false;
        return;
    }

    loading.value = true;
    error.value = '';

    try {
        const headers = { Accept: '*/*' };
        if (props.requireAuth) {
            const token = localStorage.getItem('edams_token');
            if (token) headers.Authorization = `Bearer ${token}`;
        }
        const response = await fetch(props.previewUrl, { headers });

        if (!response.ok) {
            throw new Error(response.status === 404 ? 'File not found on server.' : 'Unable to load preview.');
        }

        const blob = await response.blob();
        const mime = props.document.mime_type || blob.type || 'application/octet-stream';

        if (kind.value === 'text') {
            textContent.value = await blob.text();
            return;
        }

        if (kind.value === 'docx') {
            const mammoth = await import('mammoth');
            const arrayBuffer = await blob.arrayBuffer();
            const result = await mammoth.convertToHtml({ arrayBuffer });
            docxHtml.value = result.value || '<p><em>Empty document</em></p>';
            return;
        }

        const typed = blob.type ? blob : new Blob([blob], { type: mime });
        objectUrl.value = URL.createObjectURL(typed);
    } catch (e) {
        error.value = e.message || 'Preview failed.';
    } finally {
        loading.value = false;
    }
}

watch(
    () => [props.visible, props.document?.id, props.previewUrl],
    () => {
        if (props.visible) {
            loadPreview();
        } else {
            revoke();
        }
    },
);
</script>
