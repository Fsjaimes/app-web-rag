<script>
import { router } from '@inertiajs/vue3'
import Layout from "@/Layouts/main.vue"
import PageHeader from "@/Components/page-header.vue"
import { useFetchPetition } from '@/Composables/useFetchPetition.js';
import { useAlert } from '@/Composables/useSweetAlert.js';
import Select2 from '@/Components/Select2.vue';
import AddressModal from '@/Components/AddressModal.vue';
const { fetchPetition } = useFetchPetition();
const { showAlert, showConfirm } = useAlert();

export default {
    name: 'WarehouseEdit',
    components: {
        Layout,
        PageHeader,
        Select2,
        AddressModal
    },
    props: {
        uuid: {
            type: String,
            required: true
        },
        warehouseEdit: {
            type: Object,
            required: false
        },
        Managers: {
            type: Array,
            default: () => []
        },
        branches: {
            type: Array,
            default: () => []
        },
        address: {
            type: Object,
            default: null
        }
    },
    data() {
        return {
            warehouse: {},
            loading: false,
            alert: null, //Espacio para guardar el composable
            showAddressModal: false,
            clientErrors: {},
            codeExists: false,
            initialCode: '',
        }
    },
    mounted() {
        this.warehouse = { ...this.warehouseEdit};
        this.warehouse.status = this.warehouse.status == '1' ? true : false;
        this.warehouse.address_data = this.address;
        this.warehouse.address = this.address?.full_address || this.warehouse.address || '';
        this.initialCode = this.warehouse.code || '';
    },
    methods: {
        clearClientError(field) {
            if (!(field in this.clientErrors)) return;
            const next = { ...this.clientErrors };
            delete next[field];
            this.clientErrors = next;
        },
        handleCodeInput() {
            this.codeExists = false;
            this.clearClientError('code');
        },
        async checkCodeExists(code) {
            if (!code || code.trim() === '') return false;
            const response = await fetchPetition(`/warehouses/exists-by-code/${encodeURIComponent(code.trim())}`);
            if (response.ok) {
                return Boolean(response.data?.exists);
            }
            return false;
        },
        async submitForm() {
            this.loading = true;
            try {
                this.clientErrors = {};
                const normalizedCode = (this.warehouse.code || '').trim();
                const normalizedInitialCode = (this.initialCode || '').trim();
                const isCodeChanged = normalizedCode !== normalizedInitialCode;
                const codeExists = isCodeChanged ? await this.checkCodeExists(this.warehouse.code) : false;
                this.codeExists = codeExists;

                if (codeExists) {
                    this.clientErrors.code = true;
                    this.loading = false;
                    return;
                }

                const confirmed = await showConfirm(
                    'warning',                // icon
                    '¡Alerta!',           // title
                    '¿Está seguro que desea actualizar la bodega?', // text
                    'Sí, actualizar'               // confirmButtonText
                );
                // Si el usuario cancela, detenemos el proceso
                if (!confirmed) {
                    this.loading = false;
                    return;
                }

                const body = {
                    ...this.warehouse,
                    status: this.warehouse.status ? '1' : '0',
                };
                const response = await fetchPetition(`/warehouses/${this.uuid}`, {
                    method: 'PUT',
                    body,
                });

                if (response.ok) {
                    showAlert('success', 'Éxito', 'Bodega actualizada correctamente', 2000);
                    router.visit('/bodegas');
                } else {
                    console.warn('Error en la respuesta:', response);
                    showAlert('error', 'Error', response.data?.message || 'Error al actualizar la bodega', 2000);
                }

            } catch (error) {
                console.error('Error:', error);
                showAlert('error', 'Error inesperado', 'Ocurrió un error al actualizar la bodega', 2000);
            } finally {
                this.loading = false;
            }
        },
        handleAddressConfirm(address) {
            this.warehouse.address = address.full_address || '';
            this.warehouse.address_data = address;
            this.showAddressModal = false;
        }
    }
}
</script>

<template>
    <Layout>
        <PageHeader title="Editar Bodega" pageTitle="Bodegas" />
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Bodega</h5>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submitForm">
                            <div class="row g-3 mb-3">
                                <div class="col-6" data-form-error-anchor="code">
                                    <label for="code" class="form-label">Código<span class="text-danger">*</span></label>
                                    <input v-model="warehouse.code" type="text" class="form-control" id="code" :class="{ 'form-control--validation-error': clientErrors.code }" @input="handleCodeInput" maxlength="10" placeholder="Ingrese Código">
                                    <span v-if="codeExists" class="text-danger">El código ya existe.</span>
                                </div>
                                <div class="col-6" data-form-error-anchor="name">
                                    <label for="name" class="form-label">Nombre<span class="text-danger">*</span></label>
                                    <input v-model="warehouse.name" type="text" class="form-control" id="name" :class="{ 'form-control--validation-error': clientErrors.name }" @input="clearClientError('name')" maxlength="150" placeholder="Ingrese Nombre">
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label for="manager_id" class="form-label">Responsable</label>
                                    <Select2
                                        v-model="warehouse.manager_id"
                                        :options="Managers"
                                        value-field="id"
                                        text-field="description"
                                        placeholder="Seleccione..."
                                    />
                                </div>
                                <div class="col-6">
                                    <label for="branch_id" class="form-label">Sucursal</label>
                                    <Select2
                                        v-model="warehouse.branch_id"
                                        :options="branches"
                                        value-field="id"
                                        text-field="description"
                                        placeholder="Seleccione..."
                                    />
                                </div>
                            </div>
                            <div class="row g-3"> 
                                <div class="col-12">
                                    <AddressModal
                                        v-model="showAddressModal"
                                        @confirm="handleAddressConfirm"
                                        :initial-value="warehouse.address_data"
                                    />
                                </div>
                            </div>
                                <!-- Estado -->
                            <div class="col-md-12">
                                <label class="form-check-label mt-3" for="customSwitchsizesm">Estado</label>
                                <div class="form-check form-switch form-switch-md" dir="ltr">
                                    <input
                                        v-model="warehouse.status"
                                        type="checkbox"
                                        class="form-check-input"
                                        id="customSwitchsizesm"
                                    >
                                </div>
                            </div>
                            <!-- Botones -->
                            <div class="mt-4 text-end">
                                <button type="button" class="btn btn-light me-2" @click="$inertia.visit('/bodegas')">
                                    Cancelar
                                </button>
                                <button type="submit" class="btn btn-primary" :disabled="loading">
                                    <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    {{ loading ? 'Actualizando...' : 'Actualizar' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>

<style scoped>
    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
    }
</style>