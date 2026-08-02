<template>
    <div class="space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="font-display text-2xl font-semibold tracking-tight text-slate-900 dark:text-white sm:text-3xl">Organization</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Manage structure, designations, and employees</p>
            </div>
            <div class="w-full min-w-64 sm:w-72">
                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Active organization</label>
                <Select
                    v-model="selectedOrg"
                    :options="orgStore.organizations"
                    option-label="name"
                    option-value="id"
                    placeholder="Select organization"
                    class="w-full"
                    @change="onOrgChange"
                />
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-[270px_1fr]">
            <aside class="rounded-xl border border-slate-200/90 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                <div class="mb-2 flex items-center justify-between gap-2 px-1">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Structure tree</h2>
                    <Button
                        icon="pi pi-refresh"
                        text
                        rounded
                        size="small"
                        v-tooltip.bottom="'Refresh tree'"
                        @click="refreshTree"
                    />
                </div>
                <Tree
                    v-if="treeNodes.length"
                    :value="treeNodes"
                    class="org-tree w-full text-sm"
                />
                <div v-else class="rounded-lg bg-slate-50 px-3 py-8 text-center dark:bg-slate-900/50">
                    <i class="pi pi-sitemap mb-2 text-xl text-slate-300 dark:text-slate-600" />
                    <p class="text-sm text-slate-500">No organization selected</p>
                </div>
            </aside>

            <section class="org-tabs overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
                <TabView v-model:active-index="activeTab">
                    <TabPanel
                        v-for="tab in tabs"
                        :key="tab.key"
                        :header="tab.label"
                    >
                        <OrgEntityManager
                            :resource="tab.key"
                            :title="tab.label"
                            :fields="tab.fields"
                            :columns="tab.columns"
                            :organization-id="selectedOrg"
                        />
                    </TabPanel>
                </TabView>
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import Button from 'primevue/button';
import Select from 'primevue/select';
import TabPanel from 'primevue/tabpanel';
import TabView from 'primevue/tabview';
import Tree from 'primevue/tree';
import { useOrganizationStore } from '@/modules/organization/stores/organization';
import OrgEntityManager from '@/modules/organization/components/OrgEntityManager.vue';

const orgStore = useOrganizationStore();
const selectedOrg = ref(null);
const activeTab = ref(0);

const tabs = [
    {
        key: 'organizations',
        label: 'Organizations',
        columns: [
            { field: 'code', header: 'Code', kind: 'code' },
            { field: 'name', header: 'Name', kind: 'primary' },
            { field: 'city', header: 'City' },
            { field: 'is_active', header: 'Status', kind: 'boolean' },
        ],
        fields: [
            { key: 'code', label: 'Code', required: true },
            { key: 'name', label: 'Name', required: true },
            { key: 'legal_name', label: 'Legal name' },
            { key: 'email', label: 'Email' },
            { key: 'phone', label: 'Phone' },
            { key: 'city', label: 'City' },
            { key: 'country', label: 'Country' },
            { key: 'description', label: 'Description', type: 'textarea' },
            { key: 'is_active', label: 'Active', type: 'boolean' },
        ],
    },
    {
        key: 'branches',
        label: 'Branches',
        columns: [
            { field: 'code', header: 'Code', kind: 'code' },
            { field: 'name', header: 'Name', kind: 'primary' },
            { field: 'city', header: 'City' },
            { field: 'is_head_office', header: 'Head office', kind: 'boolean' },
        ],
        fields: [
            { key: 'code', label: 'Code', required: true },
            { key: 'name', label: 'Name', required: true },
            { key: 'type', label: 'Type' },
            { key: 'city', label: 'City' },
            { key: 'country', label: 'Country' },
            { key: 'is_head_office', label: 'Head office', type: 'boolean' },
            { key: 'is_active', label: 'Active', type: 'boolean' },
        ],
    },
    {
        key: 'departments',
        label: 'Departments',
        columns: [
            { field: 'code', header: 'Code', kind: 'code' },
            { field: 'name', header: 'Name', kind: 'primary' },
            { field: 'branch.name', header: 'Branch' },
        ],
        fields: [
            { key: 'code', label: 'Code', required: true },
            { key: 'name', label: 'Name', required: true },
            { key: 'branch_id', label: 'Branch', type: 'select', optionsKey: 'branches' },
            { key: 'description', label: 'Description', type: 'textarea' },
            { key: 'is_active', label: 'Active', type: 'boolean' },
        ],
    },
    {
        key: 'sections',
        label: 'Sections',
        columns: [
            { field: 'code', header: 'Code', kind: 'code' },
            { field: 'name', header: 'Name', kind: 'primary' },
            { field: 'department.name', header: 'Department' },
        ],
        fields: [
            { key: 'code', label: 'Code', required: true },
            { key: 'name', label: 'Name', required: true },
            { key: 'department_id', label: 'Department', type: 'select', optionsKey: 'departments', required: true },
            { key: 'description', label: 'Description', type: 'textarea' },
            { key: 'is_active', label: 'Active', type: 'boolean' },
        ],
    },
    {
        key: 'units',
        label: 'Units',
        columns: [
            { field: 'code', header: 'Code', kind: 'code' },
            { field: 'name', header: 'Name', kind: 'primary' },
            { field: 'department.name', header: 'Department' },
        ],
        fields: [
            { key: 'code', label: 'Code', required: true },
            { key: 'name', label: 'Name', required: true },
            { key: 'department_id', label: 'Department', type: 'select', optionsKey: 'departments', required: true },
            { key: 'section_id', label: 'Section', type: 'select', optionsKey: 'sections' },
            { key: 'is_active', label: 'Active', type: 'boolean' },
        ],
    },
    {
        key: 'offices',
        label: 'Offices',
        columns: [
            { field: 'code', header: 'Code', kind: 'code' },
            { field: 'name', header: 'Name', kind: 'primary' },
            { field: 'city', header: 'City' },
        ],
        fields: [
            { key: 'code', label: 'Code', required: true },
            { key: 'name', label: 'Name', required: true },
            { key: 'branch_id', label: 'Branch', type: 'select', optionsKey: 'branches' },
            { key: 'city', label: 'City' },
            { key: 'is_active', label: 'Active', type: 'boolean' },
        ],
    },
    {
        key: 'designations',
        label: 'Designations',
        columns: [
            { field: 'code', header: 'Code', kind: 'code' },
            { field: 'name', header: 'Name', kind: 'primary' },
            { field: 'grade', header: 'Grade' },
            { field: 'level', header: 'Level' },
        ],
        fields: [
            { key: 'code', label: 'Code', required: true },
            { key: 'name', label: 'Name', required: true },
            { key: 'grade', label: 'Grade' },
            { key: 'level', label: 'Level', type: 'number' },
            { key: 'is_active', label: 'Active', type: 'boolean' },
        ],
    },
    {
        key: 'employees',
        label: 'Employees',
        columns: [
            { field: 'employee_code', header: 'Code', kind: 'code' },
            { field: 'full_name', header: 'Name', kind: 'primary' },
            { field: 'email', header: 'Email' },
            { field: 'department.name', header: 'Department' },
            { field: 'employment_status', header: 'Status', kind: 'status' },
        ],
        fields: [
            { key: 'employee_code', label: 'Employee code', required: true },
            { key: 'first_name', label: 'First name', required: true },
            { key: 'last_name', label: 'Last name' },
            { key: 'email', label: 'Email' },
            { key: 'phone', label: 'Phone' },
            { key: 'department_id', label: 'Department', type: 'select', optionsKey: 'departments' },
            { key: 'section_id', label: 'Section', type: 'select', optionsKey: 'sections' },
            { key: 'branch_id', label: 'Branch', type: 'select', optionsKey: 'branches' },
            { key: 'office_id', label: 'Office', type: 'select', optionsKey: 'offices' },
            { key: 'designation_id', label: 'Designation', type: 'select', optionsKey: 'designations' },
            { key: 'joining_date', label: 'Joining date', type: 'date' },
            { key: 'employment_status', label: 'Status', type: 'status' },
            { key: 'is_active', label: 'Active', type: 'boolean' },
        ],
    },
];

const treeNodes = computed(() => {
    if (!orgStore.tree) return [];
    return [mapTree(orgStore.tree)];
});

function mapTree(node) {
    return {
        key: `${node.type}-${node.id || node.name}`,
        label: node.code ? `${node.code} — ${node.name}` : node.name,
        children: (node.children || []).map(mapTree),
    };
}

async function refreshTree() {
    await orgStore.loadTree(selectedOrg.value);
}

async function onOrgChange() {
    orgStore.setOrganization(selectedOrg.value);
    await refreshTree();
}

onMounted(async () => {
    await orgStore.loadOrganizations();
    selectedOrg.value = orgStore.currentOrganizationId;
    await refreshTree();
});
</script>

<style scoped>
.org-tabs :deep(.p-tabview-tablist) {
    background: transparent;
    border-bottom: 1px solid rgb(241 245 249);
    padding: 0 0.75rem;
    gap: 0.15rem;
}

:global(.dark) .org-tabs :deep(.p-tabview-tablist) {
    border-bottom-color: rgb(30 41 59);
}

.org-tabs :deep(.p-tabview-tablist-content) {
    border: none;
}

.org-tabs :deep(.p-tabview-tab-header) {
    font-size: 0.8125rem;
    font-weight: 500;
    padding: 0.85rem 0.9rem;
    color: rgb(100 116 139);
}

.org-tabs :deep(.p-tabview-tablist-item-active .p-tabview-tab-header) {
    color: var(--color-brand-600, #154360);
    font-weight: 600;
}

.org-tabs :deep(.p-tabview-panels) {
    padding: 0;
    background: transparent;
}

.org-tree :deep(.p-tree-node-content) {
    border-radius: 0.5rem;
    padding: 0.35rem 0.5rem;
}

.org-tree :deep(.p-tree-node-label) {
    font-size: 0.8125rem;
    color: rgb(51 65 85);
}

:global(.dark) .org-tree :deep(.p-tree-node-label) {
    color: rgb(203 213 225);
}
</style>
