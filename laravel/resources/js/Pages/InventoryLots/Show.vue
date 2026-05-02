<script>
import Layout from "@/Layouts/main.vue"
import PageHeader from "@/Components/page-header.vue"
import { useFetchPetition } from '@/Composables/useFetchPetition.js';
import { useAlert } from '@/Composables/useSweetAlert.js';
const { fetchPetition } = useFetchPetition();
const { showAlert } = useAlert();

export default {
    name: 'InventoryLotsShow',
    components: {
        Layout,
        PageHeader
    },
    props: {
        uuid: {
            type: String,
            required: true
        },
        inventoryLotShow: {
            type: Object,
            required: false
        }
    },
    data() {
        return {
            inventoryLot: {},
            estado: 'Inactivo',
        }
    },
    mounted() {
        this.inventoryLot = { ...this.inventoryLotShow };
    },
    methods: {
        normalizeStatus(status) {
            if (status === true || status === 1 || status === '1') {
                return 1;
            }
            if (status === false || status === 0 || status === '0') {
                return 0;
            }
            return null;
        },
        getStatusText(status) {
            const n = this.normalizeStatus(status);
            if (n === null) {
                return 'Desconocido';
            }
            return n === 1 ? 'Activo' : 'Inactivo';
        },
        getStatusClass(status) {
            const n = this.normalizeStatus(status);
            if (n === 1) {
                return 'badge bg-success-subtle text-success';
            }
            if (n === 0) {
                return 'badge bg-danger-subtle text-danger';
            }
            return 'badge bg-secondary-subtle text-secondary';
        },

        async loadInventoryLot() {
            this.loading = true;
            try {
                const response = await fetchPetition(`/inventory-lots/${this.uuid}`, {
                    method: 'GET'
                });
                if (response.ok == true) {
                    this.inventoryLot = response.data?.data || {};
                } else {
                    console.warn('Error al cargar lotes de inventario:', response.status, response.data);
                    showAlert('error', 'Error', 'Error al cargar los datos de lotes de inventario', 2000);
                }

            } catch (error) {
                console.error('Error:', error);
                showAlert('error', 'Error inesperado', 'Ocurrió un error al cargar los datos de lotes de inventario', 2000);
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>

<template>
    <Layout>
        <PageHeader title="Detalle Lote Inventario" pageTitle="Inventarios" />

        <div class="row">
            <div class="col-lg-12">
                <div class="card" v-if="inventoryLot">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Lote Inventario</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Producto</label>
                                <p class="text-muted">{{ inventoryLot.product_name }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Número Lote</label>
                                <p class="text-muted">{{ inventoryLot.lot_number }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Fecha Fabricación</label>
                                <p class="text-muted">{{ inventoryLot.manufacture_date }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Fecha Vencimiento</label>
                                <p class="text-muted">{{ inventoryLot.expiration_date }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Estado</label>
                                <div>
                                    <span class="badge" :class="getStatusClass(inventoryLot.status)">
                                        {{ getStatusText(inventoryLot.status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 text-end">
                                <button type="button" class="btn btn-light me-2" @click="$inertia.visit('/lotes')">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-5">
                    <div>
                        <p class="text-danger">No se encontraron datos del lote de inventario.</p>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>