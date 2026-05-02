<script>
import Layout from '@/Layouts/main.vue';
import PageHeader from '@/Components/page-header.vue';
import DataTable from '@/Components/DataTable.vue';
import DocumentCreateModal from '@/Pages/Documents/Components/DocumentCreateModal.vue';
import { router } from '@inertiajs/vue3';
import { useFetchPetition } from '@/Composables/useFetchPetition.js';
import { useAlert } from '@/Composables/useSweetAlert.js';
import { useDateFormatter } from '@/Composables/useDateFormatter.js';

const { fetchPetition } = useFetchPetition();
const { showAlert } = useAlert();
const { convertDateFormat } = useDateFormatter();

export default {
    name: 'DocumentsIndex',
    components: {
        Layout,
        PageHeader,
        DataTable,
        DocumentCreateModal,
    },
    props: {
        items: {
            type: Array,
            default: () => [],
        },
        documentType: {
            type: Object,
            default: () => null,
        },
        documentTypes: {
            type: Array,
            default: () => [],
        },
        warehouses: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            searchQuery: '',
            tableHeaders: [
                { label: 'Código', key: 'code' },
                { label: 'Nombre', key: 'name' },
                { label: 'Estado', key: 'status' },
                { label: 'Acciones', key: 'actions' },
            ],
            documents: [],

            showModalDocumentDraftHeader: false,
        };
    },
    methods: {
        openModalDocumentDraftHeader() {
            this.showModalDocumentDraftHeader = true;
        },
        closeModalDocumentDraftHeader() {
            this.showModalDocumentDraftHeader = false;
        },
        async createDocumentDraftHeader(form) {
            try {
                const body = {
                    ...form,
                    date: convertDateFormat(form.date),
                };
                const response = await fetchPetition('/documents/draft-header', {
                    method: 'POST',
                    body: body,
                });

                if (!response.ok) {
                    showAlert('error', 'Error', response.data?.message || 'Ocurrió un error al continuar con el documento', 2500);
                    return;
                }

                this.closeModalDocumentDraftHeader();
                const selectedDocumentType = this.documentTypes.find((item) => Number(item.id) === Number(form.documentTypeId));
                const prefix = selectedDocumentType?.prefix || this.documentType?.prefix;
                if (!prefix) {
                    showAlert('error', 'Error', 'No se encontró el tipo documento', 2500);
                    return;
                }
                router.visit(prefix ? route('documents.viewCreate', { prefix }) : '');
            } catch (error) {
                showAlert('error', 'Error inesperado', 'Ocurrió un error al continuar con el documento', 2500);
            }
        },
    },
    computed: {
    },
    mounted() {
    },
};
</script>

<template>
    <Layout>
        <PageHeader :title="documentType.name || 'Documentos'" pageTitle="Documentos" />
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <div class="row g-4 align-items-center">
                            <div class="col-sm">
                                <div>
                                    <h5 class="card-title mb-0">Listado {{ documentType.name || 'Documentos' }}</h5>
                                </div>
                            </div>
                            <div class="col-sm-auto">
                                <div class="d-flex flex-wrap align-items-start gap-2">
                                    <a class="btn btn-success add-btn" @click="openModalDocumentDraftHeader">
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
                                    <input type="text" class="form-control" placeholder="Buscar..." v-model.trim="searchQuery">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div>
                            <DataTable
                                id="table_documents"
                                :headers="tableHeaders"
                                :items="documents"
                                :page-length="10"
                                order-by="code"
                            >
                                <template #cell-actions="{ item }">
                                    <ul class="list-inline hstack gap-2 mb-0">
                                        <li class="list-inline-item view">
                                            <a class="text-primary" title="Ver">
                                                <i class="ri-eye-fill fs-16"></i>
                                            </a>
                                        </li>
                                        <li class="list-inline-item edit">
                                            <a  class="text-primary" title="Editar">
                                                <i class="ri-pencil-fill fs-16"></i>
                                            </a>
                                        </li>
                                        <li class="list-inline-item">
                                            <a class="text-danger" style="cursor: pointer;" title="Eliminar" >
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

        <DocumentCreateModal
            v-model="showModalDocumentDraftHeader"
            :document-types="documentTypes"
            :document-type="documentType"
            :warehouses="warehouses"
            @submit="createDocumentDraftHeader"
        />
    </Layout>
</template>