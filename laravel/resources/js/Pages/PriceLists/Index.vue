<script>
    // =======================
    // IMPORTS
    // =======================
    import Layout from '@/Layouts/main.vue';
    import PageHeader from '@/Components/page-header.vue';
    import DataTable from '@/Components/DataTable.vue';
    import { useFetchPetition } from '@/Composables/useFetchPetition.js';
    import { useDateFormatter } from '@/Composables/useDateFormatter.js';
    import { router } from '@inertiajs/vue3';

    export default {
        // =======================
        // CONFIG
        // =======================
        name: 'PriceListsIndex',
        components: {
            Layout,
            PageHeader,
            DataTable,
        },

        // =======================
        // PROPS
        // =======================
        props: {
            priceLists: {
                type: Array,
                default: () => [],
            },
        },

        // =======================
        // DATA
        // =======================
        data() {
            return {
                items: this.priceLists,
                searchQuery: '',
                headers: [
                    { label: '#', key: 'id',width: '50px', highlight: true },
                    { label: 'NOMBRE', key: 'name' },
                    { label: 'FECHA DE INICIO', key: 'startDate', width: '120px', align: 'center' },
                    { label: 'FECHA DE FIN', key: 'endDate', width: '120px', align: 'center' },
                    { label: 'ESTADO', key: 'status', width: '100px', align: 'center' },
                    { label: 'ACCIONES', key: 'actions', width: '80px', align: 'center' },
                ],
            };
        },

        // =======================
        // METHODS
        // =======================
        methods: {
            formatDateForTable(value) {
                const { displayDateFormat } = useDateFormatter();
                if (!value) {
                    return '';
                }

                const rawDate = typeof value === 'object' ? value.date : value;
                if (!rawDate) {
                    return '';
                }

                const datePart = String(rawDate).split(' ')[0];
                return displayDateFormat(datePart);
            },

            async navigateToCreate() {
                const { fetchPetition } = useFetchPetition();
                const url = route('price_lists.viewCreate');
                const { ok } = await fetchPetition(url, { method: 'GET' });
                if (ok) {
                    router.visit(url);
                }
            },

            async navigateToShow(uuid) {
                const { fetchPetition } = useFetchPetition();
                const url = route('price_lists.viewShow', {uuid});
                const { ok } = await fetchPetition(url, { method: 'GET' });
                if (ok) {
                    router.visit(url);
                }
            },

            async navigateToEdit(uuid) {
                const { fetchPetition } = useFetchPetition();
                const url = route('price_lists.viewEdit', { uuid });
                const { ok } = await fetchPetition(url, { method: 'GET' });
                if (ok) {
                    router.visit(url);
                }
            },
        },

        // =======================
        // COMPUTED
        // =======================
        computed: {
            tableItems() {
                const sourceItems = Array.isArray(this.items) ? this.items : [];
                return sourceItems.map((item) => ({
                    ...item,
                    startDate: this.formatDateForTable(item.startDate),
                    endDate: this.formatDateForTable(item.endDate),
                }));
            },
            /** Filtra filas por texto en columnas definidas en headers (excepto acciones). */
            filteredTableItems() {
                const q = (this.searchQuery || '').trim().toLowerCase();
                if (!q) {
                    return this.tableItems;
                }
                return this.tableItems.filter((row) =>
                    this.headers.some((h) => {
                        if (h.key === 'actions') {
                            return false;
                        }
                        const val = row[h.key];
                        if (val == null) {
                            return false;
                        }
                        return String(val).toLowerCase().includes(q);
                    })
                );
            },
        },

        mounted() {
            console.log(this.priceLists);
        },
    };
</script>

<template>
    <Layout>
        <PageHeader title="Listas Precios" pageTitle="Gestión" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="form-icon me-3" style="width: 100%;">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    class="form-control form-control-icon"
                                    id="iconInput"
                                    placeholder="Buscar por ID, nombre o descripción..."
                                    autocomplete="off"
                                >
                                <i class="ri-search-line"></i>
                            </div>
                            <button class="btn btn-sm btn-primary" style="width: 80px; height: 36px;" @click="navigateToCreate">
                                <i class="ri-add-line"></i>
                                Nuevo
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <DataTable :headers="headers" :items="filteredTableItems">
                            <template #cell-endDate="{ item }">
                                <span v-if="item.endDate">{{ item.endDate }}</span>
                                <span v-else class="text-muted fst-italic">sin definir</span>
                            </template>
                            <template #cell-status="{ item }">
                                <span
                                    v-if="item.status == '1'"
                                    class="badge bg-success-subtle text-success"
                                >
                                    ACTIVO
                                </span>
                                <span
                                    v-else
                                    class="badge bg-danger-subtle text-danger"
                                >
                                    INACTIVO
                                </span>
                            </template>
                            <template #cell-actions="{ item }">
                                <div class="d-flex gap-2 justify-content-center align-items-center">
                                    <a @click="navigateToShow(item.uuid)">
                                        <i class="ri-eye-fill text-info" style="font-size: 15px; cursor: pointer;"></i>
                                    </a>
                                    <a @click="navigateToEdit(item.uuid)">
                                        <i class="ri-pencil-fill text-secondary" style="font-size: 15px; cursor: pointer;"></i>
                                    </a>
                                    <a>
                                        <i class="ri-delete-bin-6-fill text-danger" style="font-size: 15px; cursor: pointer;"></i>
                                    </a>
                                </div>
                            </template>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>