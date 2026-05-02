<script>
    // =======================
    // IMPORTS
    // =======================
    import Layout from '@/Layouts/main.vue';
    import PageHeader from '@/Components/page-header.vue';
    import ProductListDataTable from '@/Pages/PriceLists/Components/ProductListDataTable.vue';
    import Flatpickr from 'vue-flatpickr-component';
    import { router } from '@inertiajs/vue3';
    import { nextTick } from 'vue';
    import Select2 from '@/Components/Select2.vue';
    import ProductListModal from '@/Pages/PriceLists/Components/ProductListModal.vue';
    import { useAlert } from '@/Composables/useSweetAlert.js';
    import { useFetchPetition } from '@/Composables/useFetchPetition.js';
    import { useFormErrorFocus } from '@/Composables/useFormErrorFocus.js';

    const { showAlert, showConfirm, showLoading } = useAlert();
    const { fetchPetition } = useFetchPetition();
    const { focusFirstFormError } = useFormErrorFocus();

    export default {
        // =======================
        // CONFIG
        // =======================
        name: 'PriceListsUpdate',
        components: {
            Layout,
            PageHeader,
            ProductListDataTable,
            Flatpickr,
            Select2,
            ProductListModal,
        },

        // =======================
        // PROPS
        // =======================
        props: {
            priceList: {
                type: Object,
                default: () => ({}),
            },
            thirdParties: {
                type: Array,
                default: () => [],
            },
            branches: {
                type: Array,
                default: () => [],
            },
        },

        // =======================
        // DATA
        // =======================
        data() {
            return {
                config: {
                    dateFormat: 'Y-m-d',
                },
                clientsOptions: this.thirdParties,
                providersOptions: this.thirdParties,
                branchesOptions: this.branches,
                productsSelected: [],
                productsTableKey: 0,
                showModal: false,
                loading: false,
                scopeGeneralEnabled: true,
                scopeClientsEnabled: false,
                scopeProvidersEnabled: false,
                scopeBranchesEnabled: false,
                clients: [],
                providers: [],
                selectedBranches: [],
                formPriceList: {
                    name: '',
                    start_date: '',
                    end_date: '',
                    status: '1',
                    scope: '1',
                    observations: '',
                },
                clientErrors: {},
                tableCellErrors: {},
                tableValidationMessage: '',
                rowCounter: 0,
            };
        },

        // =======================
        // COMPUTED
        // =======================
        computed: {
            scopeStringForPayload() {
                if (this.scopeGeneralEnabled) {
                    return '1';
                }
                const ids = [];
                if (this.scopeClientsEnabled) {
                    ids.push(2);
                }
                if (this.scopeProvidersEnabled) {
                    ids.push(3);
                }
                if (this.scopeBranchesEnabled) {
                    ids.push(4);
                }
                return ids.length ? ids.join(',') : '1';
            },
            showScopeClients() {
                return this.scopeClientsEnabled;
            },
            showScopeProviders() {
                return this.scopeProvidersEnabled;
            },
            showScopeBranches() {
                return this.scopeBranchesEnabled;
            },
        },

        // =======================
        // WATCH
        // =======================
        watch: {
            scopeStringForPayload: {
                handler(val) {
                    this.formPriceList.scope = val;
                },
                immediate: true,
            },
            scopeClientsEnabled(enabled) {
                if (!enabled) {
                    this.clients = [];
                    this.clearClientError('clients');
                }
            },
            scopeProvidersEnabled(enabled) {
                if (!enabled) {
                    this.providers = [];
                    this.clearClientError('providers');
                }
            },
            scopeBranchesEnabled(enabled) {
                if (!enabled) {
                    this.selectedBranches = [];
                    this.clearClientError('branches');
                }
            },
        },

        // =======================
        // METHODS
        // =======================
        methods: {
            closeUpdate() {
                router.visit(route('price_lists.index'));
            },

            updateProducts(products) {
                this.productsSelected = products;
                if (Object.keys(this.tableCellErrors).length > 0) {
                    const { errors } = this.validateProductsTableRequiredFields();
                    this.tableCellErrors = errors;
                }
                const { isValid } = this.validateProductsTableRequiredFields();
                if (isValid) {
                    this.tableValidationMessage = '';
                }
            },

            handleSelectedItems(selectedProducts) {
                this.productsSelected = selectedProducts.map((p, index) => ({
                    id: this.buildRowId(`modal-${index}`),
                    product_id: p.product_id ?? p.productId ?? p.id ?? null,
                    code: p.code ?? '',
                    name: p.name ?? '',
                    price: p.price ?? '',
                    discount: p.discount ?? '',
                }));
                this.productsTableKey += 1;
                this.showModal = false;
            },

            buildRowId(seed = 'row') {
                this.rowCounter += 1;
                return `${seed}-${Date.now()}-${this.rowCounter}`;
            },

            preparteData() {
                return {
                    name: this.formPriceList.name,
                    start_date: this.formPriceList.start_date,
                    end_date: this.formPriceList.end_date,
                    status: this.formPriceList.status,
                    scope: this.scopeStringForPayload,
                    observations: this.formPriceList.observations,
                    clients: this.clients,
                    providers: this.providers,
                    branches: this.selectedBranches,
                    price_list_items: [
                        ...this.productsSelected.map((product) => ({
                            product_id: product.product_id,
                            price: product.price,
                            discount_percentage: product.discount,
                        })),
                    ],
                };
            },

            validateRequiredFields() {
                const errors = {};
                const hasItems = (value) => Array.isArray(value) && value.length > 0;

                if (!String(this.formPriceList.name ?? '').trim()) {
                    errors.name = true;
                }
                if (!this.formPriceList.start_date) {
                    errors.start_date = true;
                }
                if (this.formPriceList.status !== '0' && this.formPriceList.status !== '1') {
                    errors.status = true;
                }
                if (this.scopeClientsEnabled && !hasItems(this.clients)) {
                    errors.clients = true;
                }
                if (this.scopeProvidersEnabled && !hasItems(this.providers)) {
                    errors.providers = true;
                }
                if (this.scopeBranchesEnabled && !hasItems(this.selectedBranches)) {
                    errors.branches = true;
                }

                this.clientErrors = errors;
                return Object.keys(errors).length === 0;
            },

            validateProductsTableRequiredFields() {
                const errors = {};
                this.productsSelected.forEach((item) => {
                    const rowErrors = {};
                    const hasProduct = item?.product_id !== null && item?.product_id !== undefined && String(item?.product_id).trim() !== '';
                    const hasPrice = item?.price !== null && item?.price !== undefined && String(item?.price).trim() !== '';

                    if (!hasProduct) {
                        rowErrors.product = true;
                    }
                    if (!hasPrice) {
                        rowErrors.price = true;
                    }

                    if (Object.keys(rowErrors).length > 0) {
                        errors[item.id] = rowErrors;
                    }
                });

                return {
                    isValid: Object.keys(errors).length === 0,
                    errors,
                };
            },

            clearClientError(field) {
                if (!(field in this.clientErrors)) {
                    return;
                }
                const next = { ...this.clientErrors };
                delete next[field];
                this.clientErrors = next;
            },

            onGeneralScopeChange() {
                if (this.scopeGeneralEnabled) {
                    this.scopeClientsEnabled = false;
                    this.scopeProvidersEnabled = false;
                    this.scopeBranchesEnabled = false;
                    return;
                }
                const anySpecific = this.scopeClientsEnabled || this.scopeProvidersEnabled || this.scopeBranchesEnabled;
                if (!anySpecific) {
                    nextTick(() => {
                        this.scopeGeneralEnabled = true;
                    });
                }
            },

            onSpecificScopeChange() {
                if (this.scopeClientsEnabled || this.scopeProvidersEnabled || this.scopeBranchesEnabled) {
                    this.scopeGeneralEnabled = false;
                    return;
                }
                this.scopeGeneralEnabled = true;
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
                            name: this.resolveSnapshotName(config?.[snapshotKey], `${fallbackLabel} ${numericId}`),
                        });
                    }
                });

                const options = Array.from(optionsMap.values());
                const selected = options.map((option) => option.id);

                return { options, selected };
            },

            /**
             * Opciones de terceros para Select2: lista del back + filas guardadas que ya no están en el catálogo.
             */
            mergeThirdPartySelectOptions(propList, configFallbackOptions) {
                const map = new Map();
                (Array.isArray(propList) ? propList : []).forEach((o) => {
                    const id = Number(o?.id);
                    if (!Number.isInteger(id) || id <= 0) {
                        return;
                    }
                    map.set(id, {
                        id,
                        description: o.description ?? o.name ?? String(id),
                    });
                });
                (Array.isArray(configFallbackOptions) ? configFallbackOptions : []).forEach((o) => {
                    const id = Number(o?.id);
                    if (!Number.isInteger(id) || id <= 0 || map.has(id)) {
                        return;
                    }
                    map.set(id, {
                        id,
                        description: o.name ?? o.description ?? `Tercero ${id}`,
                    });
                });
                return Array.from(map.values());
            },

            mergeBranchSelectOptions(propList, configFallbackOptions) {
                const map = new Map();
                (Array.isArray(propList) ? propList : []).forEach((o) => {
                    const id = Number(o?.id);
                    if (!Number.isInteger(id) || id <= 0) {
                        return;
                    }
                    map.set(id, {
                        id,
                        description: o.description ?? o.name ?? String(id),
                    });
                });
                (Array.isArray(configFallbackOptions) ? configFallbackOptions : []).forEach((o) => {
                    const id = Number(o?.id);
                    if (!Number.isInteger(id) || id <= 0 || map.has(id)) {
                        return;
                    }
                    map.set(id, {
                        id,
                        description: o.name ?? o.description ?? `Sucursal ${id}`,
                    });
                });
                return Array.from(map.values());
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
                    status: String(priceList.status ?? '1'),
                    scope: String(priceList.scope ?? '1'),
                    observations: priceList.observations ?? '',
                };

                this.scopeGeneralEnabled = scopeIds.includes(1);
                this.scopeClientsEnabled = scopeIds.includes(2);
                this.scopeProvidersEnabled = scopeIds.includes(3);
                this.scopeBranchesEnabled = scopeIds.includes(4);

                const clientsData = this.buildScopedSelectData(configs, 'customerId', 'customerSnapshot', 'Cliente');
                const providersData = this.buildScopedSelectData(configs, 'supplierId', 'supplierSnapshot', 'Proveedor');
                const branchesData = this.buildScopedSelectData(configs, 'branchId', 'branchSnapshot', 'Sucursal');

                this.clients = clientsData.selected;
                this.providers = providersData.selected;
                this.selectedBranches = branchesData.selected.filter((id) => Number(id) > 0);
                this.clientsOptions = this.mergeThirdPartySelectOptions(this.thirdParties, clientsData.options);
                this.providersOptions = this.mergeThirdPartySelectOptions(this.thirdParties, providersData.options);
                this.branchesOptions = this.mergeBranchSelectOptions(this.branches, branchesData.options);

                this.productsSelected = items.map((item, index) => {
                    const productId = item.product_id ?? item.productId ?? null;
                    const productSnapshot = item.productSnapshot ?? {};
                    const productCode = productSnapshot.code ?? item.code ?? (productId ? `PRD-${productId}` : '');
                    const productName = productSnapshot.name ?? productSnapshot.description ?? item.name ?? (productId ? `Producto ${productId}` : '');
                    return {
                        id: this.buildRowId(`db-${item.id ?? index}`),
                        product_id: productId,
                        code: productCode,
                        name: productName,
                        price: item.price ?? '',
                        discount: item.discount ?? '',
                    };
                });
                this.productsTableKey += 1;
            },

            async updatePriceList() {
                this.clientErrors = {};
                this.tableCellErrors = {};
                this.tableValidationMessage = '';
                const isFormValid = this.validateRequiredFields();
                const tableValidation = this.validateProductsTableRequiredFields();
                this.tableCellErrors = tableValidation.errors;

                if (!isFormValid || !tableValidation.isValid) {
                    await showAlert({
                        icon: 'warning',
                        title: '¡Alerta!',
                        text: 'Campos sin diligenciar. Revise los campos resaltados.',
                        timer: 2500,
                        returnFocus: false,
                    });
                    if (!isFormValid) {
                        await nextTick();
                        focusFirstFormError(this.$refs.priceListForm, this.clientErrors);
                    }
                    if (!tableValidation.isValid) {
                        this.tableValidationMessage = 'No deben haber celdas de productos vacías y sin precio.';
                        await nextTick();
                        setTimeout(() => {
                            this.$refs.productListDataTable?.focusFirstMissingRequiredInput?.();
                        }, 0);
                    }
                    return;
                }

                const isConfirmed = await showConfirm(
                    'question',
                    'Confirmar actualización',
                    '¿Está seguro que desea actualizar esta lista de precios?',
                    'Sí, actualizar'
                );

                if (!isConfirmed) {
                    return;
                }

                this.loading = true;
                const loadingAlert = showLoading('Actualizando lista de precios...', 'Por favor espere');

                try {
                    const body = this.preparteData();
                    const response = await fetchPetition(`/price-lists/${this.priceList.uuid}`, {
                        method: 'PUT',
                        body,
                    });

                    loadingAlert.close();

                    if (response.ok) {
                        await showAlert({
                            icon: 'success',
                            title: 'Lista actualizada',
                            text: response?.data?.message ?? 'La lista de precios se actualizó correctamente.',
                            timer: 2500,
                            returnFocus: false,
                        });
                        this.closeUpdate();
                        return;
                    }

                    const backendMessage =
                        response?.data?.message ||
                        response?.data?.error ||
                        'No fue posible actualizar la lista de precios.';

                    await showAlert({
                        icon: 'error',
                        title: 'No se pudo guardar',
                        text: backendMessage,
                        showConfirmButton: true,
                        returnFocus: false,
                    });
                } catch (error) {
                    console.error('Error:', error);
                    loadingAlert.close();
                    await showAlert({
                        icon: 'error',
                        title: 'Error inesperado',
                        text: 'Ocurrió un error al actualizar la lista de precios.',
                        showConfirmButton: true,
                        returnFocus: false,
                    });
                } finally {
                    this.loading = false;
                }
            },
        },

        mounted() {
            this.initializeFromPriceList();
        },
    };
</script>

<template>
    <Layout>
        <PageHeader title="Listas Precios" pageTitle="Actualización" />
        <div class="row">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-6">
                            <h5>Información General</h5>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <button class="btn btn-sm btn-light" style="width: 90px; height: 36px;" @click="closeUpdate">
                                    <i class="ri-arrow-left-circle-line"></i>
                                    Cancelar
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-primary"
                                    style="width: 110px; height: 36px;"
                                    :disabled="loading"
                                    @click="updatePriceList"
                                >
                                    <template v-if="loading">
                                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                        Guardando...
                                    </template>
                                    <template v-else>
                                        <i class="ri-save-line"></i>
                                        Guardar
                                    </template>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form ref="priceListForm" action="" @submit.prevent="updatePriceList">
                        <div class="row">
                            <div class="col-4 mb-3" data-form-error-anchor="name" :class="{ 'has-error': clientErrors.name }">
                                <label for="name">Nombre</label><span class="text-danger"> *</span>
                                <input type="text" class="form-control" id="name" placeholder="Nombre" v-model="formPriceList.name" @input="clearClientError('name')">
                            </div>
                            <div class="col-4 mb-3" data-form-error-anchor="start_date" :class="{ 'has-error': clientErrors.start_date }">
                                <label for="name">Fecha Inicio</label><span class="text-danger"> *</span>
                                <Flatpickr v-model="formPriceList.start_date" :config="config" class="form-control flatpickr-input" placeholder="Fecha Inicio" @on-change="clearClientError('start_date')"></Flatpickr>
                            </div>
                            <div class="col-4 mb-3">
                                <label for="name">Fecha Fin</label>
                                <Flatpickr v-model="formPriceList.end_date" :config="config" class="form-control flatpickr-input" placeholder="Fecha Fin"></Flatpickr>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="d-block mb-2">Alcance</label>
                                <div class="d-flex flex-wrap gap-4">
                                    <div class="form-check">
                                        <input id="scope-check-general" v-model="scopeGeneralEnabled" class="form-check-input" type="checkbox" @change="onGeneralScopeChange">
                                        <label class="form-check-label" for="scope-check-general">General</label>
                                    </div>
                                    <div class="form-check">
                                        <input id="scope-check-clients" v-model="scopeClientsEnabled" class="form-check-input" type="checkbox" @change="onSpecificScopeChange(); clearClientError('clients')">
                                        <label class="form-check-label" for="scope-check-clients">Clientes</label>
                                    </div>
                                    <div class="form-check">
                                        <input id="scope-check-providers" v-model="scopeProvidersEnabled" class="form-check-input" type="checkbox" @change="onSpecificScopeChange(); clearClientError('providers')">
                                        <label class="form-check-label" for="scope-check-providers">Proveedores</label>
                                    </div>
                                    <div class="form-check">
                                        <input id="scope-check-branches" v-model="scopeBranchesEnabled" class="form-check-input" type="checkbox" @change="onSpecificScopeChange(); clearClientError('branches')">
                                        <label class="form-check-label" for="scope-check-branches">Sucursales</label>
                                    </div>
                                </div>
                            </div>
                            <div v-if="showScopeClients" class="col-4 mb-3" data-form-error-anchor="clients" :class="{ 'has-error': clientErrors.clients }">
                                <label for="price-list-clients">Clientes</label><span class="text-danger"> *</span>
                                <Select2
                                    id="price-list-clients"
                                    v-model="clients"
                                    :options="clientsOptions"
                                    text-field="description"
                                    class="form-control"
                                    placeholder="Clientes"
                                    :show-validation-error="!!clientErrors.clients"
                                    @change="clearClientError('clients')"
                                    multiple
                                />
                            </div>
                            <div v-if="showScopeProviders" class="col-4 mb-3" data-form-error-anchor="providers" :class="{ 'has-error': clientErrors.providers }">
                                <label for="price-list-providers">Proveedores</label><span class="text-danger"> *</span>
                                <Select2
                                    id="price-list-providers"
                                    v-model="providers"
                                    :options="providersOptions"
                                    text-field="description"
                                    class="form-control"
                                    placeholder="Proveedores"
                                    :show-validation-error="!!clientErrors.providers"
                                    @change="clearClientError('providers')"
                                    multiple
                                />
                            </div>
                            <div v-if="showScopeBranches" class="col-4 mb-3" data-form-error-anchor="branches" :class="{ 'has-error': clientErrors.branches }">
                                <label for="price-list-branches">Sucursales</label><span class="text-danger"> *</span>
                                <Select2
                                    id="price-list-branches"
                                    v-model="selectedBranches"
                                    :options="branchesOptions"
                                    text-field="description"
                                    class="form-control"
                                    placeholder="Sucursales"
                                    :show-validation-error="!!clientErrors.branches"
                                    @change="clearClientError('branches')"
                                    multiple
                                />
                            </div>
                            <div class="col-12 mb-3 mt-2" data-form-error-anchor="status" :class="{ 'has-error': clientErrors.status }">
                                <div class="form-check form-switch form-switch-md" dir="ltr">
                                    <label class="form-check-label" for="price-list-status-switch">Estado</label>
                                    <input
                                        id="price-list-status-switch"
                                        v-model="formPriceList.status"
                                        class="form-check-input"
                                        type="checkbox"
                                        true-value="1"
                                        false-value="0"
                                        @change="clearClientError('status')"
                                    >
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="name">Descripción</label>
                                <textarea class="form-control" id="name" placeholder="Descripción" v-model="formPriceList.observations"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <ProductListDataTable
                ref="productListDataTable"
                :key="productsTableKey"
                :items="productsSelected"
                :cell-errors="tableCellErrors"
                :validation-message="tableValidationMessage"
                show-catalog-link
                @update:items="updateProducts"
                @add-products="showModal = true"
            />
        </div>
        <ProductListModal
            v-if="showModal"
            @close="showModal = false"
            @selected-items="handleSelectedItems"
        />
    </Layout>
</template>

<style scoped>
    .has-error :deep(.select2-selection),
    .has-error :deep(.select2-selection--single),
    .has-error :deep(.select2-selection--multiple) {
        border-color: #dc3545 !important;
    }

    .has-error :deep(.flatpickr-input),
    .has-error .flatpickr-input,
    .has-error .form-control {
        border-color: #dc3545 !important;
    }

    .has-error .form-check-input {
        border-color: #dc3545 !important;
    }
</style>
