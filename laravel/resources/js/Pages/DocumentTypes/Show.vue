<script>
import Layout from "@/Layouts/main.vue"
import PageHeader from "@/Components/page-header.vue"
import { useFetchPetition } from '@/Composables/useFetchPetition.js'
import { useAlert } from '@/Composables/useSweetAlert.js'

const { fetchPetition } = useFetchPetition()
const { showAlert } = useAlert()

export default {
    name: 'DocumentTypesShow',
    components: {
        Layout,
        PageHeader,
    },
    props: {
        uuid: {
            type: String,
            required: false,
            default: '',
        },
        item: {
            type: Object,
            required: false,
            default: () => null,
        },
    },
    data() {
        return {
            loading: false,
            documentType: null,
        }
    },
    computed: {
        hasData() {
            return this.documentType && Object.keys(this.documentType).length > 0;
        },
        affectsInventoryEnabled() {
            return this.documentType?.affectsInventory;
        },
        hasDateEnabled() {
            return this.documentType?.hasDate;
        },
    },
    methods: {
    },
    async mounted() {
        if (this.item && Object.keys(this.item).length > 0) {
            this.documentType = { ...this.item }
            return
        }
    },
}
</script>

<template>
    <Layout>
        <PageHeader title="Detalle Tipo Documento" pageTitle="Tipos Documentos" />

        <div class="row">
            <div class="col-lg-12">
                <div v-if="loading" class="card">
                    <div class="card-body text-center py-5">
                        <p class="text-muted mb-0">Cargando información...</p>
                    </div>
                </div>

                <div v-else-if="hasData" class="row">
                    <div class="col-8">
                        <div class="card">
                            <div class="card-header">
                                <div class="form-section-title">
                                    <h6 class="mb-1">Información principal</h6>
                                    <p class="text-muted mb-0">Detalle del tipo documento registrado.</p>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label">Prefijo</label>
                                        <p class="text-muted text-uppercase">{{ documentType.prefix || '-' }}</p>
                                    </div>
                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label">Nombre</label>
                                        <p class="text-muted text-capitalize">{{ documentType.name || '-' }}</p>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12 mb-2">
                                        <div class="check-card h-100">
                                            <div class="check-info-row">
                                                <div class="form-check form-check-custom m-0">
                                                    <input :checked="documentType.affectsInventory" class="form-check-input" type="checkbox" id="affectsInventoryShow" disabled>
                                                    <label class="form-check-label fw-semibold" for="affectsInventoryShow">Afecta Inventario</label>
                                                </div>
                                                <small class="text-muted text-end"><i class="ri-information-line"></i> Indica si este tipo documento genera movimientos en el inventario.</small>
                                            </div>
                                        </div>
                                    </div>

                                    <template v-if="affectsInventoryEnabled">
                                        <div class="col-12 col-md-6 mb-3">
                                            <label class="form-label">Tipo Movimiento Inventario</label>
                                            <p class="text-muted text-uppercase">{{ documentType.inventoryMovementTypeDescription || '' }}</p>
                                        </div>
                                        <div class="col-12 col-md-6 mb-2">
                                            <div class="check-card h-100">
                                                <div class="check-info-row">
                                                    <div class="form-check form-check-custom m-0">
                                                        <input :checked="documentType.allowNegativeInventory" class="form-check-input" type="checkbox" id="allowNegativeInventoryShow" disabled>
                                                        <label class="form-check-label fw-semibold" for="allowNegativeInventoryShow">Permite Inventario Negativo</label>
                                                    </div>
                                                    <small class="text-muted text-end"><i class="ri-information-line"></i> Indica si este tipo documento permite registrar inventario negativo.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12 d-flex align-items-center mb-2 mt-4">
                                        <label class="form-check-label me-3" for="statusShow">Estado</label>
                                        <div class="form-check form-switch form-switch-md" dir="ltr">
                                            <input :checked="documentType.status" type="checkbox" class="form-check-input" id="statusShow" disabled>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="button" class="btn btn-light me-2" @click="$inertia.visit('/tipos-documentos')">
                                        Volver
                                    </button>
                                    <a :href="`/document-types/${documentType.uuid}/edit`" class="btn btn-primary px-4">
                                        Editar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card">
                            <div class="card-header">
                                <div class="form-section-title">
                                    <h6 class="mb-1">Numeración</h6>
                                    <p class="text-muted mb-0">Configuración aplicada al tipo documento.</p>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <div class="form-check px-0 d-inline-flex align-items-center m-0">
                                                    <input :checked="documentType.hasPrefix" class="form-check-input ms-1 me-2" type="checkbox" id="hasPrefixShow" disabled>
                                                    <label class="form-check-label mb-0" for="hasPrefixShow">Prefijo</label>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-check px-0 d-inline-flex align-items-center m-0">
                                                    <input :checked="documentType.hasDate" class="form-check-input ms-1 me-2" type="checkbox" id="hasDateShow" disabled>
                                                    <label class="form-check-label mb-0" for="hasDateShow">Fecha</label>
                                                </div>
                                            </div>
                                            <div v-if="hasDateEnabled" class="col-12 mt-3">
                                                <div class="numbering-date-select">
                                                    <label class="form-label mb-1">Formato fecha</label>
                                                    <p class="text-muted">{{ documentType.dateFormatDescription || '' }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-3">
                                            <label class="form-label">Longitud Consecutivo</label>
                                            <p class="text-muted">{{ documentType.lengthSequenceDescription || '' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="card">
                    <div class="card-body text-center py-5">
                        <p class="text-danger mb-0">No se encontró información del tipo documento.</p>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>

<style scoped>
.form-section-title h6 {
    font-size: 0.95rem;
    font-weight: 600;
}

.check-card {
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: center;
    border: 1px solid #e9ebec;
    border-radius: 0.5rem;
    padding: 0.85rem 1rem;
    background-color: #fafbfc;
}

.check-info-row {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.check-info-row small {
    max-width: 56%;
    line-height: 1.2;
}

.numbering-date-select {
    min-width: 62%;
}


</style>
