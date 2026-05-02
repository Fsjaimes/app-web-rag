<script>
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import DataTable from '@/Components/DataTable.vue';
import AccessDeniedCard from '@/Components/AccessDeniedCard.vue';
import { router } from '@inertiajs/vue3';
import { useFetchPetition } from '@/Composables/useFetchPetition.js';
import { useAlert } from '@/Composables/useSweetAlert.js';

const { fetchPetition } = useFetchPetition();
const { showAlert, showConfirm } = useAlert();

export default {
    name: 'ProductsIndex',
    components: {
        Layout,
        PageHeader,
        DataTable,
        AccessDeniedCard,
    },
    props: {
        products: {
            type: Array,
            required: true,
        },
        // canViewModule: {
        //     type: Boolean,
        //     required: true,
        // },
        // userPermissions: {
        //     type: Object,
        //     required: true,
        // },
        message: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            searchQuery: '',
            tableHeaders: [
                { label: '#', key: 'id' },
                { label: 'Código', key: 'code' },
                { label: 'Nombre', key: 'name' },
                { label: 'Estado', key: 'status' },
                { label: 'Acciones', key: 'actions' },
            ],
        };
    },
    mounted() {
        // console.log(this.products);
    },
    computed: {
        filteredProducts() {
            if (!this.searchQuery.trim()) return this.products;
            const q = this.searchQuery.toLowerCase().trim();
            return this.products.filter(product => {
                const name = (product.name || '').toLowerCase();
                const code = (product.code || '').toLowerCase();
                return name.includes(q) || code.includes(q);
            });
        },
    },
    methods: {
        async deleteProduct(id) {
            try {
                const confirmed = await showConfirm(
                    'warning',
                    '¡Alerta!',
                    '¿Está seguro que desea eliminar este producto?',
                    'Sí, eliminar'
                );
                if (!confirmed) return;
                const response = await fetchPetition(route('products.destroy', { id }), {
                    method: 'DELETE',
                });
                if (response.ok) {
                    showAlert('success', '¡Éxito!', 'Producto eliminado correctamente', 1500);
                    router.visit(route('productos.index'));
                } else {
                    showAlert('error', 'Error', 'Error al eliminar producto', 1500);
                }
            } catch (error) {
                showAlert('error', 'Error inesperado', 'Ocurrió un problema al eliminar el producto', 1500);
            }
        },
    },
};
</script>

<template>
    <Layout>
        <PageHeader title="Productos" pageTitle="Gestión" />
        <!-- <div v-if="!canViewModule" class="row justify-content-center">
            <div class="col-lg-6">
                <AccessDeniedCard :message="message" />
            </div>
        </div> -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <div class="row g-4 align-items-center">
                            <div class="col-sm">
                                <div>
                                    <h5 class="card-title mb-0">Listado Productos</h5>
                                </div>
                            </div>
                            <div class="col-sm-auto">
                                <div class="d-flex flex-wrap align-items-start gap-2">
                                    <a :href="route('products.viewCreate')" class="btn btn-success add-btn">
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
                                id="table_products"
                                :headers="tableHeaders"
                                :items="filteredProducts"
                                :page-length="10"
                                order-by="name"
                            >
                                <template #cell-code="{ item }">
                                    <a
                                        :href="route('products.viewShow', { uuid: item.uuid })"
                                        class="fw-medium text-primary"
                                        style="cursor: pointer; text-decoration: underline;"
                                        title="Ver producto"
                                    >
                                        {{ item.code }}
                                    </a>
                                </template>
                                <template #cell-status="{ item }">
                                    <span class="badge bg-success-subtle text-success" v-if="item.status == '1'">Activo</span>
                                    <span class="badge bg-danger-subtle text-danger" v-else>Inactivo</span>
                                </template>
                                <template #cell-actions="{ item }">
                                    <ul class="list-inline hstack gap-2 mb-0">
                                        <li class="list-inline-item">
                                            <a :href="route('products.viewShow', { uuid: item.uuid })" class="text-primary" title="Ver">
                                                <i class="ri-eye-fill fs-16"></i>
                                            </a>
                                        </li>
                                        <!-- <li v-if="userPermissions.edit" class="list-inline-item edit"> -->
                                        <li class="list-inline-item edit">
                                            <a :href="route('products.viewEdit', { uuid: item.uuid })" class="text-primary" title="Editar">
                                                <i class="ri-pencil-fill fs-16"></i>
                                            </a>
                                        </li>
                                        <!-- <li v-if="userPermissions.delete" class="list-inline-item"> -->
                                        <li class="list-inline-item">
                                            <a class="text-danger" style="cursor: pointer;" title="Eliminar" @click="deleteProduct(item.id)">
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
