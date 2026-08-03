<template>
    <div
        class="flex min-h-screen flex-col bg-[radial-gradient(circle_at_top_left,#d5e6f5,transparent_40%),linear-gradient(180deg,#f8fafc,#eef5fb)] dark:bg-[radial-gradient(circle_at_top_left,#0e2f45,transparent_40%),linear-gradient(180deg,#020617,#0f172a)]"
    >
        <!-- Top navbar -->
        <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-950/90">
            <div class="flex h-14 items-center justify-between gap-3 px-3 sm:px-4 lg:px-6">
                <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                    <Button
                        type="button"
                        severity="secondary"
                        text
                        rounded
                        icon="pi pi-bars"
                        :aria-label="menuAriaLabel"
                        v-tooltip.bottom="menuTooltip"
                        @click="onMenuClick"
                    />
                    <div class="flex min-w-0 items-center gap-2.5">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-sm font-bold text-white shadow-sm">
                            EH
                        </div>
                        <div class="min-w-0">
                            <p class="font-display truncate text-sm font-semibold tracking-wide text-brand-700 dark:text-brand-100">EDAMS</p>
                            <p class="hidden truncate text-xs text-slate-500 dark:text-slate-400 sm:block">Enterprise Document Archiving</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-1 sm:gap-2">
                    <Button
                        type="button"
                        class="hidden md:inline-flex"
                        severity="secondary"
                        text
                        rounded
                        :icon="isRight ? 'pi pi-arrow-left' : 'pi pi-arrow-right'"
                        v-tooltip.bottom="isRight ? 'Move sidebar left' : 'Move sidebar right'"
                        aria-label="Toggle sidebar side"
                        @click="toggleSide"
                    />
                    <Button
                        type="button"
                        severity="secondary"
                        text
                        rounded
                        :icon="isDark ? 'pi pi-sun' : 'pi pi-moon'"
                        v-tooltip.bottom="isDark ? 'Light mode' : 'Dark mode'"
                        @click="toggleTheme"
                    />
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg px-1.5 py-1 text-left transition hover:bg-slate-100 dark:hover:bg-slate-900"
                        aria-haspopup="true"
                        aria-label="User menu"
                        @click="toggleUserMenu"
                    >
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ auth.user?.name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ auth.user?.email }}</p>
                        </div>
                        <UserAvatar
                            :name="auth.user?.name"
                            :avatar-url="auth.user?.avatar_url"
                            size-class="h-9 w-9 text-sm"
                        />
                        <i class="pi pi-chevron-down hidden text-xs text-slate-400 sm:inline" />
                    </button>
                    <Menu ref="userMenu" :model="userMenuItems" popup />
                </div>
            </div>
        </header>

        <!-- Body: sidebar + content -->
        <div class="relative flex min-h-0 flex-1" :class="isRight ? 'md:flex-row-reverse' : 'md:flex-row'">
            <!-- Desktop sidebar -->
            <aside
                class="hidden shrink-0 border-slate-200/80 bg-white/90 backdrop-blur transition-[width] duration-200 dark:border-slate-800 dark:bg-slate-950/90 md:flex md:flex-col"
                :class="[
                    isRight ? 'border-l' : 'border-r',
                    collapsed ? 'w-[4.5rem]' : 'w-64',
                ]"
            >
                <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                    <RouterLink
                        v-for="item in navItems"
                        :key="item.to"
                        :to="{ name: item.to }"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-600 transition hover:bg-brand-50 hover:text-brand-700 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white"
                        active-class="!bg-brand-50 !font-semibold !text-brand-700 dark:!bg-slate-900 dark:!text-brand-100"
                        :title="item.label"
                    >
                        <i :class="['pi text-base', item.icon]" />
                        <span v-show="!collapsed" class="truncate">{{ item.label }}</span>
                    </RouterLink>
                </nav>
                <div v-if="!collapsed" class="border-t border-slate-200 px-4 py-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                    Sidebar: {{ isRight ? 'Right' : 'Left' }}
                </div>
            </aside>

            <!-- Mobile drawer backdrop -->
            <Transition name="fade">
                <div
                    v-if="mobileOpen"
                    class="fixed inset-0 z-40 bg-slate-950/50 md:hidden"
                    @click="closeMobile"
                />
            </Transition>

            <!-- Mobile drawer -->
            <Transition :name="isRight ? 'drawer-right' : 'drawer-left'">
                <aside
                    v-if="mobileOpen"
                    class="fixed inset-y-0 z-50 flex w-72 max-w-[85vw] flex-col bg-white shadow-xl dark:bg-slate-950 md:hidden"
                    :class="isRight ? 'right-0' : 'left-0'"
                >
                    <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-md bg-brand-600 text-xs font-bold text-white">EH</div>
                            <p class="font-display text-sm font-semibold text-brand-700 dark:text-brand-100">Menu</p>
                        </div>
                        <Button type="button" severity="secondary" text rounded icon="pi pi-times" @click="closeMobile" />
                    </div>
                    <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                        <RouterLink
                            v-for="item in navItems"
                            :key="'m-' + item.to"
                            :to="{ name: item.to }"
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-600 transition hover:bg-brand-50 hover:text-brand-700 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white"
                            active-class="!bg-brand-50 !font-semibold !text-brand-700 dark:!bg-slate-900 dark:!text-brand-100"
                            @click="closeMobile"
                        >
                            <i :class="['pi text-base', item.icon]" />
                            <span>{{ item.label }}</span>
                        </RouterLink>
                    </nav>
                    <div class="border-t border-slate-200 p-3 dark:border-slate-800">
                        <Button
                            class="w-full justify-center"
                            size="small"
                            outlined
                            :label="isRight ? 'Sidebar: Right (tap to Left)' : 'Sidebar: Left (tap to Right)'"
                            :icon="isRight ? 'pi pi-arrow-left' : 'pi pi-arrow-right'"
                            @click="toggleSide"
                        />
                    </div>
                </aside>
            </Transition>

            <!-- Main content -->
            <div class="flex min-w-0 flex-1 flex-col">
                <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    <RouterView />
                </main>

                <footer class="border-t border-slate-200/80 bg-white/80 dark:border-slate-800 dark:bg-slate-950/80">
                    <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8 dark:text-slate-400">
                        <p>
                            <span class="font-display font-semibold text-brand-700 dark:text-brand-100">EDAMS</span>
                            · Enterprise Document Archiving & Records Management
                        </p>
                        <p class="text-xs sm:text-sm">© {{ year }} Softcell Solution Limited</p>
                    </div>
                </footer>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import UserAvatar from '@/components/UserAvatar.vue';
import { useAuthStore } from '@/modules/auth/stores/auth';
import { useTheme } from '@/composables/useTheme';
import { useSidebarLayout } from '@/composables/useSidebarLayout';

const auth = useAuthStore();
const router = useRouter();
const toast = useToast();
const userMenu = ref(null);
const { isDark, toggleTheme } = useTheme();
const {
    isRight,
    collapsed,
    mobileOpen,
    isDesktop,
    toggleSide,
    toggleCollapsed,
    toggleMobile,
    closeMobile,
} = useSidebarLayout();

const year = computed(() => new Date().getFullYear());

const menuTooltip = computed(() => {
    if (!isDesktop.value) return mobileOpen.value ? 'Close menu' : 'Open menu';
    return collapsed.value ? 'Expand sidebar' : 'Collapse sidebar';
});

const menuAriaLabel = computed(() => menuTooltip.value);

function onMenuClick() {
    if (isDesktop.value) {
        toggleCollapsed();
        return;
    }
    toggleMobile();
}

const allNavItems = [
    { label: 'Dashboard', to: 'dashboard', icon: 'pi-home' },
    { label: 'Organization', to: 'organization', icon: 'pi-building' },
    { label: 'Documents', to: 'documents', icon: 'pi-folder' },
    { label: 'Archive & Records', to: 'archive', icon: 'pi-box', permission: 'archive.view' },
    { label: 'Search', to: 'search', icon: 'pi-search' },
    { label: 'Workflow', to: 'workflow', icon: 'pi-sitemap' },
    { label: 'Operations', to: 'operations', icon: 'pi-cog' },
    { label: 'Users & Roles', to: 'users', icon: 'pi-users' },
    { label: 'OTP Book', to: 'otp-book', icon: 'pi-key', permission: 'otp.view' },
    { label: 'Login Activity', to: 'login-activity', icon: 'pi-history' },
    { label: 'Devices', to: 'sessions', icon: 'pi-desktop' },
    { label: 'Software Manual', to: 'manual', icon: 'pi-book' },
];

const navItems = computed(() =>
    allNavItems.filter((item) => !item.permission || auth.hasPermission(item.permission))
);

const userMenuItems = [
    {
        label: 'Profile',
        icon: 'pi pi-user',
        command: () => router.push({ name: 'profile' }),
    },
    { separator: true },
    {
        label: 'Logout',
        icon: 'pi pi-sign-out',
        command: () => onLogout(),
    },
];

function toggleUserMenu(event) {
    userMenu.value?.toggle(event);
}

async function onLogout() {
    await auth.logout();
    toast.add({ severity: 'success', summary: 'Signed out', life: 2500 });
    router.push({ name: 'login' });
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.drawer-left-enter-active,
.drawer-left-leave-active,
.drawer-right-enter-active,
.drawer-right-leave-active {
    transition: transform 0.22s ease;
}
.drawer-left-enter-from,
.drawer-left-leave-to {
    transform: translateX(-100%);
}
.drawer-right-enter-from,
.drawer-right-leave-to {
    transform: translateX(100%);
}
</style>
