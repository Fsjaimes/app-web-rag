<script>
    import { router } from '@inertiajs/vue3'
    import Layout from "@/Layouts/main.vue"
    import PageHeader from "@/Components/page-header.vue"
    import Select2 from '@/Components/Select2.vue'
    import { useFetchPetition } from '@/Composables/useFetchPetition.js';
    import { useAlert } from '@/Composables/useSweetAlert.js';
    import { useFormErrorFocus } from '@/Composables/useFormErrorFocus.js';
    const { focusFirstFormError } = useFormErrorFocus();
    const { fetchPetition } = useFetchPetition();
    const { showAlert, showConfirm, showWarning } = useAlert();
    export default {
        name: 'DocumentTypesCreate',
        props: {
            serverDateFormats: {
                type: Object,
                default: () => ({
                    YYYY: '',
                    YYMM: '',
                    YYMMDD: '',
                }),
            },
            datesFormats: {
                type: Array,
                default: () => [],
            },
            inventoryMovementTypes: {
                type: Array,
                default: () => [],
            },
            lengthSequences: {
                type: Array,
                default: () => [],
            },
        },
        components: {
            Layout,
            PageHeader,
            Select2,
        },
        data() {
            return {
                form: {
                    prefix: '',
                    name: '',
                    module: '',
                    affectsInventory: false,
                    inventoryMovementType: '',
                    allowNegativeInventory: false,
                    status: true,
                    hasPrefix: true,
                    hasDate: false,
                    dateFormat: '',
                    lengthSequence: '',
                },
                loading: false,
                clientErrors: {},
                prefixExistsError: '',
                prefixValidationTimeout: null,
            }
        },
        computed: {
            numberingPreview() {
                const parts = [];

                if (this.form.hasPrefix && this.form.prefix) {
                    parts.push(this.form.prefix);
                }

                if (this.form.hasDate) {
                    if (this.form.dateFormat === 'YYYY' && this.serverDateFormats.YYYY) parts.push(this.serverDateFormats.YYYY)
                    else if (this.form.dateFormat === 'YYMM' && this.serverDateFormats.YYMM) parts.push(this.serverDateFormats.YYMM)
                    else if (this.form.dateFormat === 'YYMMDD' && this.serverDateFormats.YYMMDD) parts.push(this.serverDateFormats.YYMMDD)
                }

                const seqLength = Number(this.form.lengthSequence);
                if (seqLength > 0) {
                    parts.push(String(1).padStart(seqLength, '0'));
                }

                return parts.join('');
            },
        },
        watch: {
            'form.affectsInventory'(value) {
                if (value) return
                this.form.inventoryMovementType = null;
                this.form.allowNegativeInventory = false;
            },
            'form.hasDate'(value) {
                if (value) return
                this.form.dateFormat = null;
                this.clearClientError('dateFormat');
            },
        },
        methods: {
            normalizePrefixUppercase() {
                this.form.prefix = (this.form.prefix || '').toUpperCase();
                this.clearClientError('prefix');
                this.prefixExistsError = '';
                this.schedulePrefixValidation();
            },
            schedulePrefixValidation() {
                if (this.prefixValidationTimeout) {
                    clearTimeout(this.prefixValidationTimeout);
                }

                const prefix = (this.form.prefix || '').trim();
                if (prefix === '') {
                    this.prefixExistsError = '';
                    return;
                }

                this.prefixValidationTimeout = setTimeout(() => {
                    this.validatePrefixUniqueness();
                }, 1000);
            },
            async validatePrefixUniqueness({ showAlertOnExists = false } = {}) {
                const prefix = (this.form.prefix || '').trim().toUpperCase();
                if (prefix === '') {
                    this.prefixExistsError = '';
                    return false;
                }

                const response = await fetchPetition('/document-types/validate-prefix', {
                    method: 'POST',
                    body: {
                        prefix,
                        uuid: null,
                    },
                });

                if (!response.ok) {
                    return false;
                }

                const exists = Boolean(response.data?.exists);
                this.prefixExistsError = exists ? 'El prefijo ya existe' : '';

                if (exists && showAlertOnExists) {
                    await showAlert('warning', '¡Alerta!', 'El prefijo ya existe', 2500);
                }

                return exists;
            },
            collectDocumentTypeErrors() {
                const e = {};
                const f = this.form;

                if (!f.prefix || f.prefix.trim() == '') e.prefix = true;
                if (!f.name || f.name.trim() == '') e.name = true;
                if (!f.lengthSequence) e.lengthSequence = true;

                if (f.affectsInventory && !f.inventoryMovementType) {
                    e.inventoryMovementType = true;
                }

                if (f.hasDate && !f.dateFormat) {
                    e.dateFormat = true;
                }

                return e;
            },
            clearClientError(field) {
                if (!(field in this.clientErrors)) return;
                const next = { ...this.clientErrors };
                delete next[field];
                this.clientErrors = next;
            },
            async submitForm() {
                this.clientErrors = {};
                try {
                    const errors = this.collectDocumentTypeErrors();
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

                    const prefixExists = await this.validatePrefixUniqueness({ showAlertOnExists: true });
                    if (prefixExists) {
                        this.clientErrors = { ...this.clientErrors, prefix: true };
                        await this.$nextTick();
                        focusFirstFormError(this.$refs.formRef, this.clientErrors);
                        return;
                    }

                    const confirmed = await showConfirm(
                        'warning',
                        '¡Alerta!',
                        '¿Está seguro que desea crear un tipo documento?',
                        'Sí, crear'
                    );
                    if (!confirmed) return;
                    this.loading = true;
                    const response = await fetchPetition('/document-types', {
                        method: 'POST',
                        body: this.form,
                    });
                    if (response.ok) {
                        showAlert('success', '¡Éxito!', 'Tipo documento creado correctamente', 1500);
                        router.visit('/tipos-documentos');
                    } else {
                        const data = response.data;
                        const message = data?.message || (data?.errors ? Object.values(data.errors || {}).flat().join(' ') : 'Ocurrió un error al crear el tipo documento');
                        showAlert('error', 'Error', message, 3000);
                    }
                } catch (error) {
                    showAlert('error', 'Error inesperado', 'Ocurrió un error al crear el tipo documento', 3000);
                } finally {
                    this.loading = false;
                }
            }
        },
        mounted() {
        },
        beforeUnmount() {
            if (this.prefixValidationTimeout) {
                clearTimeout(this.prefixValidationTimeout);
            }
        }
    }
</script>
<template>
    <Layout>
        <PageHeader title="Crear Tipo Documento" pageTitle="Tipos Documentos" />
        <div class="row">
            <div class="col-8">
                <div class="card">
                    <div class="card-header">
                        <div class="form-section-title">
                            <h6 class="mb-1">Información principal</h6>
                            <p class="text-muted mb-0">Completa los campos para crear el tipo documento.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submitForm" ref="formRef">
                            <div class="row g-3">
                                <div class="col-12 col-md-6 mb-3" data-form-error-anchor="prefix">
                                    <label for="prefix" class="form-label">Prefijo<span class="text-danger ms-1">*</span></label>
                                    <input v-model="form.prefix" type="text" class="form-control" :class="{ 'form-control--validation-error': clientErrors.prefix || prefixExistsError }" @input="normalizePrefixUppercase" id="prefix" maxlength="4" placeholder="Ej: FAC" autocomplete="off">
                                    <small v-if="prefixExistsError" class="text-danger fs-12"><i class="ri-error-warning-line mx-1"></i>{{ prefixExistsError }}</small>
                                </div>
                                <div class="col-12 col-md-6 mb-3" data-form-error-anchor="name">
                                    <label for="name" class="form-label">Nombre<span class="text-danger ms-1">*</span></label>
                                    <input v-model="form.name" type="text" class="form-control" :class="{ 'form-control--validation-error': clientErrors.name }" @input="clearClientError('name')" id="name" placeholder="Ingrese Nombre" autocomplete="off">
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 mb-2">
                                    <div class="check-card h-100">
                                        <div class="check-info-row">
                                            <div class="form-check form-check-custom m-0">
                                                <input v-model="form.affectsInventory" class="form-check-input" type="checkbox" id="affectsInventory">
                                                <label class="form-check-label fw-semibold" for="affectsInventory">Afecta Inventario</label>
                                            </div>
                                            <small class="text-muted text-end"><i class="ri-information-line"></i> Indica si este tipo documento genera movimientos en el inventario.</small>
                                        </div>
                                    </div>
                                </div>

                                <template v-if="form.affectsInventory">
                                    <div class="col-12 col-md-6 mb-3" data-form-error-anchor="inventoryMovementType">
                                        <label for="inventoryMovementType" class="form-label">Tipo Movimiento Inventario<span class="text-danger ms-1">*</span></label>
                                        <Select2
                                            id="inventoryMovementType"
                                            :options="inventoryMovementTypes"
                                            v-model="form.inventoryMovementType"
                                            :placeholder="'Seleccione...'"
                                            :value-field="'id'"
                                            :text-field="'description'"
                                            :show-validation-error="clientErrors.inventoryMovementType"
                                            @change="clearClientError('inventoryMovementType')"
                                        />
                                    </div>
                                    <div class="col-12 col-md-6 mb-2">
                                        <div class="check-card h-100">
                                            <div class="check-info-row">
                                                <div class="form-check form-check-custom m-0">
                                                    <input v-model="form.allowNegativeInventory" class="form-check-input" type="checkbox" id="allowNegativeInventory">
                                                    <label class="form-check-label fw-semibold" for="allowNegativeInventory">Permite Inventario Negativo</label>
                                                </div>
                                                <small class="text-muted text-end"><i class="ri-information-line"></i> Indica si este tipo documento permite registrar inventario negativo.</small>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 d-flex align-items-center mb-2 mt-4">
                                    <label class="form-check-label me-3" for="customSwitchsizesm">Estado</label>
                                    <div class="form-check form-switch form-switch-md" dir="ltr">
                                        <input v-model="form.status" type="checkbox" class="form-check-input" id="customSwitchsizesm">
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end ">
                                <button type="button" class="btn btn-light me-2" @click="$inertia.visit('/tipos-documentos')">
                                    Cancelar
                                </button>
                                <button type="submit" class="btn btn-primary px-4" :disabled="loading">
                                    {{ loading ? 'Guardando...' : 'Guardar' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card">
                    <div class="card-header">
                        <div class="form-section-title">
                            <h6 class="mb-1">Numeración</h6>
                            <p class="text-muted mb-0">Configura la numeración del tipo documento.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">

                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="form-check px-0 d-inline-flex align-items-center m-0">
                                            <input v-model="form.hasPrefix" class="form-check-input ms-1 me-2" type="checkbox" id="hasPrefix">
                                            <label class="form-check-label mb-0" for="hasPrefix">Prefijo</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check px-0 d-inline-flex align-items-center m-0">
                                            <input v-model="form.hasDate" class="form-check-input ms-1 me-2" type="checkbox" id="hasDate">
                                            <label class="form-check-label mb-0" for="hasDate">Fecha</label>
                                        </div>
                                    </div>
                                    <div v-if="form.hasDate" class="col-12 mt-3" data-form-error-anchor="dateFormat">
                                        <div class="numbering-date-select">
                                            <label for="dateFormat" class="form-label mb-1">Formato Fecha<span class="text-danger ms-1">*</span></label>
                                            <Select2
                                                id="dateFormat"
                                                :options="datesFormats"
                                                v-model="form.dateFormat"
                                                :placeholder="'Seleccione...'"
                                                :value-field="'code'"
                                                :text-field="'description'"
                                                :show-validation-error="clientErrors.dateFormat"
                                                @change="clearClientError('dateFormat')"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3" data-form-error-anchor="lengthSequence">
                                    <label for="lengthSequence" class="form-label">Longitud Consecutivo<span class="text-danger ms-1">*</span></label>
                                    <Select2
                                        id="lengthSequence"
                                        :options="lengthSequences"
                                        v-model="form.lengthSequence"
                                        :placeholder="'Seleccione...'"
                                        :value-field="'id'"
                                        :text-field="'description'"
                                        :show-validation-error="clientErrors.lengthSequence"
                                        @change="clearClientError('lengthSequence')"
                                    />
                                    <small class="text-muted d-block mt-2">Ejemplo numeración: <strong class="text-primary">{{ numberingPreview }}</strong></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>
<style scoped>
.form-section-title h6 {
    font-size: 0.95rem;
    font-weight: 600;
}

.check-card {
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: center;
    border: 1px solid #e9ebec;
    border-radius: 0.5rem;
    padding: 0.85rem 1rem;
    background-color: #fafbfc;
}

.check-info-row {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.check-info-row small {
    max-width: 56%;
    line-height: 1.2;
}

.numbering-section {
    padding-top: 0.75rem;
}

.numbering-toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.numbering-toggle-row small {
    max-width: 62%;
    text-align: right;
}

.numbering-date-select {
    min-width: 62%;
}

.numbering-toggle-row--stacked {
    flex-direction: column;
    align-items: flex-start;
}

.numbering-toggle-row--stacked .numbering-date-select {
    width: 100%;
    min-width: 100%;
}

/* @media (max-width: 768px) {
    .check-info-row {
        flex-direction: column;
        align-items: flex-start;
    }

    .check-info-row small {
        max-width: 100%;
        text-align: left !important;
    }

    .numbering-toggle-row {
        flex-direction: column;
        align-items: flex-start;
    }

    .numbering-toggle-row small,
    .numbering-date-select {
        max-width: 100%;
        min-width: 100%;
        text-align: left;
    }
} */

</style>