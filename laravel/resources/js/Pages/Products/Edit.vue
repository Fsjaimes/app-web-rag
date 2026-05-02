<script>
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import Select2 from '@/Components/Select2.vue';
import AutocompleteSearchInput from '@/Components/AutocompleteSearchInput.vue';

import { router } from '@inertiajs/vue3';
import { useFetchPetition } from '@/Composables/useFetchPetition.js';
import { useAlert } from '@/Composables/useSweetAlert.js';

const { fetchPetition } = useFetchPetition();
const { showAlert, showConfirm, showWarning } = useAlert();

export default {
    name: 'ProductEdit',
    components: {
        Layout,
        PageHeader,
        Select2,
        AutocompleteSearchInput,
    },
    props: {
        product: {
            type: Object,
            required: true,
        },
        categories: {
            type: Array,
            required: true,
        },
        taxes: {
            type: Array,
            required: true,
        },
        unitOfMeasures: {
            type: Array,
            required: true,
        },
        retentions: {
            type: Array,
            required: true,
        },
    },
    data() {
        const p = this.product;
        return {
            form: {
                code: p.code ?? '',
                name: p.name ?? '',
                description: p.description ?? '',
                categoryId: p.categoryId ?? '',
                unitOfMeasureId: p.unitOfMeasureId ?? '',
                taxId: p.taxId ?? '',
                salesAccountCode: p.salesAccountCode ?? '',
                returnsAccountCode: p.returnsAccountCode ?? '',
                status: p.status === '1',
            },
            isSubmitting: false,
            codeExists: false,
            validateTimer: null,
        };
    },
    watch: {
        'form.code'(newValue) { // Validar el existencia de producto por código
                // Limpiar timer anterior
                clearTimeout(this.validateTimer);

                // Si está vacío, no validamos
                if (!newValue || newValue.trim() === '') {
                    this.codeExists = false;
                    return;
                }

                // Ejecutar solo cuando el usuario deja de escribir
                this.validateTimer = setTimeout(() => {
                    this.validateIfExists();
                }, 1000); // 1 segundo después de dejar de escribir
            },
    },
    methods: {
        goBack() {
            router.visit('/productos');
        },

        async submitForm() {
            if (this.codeExists) {
                await showWarning('¡Alerta!', 'El código ya existe.');
                return;
            }
            if (!this.form.code || !this.form.code.trim()) {
                await showWarning('¡Alerta!', 'Ingrese código del producto.');
                return;
            }
            if (this.form.code.length > 10) {
                await showWarning('¡Alerta!', 'Código no puede superar 10 caracteres.');
                return;
            }
            if (!this.form.name || !this.form.name.trim()) {
                await showWarning('¡Alerta!', 'Ingrese nombre del producto.');
                return;
            }
            if (!this.form.categoryId) {
                await showWarning('¡Alerta!', 'Seleccione categoría.');
                return;
            }
            if (!this.form.unitOfMeasureId) {
                await showWarning('¡Alerta!', 'Seleccione unidad de medida.');
                return;
            }
            if (!this.form.taxId) {
                await showWarning('¡Alerta!', 'Seleccione impuesto.');
                return;
            }

            const confirmed = await showConfirm(
                'warning',
                '¡Alerta!',
                '¿Está seguro que desea actualizar este producto?',
                'Sí, actualizar'
            );

            if (!confirmed) {
                return;
            }

            this.isSubmitting = true;
            this.form.status = this.form.status ? '1' : '0';

            try {
                const body = { ...this.form };
                const response = await fetchPetition(`/products/${this.product.uuid}`, {
                    method: 'PUT',
                    body,
                });

                if (response.ok) {
                    showAlert('success', '¡Éxito!', 'Producto actualizado correctamente', 1500);
                    router.visit('/productos');
                } else {
                    const data = response.data;
                    const message = data?.message || (data?.errors ? Object.values(data.errors || {}).flat().join(' ') : 'Error al actualizar producto');
                    showAlert('error', 'Error', message, 3000);
                }
            } catch (error) {
                showAlert('error', 'Error inesperado', 'Ocurrió un error al actualizar el producto', 1500);
            } finally {
                this.isSubmitting = false;
                this.form.status = this.form.status === '1';
            }
        },

        async validateIfExists() { // Validar si el código ya existe (ignorando el propio producto)
            // Si el código está vacío, resetear la variable y retornar
            if (!this.form.code || this.form.code.trim() === "") {
                this.codeExists = false;
                return;
            }

            try {
                const response = await fetchPetition('/products/validate-code', {
                    method: 'POST',
                    body: [this.form.code, this.product.id],
                });
                // El endpoint retorna { exists: true/false }
                this.codeExists = response.data?.exists;
            } catch (error) {
                console.error('Error validando existencia de producto por código:', error);
                this.codeExists = false;
            }
        },
    },
};
</script>

<template>
    <Layout>
        <PageHeader title="Editar Producto" pageTitle="Productos" />
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Datos del producto</h5>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submitForm">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="code" class="form-label">Código<span class="text-danger ms-1">*</span></label>
                                    <input
                                        v-model.trim="form.code"
                                        type="text"
                                        class="form-control"
                                        id="code"
                                        maxlength="10"
                                        placeholder="Ej: PROD001"
                                        :class="{ 'is-invalid': codeExists }"
                                    />
                                    <span class="text-danger small" v-if="codeExists"><i class="ri-alert-line mx-1"></i>El código ya existe!</span>
                                </div>
                                <div class="col-md-4">
                                    <label for="name" class="form-label">Nombre<span class="text-danger ms-1">*</span></label>
                                    <input
                                        v-model.trim="form.name"
                                        type="text"
                                        class="form-control"
                                        id="name"
                                        maxlength="255"
                                        placeholder="Ingrese nombre"
                                    />
                                </div>
                                <div class="col-md-4">
                                    <label for="sel_category">Categoría<span class="text-danger ms-1">*</span></label>
                                    <Select2
                                        id="sel_category"
                                        v-model="form.categoryId"
                                        :options="categories"
                                        value-field="id"
                                        text-field="description"
                                        placeholder="Seleccione ..."
                                    />
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <label for="description" class="form-label">Descripción</label>
                                    <textarea
                                        v-model.trim="form.description"
                                        class="form-control"
                                        id="description"
                                        rows="2"
                                        placeholder="Ingrese descripción"
                                    ></textarea>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="sel_unit">Unidad Medida<span class="text-danger ms-1">*</span></label>
                                    <Select2
                                        id="sel_unit"
                                        v-model="form.unitOfMeasureId"
                                        :options="unitOfMeasures"
                                        value-field="id"
                                        text-field="description"
                                        placeholder="Seleccione ..."
                                    />
                                </div>
                                <div class="col-md-4">
                                    <label for="sel_tax">Impuesto<span class="text-danger ms-1">*</span></label>
                                    <Select2
                                        id="sel_tax"
                                        v-model="form.taxId"
                                        :options="taxes"
                                        value-field="id"
                                        text-field="description"
                                        placeholder="Seleccione ..."
                                    />
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="autocomplete_account">Cuenta Venta<span class="text-danger ms-1">*</span></label>
                                    <AutocompleteSearchInput
                                        v-model="form.salesAccountCode"
                                        search-url="/products/search-accounts"
                                        label-template="{full_code} - {name}"
                                        value-field="full_code"
                                        :select-fields="['full_code', 'name']"
                                        :initial-options="{ full_code: product.salesAccountCode, name: product.salesAccount }"
                                        :multiple="false"
                                        :min-chars="4"
                                        :limit="10"
                                        :filters="{ type: '2', disabled_ids: form.returnsAccountCode ? [form.returnsAccountCode] : [] }"
                                        placeholder="Buscar cuenta ..."
                                    />
                                </div>
                                <div class="col-md-6">
                                    <label for="autocomplete_account">Cuenta Devolución<span class="text-danger ms-1">*</span></label>
                                    <AutocompleteSearchInput
                                        v-model="form.returnsAccountCode"
                                        search-url="/products/search-accounts"
                                        label-template="{full_code} - {name}"
                                        value-field="full_code"
                                        :select-fields="['full_code', 'name']"
                                        :initial-options="{ full_code: product.returnsAccountCode, name: product.salesAccount }"
                                        :multiple="false"
                                        :min-chars="4"
                                        :limit="10"
                                        :filters="{ type: '2', status: '1', disabled_ids: form.salesAccountCode ? [form.salesAccountCode] : [] }"
                                        placeholder="Buscar cuenta ..."
                                    />
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12 d-flex align-items-center">
                                    <label class="form-check-label me-3" for="productStatus">Estado</label>
                                    <div class="form-check form-switch form-switch-md" dir="ltr">
                                        <input
                                            v-model="form.status"
                                            type="checkbox"
                                            class="form-check-input"
                                            id="productStatus"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 text-end">
                                <button type="button" class="btn btn-light me-2" @click="goBack">
                                    Cancelar
                                </button>
                                <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                                    <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    {{ isSubmitting ? 'Actualizando...' : 'Actualizar' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>
