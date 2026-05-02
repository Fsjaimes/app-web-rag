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

    const { showAlert, showConfirm, showLoading, showWarning } = useAlert();
    const { fetchPetition } = useFetchPetition();
    const { focusFirstFormError } = useFormErrorFocus();

    export default {
        // =======================
        // CONFIG
        // =======================
        name: 'PriceListsCreate',
        components: {
            Layout,
            PageHeader,
            ProductListDataTable,
            Flatpickr,
            Select2,
            ProductListModal,
        },

        // =======================1
        // PROPS
        // =======================
        props: {
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
            };
        },

        // =======================
        // COMPUTED
        // =======================
        computed: {
            /**
             * Valor de scope para el API: "1" = general;
             * dimensiones específicas como "2,3,4" (2 clientes, 3 proveedores, 4 sucursales).
             * @returns {string}
             */
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
            /**
             * Cerrar el modal de creación de lista de precios
             */
            closeCreate() {
                router.visit(route('price_lists.index'));
            },

            /**
             * Actualizar los productos en la tabla
             * @param products - Los productos a actualizar
             */
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

            /**
             * Manejar los productos seleccionados traidos desde el modal
             * @param selectedProducts - Los productos seleccionados
             */
            handleSelectedItems(selectedProducts) {
                this.productsSelected = selectedProducts.map((p) => ({
                    ...p,
                    price: p.price ?? '',
                }));
                this.productsTableKey += 1;
                this.showModal = false;
            },

            /**
             * Preparar los datos para el envío al back
             * @returns {Object} - Los datos preparados para el envío al back
             */
            preparteData() {
                const priceListConfigurations = [
                    ...this.clients.map((client) => ({
                        customer_id: client?.id ?? client ?? null,
                        supplier_id: null,
                        branch_id: null,
                    })),
                    ...this.providers.map((provider) => ({
                        customer_id: null,
                        supplier_id: provider?.id ?? provider ?? null,
                        branch_id: null,
                    })),
                    ...this.selectedBranches.map((branch) => ({
                        customer_id: null,
                        supplier_id: null,
                        branch_id: branch?.id ?? branch ?? null,
                    })),
                ];

                const body = {
                    name: this.formPriceList.name,
                    start_date: this.formPriceList.start_date,
                    end_date: this.formPriceList.end_date,
                    status: this.formPriceList.status,
                    scope: this.scopeStringForPayload,
                    observations: this.formPriceList.observations,
                    price_list_configurations: priceListConfigurations,
                    price_list_items:[
                        ...this.productsSelected.map((product) => ({
                            product_id: product.product_id,
                            price: product.price,
                            discount_percentage: product.discount,
                        })),
                    ],
                };
                return body;
            },

            /**
             * Validar los campos requeridos
             * @returns {boolean} - true si los campos son válidos, false en caso contrario
             */
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

            /**
             * Validar los campos requeridos de la tabla de productos
             * @returns {Object} - Los errores de la tabla de productos
             */
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

            /**
             * Limpiar el error de un campo
             * @param field - El campo a limpiar
             */
            clearClientError(field) {
                if (!(field in this.clientErrors)) {
                    return;
                }
                const next = { ...this.clientErrors };
                delete next[field];
                this.clientErrors = next;
            },

            /**
             * Alcance general: al activarlo se quitan las demás dimensiones.
             * Si se desactiva sin ninguna dimensión, se vuelve a marcar general.
             */
            onGeneralScopeChange() {
                if (this.scopeGeneralEnabled) {
                    this.scopeClientsEnabled = false;
                    this.scopeProvidersEnabled = false;
                    this.scopeBranchesEnabled = false;
                    return;
                }
                const anySpecific =
                    this.scopeClientsEnabled || this.scopeProvidersEnabled || this.scopeBranchesEnabled;
                if (!anySpecific) {
                    nextTick(() => {
                        this.scopeGeneralEnabled = true;
                    });
                }
            },

            /**
             * Dimensiones específicas: al marcar alguna se quita general;
             * si no queda ninguna, se restaura general.
             */
            onSpecificScopeChange() {
                if (this.scopeClientsEnabled || this.scopeProvidersEnabled || this.scopeBranchesEnabled) {
                    this.scopeGeneralEnabled = false;
                    return;
                }
                this.scopeGeneralEnabled = true;
            },

            /**
             * Crear la lista de precios
             * @returns {Promise<void>}
             */
            async createPriceList() {
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
                    'Confirmar creación',
                    '¿Está seguro que desea crear esta lista de precios?',
                    'Sí, crear'
                );

                if (!isConfirmed) {
                    return;
                }

                this.loading = true;
                const loadingAlert = showLoading('Guardando lista de precios...', 'Por favor espere');

                try {
                    const body = this.preparteData();
                    const response = await fetchPetition('/price-lists', {
                        method: 'POST',
                        body: body,
                    });

                    loadingAlert.close();

                    if (response.ok) {
                        await showAlert({
                            icon: 'success',
                            title: 'Lista creada',
                            text: response?.data?.message ?? 'La lista de precios se creó correctamente.',
                            timer: 2500,
                            returnFocus: false,
                        });
                        this.closeCreate();
                        return;
                    }

                    const backendMessage =
                        response?.data?.message ||
                        response?.data?.error ||
                        'No fue posible crear la lista de precios.';

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
                        text: 'Ocurrió un error al crear la lista de precios.',
                        showConfirmButton: true,
                        returnFocus: false,
                    });
                } finally {
                    this.loading = false;
                }
            },
        },
    };
</script>

<template>
    <Layout>
        <PageHeader title="Listas Precios" pageTitle="Creación" />
        <div class="row">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-6">
                            <h5>
                                Información Generals
                            </h5>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <button class="btn btn-sm btn-light" style="width: 90px; height: 36px;" @click="closeCreate">
                                    <i class="ri-arrow-left-circle-line"></i>
                                    Cancelar
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-primary"
                                    style="width: 110px; height: 36px;"
                                    :disabled="loading"
                                    @click="createPriceList"
                                >
                                    <template v-if="loading">
                                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                        Guardando...
                                    </template>
                                    <template v-else>
                                        <i class="ri-add-line"></i>
                                        Guardar
                                    </template>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form ref="priceListForm" action="" @submit.prevent="createPriceList">
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
                                        <input
                                            id="scope-check-general"
                                            v-model="scopeGeneralEnabled"
                                            class="form-check-input"
                                            type="checkbox"
                                            @change="onGeneralScopeChange"
                                        >
                                        <label class="form-check-label" for="scope-check-general">General</label>
                                    </div>
                                    <div class="form-check">
                                        <input
                                            id="scope-check-clients"
                                            v-model="scopeClientsEnabled"
                                            class="form-check-input"
                                            type="checkbox"
                                            @change="onSpecificScopeChange(); clearClientError('clients')"
                                        >
                                        <label class="form-check-label" for="scope-check-clients">Clientes</label>
                                    </div>
                                    <div class="form-check">
                                        <input
                                            id="scope-check-providers"
                                            v-model="scopeProvidersEnabled"
                                            class="form-check-input"
                                            type="checkbox"
                                            @change="onSpecificScopeChange(); clearClientError('providers')"
                                        >
                                        <label class="form-check-label" for="scope-check-providers">Proveedores</label>
                                    </div>
                                    <div class="form-check">
                                        <input
                                            id="scope-check-branches"
                                            v-model="scopeBranchesEnabled"
                                            class="form-check-input"
                                            type="checkbox"
                                            @change="onSpecificScopeChange(); clearClientError('branches')"
                                        >
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
                                    <label class="form-check-label" for="price-list-status-switch">
                                        Estado 
                                    </label>
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

    /* Estilos para los campos con errores */
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