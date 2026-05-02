<script>
import { router } from '@inertiajs/vue3'
import Layout from "@/Layouts/main.vue"
import PageHeader from "@/Components/page-header.vue"
import { useFetchPetition } from '@/Composables/useFetchPetition.js';
import { useAlert } from '@/Composables/useSweetAlert.js';
import Select2 from '@/Components/Select2.vue';
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { Spanish } from "flatpickr/dist/l10n/es.js";  // Importa la localización en español
import { useFormErrorFocus } from '@/Composables/useFormErrorFocus.js';
const { focusFirstFormError } = useFormErrorFocus();
const { fetchPetition } = useFetchPetition();
const { showAlert, showConfirm } = useAlert();

export default {
    name: 'InventoryLotsEdit',
    components: {
        Layout,
        PageHeader,
        Select2,
        flatPickr
    },
    props: {
        uuid: {
            type: String,
            required: true
        },
        inventoryLotEdit: {
            type: Object,
            required: false
        },
        products: {
            type: Array,
            required: true,
        },
    },
    data() {
        return {
            inventoryLot: {},
            loading: false,
            alert: null, //Espacio para guardar el composable
            config: {
                altFormat: "d/m/Y",
                altInput: true,
                dateFormat: "Y-m-d",
                locale: Spanish,
            },
            clientErrors: {},
        }
    },
    mounted() {
        if (this.inventoryLotEdit) {
            this.inventoryLot = {
                ...this.inventoryLotEdit,
                status: this.isActiveStatus(this.inventoryLotEdit.status),
            };
        }
    },
    methods: {
        normalizeDateValue(value) {
            if (!value) return null;
            const raw = value instanceof Date
                ? value.toISOString().slice(0, 10)
                : String(value).slice(0, 10);
            const [year, month, day] = raw.split('-').map(Number);
            if (!year || !month || !day) return null;
            return Date.UTC(year, month - 1, day);
        },
        dateValidation(date1, date2) {
            if (!date1 || !date2) return false;
            const d1 = this.normalizeDateValue(date1);
            const d2 = this.normalizeDateValue(date2);
            if (d1 === null || d2 === null) return false;
            return d1 >= d2;
        },
        collectInventoryLotErrors() {
            const e = {};
            const f = this.inventoryLot;

            if (!f.product_id) e.product_id = true;
            if (!f.lot_number || f.lot_number.trim() === '') e.lot_number = true;

            return e;
        },
        hasDateRangeError() {
            const f = this.inventoryLot;
            return this.dateValidation(f.manufacture_date, f.expiration_date);
        },
        clearClientError(field) {
            if (!(field in this.clientErrors)) return;
            const next = { ...this.clientErrors };
            delete next[field];
            this.clientErrors = next;
        },
        isActiveStatus(status) {
            return status === true || status === 1 || status === '1';
        },

        async submitForm() {
            this.clientErrors = {};
            this.loading = true;
            try {
                const errors = this.collectInventoryLotErrors();
                if (Object.keys(errors).length > 0) {
                    this.clientErrors = errors;
                    await this.$nextTick();
                    focusFirstFormError(this.$refs.formRef, errors);
                    await showAlert(
                        'warning',
                        '¡Alerta!',
                        'Campos sin diligenciar. Revise los campos resaltados.',
                        2500
                    );
                    this.loading = false;
                    return;
                }

                if (this.hasDateRangeError()) {
                    this.clientErrors = { expiration_date: true };
                    this.loading = false;
                    return;
                }

                const confirmed = await showConfirm(
                    'warning',                // icon
                    '¡Alerta!',           // title
                    '¿Está seguro que desea actualizar el lote de inventario?', // text
                    'Sí, actualizar'               // confirmButtonText
                );
                // Si el usuario cancela, detenemos el proceso
                if (!confirmed) {
                    this.loading = false;
                    return;
                }

                if (!this.inventoryLot.product_id) {
                    showAlert('error', 'Error', 'El producto es requerido', 2000);
                    this.loading = false;
                    return;
                }

                const body = {
                    ...this.inventoryLot,
                    status: this.inventoryLot.status ? '1' : '0',
                };
                const response = await fetchPetition(`/inventory-lots/${this.uuid}`, {
                    method: 'PUT',
                    body,
                });

                if (response.ok) {
                    showAlert('success', 'Éxito', 'Lote de inventario actualizado correctamente', 2000);
                    router.visit('/lotes');
                } else {
                    console.warn('Error en la respuesta:', response);
                    showAlert('error', 'Error', response.data?.message || 'Error al actualizar el lote de inventario', 2000);
                }

            } catch (error) {
                console.error('Error:', error);
                showAlert('error', 'Error inesperado', 'Ocurrió un error al actualizar el lote de inventario', 2000);
            } finally {
                this.loading = false;
            }
        },
    }
}
</script>

<template>
    <Layout>
        <PageHeader title="Editar Lote Inventario" pageTitle="Inventarios" />
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Lote Inventario</h5>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submitForm">
                            <div class="row g-3 mb-3">
                                <div class="col-6" data-form-error-anchor="product_id">
                                    <label for="product_id" class="form-label">Producto<span class="text-danger">*</span></label>
                                    <Select2
                                        v-model="inventoryLot.product_id"
                                        :options="products"
                                        value-field="id"
                                        text-field="description"
                                        placeholder="Seleccione..."
                                        :show-validation-error="clientErrors.product_id"
                                        @change="clearClientError('product_id')"
                                    />
                                </div>
                                <div class="col-6" data-form-error-anchor="lot_number">
                                    <label for="lot_number" class="form-label">Número Lote<span class="text-danger">*</span></label>
                                    <input v-model="inventoryLot.lot_number" type="text" class="form-control" id="lot_number" maxlength="150" placeholder="Ingrese Número de Lote" :class="{ 'form-control--validation-error': clientErrors.lot_number }" @input="clearClientError('lot_number')">
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label for="manufacture_date" class="form-label">Fecha Fabricación</label>
                                    <flat-pickr placeholder="Seleccione..." v-model="inventoryLot.manufacture_date" :config="config" class="form-control flatpickr-input"></flat-pickr>
                                </div>
                                <div
                                    class="col-6"
                                    data-form-error-anchor="expiration_date"
                                    :class="{ 'flatpickr-error': clientErrors.expiration_date }"
                                >
                                    <label for="expiration_date" class="form-label">Fecha Vencimiento</label>
                                    <flat-pickr placeholder="Seleccione..." v-model="inventoryLot.expiration_date" :config="config" class="form-control flatpickr-input" :class="{ 'form-control--validation-error': clientErrors.expiration_date }" @input="clearClientError('expiration_date')"></flat-pickr>
                                    <span v-if="clientErrors.expiration_date" class="text-danger">
                                        La fecha de vencimiento debe ser mayor a la fecha de fabricación.
                                    </span>
                                </div>
                            </div>
                                <!-- Estado -->
                            <div class="col-md-12">
                                <label class="form-check-label mt-3" for="customSwitchsizesm">Estado</label>
                                <div class="form-check form-switch form-switch-md" dir="ltr">
                                    <input
                                        v-model="inventoryLot.status"
                                        type="checkbox"
                                        class="form-check-input"
                                        id="customSwitchsizesm"
                                    >
                                </div>
                            </div>
                            <!-- Botones -->
                            <div class="mt-4 text-end">
                                <button type="button" class="btn btn-light me-2" @click="$inertia.visit('/lotes')">
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

    .flatpickr-error :deep(input.flatpickr-input) {
        border-color: #f06548 !important;
    }

    .flatpickr-error :deep(input.flatpickr-input:focus) {
        border-color: #f06548 !important;
        box-shadow: 0 0 0 0.15rem rgba(240, 101, 72, 0.25) !important;
    }
</style>