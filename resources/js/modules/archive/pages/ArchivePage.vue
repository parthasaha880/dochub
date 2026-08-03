<template>
    <div class="space-y-5 p-4 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="font-display text-2xl font-semibold text-slate-900 dark:text-white">Archive &amp; Records</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Digital, physical, and hybrid archiving · locations · classifications · barcode / QR tracking
                </p>
            </div>
            <Select
                v-model="selectedOrg"
                :options="organizations"
                option-label="name"
                option-value="id"
                placeholder="Organization"
                class="w-56"
                @change="onOrgChange"
            />
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="card in statCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white/90 p-4 dark:border-slate-800 dark:bg-slate-950/80">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
                <p class="mt-2 font-display text-2xl font-semibold text-brand-700 dark:text-brand-100">{{ card.value }}</p>
            </div>
        </div>

        <Tabs value="0">
            <TabList>
                <Tab value="0">Locations</Tab>
                <Tab value="1">Digital archive</Tab>
                <Tab value="2">Physical / Hybrid</Tab>
                <Tab value="3">Classifications</Tab>
                <Tab value="4">Barcode &amp; QR</Tab>
            </TabList>
            <TabPanels>
                <TabPanel value="0">
                    <div class="space-y-4 pt-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm text-slate-500">Room → Rack → Shelf → Box → File</p>
                            <Button label="Add room" icon="pi pi-plus" size="small" @click="openLocationCreate('room')" />
                        </div>
                        <div v-if="!locationTree.length" class="rounded-xl border border-dashed border-slate-300 py-12 text-center text-sm text-slate-500 dark:border-slate-700">
                            No archive locations yet. Add a room to start the hierarchy.
                        </div>
                        <div v-else class="space-y-2">
                            <LocationNode
                                v-for="node in locationTree"
                                :key="node.id"
                                :node="node"
                                :depth="0"
                                @add-child="openLocationCreate"
                                @edit="openLocationEdit"
                                @remove="removeLocation"
                            />
                        </div>
                    </div>
                </TabPanel>

                <TabPanel value="1">
                    <div class="space-y-3 pt-3">
                        <div class="flex flex-wrap gap-2">
                            <InputText v-model="digitalSearch" placeholder="Search archived documents..." class="w-64" @keyup.enter="loadDigital" />
                            <Button icon="pi pi-search" @click="loadDigital" />
                        </div>
                        <DataTable :value="digitalRows" :loading="store.loading" striped-rows paginator :rows="10" size="small">
                            <Column field="title" header="Document" />
                            <Column field="archive_no" header="Archive no." />
                            <Column field="reference_no" header="Reference" />
                            <Column field="media_type" header="Media" />
                            <Column field="barcode" header="Barcode" />
                            <Column field="archive_date" header="Archived" />
                            <template #empty>
                                <div class="py-8 text-center text-sm text-slate-500">No digitally archived documents yet.</div>
                            </template>
                        </DataTable>
                    </div>
                </TabPanel>

                <TabPanel value="2">
                    <div class="space-y-4 pt-3">
                        <div class="flex flex-wrap gap-2">
                            <Select
                                v-model="physicalMode"
                                :options="[
                                    { label: 'Physical', value: 'physical' },
                                    { label: 'Hybrid', value: 'hybrid' },
                                ]"
                                option-label="label"
                                option-value="value"
                                class="w-40"
                                @change="loadPhysicalHybrid"
                            />
                            <InputText v-model="physicalSearch" placeholder="Search..." class="w-56" @keyup.enter="loadPhysicalHybrid" />
                            <Button icon="pi pi-search" @click="loadPhysicalHybrid" />
                        </div>
                        <DataTable :value="physicalRows" :loading="store.loading" striped-rows paginator :rows="10" size="small">
                            <Column field="title" header="Record" />
                            <Column field="media_type" header="Type" />
                            <Column field="archive_no" header="Archive no." />
                            <Column field="physical_reference" header="Physical path" />
                            <Column header="Location">
                                <template #body="{ data }">
                                    {{ data.location?.code || '—' }}
                                </template>
                            </Column>
                            <Column field="barcode" header="Barcode" />
                            <template #empty>
                                <div class="py-8 text-center text-sm text-slate-500">No physical/hybrid records yet. Assign a box/file location to a document.</div>
                            </template>
                        </DataTable>
                    </div>
                </TabPanel>

                <TabPanel value="3">
                    <div class="space-y-4 pt-3">
                        <div class="flex justify-end">
                            <Button label="Add classification" icon="pi pi-plus" size="small" @click="openCategoryCreate()" />
                        </div>
                        <DataTable :value="flatCategories" striped-rows size="small">
                            <Column field="code" header="Code" style="width: 8rem" />
                            <Column field="name" header="Name" />
                            <Column field="level" header="Level" style="width: 6rem" />
                            <Column header="Actions" style="width: 8rem">
                                <template #body="{ data }">
                                    <Button icon="pi pi-plus" text rounded v-tooltip.top="'Add child'" @click="openCategoryCreate(data.id)" />
                                    <Button icon="pi pi-trash" text rounded severity="danger" @click="removeCategory(data)" />
                                </template>
                            </Column>
                            <template #empty>
                                <div class="py-8 text-center text-sm text-slate-500">No classifications yet.</div>
                            </template>
                        </DataTable>
                    </div>
                </TabPanel>

                <TabPanel value="4">
                    <div class="space-y-4 pt-3">
                        <p class="text-sm text-slate-500">Look up documents or locations by barcode, QR code, archive number, or location code.</p>
                        <div class="flex flex-wrap gap-2">
                            <InputText v-model="lookupQuery" placeholder="Scan or type code..." class="w-72" @keyup.enter="runLookup" />
                            <Button label="Track" icon="pi pi-qrcode" :loading="lookingUp" @click="runLookup" />
                        </div>
                        <div v-if="lookupResult" class="rounded-xl border border-brand-100 bg-brand-50/70 p-4 text-sm dark:border-slate-700 dark:bg-slate-900">
                            <p class="mb-2 font-semibold text-brand-800 dark:text-brand-100">
                                Matched {{ lookupResult.type }}
                            </p>
                            <template v-if="lookupResult.type === 'document'">
                                <p><strong>Title:</strong> {{ lookupResult.document.title }}</p>
                                <p><strong>Archive no:</strong> {{ lookupResult.document.archive_no || '—' }}</p>
                                <p><strong>Barcode:</strong> {{ lookupResult.document.barcode }}</p>
                                <p><strong>QR:</strong> {{ lookupResult.document.qr_code }}</p>
                                <p><strong>Media:</strong> {{ lookupResult.document.media_type }}</p>
                                <p v-if="lookupResult.location_path?.length">
                                    <strong>Location:</strong> {{ lookupResult.location_path.join(' → ') }}
                                </p>
                            </template>
                            <template v-else>
                                <p><strong>Name:</strong> {{ lookupResult.location.name }}</p>
                                <p><strong>Type:</strong> {{ lookupResult.location.type_label || lookupResult.location.type }}</p>
                                <p><strong>Code:</strong> {{ lookupResult.location.code }}</p>
                                <p><strong>Barcode:</strong> {{ lookupResult.location.barcode }}</p>
                                <p v-if="lookupResult.path?.length"><strong>Path:</strong> {{ lookupResult.path.join(' → ') }}</p>
                            </template>
                        </div>
                    </div>
                </TabPanel>
            </TabPanels>
        </Tabs>

        <Dialog v-model:visible="locationDialog" modal :header="locationForm.id ? 'Edit location' : `Add ${locationForm.type}`" class="w-full max-w-lg">
            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-sm font-medium">Name</label>
                    <InputText v-model="locationForm.name" class="w-full" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Code</label>
                    <InputText v-model="locationForm.code" class="w-full" placeholder="Auto if blank" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Capacity</label>
                    <InputNumber v-model="locationForm.capacity" class="w-full" :min="1" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Description</label>
                    <Textarea v-model="locationForm.description" rows="3" class="w-full" />
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" text @click="locationDialog = false" />
                <Button label="Save" :loading="saving" @click="saveLocation" />
            </template>
        </Dialog>

        <Dialog v-model:visible="categoryDialog" modal header="Classification" class="w-full max-w-lg">
            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-sm font-medium">Code</label>
                    <InputText v-model="categoryForm.code" class="w-full" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Name</label>
                    <InputText v-model="categoryForm.name" class="w-full" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Description</label>
                    <Textarea v-model="categoryForm.description" rows="3" class="w-full" />
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" text @click="categoryDialog = false" />
                <Button label="Save" :loading="saving" @click="saveCategory" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import Textarea from 'primevue/textarea';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import api from '@/services/api';
import { useArchiveStore } from '@/modules/archive/stores/archive';
import LocationNode from '@/modules/archive/components/LocationNode.vue';

const store = useArchiveStore();
const toast = useToast();
const confirm = useConfirm();

const organizations = ref([]);
const selectedOrg = ref(null);
const stats = ref({});
const locationTree = ref([]);
const categories = ref([]);
const digitalRows = ref([]);
const physicalRows = ref([]);
const digitalSearch = ref('');
const physicalSearch = ref('');
const physicalMode = ref('physical');
const lookupQuery = ref('');
const lookupResult = ref(null);
const lookingUp = ref(false);
const locationDialog = ref(false);
const categoryDialog = ref(false);
const saving = ref(false);

const locationForm = reactive({
    id: null,
    parent_id: null,
    type: 'room',
    name: '',
    code: '',
    capacity: null,
    description: '',
});

const categoryForm = reactive({
    parent_id: null,
    code: '',
    name: '',
    description: '',
});

const childTypeMap = {
    room: 'rack',
    rack: 'shelf',
    shelf: 'box',
    box: 'file',
};

const statCards = computed(() => [
    { label: 'Digital', value: stats.value.digital ?? 0 },
    { label: 'Physical', value: stats.value.physical ?? 0 },
    { label: 'Hybrid', value: stats.value.hybrid ?? 0 },
    { label: 'Archived', value: stats.value.archived ?? 0 },
    { label: 'Locations', value: stats.value.locations ?? 0 },
    { label: 'Rooms', value: stats.value.rooms ?? 0 },
    { label: 'Boxes', value: stats.value.boxes ?? 0 },
    { label: 'Classifications', value: stats.value.categories ?? 0 },
]);

const flatCategories = computed(() => {
    const rows = [];
    const walk = (nodes, level = 0) => {
        (nodes || []).forEach((n) => {
            rows.push({ ...n, level: level === 0 ? 'Root' : `L${level}` });
            walk(n.children || [], level + 1);
        });
    };
    walk(categories.value);
    return rows;
});

async function bootstrap() {
    const { data } = await api.get('/organizations', { params: { per_page: 100 } });
    organizations.value = data.data?.data || data.data || [];
    selectedOrg.value = store.syncOrganization(organizations.value);
    await refreshAll();
}

async function onOrgChange() {
    store.setOrganization(selectedOrg.value);
    await refreshAll();
}

async function refreshAll() {
    if (!store.organizationId) return;
    await Promise.all([loadStats(), loadLocations(), loadCategories(), loadDigital(), loadPhysicalHybrid()]);
}

async function loadStats() {
    stats.value = await store.fetchStats();
}

async function loadLocations() {
    locationTree.value = await store.fetchLocationTree();
}

async function loadCategories() {
    categories.value = await store.fetchCategories();
}

async function loadDigital() {
    const payload = await store.fetchDigital({ search: digitalSearch.value || undefined, per_page: 50 });
    digitalRows.value = payload.data || payload;
}

async function loadPhysicalHybrid() {
    const fetcher = physicalMode.value === 'hybrid' ? store.fetchHybrid : store.fetchPhysical;
    const payload = await fetcher({ search: physicalSearch.value || undefined, per_page: 50 });
    physicalRows.value = payload.data || payload;
}

function openLocationCreate(typeOrParent) {
    if (typeof typeOrParent === 'string') {
        locationForm.id = null;
        locationForm.parent_id = null;
        locationForm.type = typeOrParent;
    } else {
        locationForm.id = null;
        locationForm.parent_id = typeOrParent.id;
        locationForm.type = childTypeMap[typeOrParent.type] || 'file';
    }
    locationForm.name = '';
    locationForm.code = '';
    locationForm.capacity = null;
    locationForm.description = '';
    locationDialog.value = true;
}

function openLocationEdit(node) {
    locationForm.id = node.id;
    locationForm.parent_id = node.parent_id;
    locationForm.type = node.type;
    locationForm.name = node.name;
    locationForm.code = node.code;
    locationForm.capacity = node.capacity;
    locationForm.description = node.description || '';
    locationDialog.value = true;
}

async function saveLocation() {
    saving.value = true;
    try {
        if (locationForm.id) {
            await store.updateLocation(locationForm.id, {
                name: locationForm.name,
                code: locationForm.code,
                capacity: locationForm.capacity,
                description: locationForm.description,
            });
        } else {
            await store.createLocation({
                parent_id: locationForm.parent_id,
                type: locationForm.type,
                name: locationForm.name,
                code: locationForm.code || undefined,
                capacity: locationForm.capacity,
                description: locationForm.description,
            });
        }
        locationDialog.value = false;
        toast.add({ severity: 'success', summary: 'Location saved', life: 2000 });
        await Promise.all([loadLocations(), loadStats()]);
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Save failed',
            detail: error.response?.data?.errors?.type?.[0]
                || error.response?.data?.errors?.parent_id?.[0]
                || error.response?.data?.message
                || 'Unable to save location',
            life: 4500,
        });
    } finally {
        saving.value = false;
    }
}

function removeLocation(node) {
    confirm.require({
        message: `Delete ${node.type} “${node.name}”?`,
        header: 'Delete location',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await store.deleteLocation(node.id);
                toast.add({ severity: 'success', summary: 'Deleted', life: 2000 });
                await Promise.all([loadLocations(), loadStats()]);
            } catch (error) {
                toast.add({
                    severity: 'error',
                    summary: 'Delete failed',
                    detail: error.response?.data?.errors?.location?.[0] || error.response?.data?.message,
                    life: 4000,
                });
            }
        },
    });
}

function openCategoryCreate(parentId = null) {
    categoryForm.parent_id = parentId;
    categoryForm.code = '';
    categoryForm.name = '';
    categoryForm.description = '';
    categoryDialog.value = true;
}

async function saveCategory() {
    saving.value = true;
    try {
        await store.createCategory({ ...categoryForm });
        categoryDialog.value = false;
        toast.add({ severity: 'success', summary: 'Classification saved', life: 2000 });
        await Promise.all([loadCategories(), loadStats()]);
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Save failed',
            detail: error.response?.data?.message || 'Unable to save',
            life: 4000,
        });
    } finally {
        saving.value = false;
    }
}

function removeCategory(row) {
    confirm.require({
        message: `Delete classification ${row.code}?`,
        header: 'Delete',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await store.deleteCategory(row.id);
                toast.add({ severity: 'success', summary: 'Deleted', life: 2000 });
                await Promise.all([loadCategories(), loadStats()]);
            } catch (error) {
                toast.add({
                    severity: 'error',
                    summary: 'Delete failed',
                    detail: error.response?.data?.errors?.category?.[0] || error.response?.data?.message,
                    life: 4000,
                });
            }
        },
    });
}

async function runLookup() {
    lookingUp.value = true;
    lookupResult.value = null;
    try {
        lookupResult.value = await store.lookup(lookupQuery.value);
    } catch (error) {
        toast.add({
            severity: 'warn',
            summary: 'Not found',
            detail: error.response?.data?.errors?.query?.[0] || error.response?.data?.message || 'No match',
            life: 3500,
        });
    } finally {
        lookingUp.value = false;
    }
}

onMounted(bootstrap);
</script>
