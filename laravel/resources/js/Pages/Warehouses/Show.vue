<script>
import { ref, onMounted } from 'vue'
import Layout from "@/Layouts/main.vue"
import PageHeader from "@/Components/page-header.vue"
import { useFetchPetition } from '@/Composables/useFetchPetition.js';
import { useAlert } from '@/Composables/useSweetAlert.js';
const { fetchPetition } = useFetchPetition();
const { showAlert } = useAlert();

export default {
    name: 'WarehousesShow',
    components: {
        Layout,
        PageHeader
    },
    props: {
        uuid: {
            type: String,
            required: true
        },
        warehouseShow: {
            type: Object,
            required: false
        },
    },
    data() {
        return {
            warehouse: {},
            estado: 'Inactivo',
        }
    },
    mounted() {
        this.warehouse = { ...this.warehouseShow };
    },
    methods: {

        getStatusText(status) {
            const texts = {
                1: 'Activo',
                0: 'Inactivo'
            };
            return texts[status] || 'Desconocido';
        },
        getStatusClass(status) {
            const classes = {
                1: 'badge bg-success-subtle text-success',
                0: 'badge bg-danger-subtle text-danger'
            };
            return classes[status] || 'bg-secondary-subtle';
        },

        async loadWarehouse() {
            this.loading = true;
            try {
                const response = await fetchPetition(`/warehouses/${this.uuid}`, {
                    method: 'GET'
                });
                console.log('Response fetchPetition:', response);
                if (response.ok == true) {
                    this.warehouse = response.data?.data || {};
                } else {
                    console.warn('Error al cargar bodegas:', response.status, response.data);
                    showAlert('error', 'Error', 'Error al cargar los datos de bodegas', 2000);
                }

            } catch (error) {
                console.error('Error:', error);
                showAlert('error', 'Error inesperado', 'Ocurrió un error al cargar los datos de bodegas', 2000);
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>

<template>
    <Layout>
        <PageHeader title="Detalle Bodega" pageTitle="Bodegas" />

        <div class="row">
            <div class="col-lg-12">
                <div class="card" v-if="warehouseShow">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Bodega</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Código</label>
                                <p class="text-muted">{{ warehouseShow.code }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Nombre</label>
                                <p class="text-muted">{{ warehouseShow.name }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Responsable</label>
                                <p class="text-muted">{{ warehouseShow.manager_name }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Sucursal</label>
                                <p class="text-muted">{{ warehouseShow.branch_name }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Dirección</label>
                                <p class="text-muted">{{ warehouseShow.address }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Estado</label>
                                <div>
                                    <span class="badge" :class="getStatusClass(warehouseShow.status)">
                                        {{ getStatusText(warehouseShow.status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 text-end">
                                <button type="button" class="btn btn-light me-2" @click="$inertia.visit('/bodegas')">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-5">
                    <div>
                        <p class="text-danger">No se encontraron datos de la bodega.</p>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>