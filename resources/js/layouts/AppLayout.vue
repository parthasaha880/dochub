<template>
    <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,#d5e6f5,transparent_40%),linear-gradient(180deg,#f8fafc,#eef5fb)] dark:bg-[radial-gradient(circle_at_top_left,#0e2f45,transparent_40%),linear-gradient(180deg,#020617,#0f172a)]">
        <header class="border-b border-slate-200/80 bg-white/80 backdrop-blur dark:border-slate-800 dark:bg-slate-950/80">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-600 text-sm font-bold text-white shadow-sm">
                        EH
                    </div>
                    <div>
                        <p class="font-display text-sm font-semibold tracking-wide text-brand-700 dark:text-brand-100">EDAMS</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Enterprise Document Archiving</p>
                    </div>
                </div>

                <nav class="hidden items-center gap-6 text-sm md:flex">
                    <RouterLink
                        class="text-slate-600 transition hover:text-brand-600 dark:text-slate-300 dark:hover:text-white"
                        active-class="font-semibold text-brand-700 dark:text-white"
                        :to="{ name: 'dashboard' }"
                    >Dashboard</RouterLink>
                    <RouterLink
                        class="text-slate-600 transition hover:text-brand-600 dark:text-slate-300 dark:hover:text-white"
                        active-class="font-semibold text-brand-700 dark:text-white"
                        :to="{ name: 'organization' }"
                    >Organization</RouterLink>
                    <RouterLink
                        class="text-slate-600 transition hover:text-brand-600 dark:text-slate-300 dark:hover:text-white"
                        active-class="font-semibold text-brand-700 dark:text-white"
                        :to="{ name: 'documents' }"
                    >Documents</RouterLink>
                    <RouterLink
                        class="text-slate-600 transition hover:text-brand-600 dark:text-slate-300 dark:hover:text-white"
                        active-class="font-semibold text-brand-700 dark:text-white"
                        :to="{ name: 'search' }"
                    >Search</RouterLink>
                    <RouterLink
                        class="text-slate-600 transition hover:text-brand-600 dark:text-slate-300 dark:hover:text-white"
                        active-class="font-semibold text-brand-700 dark:text-white"
                        :to="{ name: 'workflow' }"
                    >Workflow</RouterLink>
                    <RouterLink
                        class="text-slate-600 transition hover:text-brand-600 dark:text-slate-300 dark:hover:text-white"
                        active-class="font-semibold text-brand-700 dark:text-white"
                        :to="{ name: 'operations' }"
                    >Operations</RouterLink>
                    <RouterLink
                        class="text-slate-600 transition hover:text-brand-600 dark:text-slate-300 dark:hover:text-white"
                        active-class="font-semibold text-brand-700 dark:text-white"
                        :to="{ name: 'users' }"
                    >Users & Roles</RouterLink>
                    <RouterLink
                        class="text-slate-600 transition hover:text-brand-600 dark:text-slate-300 dark:hover:text-white"
                        active-class="font-semibold text-brand-700 dark:text-white"
                        :to="{ name: 'login-activity' }"
                    >Login Activity</RouterLink>
                    <RouterLink
                        class="text-slate-600 transition hover:text-brand-600 dark:text-slate-300 dark:hover:text-white"
                        active-class="font-semibold text-brand-700 dark:text-white"
                        :to="{ name: 'sessions' }"
                    >Devices</RouterLink>
                </nav>

                <div class="flex items-center gap-2">
                    <Button
                        type="button"
                        severity="secondary"
                        text
                        rounded
                        :icon="isDark ? 'pi pi-sun' : 'pi pi-moon'"
                        @click="toggleTheme"
                    />
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-medium">{{ auth.user?.name }}</p>
                        <p class="text-xs text-slate-500">{{ auth.user?.email }}</p>
                    </div>
                    <Button label="Logout" severity="danger" outlined size="small" @click="onLogout" />
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
            <RouterView />
        </main>
    </div>
</template>

<script setup>
import Button from 'primevue/button';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useAuthStore } from '@/modules/auth/stores/auth';
import { useTheme } from '@/composables/useTheme';

const auth = useAuthStore();
const router = useRouter();
const toast = useToast();
const { isDark, toggleTheme } = useTheme();

async function onLogout() {
    await auth.logout();
    toast.add({ severity: 'success', summary: 'Signed out', life: 2500 });
    router.push({ name: 'login' });
}
</script>
