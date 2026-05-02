<script>
import Layout from '@/Layouts/main.vue';
import PageHeader from '@/Components/page-header.vue';
import { useAlert } from '@/Composables/useSweetAlert.js';
import { router } from '@inertiajs/vue3';
import GenericDetailTable from '@/Components/GenericDetailTable.vue';
import { useDateFormatter } from '@/Composables/useDateFormatter.js';
import { useFetchPetition } from '@/Composables/useFetchPetition.js';
const { fetchPetition } = useFetchPetition();
const { showConfirm, showAlert } = useAlert();
const { displayDateFormat } = useDateFormatter();
export default {
    name: 'DocumentsCreate',
    components: {
        Layout,
        PageHeader,
        GenericDetailTable,
    },
    props: {
        document: {
            type: Object,
            required: true,
        },
        wharehouses: {
            type: Array,
            required: true,
        },
    },
    data() {
        return {
            form: {
                notes: '',
            },
            details: [],
            detailColumns: [
                {
                    key: 'product',
                    label: 'Producto',
                    model: 'productId',
                    type: 'select',
                    optionsKey: 'products',
                    valueField: 'id',
                    primaryField: 'code',
                    secondaryField: 'name',
                    align: 'start',
                    portalTarget: '#card-details',
                },
                {
                    key: 'lot',
                    label: 'Lote',
                    model: 'lotId',
                    type: 'select',
                    align: 'start',
                    optionsSource: 'row',
                    optionsKey: 'lots',
                    valueField: 'id',
                    primaryField: 'name',
                    portalTarget: '#card-details',
                },
                {
                    key: 'quantity',
                    label: 'Cantidad',
                    model: 'quantity',
                    type: 'number_thousands',
                    align: 'end',
                },
                {
                    key: 'unit',
                    label: 'Unidad',
                    model: 'unitId',
                    type: 'select',
                    align: 'start',
                    optionsKey: 'units',
                    valueField: 'id',
                    primaryField: 'name',
                    portalTarget: '#card-details',
                },
                {
                    key: 'unitPrice',
                    label: 'Precio unitario',
                    model: 'unitPrice',
                    type: 'number_thousands',
                    align: 'end',
                },
                {
                    key: 'total',
                    label: 'Precio total',
                    model: 'total',
                    type: 'number_thousands',
                    align: 'end',
                    disabled: true,
                },
                {
                    key: 'warehouseTo',
                    label: 'Bodega Destino',
                    model: 'warehouseToId',
                    type: 'select',
                    align: 'start',
                    optionsKey: 'warehouses',
                    valueField: 'id',
                    primaryField: 'labelDescription',
                    portalTarget: '#card-details',
                },
                {
                    key: 'description',
                    label: 'Descripción',
                    model: 'description',
                    type: 'text',
                    align: 'start',
                },
            ],
            detailOptionSources: {
                products: [
                    { id: 1, code: '123456', name: 'Producto 1', unitId: 1, unitPrice: 10000, lots: [{ id: 1, name: '123456' },{ id: 2, name: '123457' }]  },
                    { id: 2, code: '123457', name: 'Producto 2', unitId: 2, unitPrice: 10000, lots: [{ id: 2, name: '123458' },{ id: 3, name: '123459' }]  },
                    { id: 3, code: '123458', name: 'Producto 3', unitId: 3, unitPrice: 10000, lots: [{ id: 3, name: '123460' },{ id: 4, name: '123461' }]  },
                    { id: 4, code: '123459', name: 'Producto 4', unitId: 4, unitPrice: 10000, lots: [{ id: 4, name: '123462' },{ id: 5, name: '123463' }]  },
                ],
                units: [
                    { id: 1, name: 'Unidad 1' },
                    { id: 2, name: 'Unidad 2' },
                    { id: 3, name: 'Unidad 3' },
                    { id: 4, name: 'Unidad 4' },
                ],
                warehouses: this.wharehouses || [],
            },
            loading: false,
        }
    },
    methods: {
        getStatusClass(status) {
            const classes = {
                0: 'badge bg-dark-subtle text-dark',
                1: 'badge bg-success-subtle text-success',
                2: 'badge bg-danger-subtle text-danger',
            };
            return classes[status] || 'badge bg-secondary-subtle text-secondary';
        },
        async cancelDocument() {
            try {
                const confirmedCancel = await showConfirm(
                    'warning',
                    '¡Alerta!',
                    '¿Está seguro que desea cancelar el documento?',
                    'Sí, cancelar'
                );
                console.log(confirmedCancel);
                
                if (!confirmedCancel) return;
                router.visit('/documentos/REM');
            } catch (error) {
                showAlert('error', 'Error', 'Error al cancelar el documento', 1500);
            }
        },
        createDetail() {
            console.log('createDetail');
            
            this.details.push(this.makeDetailRow());
        },
        duplicateDetail(tempUuid) {
            const sourceIndex = this.details.findIndex((detail) => (detail.tempUuid || detail.uuid) === tempUuid);
            if (sourceIndex < 0) return;
            const source = this.details[sourceIndex];
            const copy = {
                ...source,
                lotOptions: [...(source.lotOptions || [])],
                tempUuid: `tmp-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
            };
            this.details.splice(sourceIndex + 1, 0, copy);
        },
        detailUpdated(payload) {
            console.log(payload);
            let detail = this.details.find((detail) => (detail.tempUuid || detail.uuid) === payload.tempUuid);
            if (detail) {
                if(detail.productId && payload.selected && payload.field === 'productId'){
                    detail.unitPrice = payload.selected.unitPrice || null;
                    detail.unitId = payload.selected.unitId || null;
                    detail.lots = payload.selected.lots || [];
                    detail.lotId = null;
                    detail.total = (Number(detail.quantity || 0) * Number(detail.unitPrice || 0)) === 0
                        ? null
                        : Number(detail.quantity || 0) * Number(detail.unitPrice || 0);
                }else if(payload.field === 'productId'){
                    detail.unitPrice = null;
                    detail.total = null;
                    detail.unitId = null;
                    detail.lots = [];
                    detail.lotId = null;
                }

                if(payload.field === 'unitPrice' && detail.unitPrice){
                    detail.total = (Number(detail.quantity || 0) * Number(detail.unitPrice || 0)) === 0
                        ? null
                        : Number(detail.quantity || 0) * Number(detail.unitPrice || 0);
                }else if(payload.field === 'unitPrice'){
                    detail.total = null;
                }

                if(payload.field === 'quantity' && detail.quantity){
                    detail.total = (Number(detail.quantity || 0) * Number(detail.unitPrice || 0)) === 0
                        ? null
                        : Number(detail.quantity || 0) * Number(detail.unitPrice || 0);
                }else if(payload.field === 'quantity'){
                    detail.total = null;
                }

            }
        },
        deleteDetail(tempUuid) {
            this.details = this.details.filter((detail) => (detail.tempUuid || detail.uuid) !== tempUuid);
        },
        makeDetailRow() {
            return {
                tempUuid: `tmp-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
                productId: '',
                lotId: '',
                lots: [],
                quantity: '',
                unitId: '',
                unitPrice: '',
                total: '',
                warehouseToId: '',
                description: '',
            };
        },
        convertDateFormat(date) {
            return displayDateFormat(date);
        },
        async submitForm() {
            try {
                const confirmed = await showConfirm(
                    'warning',
                    '¡Alerta!',
                    '¿Está seguro que desea guardar el documento?',
                    'Sí, guardar'
                );
                if (!confirmed) return;
                this.loading = true;
                const body = {
                    ...this.document,
                    ...this.form,
                    details: this.details,
                };
                const response = await fetchPetition('/documents', {
                    method: 'POST',
                    body,
                });
                if (response.ok) {
                    showAlert('success', 'Éxito', 'Documento guardado correctamente', 1500);
                    router.visit('/documentos');
                } else {
                    showAlert('error', 'Error', response.data?.message || 'Error al guardar el documento', 3000);
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('error', 'Error inesperado', 'Ocurrió un error al guardar el documento', 3000);
            } finally {
                this.loading = false;
            }
        },
    },
    mounted() {
        this.createDetail();
    },
};
</script>
<template>
    <Layout>
        <PageHeader title="Crear Documento" pageTitle="Documentos" />
        <div class="row">
            <div class="col-12">
                <div class="card ">
                    <div class="card-body p-0">
                        <div class="accordion-item border-0">
                            <h2 id="headingGeneral" class="accordion-header border-bottom">
                                <div role="button" data-bs-toggle="collapse" data-bs-target="#collapseGeneral" aria-expanded="true" aria-controls="collapseGeneral" class="d-flex align-items-center p-2 shadow-none p-3">
                                    <i class="ri ri-file-list-2-line fs-16 text-primary me-2"></i>
                                    <h5 class="card-title mb-0 flex-fill">Información general</h5>
                                    <i class="ri ri-arrow-down-s-line fs-18 ms-auto"></i>
                                </div>
                            </h2>
                            <div id="collapseGeneral" aria-labelledby="headingGeneral" data-bs-parent="#accordion-general" class="accordion-collapse collapse show">
                                <div class="accordion-body p-3 ">
                                    <div class="row">
                                        <div class="col-4 mb-3">
                                            <label class="form-label">Código</label>
                                            <h6 class="mb-3 text-uppercase" :class="{'text-primary': document.code, 'text-muted': !document.code}">{{ document.code ?? 'SIN DEFINIR' }}</h6>
                                        </div>
                                        <div class="col-4 mb-3">
                                            <label class="form-label">Fecha</label>
                                            <p class="text-muted text-uppercase">{{ convertDateFormat(document.date) ?? 'SIN DEFINIR' }}</p>
                                        </div>
                                        <div class="col-4 mb-3">
                                            <label class="form-label">Estado</label> <br>
                                            <span :class="getStatusClass(document.status)" class="text-uppercase">{{ document.statusDescription ?? 'SIN DEFINIR' }}</span>
                                        </div>
                                        <div class="col-4 mb-3">
                                            <label class="form-label">Tipo Documento</label>
                                            <p class="text-muted text-uppercase">{{ document.labelDocumentTypeDescription ?? 'SIN DEFINIR' }}</p>
                                        </div>
                                        <div class="col-4 mb-3">
                                            <label class="form-label">Sucursal</label>
                                            <p class="text-muted text-uppercase">{{ document.labelWarehouseDescription ?? 'SIN DEFINIR' }}</p>
                                        </div>
                                        <div class="col-12 mb-3">
                                            <label class="form-label">Notas</label>
                                            <textarea
                                                placeholder="Ingrese Notas"
                                                class="form-control"
                                                v-model="form.notes"
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card" id="card-details">
                    <div class="card-body p-0">
                        <div class="accordion-item border-0">
                            <h2 id="headingDetails" class="accordion-header">
                                <div role="button" data-bs-toggle="collapse" data-bs-target="#collapseDetails" aria-expanded="true" aria-controls="collapseDetails" class="d-flex align-items-center p-2 shadow-none p-3">
                                    <i class="ri ri-stack-line fs-16 text-primary me-2"></i>
                                    <h5 class="card-title mb-0 flex-fill">Detalles</h5>
                                    <i class="ri ri-arrow-down-s-line fs-18 ms-auto"></i>
                                </div>
                            </h2>
                            <div id="collapseDetails" aria-labelledby="headingDetails" data-bs-parent="#accordion-details" class="accordion-collapse collapse show">
                                <div class="accordion-body p-3 ">
                                    <GenericDetailTable
                                        ref="documentDetailTable"
                                        :details="details"
                                        :columns="detailColumns"
                                        :option-sources="detailOptionSources"
                                        :per-page="25"
                                        @create-detail="createDetail"
                                        @duplicate-detail="duplicateDetail"
                                        @delete-detail="deleteDetail"
                                        @detail-updated="detailUpdated"
                                        :box-color="'#6725e2'"
                                        :bg-color="'#6725e20d'"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end mb-4">
                <button type="button" class="btn btn-light me-2 w-sm" @click="cancelDocument">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary w-sm" @click="submitForm" :disabled="loading">
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    {{ loading ? 'Guardando...' : 'Guardar' }}
                </button>
            </div>
        </div>
    </Layout>
</template>
<style scoped>
</style>