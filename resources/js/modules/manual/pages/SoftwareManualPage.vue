<template>
    <div class="space-y-6">
        <header class="rounded-2xl border border-slate-200/90 bg-gradient-to-br from-white via-brand-50/40 to-white p-6 shadow-sm dark:border-slate-800 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand-600 dark:text-brand-300">Help center</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">
                {{ manualIntro.title }}
            </h1>
            <p class="mt-1 text-sm font-medium text-slate-600 dark:text-slate-300">{{ manualIntro.subtitle }}</p>
            <p class="mt-4 max-w-3xl text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                {{ manualIntro.summary }}
            </p>
            <div class="mt-5 flex flex-wrap gap-2">
                <Button label="Browse topics" icon="pi pi-book" size="small" @click="scrollTo('toc')" />
                <Button label="FAQ" icon="pi pi-question-circle" size="small" outlined @click="scrollTo('faq')" />
            </div>
        </header>

        <div class="grid gap-6 lg:grid-cols-[240px_1fr]">
            <aside
                id="toc"
                class="h-fit rounded-xl border border-slate-200/90 bg-white p-4 shadow-sm lg:sticky lg:top-20 dark:border-slate-800 dark:bg-slate-950"
            >
                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Contents</p>
                <nav class="space-y-1">
                    <button
                        v-for="section in manualSections"
                        :key="section.id"
                        type="button"
                        class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left text-sm text-slate-600 transition hover:bg-brand-50 hover:text-brand-700 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-brand-100"
                        :class="activeSection === section.id ? 'bg-brand-50 font-semibold text-brand-700 dark:bg-slate-900 dark:text-brand-100' : ''"
                        @click="scrollTo(section.id)"
                    >
                        <i :class="['pi text-xs', section.icon]" />
                        <span class="truncate">{{ section.title }}</span>
                    </button>
                    <button
                        type="button"
                        class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left text-sm text-slate-600 transition hover:bg-brand-50 hover:text-brand-700 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-brand-100"
                        :class="activeSection === 'faq' ? 'bg-brand-50 font-semibold text-brand-700 dark:bg-slate-900 dark:text-brand-100' : ''"
                        @click="scrollTo('faq')"
                    >
                        <i class="pi pi-question-circle text-xs" />
                        <span>FAQ</span>
                    </button>
                </nav>
            </aside>

            <div class="space-y-5">
                <section
                    v-for="section in manualSections"
                    :id="section.id"
                    :key="section.id"
                    class="scroll-mt-24 rounded-xl border border-slate-200/90 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-800 dark:bg-slate-950"
                >
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-600 text-white">
                            <i :class="['pi', section.icon]" />
                        </div>
                        <h2 class="font-display text-xl font-semibold text-slate-900 dark:text-white">{{ section.title }}</h2>
                    </div>

                    <div class="space-y-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                        <p v-for="(para, idx) in section.body" :key="idx">{{ para }}</p>
                    </div>

                    <ol
                        v-if="section.steps?.length"
                        class="mt-5 space-y-3 border-t border-slate-100 pt-5 dark:border-slate-800"
                    >
                        <li
                            v-for="(step, idx) in section.steps"
                            :key="step.title"
                            class="flex gap-3"
                        >
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-bold text-brand-700 dark:bg-slate-900 dark:text-brand-200">
                                {{ idx + 1 }}
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ step.title }}</p>
                                <p class="mt-0.5 text-sm text-slate-600 dark:text-slate-400">{{ step.text }}</p>
                            </div>
                        </li>
                    </ol>

                    <ul
                        v-if="section.tips?.length"
                        class="mt-5 space-y-2 rounded-lg border border-slate-100 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/50"
                    >
                        <li
                            v-for="tip in section.tips"
                            :key="tip"
                            class="flex gap-2 text-sm text-slate-600 dark:text-slate-300"
                        >
                            <i class="pi pi-check-circle mt-0.5 text-accent-500" />
                            <span>{{ tip }}</span>
                        </li>
                    </ul>
                </section>

                <section
                    id="faq"
                    class="scroll-mt-24 rounded-xl border border-slate-200/90 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-800 dark:bg-slate-950"
                >
                    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-600 text-white">
                                <i class="pi pi-question-circle" />
                            </div>
                            <div>
                                <h2 class="font-display text-xl font-semibold text-slate-900 dark:text-white">Frequently asked questions</h2>
                                <p class="text-sm text-slate-500">Quick answers to common issues</p>
                            </div>
                        </div>
                        <InputText
                            v-model="faqQuery"
                            placeholder="Filter FAQ…"
                            class="w-full sm:w-56"
                        />
                    </div>

                    <Accordion v-if="filteredFaq.length" :multiple="true" class="manual-faq">
                        <AccordionTab
                            v-for="(item, idx) in filteredFaq"
                            :key="item.q"
                            :header="`${idx + 1}. ${item.q}`"
                        >
                            <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ item.a }}</p>
                        </AccordionTab>
                    </Accordion>
                    <p v-else class="py-8 text-center text-sm text-slate-400">No FAQ matches that filter.</p>
                </section>

                <section class="rounded-xl border border-dashed border-slate-300 bg-slate-50/60 p-5 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-300">
                    <p class="font-semibold text-slate-800 dark:text-slate-100">Need more help?</p>
                    <p class="mt-1">
                        Contact your EDAMS administrator or Softcell Solution Limited support. Include your user email, organization, and steps to reproduce the issue.
                    </p>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import { manualFaq, manualIntro, manualSections } from '@/modules/manual/content/manual.js';

const activeSection = ref('getting-started');
const faqQuery = ref('');

const filteredFaq = computed(() => {
    const q = faqQuery.value.trim().toLowerCase();
    if (!q) return manualFaq;
    return manualFaq.filter((item) => item.q.toLowerCase().includes(q) || item.a.toLowerCase().includes(q));
});

function scrollTo(id) {
    const el = document.getElementById(id);
    if (!el) return;
    activeSection.value = id;
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function onScroll() {
    const ids = [...manualSections.map((s) => s.id), 'faq'];
    let current = ids[0];
    for (const id of ids) {
        const el = document.getElementById(id);
        if (!el) continue;
        if (el.getBoundingClientRect().top <= 120) {
            current = id;
        }
    }
    activeSection.value = current;
}

onMounted(() => {
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
});

onUnmounted(() => {
    window.removeEventListener('scroll', onScroll);
});
</script>

<style scoped>
.manual-faq :deep(.p-accordionheader) {
    font-size: 0.925rem;
    font-weight: 600;
}
</style>
