<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold text-slate-900 dark:text-white">Organization</h1>
                <p class="mt-1 text-sm text-slate-500">Manage structure, designations, and employees</p>
            </div>
            <div class="min-w-64">
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Active organization</label>
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

        <div class="grid gap-6 xl:grid-cols-[280px_1fr]">
            <aside class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold">Structure tree</h2>
                    <Button icon="pi pi-refresh" text rounded size="small" @click="refreshTree" />
                </div>
                <Tree v-if="treeNodes.length" :value="treeNodes" class="w-full text-sm" />
                <p v-else class="text-sm text-slate-500">No organization selected.</p>
            </aside>

            <section class="rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
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
            { field: 'code', header: 'Code' },
            { field: 'name', header: 'Name' },
            { field: 'city', header: 'City' },
            { field: 'is_active', header: 'Active' },
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
            { field: 'code', header: 'Code' },
            { field: 'name', header: 'Name' },
            { field: 'city', header: 'City' },
            { field: 'is_head_office', header: 'Head office' },
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
            { field: 'code', header: 'Code' },
            { field: 'name', header: 'Name' },
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
            { field: 'code', header: 'Code' },
            { field: 'name', header: 'Name' },
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
            { field: 'code', header: 'Code' },
            { field: 'name', header: 'Name' },
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
            { field: 'code', header: 'Code' },
            { field: 'name', header: 'Name' },
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
            { field: 'code', header: 'Code' },
            { field: 'name', header: 'Name' },
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
            { field: 'employee_code', header: 'Code' },
            { field: 'full_name', header: 'Name' },
            { field: 'email', header: 'Email' },
            { field: 'department.name', header: 'Department' },
            { field: 'employment_status', header: 'Status' },
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
