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
    const { showAlert, showConfirm, showWarning } = useAlert();

    export default {
        name: 'InventoryLotsCreate',
        components: {
            Layout,
            PageHeader,
            Select2,
            flatPickr
        },
        props: {
            products: {
                type: Array,
                required: true,
            },
        },
            data() {
                return {
                form: {
                    product_id: '',
                    lot_number: '',
                    manufacture_date: null,
                    expiration_date: null,
                    status: true,
                },
                loading: false,
                config: {
                    altFormat: "d/m/Y",
                    altInput: true,
                    dateFormat: "Y-m-d",
                    locale: Spanish,
                },
                clientErrors: {},
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
            hasDateRangeError() {
                const f = this.form;
                if (!f.manufacture_date || !f.expiration_date) return false;
                return this.dateValidation(f.manufacture_date, f.expiration_date);
            },
            collectInventoryLotErrors() {
                const e = {};
                const f = this.form;

                if (!f.product_id) e.product_id = true;
                if (!f.lot_number || f.lot_number.trim() === '') e.lot_number = true;
                return e;
            },
            clearClientError(field) {
                if (!(field in this.clientErrors)) return;
                const next = { ...this.clientErrors };
                delete next[field];
                this.clientErrors = next;
            },
            messagesFromApiPayload(data) {
                if (!data) return [];
                if (data.errors && typeof data.errors === 'object') {
                    return Object.values(data.errors).flat().filter(Boolean);
                }
                if (typeof data.message === 'string' && data.message.trim() !== '') {
                    return [data.message];
                }
                return [];
            },
            formatErrorsAlertText(messages) {
                return messages.map((m) => `• ${m}`).join('\n');
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
                        '¿Está seguro que desea crear un lote de inventario?', // text
                        'Sí, crear'               // confirmButtonText
                    );
                    // Si el usuario cancela, detenemos el proceso
                    if (!confirmed) {
                        this.loading = false;
                        return;
                    }
                    const body = {
                        ...this.form,
                        status: this.form.status ? '1' : '0',
                    };
                    const response = await fetchPetition('/inventory-lots', {
                        method: 'POST',
                        body,
                    });
                    if (response.ok) {
                        showAlert('success', '¡Éxito!', 'Lote de inventario creado correctamente', '', 1500);
                        router.visit('/lotes');
                    } else {
                        const apiMessages = this.messagesFromApiPayload(response.data);
                        const text = apiMessages.length > 0
                            ? this.formatErrorsAlertText(apiMessages)
                            : 'Ocurrió un error al crear el lote de inventario.';
                        await showWarning('No se pudo crear el lote', text);
                    }
                } catch (error) {
                    const msg = error?.message ? String(error.message) : 'Error desconocido';
                    await showWarning('Error inesperado', this.formatErrorsAlertText([msg]));
                } finally {
                    this.loading = false;
                }
            },
        }
    }
</script>

<template>
    <Layout>
        <PageHeader title="Crear Lote Inventario" pageTitle="Inventarios" />
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Lote Inventario</h5>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submitForm" ref="formRef">
                            <div class="row g-3 mb-3">
                                <div class="col-6" data-form-error-anchor="product_id">
                                    <label for="product_id" class="form-label">Producto<span class="text-danger">*</span></label>
                                    <Select2
                                        v-model="form.product_id"
                                        :options="products"
                                        value-field="id"
                                        text-field="description"
                                        placeholder="Seleccione..."
                                        :show-validation-error="clientErrors.product_id"
                                        @change="clearClientError('product_id')"
                                    />
                                    <!-- <span v-if="errors.product_id" class="text-danger">{{ errors.product_id }}</span> -->
                                </div>
                                <div class="col-6" data-form-error-anchor="lot_number">
                                    <label for="lot_number" class="form-label">Número Lote<span class="text-danger">*</span></label>
                                    <input v-model="form.lot_number" type="text" class="form-control" id="lot_number" :class="{ 'form-control--validation-error': clientErrors.lot_number }" @input="clearClientError('lot_number')" maxlength="150" placeholder="Ingrese Número de Lote">
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label for="manufacture_date" class="form-label">Fecha Fabricación</label>
                                    <flat-pickr placeholder="Seleccione..." v-model="form.manufacture_date" :config="config" class="form-control flatpickr-input"></flat-pickr>
                                </div>
                                <div
                                    class="col-6"
                                    data-form-error-anchor="expiration_date"
                                    :class="{ 'flatpickr-error': clientErrors.expiration_date }"
                                >
                                    <label for="expiration_date" class="form-label">Fecha Vencimiento</label>
                                    <flat-pickr placeholder="Seleccione..." v-model="form.expiration_date" :config="config" class="form-control flatpickr-input" :class="{ 'form-control--validation-error': clientErrors.expiration_date }" @input="clearClientError('expiration_date')"></flat-pickr>
                                    <span v-if="clientErrors.expiration_date" class="text-danger">
                                        La fecha de vencimiento debe ser mayor a la fecha de fabricación.
                                    </span>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12 d-flex align-items-center">
                                    <label class="form-check-label me-3" for="customSwitchsizesm">Estado</label>
                                    <div class="form-check form-switch form-switch-md" dir="ltr">
                                        <input
                                            v-model="form.status"
                                            type="checkbox"
                                            class="form-check-input"
                                            id="customSwitchsizesm"
                                        >
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 text-end">
                                <button type="button" class="btn btn-light me-2" @click="$inertia.visit('/lotes')">
                                    Cancelar
                                </button>
                                <button type="submit" class="btn btn-primary" :disabled="loading">
                                    <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    {{ loading ? 'Creando...' : 'Guardar' }}
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