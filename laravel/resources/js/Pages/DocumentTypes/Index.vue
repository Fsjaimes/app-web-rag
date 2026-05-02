<script>
import { router } from '@inertiajs/vue3'
import Layout from "@/Layouts/main.vue"
//Componentes
import PageHeader from "@/Components/page-header.vue"
import DataTable from '@/Components/DataTable.vue'
//Composables
import { useFetchPetition } from '@/Composables/useFetchPetition.js'
import { useAlert } from '@/Composables/useSweetAlert.js'

const { showAlert, showConfirm } = useAlert()
const { fetchPetition } = useFetchPetition()
export default {
    name: 'DocumentTypesIndex',
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
                { label: 'Prefijo', key: 'prefix', highlight: true },
                { label: 'Nombre', key: 'name' },
                { label: 'Estado', key: 'status' },
                { label: 'Acciones', key: 'actions' },
            ],
        }
    },
    computed: {
        filteredDocumentTypes() {
            const rows = this.documentTypes?.data ?? []
            const raw = (this.searchQuery || '').trim()
            if (!raw) return rows
            const q = raw.toLowerCase()
            const keys = this.tableHeaders
                .map((h) => h.key)
                .filter((key) => key && key !== 'actions')
            return rows.filter((item) =>
                keys.some((key) => {
                    const text =
                        key === 'status'
                            ? this.getStatusText(item.status)
                            : item[key] != null && item[key] !== ''
                              ? String(item[key])
                              : ''
                    return text.toLowerCase().includes(q)
                })
            )
        },
    },
    props: {
        documentTypes: {
            type: Object,
            required: true,
            default: () => ({ data: [], meta: { total: 0 } })
        },
        // documentType: {
        //     type: Object,
        //     required: false,
        // },
    },
    methods: {
        getStatusText(status) {
            if (typeof status === 'string' && status.trim() !== '') return status
            const texts = { 1: 'Activo', 0: 'Inactivo' }
            return texts[status] || 'Desconocido'
        },
        getStatusClass(status) {
            if (typeof status === 'string') {
                const normalized = status.toLowerCase()
                if (normalized === 'activo') return 'badge bg-success-subtle text-success'
                if (normalized === 'inactivo') return 'badge bg-danger-subtle text-danger'
            }

            const statusId = Number(status)

            const classes = {
                1: 'badge bg-success-subtle text-success',
                0: 'badge bg-danger-subtle text-danger'
            };
            return classes[statusId] || 'bg-secondary-subtle';
        },
        async deleteDocumentType(uuid) {
            try {
                const confirmed = await showConfirm(
                    'warning',
                    '¡Alerta!',
                    '¿Está seguro que desea eliminar este tipo documento?',
                    'Sí, eliminar'
                )

                if (!confirmed) return

                const response = await fetchPetition(`/document-types/${uuid}`, {
                    method: 'DELETE',
                })

                if (response.ok) {
                    showAlert('success', '¡Éxito!', 'Tipo documento eliminado correctamente', 1500)
                    router.visit('/tipos-documentos')
                    return
                }

                showAlert('error', 'Error', 'No fue posible eliminar el tipo documento', 1500)
            } catch (error) {
                showAlert('error', 'Error inesperado', 'Ocurrió un problema al eliminar el tipo documento', 1500)
            }
        },
    }
}
</script>

<template>
    <Layout>
        <PageHeader title="Tipos Documentos" pageTitle="Configuración" />
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <div class="row g-4 align-items-center">
                            <div class="col-sm">
                                <div>
                                    <h5 class="card-title mb-0">Listado Tipos Documentos</h5>
                                </div>
                            </div>
                            <div class="col-sm-auto">
                                <div class="d-flex flex-wrap align-items-start gap-2">
                                    <a class="btn btn-success add-btn" :href="route('document-types.viewCreate')">
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
                                id="table_documentTypes"
                                :headers="tableHeaders"
                                :items="filteredDocumentTypes"
                                :page-length="10"
                                order-by="name"
                            >
                                <template #cell-prefix="{ item }">
                                    <a :href="`/document-types/${item.uuid}/show`" class="fw-bold text-primary text-uppercase" style="cursor: pointer;">{{ item.prefix || '' }}</a>
                                </template>
                                <template #cell-status="{ item }">
                                    <span class="badge" :class="getStatusClass(item.status)">
                                        {{ getStatusText(item.status) }}
                                    </span>
                                </template>
                                <template #cell-actions="{ item }">
                                    <ul class="list-inline hstack gap-2 mb-0">
                                        <li class="list-inline-item view">
                                            <a :href="`/document-types/${item.uuid}/show`" class="text-primary" title="Ver">
                                                <i class="ri-eye-fill fs-16"></i>
                                            </a>
                                        </li>
                                        <li class="list-inline-item edit">
                                            <a :href="`/document-types/${item.uuid}/edit`" class="text-primary" title="Editar">
                                                <i class="ri-pencil-fill fs-16"></i>
                                            </a>
                                        </li>
                                        <li class="list-inline-item">
                                            <a class="text-danger" style="cursor: pointer;" title="Eliminar" @click="deleteDocumentType(item.uuid)">
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