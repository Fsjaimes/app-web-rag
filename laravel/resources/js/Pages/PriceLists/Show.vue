<script>
// =======================
// IMPORTS
// =======================
import Layout from '@/Layouts/main.vue';
import PageHeader from '@/Components/page-header.vue';
import ProductListDataTable from '@/Pages/PriceLists/Components/ProductListDataTable.vue';
import { router } from '@inertiajs/vue3';
import { useDateFormatter } from '@/Composables/useDateFormatter.js';

const { displayDateFormat } = useDateFormatter();

export default {
    // =======================
    // CONFIG
    // =======================
    name: 'PriceListsShow',
    components: {
        Layout,
        PageHeader,
        ProductListDataTable,
    },

    // =======================
    // PROPS
    // =======================
    props: {
        priceList: {
            type: Object,
            default: () => ({}),
        },
    },

    // =======================
    // DATA
    // =======================
    data() {
        return {
            clientsOptions: [],
            providersOptions: [],
            branchesOptions: [],
            productsSelected: [],
            productsTableKey: 0,
            scopeGeneralEnabled: true,
            scopeClientsEnabled: false,
            scopeProvidersEnabled: false,
            scopeBranchesEnabled: false,
            clients: [],
            providers: [],
            branches: [],
            formPriceList: {
                name: '',
                start_date: '',
                end_date: '',
                start_date_display: '',
                end_date_display: '',
                status: '1',
                scope: '1',
                observations: '',
            },
        };
    },

    // =======================
    // COMPUTED
    // =======================
    computed: {
        showScopeClients() {
            return this.scopeClientsEnabled;
        },
        showScopeProviders() {
            return this.scopeProvidersEnabled;
        },
        showScopeBranches() {
            return this.scopeBranchesEnabled;
        },
        scopeTags() {
            const tags = [];
            if (this.scopeGeneralEnabled) {
                tags.push('General');
            }
            if (this.scopeClientsEnabled) {
                tags.push('Clientes');
            }
            if (this.scopeProvidersEnabled) {
                tags.push('Proveedores');
            }
            if (this.scopeBranchesEnabled) {
                tags.push('Sucursales');
            }
            return tags;
        },
        statusText() {
            return this.formPriceList.status === '1' ? 'Activo' : 'Inactivo';
        },
        statusClass() {
            return this.formPriceList.status === '1'
                ? 'badge bg-success-subtle text-success'
                : 'badge bg-secondary-subtle text-secondary';
        },
        clientNames() {
            return this.getSelectedNames(this.clientsOptions, this.clients);
        },
        providerNames() {
            return this.getSelectedNames(this.providersOptions, this.providers);
        },
        branchNames() {
            return this.getSelectedNames(this.branchesOptions, this.branches);
        },
        hasDescription() {
            return Boolean(String(this.formPriceList.observations ?? '').trim());
        },
    },

    // =======================
    // METHODS
    // =======================
    methods: {
        closeCreate() {
            router.visit(route('price_lists.index'));
        },
        updateProducts() {},
        getSelectedNames(options, selectedIds) {
            if (!Array.isArray(options) || !Array.isArray(selectedIds) || !selectedIds.length) {
                return [];
            }
            const selectedSet = new Set(selectedIds.map((id) => Number(id)));
            return options
                .filter((option) => selectedSet.has(Number(option.id)))
                .map((option) => option.name);
        },
        parseScopeIds(scopeValue) {
            if (!scopeValue) {
                return [1];
            }
            return String(scopeValue)
                .split(',')
                .map((id) => Number.parseInt(id.trim(), 10))
                .filter((id) => Number.isInteger(id));
        },
        uniqueNumericIds(values) {
            const ids = values
                .map((id) => Number.parseInt(String(id), 10))
                .filter((id) => Number.isInteger(id));
            return [...new Set(ids)];
        },
        resolveSnapshotName(snapshot, fallbackLabel) {
            if (!snapshot || typeof snapshot !== 'object') {
                return fallbackLabel;
            }
            if (snapshot.full_name) {
                return snapshot.full_name;
            }
            if (snapshot.name) {
                return snapshot.name;
            }
            if (snapshot.description) {
                return snapshot.description;
            }
            return fallbackLabel;
        },
        buildScopedSelectData(configs, idKey, snapshotKey, fallbackLabel) {
            const optionsMap = new Map();

            configs.forEach((config) => {
                const numericId = Number.parseInt(String(config?.[idKey] ?? 0), 10);
                if (!Number.isInteger(numericId) || numericId <= 0) {
                    return;
                }
                if (!optionsMap.has(numericId)) {
                    optionsMap.set(numericId, {
                        id: numericId,
                        name: this.resolveSnapshotName(
                            config?.[snapshotKey],
                            `${fallbackLabel} ${numericId}`
                        ),
                    });
                }
            });

            const options = Array.from(optionsMap.values());
            const selected = options.map((option) => option.id);

            return { options, selected };
        },
        initializeFromPriceList() {
            const priceList = this.priceList ?? {};
            const configs = Array.isArray(priceList.configurations) ? priceList.configurations : [];
            const items = Array.isArray(priceList.items) ? priceList.items : [];
            const scopeIds = this.parseScopeIds(priceList.scope);

            this.formPriceList = {
                name: priceList.name ?? '',
                start_date: priceList.startDate ?? '',
                end_date: priceList.endDate ?? '',
                start_date_display: displayDateFormat(priceList.startDate ?? ''),
                end_date_display: displayDateFormat(priceList.endDate ?? ''),
                status: String(priceList.status ?? '1'),
                scope: String(priceList.scope ?? '1'),
                observations: priceList.observations ?? '',
            };

            this.scopeGeneralEnabled = scopeIds.includes(1);
            this.scopeClientsEnabled = scopeIds.includes(2);
            this.scopeProvidersEnabled = scopeIds.includes(3);
            this.scopeBranchesEnabled = scopeIds.includes(4);

            const clientsData = this.buildScopedSelectData(
                configs,
                'customerId',
                'customerSnapshot',
                'Cliente'
            );
            const providersData = this.buildScopedSelectData(
                configs,
                'supplierId',
                'supplierSnapshot',
                'Proveedor'
            );
            const branchesData = this.buildScopedSelectData(
                configs,
                'branchId',
                'branchSnapshot',
                'Sucursal'
            );

            this.clients = clientsData.selected;
            this.providers = providersData.selected;
            this.branches = branchesData.selected;
            this.clientsOptions = clientsData.options;
            this.providersOptions = providersData.options;
            this.branchesOptions = branchesData.options;

            this.productsSelected = items.map((item, index) => {
                const productId = item.product_id ?? item.productId ?? null;
                const productSnapshot = item.productSnapshot ?? {};
                const productCode = productSnapshot.code ?? item.code ?? (productId ? `PRD-${productId}` : '');
                const productName = productSnapshot.name
                    ?? productSnapshot.description
                    ?? item.name
                    ?? (productId ? `Producto ${productId}` : '');
                return {
                    id: item.id ?? `row-${index}`,
                    product_id: productId,
                    code: productCode,
                    name: productName,
                    price: item.price ?? '',
                    discount: item.discount ?? '',
                };
            });
            this.productsTableKey += 1;
        },
    },

    // =======================
    // MOUNTED
    // =======================
    mounted() {
        console.log(this.priceList);
        this.initializeFromPriceList();
    },
};
</script>

<template>
    <Layout>
        <PageHeader title="Listas Precios" pageTitle="Detalle" />
        <div class="row">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-6">
                            <h5>
                                Información General
                            </h5>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <button class="btn btn-sm btn-light" style="width: 90px; height: 36px;" @click="closeCreate">
                                    <i class="ri-arrow-left-circle-line"></i>
                                    Volver
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4 mb-2">
                            <label >Nombre</label>
                            <p class="text-muted">{{ formPriceList.name || '—' }}</p>
                        </div>
                        <div class="col-12 col-md-4 mb-2">
                            <label >Fecha Inicio</label>
                            <p class=" text-muted">{{ formPriceList.start_date_display || '—' }}</p>
                        </div>
                        <div class="col-12 col-md-4 mb-2">
                            <label >Fecha Fin</label>
                            <p class="text-muted">{{ formPriceList.end_date_display || '—' }}</p>
                        </div>

                        <div class="col-12 mb-2">
                            <label >Alcance</label>
                            <div class="d-flex flex-wrap gap-4">
                                <div class="form-check">
                                    <input
                                        id="scope-check-general"
                                        v-model="scopeGeneralEnabled"
                                        class="form-check-input"
                                        type="checkbox"
                                        disabled
                                    >
                                    <label class="form-check-label" for="scope-check-general">General</label>
                                </div>
                                <div class="form-check">
                                    <input
                                        id="scope-check-clients"
                                        v-model="scopeClientsEnabled"
                                        class="form-check-input"
                                        type="checkbox"
                                        disabled
                                    >
                                    <label class="form-check-label" for="scope-check-clients">Clientes</label>
                                </div>
                                <div class="form-check">
                                    <input
                                        id="scope-check-providers"
                                        v-model="scopeProvidersEnabled"
                                        class="form-check-input"
                                        type="checkbox"
                                        disabled
                                    >
                                    <label class="form-check-label" for="scope-check-providers">Proveedores</label>
                                </div>
                                <div class="form-check">
                                    <input
                                        id="scope-check-branches"
                                        v-model="scopeBranchesEnabled"
                                        class="form-check-input"
                                        type="checkbox"
                                        disabled
                                    >
                                    <label class="form-check-label" for="scope-check-branches">Sucursales</label>
                                </div>
                            </div>
                        </div>

                        <div v-if="showScopeClients" class="col-4 mb-2">
                            <label >Clientes</label>
                            <div class="chips-wrap">
                                <span
                                    v-for="name in clientNames"
                                    :key="name"
                                    class="badge bg-primary-subtle text-primary"
                                >
                                    {{ name }}
                                </span>
                                <span v-if="!clientNames.length" class="detail-empty">—</span>
                            </div>
                        </div>

                        <div v-if="showScopeProviders" class="col-4 mb-2">
                            <label >Proveedores</label>
                            <div class="chips-wrap">
                                <span
                                    v-for="name in providerNames"
                                    :key="name"
                                    class="badge bg-primary-subtle text-primary"
                                >
                                    {{ name }}
                                </span>
                                <span v-if="!providerNames.length" class="detail-empty">—</span>
                            </div>
                        </div>

                        <div v-if="showScopeBranches" class="col-4 mb-2">
                            <label >Sucursales</label>
                            <div class="chips-wrap">
                                <span
                                    v-for="name in branchNames"
                                    :key="name"
                                    class="badge bg-primary-subtle text-primary"
                                >
                                    {{ name }}
                                </span>
                                <span v-if="!branchNames.length" class="detail-empty">—</span>
                            </div>
                        </div>

                        <div class="col-4 mb-2">
                            <label >Estado</label>
                            <div>
                                <span :class="statusClass">{{ statusText }}</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class=" mb-2">Descripción</label>
                            <p v-if="hasDescription" class="detail-description-text">
                                {{ formPriceList.observations }}
                            </p>
                            <p v-else class="detail-empty mb-0">—</p>
                        </div>
                    </div>
                </div>
            </div>
            <ProductListDataTable
                :key="productsTableKey"
                :items="productsSelected"
                readonly
                search-disabled
                @update:items="updateProducts"
            />
        </div>
    </Layout>
</template>

<style scoped>

.detail-label {
    display: block;
    margin-bottom: 0.35rem;
    color: #6b7280;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.detail-value {
    margin: 0;
    color: #1f2937;
    font-size: 0.96rem;
    font-weight: 500;
    line-height: 1.35;
}

.chips-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.detail-description-text {
    margin: 0;
    color: #374151;
    font-size: 0.92rem;
    white-space: pre-wrap;
    line-height: 1.4;
}

.detail-empty {
    color: #9ca3af;
    font-size: 0.88rem;
    font-style: italic;
}
</style>