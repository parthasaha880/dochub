<template>
    <div class="space-y-6">
        <div>
            <h1 class="font-display text-3xl font-semibold text-slate-900 dark:text-white">Users & Roles</h1>
            <p class="mt-1 text-sm text-slate-500">Manage accounts, Spatie roles, permissions, and hierarchy</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
            <TabView>
                <TabPanel header="Users">
                    <UsersManager />
                </TabPanel>
                <TabPanel header="Roles">
                    <RolesManager />
                </TabPanel>
                <TabPanel header="Permissions">
                    <PermissionsManager />
                </TabPanel>
            </TabView>
        </div>
    </div>
</template>

<script setup>
import TabPanel from 'primevue/tabpanel';
import TabView from 'primevue/tabview';
import UsersManager from '@/modules/users/components/UsersManager.vue';
import RolesManager from '@/modules/users/components/RolesManager.vue';
import PermissionsManager from '@/modules/users/components/PermissionsManager.vue';
</script>
