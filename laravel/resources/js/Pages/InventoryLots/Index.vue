<script>
import { router } from '@inertiajs/vue3'
import Layout from "@/Layouts/main.vue"
//Componentes
import PageHeader from "@/Components/page-header.vue"
import DataTable from '@/Components/DataTable.vue'
//Composables
import { useDateFormatter } from '@/Composables/useDateFormatter.js';
import { useFetchPetition } from '@/Composables/useFetchPetition.js';
import { useAlert } from '@/Composables/useSweetAlert.js';
const { showAlert, showConfirm } = useAlert();
const { fetchPetition } = useFetchPetition();
const { formatMonthYear } = useDateFormatter();
export default {
    name: 'InventoryLotsIndex',
    components: {
        Layout,
        PageHeader,
        DataTable,
    },
    data() {
        return {
            id: '',
            searchQuery: '',
            tableHeaders: [
                { label: 'Número Lote', key: 'lot_number' },
                { label: 'Producto', key: 'product_name' },
                { label: 'Fecha Fabricación', key: 'manufacture_date' },
                { label: 'Fecha Vencimiento', key: 'expiration_date' },
                { label: 'Estado', key: 'status' },
                { label: 'Acciones', key: 'actions' },
            ],
        }
    },
    props: {
        inventoryLots: {
            type: Object,
            required: true,
            default: () => ({ data: [], meta: { total: 0 } })
        },
        inventoryLot: {
            type: Object,
            required: false,
        },
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

        async deleteInventoryLot(id) {
            try {
                const confirmed = await showConfirm(
                    'warning',
                    '¡Alerta!',
                    '¿Está seguro que desea eliminar el lote de inventario?',
                    'Sí, eliminar'
                );
                if (!confirmed) return;

                const response = await fetchPetition(`/inventory-lots/${id}`, {
                    method: 'DELETE'
                });
                if (response.ok) {
                    showAlert('success', '¡Éxito!', 'El lote de inventario ha sido eliminado correctamente', 1500);
                    router.visit('/lotes');
                } else {
                    showAlert('error', 'Error', 'Error al eliminar el lote de inventario', 1500);
                }
            } catch (error) {
                showAlert('error', 'Error inesperado', 'Ocurrió un problema al eliminar el lote de inventario', 1500);
            }
        },
    }
}
</script>

<template>
    <Layout>
        <PageHeader title="Lotes Inventario" pageTitle="Inventarios" />
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <div class="row g-4 align-items-center">
                            <div class="col-sm">
                                <div>
                                    <h5 class="card-title mb-0">Lotes Inventario</h5>
                                </div>
                            </div>
                            <div class="col-sm-auto">
                                <div class="d-flex flex-wrap align-items-start gap-2">
                                    <a :href= "route('inventoryLots.viewCreate')" class="btn btn-success add-btn">
                                        <i class="ri-add-line align-bottom me-1"></i> Nuevo 
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body border-bottom-dashed border-bottom">
                        <div class="row g-3">
                            <div class="col-xl-12">
                                <div class="">
                                    <input type="text" class="form-control" placeholder="Buscar..." v-model="searchQuery">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div>
                            <DataTable
                                id="table_inventoryLots"
                                :headers="tableHeaders"
                                :items="inventoryLots.data"
                                :page-length="10"
                                order-by="lot_number"
                            >
                                <template #cell-status="{ item }">
                                    <span class="badge" :class="getStatusClass(item.status)">
                                        {{ getStatusText(item.status) }}
                                    </span>
                                </template>
                                <template #cell-actions="{ item }">
                                    <ul class="list-inline hstack gap-2 mb-0">
                                        <li class="list-inline-item">
                                            <a :href="`/inventory-lots/${item.uuid}/show`" class="text-primary" title="Ver">
                                                <i class="ri-eye-fill fs-16"></i>
                                            </a>
                                        </li>
                                        <li class="list-inline-item edit">
                                            <a :href="`/inventory-lots/${item.uuid}/edit`" class="text-primary" title="Editar">
                                                <i class="ri-pencil-fill fs-16"></i>
                                            </a>
                                        </li>
                                        <li class="list-inline-item">
                                            <a class="text-danger" style="cursor: pointer;" title="Eliminar" @click="deleteInventoryLot(item.id)">
                                                <i class="ri-delete-bin-5-fill fs-16"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </template>
                            </DataTable>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>

<style scoped>
    .search-box {
        position: relative;
    }

    .search-icon {
        position: absolute;
        top: 50%;
        right: 12px;
        transform: translateY(-50%);
        color: #74788d;
    }

    .fs-15 {
        font-size: 0.9375rem;
    }
</style>