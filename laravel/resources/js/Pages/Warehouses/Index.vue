<script>
import { router } from '@inertiajs/vue3'
import Layout from "@/Layouts/main.vue"
//Componentes
import PageHeader from "@/Components/page-header.vue"
import DataTable from '@/Components/DataTable.vue'
//Composables
import { useFetchPetition } from '@/Composables/useFetchPetition.js';
import { useAlert } from '@/Composables/useSweetAlert.js';
const { showAlert, showConfirm } = useAlert();
const { fetchPetition } = useFetchPetition();

export default {
    name: 'WarehousesIndex',
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
                { label: 'Código', key: 'code' },
                { label: 'Nombre', key: 'name' },
                { label: 'Responsable', key: 'manager_name' },
                { label: 'Sucursal', key: 'branch' },
                { label: 'Estado', key: 'status' },
                { label: 'Acciones', key: 'actions' },
            ],
        }
    },
    props: {
        warehouses: {
            type: Object,
            required: true,
            default: () => ({ data: [], meta: { total: 0 } })
        },
        warehouse: {
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

        async deleteWarehouse(id) {
            try {
                const confirmed = await showConfirm(
                    'warning',
                    '¡Alerta!',
                    '¿Está seguro que desea eliminar la bodega?',
                    'Sí, eliminar'
                );
                if (!confirmed) return;

                const response = await fetchPetition(`/warehouses/${id}`, {
                    method: 'DELETE'
                });
                if (response.ok) {
                    showAlert('success', '¡Éxito!', 'La bodega ha sido eliminada correctamente', 1500);
                    router.visit('/bodegas');
                } else {
                    showAlert('error', 'Error', 'Error al eliminar la bodega', 1500);
                }
            } catch (error) {
                showAlert('error', 'Error inesperado', 'Ocurrió un problema al eliminar la bodega', 1500);
            }
        },
    }
}

</script>

<template>
    <Layout>
        <PageHeader title="Bodegas" pageTitle="Configuración" />
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <div class="row g-4 align-items-center">
                            <div class="col-sm">
                                <div>
                                    <h5 class="card-title mb-0">Bodegas</h5>
                                </div>
                            </div>
                            <div class="col-sm-auto">
                                <div class="d-flex flex-wrap align-items-start gap-2">
                                    <a :href= "route('warehouses.viewCreate')" class="btn btn-success add-btn">
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
                                id="table_warehouses"
                                :headers="tableHeaders"
                                :items="warehouses.data"
                                :page-length="10"
                                order-by="code"
                            >
                                <template #cell-status="{ item }">
                                    <span class="badge" :class="getStatusClass(item.status)">
                                        {{ getStatusText(item.status) }}
                                    </span>
                                </template>
                                <template #cell-actions="{ item }">
                                    <ul class="list-inline hstack gap-2 mb-0">
                                        <li class="list-inline-item">
                                            <a :href="`/warehouses/${item.uuid}/show`" class="text-primary" title="Ver">
                                                <i class="ri-eye-fill fs-16"></i>
                                            </a>
                                        </li>
                                        <li class="list-inline-item edit">
                                            <a :href="`/warehouses/${item.uuid}/edit`" class="text-primary" title="Editar">
                                                <i class="ri-pencil-fill fs-16"></i>
                                            </a>
                                        </li>
                                        <li class="list-inline-item">
                                            <a class="text-danger" style="cursor: pointer;" title="Eliminar" @click="deleteWarehouse(item.id)">
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