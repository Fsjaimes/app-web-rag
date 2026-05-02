<script>
    import { router } from '@inertiajs/vue3'
    import Layout from "@/Layouts/main.vue"
    import PageHeader from "@/Components/page-header.vue"
    import { useFetchPetition } from '@/Composables/useFetchPetition.js';
    import { useAlert } from '@/Composables/useSweetAlert.js';
    import Select2 from '@/Components/Select2.vue';
    import AddressModal from '@/Components/AddressModal.vue';
    import { useFormErrorFocus } from '@/Composables/useFormErrorFocus.js';
    const { focusFirstFormError } = useFormErrorFocus();
    const { fetchPetition } = useFetchPetition();
    const { showAlert, showConfirm, showWarning } = useAlert();

    export default {
        name: 'WarehousesCreate',
        components: {
            Layout,
            PageHeader,
            Select2,
            AddressModal
        },
        props: {
            Managers: {
                type: Array,
                default: () => [],
            },
            branches: {
                type: Array,
                default: () => [],
            },
        },
        data() {
            return {
            form: {
                name: '',
                address: '',
                address_data: null,
                manager_id: '',
                city_id: '',
                branch_id: '',
                code: '',
                status: true,
            },
            loading: false,
            showAddressModal: false,
            clientErrors: {},
            codeExists: false,
            }
        },
        methods: {
            collectWarehouseErrors() {
                const e = {};
                const f = this.form;

                if (!f.code || f.code.trim() === '') e.code = true;
                if (!f.name || f.name.trim() === '') e.name = true;

                return e;
            },
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
                this.clientErrors = {};
                const codeExists = await this.checkCodeExists(this.form.code);
                this.codeExists = codeExists;
                if (codeExists) {
                    this.clientErrors.code = true;
                    await this.$nextTick();
                    focusFirstFormError(this.$refs.formRef, { code: true });
                    this.loading = false;
                    return;
                }
                try {
                    const errors = this.collectWarehouseErrors();
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

                    const confirmed = await showConfirm(
                        'warning',
                        '¡Alerta!',
                        '¿Está seguro que desea crear una bodega?',
                        'Sí, crear'
                    );

                    if (!confirmed) {
                        this.loading = false;
                        return;
                    }
                    const body = {
                        ...this.form,
                        status: this.form.status ? '1' : '0',
                    };
                    const response = await fetchPetition('/warehouses', {
                        method: 'POST',
                        body,
                    });
                    if (response.ok) {
                        showAlert('success', '¡Éxito!', 'Bodega creada correctamente', '', 1500);
                        router.visit('/bodegas');
                    } else {
                        showAlert('error', 'Error', 'Error al crear bodega', 1500);
                    }
                } catch (error) {
                    showAlert('error', 'Error inesperado', 'Ocurrió un error al crear bodega', 1500);
                } finally {
                    this.loading = false;
                }
            },
            handleAddressConfirm(address) {
                this.form.address = address.full_address || '';
                this.form.address_data = address;
                this.showAddressModal = false;
            }
        }
    }
</script>

<template>
    <Layout>
        <PageHeader title="Crear Bodegas" pageTitle="Bodegas" />
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Bodegas</h5>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submitForm" ref="formRef">
                            <div class="row g-3 mb-3">
                                <div class="col-6" data-form-error-anchor="code">
                                    <label for="code" class="form-label">Código<span class="text-danger">*</span></label>
                                    <input v-model="form.code" type="text" class="form-control" :class="{ 'form-control--validation-error': clientErrors.code }" @input="handleCodeInput" maxlength="10" placeholder="Ingrese Código">
                                    <span v-if="codeExists" class="text-danger">El código ya existe.</span>
                                </div>
                                <div class="col-6" data-form-error-anchor="name">
                                    <label for="name" class="form-label">Nombre<span class="text-danger">*</span></label>
                                    <input v-model="form.name" type="text" class="form-control" id="name" :class="{ 'form-control--validation-error': clientErrors.name }" @input="clearClientError('name')" maxlength="150" placeholder="Ingrese Nombre">
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label for="manager_id" class="form-label">Responsable</label>
                                    <Select2
                                        v-model="form.manager_id"
                                        :options="Managers"
                                        :show-validation-error="clientErrors.manager_id"
                                        @input="clearClientError('manager_id')"
                                        value-field="id"
                                        text-field="description"
                                        placeholder="Seleccione..."
                                    />
                                </div>
                                <div class="col-6">
                                    <label for="branch_id" class="form-label">Sucursal</label>
                                    <Select2
                                        v-model="form.branch_id"
                                        :options="branches"
                                        :show-validation-error="clientErrors.branch_id"
                                        @input="clearClientError('branch_id')"
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
                                        :initial-value="form.address_data"
                                        :show-validation-error="clientErrors.address"
                                        @input="clearClientError('address')"
                                    />
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
                                <button type="button" class="btn btn-light me-2" @click="$inertia.visit('/bodegas')">
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
</style>