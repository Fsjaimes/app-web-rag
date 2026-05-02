<script>
import Select2 from '@/Components/Select2.vue';
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { Spanish } from "flatpickr/dist/l10n/es.js";
import { useFormErrorFocus } from '@/Composables/useFormErrorFocus.js';
import { useAlert } from '@/Composables/useSweetAlert.js';
const { showAlert, showConfirm } = useAlert();
const { focusFirstFormError } = useFormErrorFocus();

export default {
    name: 'DocumentCreateModal',
    components: {
        Select2,
        flatPickr,
    },
    props: {
        modelValue: {
            type: Boolean,
            default: false,
        },
        documentTypes: {
            type: Array,
            default: () => [],
        },
        documentType: {
            type: Object,
            default: () => null,
        },
        warehouses: {
            type: Array,
            default: () => [],
        },
    },
    emits: ['update:modelValue', 'submit'],
    data() {
        return {
            form: {
                documentTypeId: null,
                date: null,
                warehouseId: null,
            },
            modalId: 'modalDocumentCreate',
            timeConfig: {
                enableTime: false,
                dateFormat: "d M, Y",
                locale: Spanish,
            },
            clientErrors: {},
        };
    },
    methods: {
        close() {
            this.$emit('update:modelValue', false);
        },
        collectDocumentCreateErrors() {
            const e = {};
            const f = this.form;
            if (!f.documentTypeId) e.documentTypeId = true;
            if (!f.date) e.date = true;
            if (!f.warehouseId) e.warehouseId = true;
            return e;
        },
        async submit() {
            try {
                this.clientErrors = {};
                const errors = this.collectDocumentCreateErrors();
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
                    return;
                }
                const confirmed = await showConfirm(
                    'warning',
                    '¡Alerta!',
                    '¿Está seguro que desea continuar?',
                    'Sí, continuar'
                );
                if (!confirmed) {
                    return;
                }

                this.$emit('submit', { ...this.form });
            } catch (error) {
                showAlert('error', 'Error inesperado', 'Ocurrió un error al continuar', 1500);
            }
        },
        clearClientError(field) {
            if (!(field in this.clientErrors)) return;
            const next = { ...this.clientErrors };
            delete next[field];
            this.clientErrors = next;
        },
    },
    mounted() {
        if (this.documentType) {
            this.form.documentTypeId = this.documentType.id || null;
        }
    },
};
</script>

<template>
    <div
        v-if="modelValue"
        :id="modalId"
        class="modal fade show d-block"
        tabindex="-1"
        style="background-color: rgba(0, 0, 0, 0.5);"
    >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header p-3 bg-light">
                    <h5 class="modal-title">Documento</h5>
                    <button type="button" class="btn-close" @click="close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Tipo Documento<span class="text-danger ms-1">*</span></label>
                            <Select2
                                v-model="form.documentTypeId"
                                :options="documentTypes"
                                :modal-id="modalId"
                                value-field="id"
                                text-field="name"
                                placeholder="Seleccione..."
                                :disabled="documentType ? true : false"
                                :show-validation-error="clientErrors.documentTypeId"
                                @change="clearClientError('documentTypeId')"
                            />
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Fecha<span class="text-danger ms-1">*</span></label>
                            <flat-pickr
                                v-model="form.date"
                                :config="timeConfig"
                                class="form-control flatpickr-input"
                                placeholder="Seleccione Fecha"
                                :class="{ 'form-control--validation-error': clientErrors.date }"
                                :show-validation-error="clientErrors.date"
                                @change="clearClientError('date')"
                            />
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Bodega<span class="text-danger ms-1">*</span></label>
                            <Select2
                                v-model="form.warehouseId"
                                :options="warehouses"
                                :modal-id="modalId"
                                value-field="id"
                                text-field="labelDescription"
                                placeholder="Seleccione..."
                                :show-validation-error="clientErrors.warehouseId"
                                @change="clearClientError('warehouseId')"
                            />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" @click="close">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="submit">Continuar</button>
                </div>
            </div>
        </div>
    </div>
</template>
