<script>
    import Select2 from '@/Components/Select2.vue';
    import InputDialingCodes from '@/Components/InputDialingCodes.vue';
    import DropZone from '@/Components/widgets/dropZone.vue';
    import FileList from '@/Components/FileList.vue';
    import { useAlert } from '@/Composables/useSweetAlert.js';
    import {getDisabledTaxRegimeTypeIds, applyTaxRegimeSelectionRules, resolveTaxpayerTypeByIdentification} from '@/Composables/useThirdPartyFormRules.js';

    const { showWarning } = useAlert();

    export default {
        name: 'ThirdPartyForm',
        components: {
            Select2,
            InputDialingCodes,
            DropZone,
            FileList,
        },
        emits: ['update:modelValue', 'add-attachment', 'delete-file'],
        props: {
            modelValue: {
                type: Object,
                required: true,
            },
            thirdPartyTypes: {
                type: Array,
                required: true,
            },
            taxRegimeTypes: {
                type: Array,
                required: true,
            },
            taxpayerTypes: {
                type: Array,
                required: true,
            },
            legislationTypes: {
                type: Array,
                required: true,
            },
            countries: {
                type: Array,
                required: true,
            },
            regions: {
                type: Array,
                required: true,
            },
            cities: {
                type: Array,
                required: true,
            },
            identificationTypes: {
                type: Array,
                required: true,
            },
            canEditIdentificationData: {
                type: Boolean,
                required: true,
                default: true,
            },
            isCompany: {
                type: Boolean,
                required: true,
                default: false,
            },
            errors: {
                type: Object,
                default: () => ({}),
            },
            identificationNumberExists: {
                type: Boolean,
                default: false,
            },
            files: {
                type: Array,
                default: () => [],
            },
        },
        data() {
            return {
                selectDisabled: true,
                filteredCities: [],
                showCities: true,
                vatResponsibilities: [
                    { id: 1, description: 'Responsable de IVA' },
                    { id: 2, description: 'No responsable de IVA' },
                ],
            }
        },
        computed: {
            disabledTaxRegimeTypeIds() {
                return getDisabledTaxRegimeTypeIds(this.taxRegimeTypes, this.form.taxRegimeTypeId);
            },
            form:{
                get() {
                    return this.modelValue;
                },
                set(value) {
                    this.$emit('update:modelValue', value);
                }
            },
            isColombia() {
                const country = this.countries.find(c => c.id == this.form.countryId);
                return country?.description == "COLOMBIA";
            },
            taxpayerTypeByIdentificationType() {
                return resolveTaxpayerTypeByIdentification(
                    this.form.identificationTypeId,
                    this.taxpayerTypes,
                );
            },
        },
        watch: {
            'form.countryId': function(newValue) {
                this.changeSelectDisabled(newValue);
            },
            'form.regionId': function(newRegionId, oldVal) {
                // Solo limpiar ciudad cuando el usuario cambia de departamento (ya tenía un valor), no al cargar
                if (oldVal != null && newRegionId !== oldVal) {
                    this.form.cityId = '';
                    this.filteredCities = [];
                }
                if (newRegionId) {
                    this.filterCities(newRegionId);
                }
            },
            'form.identificationTypeId': function() {
                const { id } = this.taxpayerTypeByIdentificationType;
                this.form.taxpayerTypeId = id ?? '';
            },
            'form.taxRegimeTypeId': function (newValue) {
                const { nextValue, warningMessage } = applyTaxRegimeSelectionRules(newValue, this.taxRegimeTypes);
                if (nextValue !== null) {
                    this.form.taxRegimeTypeId = nextValue;
                    if (warningMessage) {
                        showWarning('¡Alerta!', warningMessage);
                    }
                }
            },
        },

        mounted() {
            if (this.form.regionId) {
                this.filterCities(this.form.regionId);
            }
        },

        methods: {
            changeSelectDisabled(countryId) {
                // Deshabilitar los selects de Departamento y de Ciudad si el país no es colombia
                if (countryId != '42') {
                    this.selectDisabled = true;
                    this.form.regionId = '';  // Restablecer regionId
                    this.form.cityId = '';    // Restablecer cityId
                } else {
                    this.selectDisabled = false;  // Habilitar los select2 si el país es Colombia
                }
            },

            filterCities(regionId) {
                this.filteredCities = [];
                this.showCities = false;
                this.$nextTick(() => {
                    if (Array.isArray(this.cities) && this.cities.length > 0) {
                        const regionKey = (city) => city.regionId ?? city.region_id;
                        const filtered = this.cities.filter(
                            city => String(regionKey(city)) === String(regionId)
                        );
                        this.filteredCities = [...filtered];
                    }
                    this.showCities = true;
                });
            },
        },
    }
</script>

<template>
    <div class="row">
        <div :class="isCompany ? 'col-12 pe-0 ps-0' : 'col-9 pe-0 ps-0'">
            <div class="card">
                <div class="card-header" v-if="!isCompany">
                    <h5 class="card-title mb-0">Tercero</h5>
                </div>
                <div class="card-body">
            <!-- Sección Básica -->
            <div class="mb-2 mt-1">
                <div class="mb-3">
                    <h4 class="card-title mb-0 flex-grow-1">Información Básica</h4>
                    <!-- <p class="text-muted mb-0">Información fundamental para el reconocimiento inicial.</p> -->
                </div>
                <div class="row mb-3 g-3">
                    <!-- Tipo Identificación -->
                    <div class="col-3">
                        <label for="sel_tipo_identificacion">Tipo Identificación<span class="text-danger ms-1">*</span></label>
                        <Select2
                            v-model="form.identificationTypeId"
                            :options="identificationTypes"
                            value-field="id"
                            text-field="description"
                            placeholder="Seleccione..."
                            :disabled="!canEditIdentificationData"
                        />
                        <span v-if="errors.identificationTypeId" class="text-danger" style="font-size: .875em;">{{ errors.identificationTypeId }}</span>
                    </div>
                    <!-- Número Identificación -->
                    <div class="col">
                        <label for="identificationNumber" class="form-label">Número Identificación<span class="text-danger ms-1">*</span></label>
                        <input 
                            v-model="form.identificationNumber" 
                            type="text" class="form-control" 
                            id="identificationNumber" 
                            maxlength="150" 
                            placeholder="Ingrese Número Identificación"
                            @input="form.identificationNumber = (form.identificationNumber || '').replace(/[^0-9]/g, '')"
                            :class="{ 'is-invalid': errors.identificationNumber }"
                            :disabled="!canEditIdentificationData"
                        >
                        <span v-if="errors.identificationNumber" class="text-danger" style="font-size: .875em;">{{ errors.identificationNumber }}</span>
                        <span v-if="identificationNumberExists" class="text-danger" style="font-size: .875em;">Ya existe la identificación en el sistema!</span>
                    </div>
                    <!-- Digito Verificación -->
                    <div v-show="form.identificationTypeId == 6 || form.identificationTypeId == 3" class="col">
                        <label for="dv" class="form-label">Digito Verificación <span v-if="form.identificationTypeId == 6" class="text-danger ms-1">*</span></label>
                        <input 
                            v-model="form.dv"
                            type="text"
                            class="form-control"
                            id="identificationTypeId"
                            maxlength="1"
                            placeholder="Ingrese Digito Verificación"
                            @input="form.dv = (form.dv || '').replace(/[^0-9]/g, '')"
                            :class="{ 'is-invalid': errors.dv }"
                            :disabled="!canEditIdentificationData"
                        >
                        <span v-if="errors.dv" class="text-danger" style="font-size: .875em;">{{ errors.dv }}</span>
                    </div>
                    <!-- Ciudad de expedición del documento -->
                    <div class="col-3" v-if="showCities">
                        <label for="ciudad">Lugar Expedición<span class="text-danger ms-1">*</span></label>
                        <Select2
                            v-model="form.issuanceCityId"
                            :options="cities"
                            value-field="id"
                            text-field="description"
                            placeholder="Seleccione..."
                        />
                        <span v-if="errors.issuanceCityId" class="text-danger" style="font-size: .875em;">{{ errors.issuanceCityId }}</span>
                    </div>
                </div>
                <div class="row g-3 mb-2">
                    <!-- Campos de persona natural -->
                    <template v-if="form.identificationTypeId != 6">
                        <!-- Primer Nombre-->
                        <div class="col-3">
                            <label for="firstName" class="form-label">Primer Nombre <span class="text-danger ms-1">*</span></label>
                            <input v-model="form.firstName" type="text" class="form-control" id="firstName" maxlength="150" placeholder="Ingrese Primer Nombre" :class="{ 'is-invalid': errors.firstName }">
                            <span v-if="errors.firstName" class="text-danger" style="font-size: .875em;">{{ errors.firstName }}</span>
                        </div>
                        <!-- Otros Nombres-->
                        <div class="col-3">
                            <label for="otherName" class="form-label">Otros Nombres</label>
                            <input v-model="form.otherName" type="text" class="form-control" id="otherName" maxlength="150" placeholder="Ingrese Otros Nombres" :class="{ 'is-invalid': errors.otherName }">
                            <span v-if="errors.otherName" class="text-danger" style="font-size: .875em;">{{ errors.otherName }}</span>
                        </div>
                        <!-- Primer Apellido -->
                        <div class="col-3">
                            <label for="firstLastName" class="form-label">Primer Apellido<span class="text-danger ms-1">*</span></label>
                            <input v-model="form.firstLastName" type="text" class="form-control" id="firstLastName" maxlength="150" placeholder="Ingrese Primer Apellido" :class="{ 'is-invalid': errors.firstLastName }">
                            <span v-if="errors.firstLastName" class="text-danger" style="font-size: .875em;">{{ errors.firstLastName }}</span>
                        </div>
                        <!-- Segundo Apellido -->
                        <div class="col-3">
                            <label for="secondLastName" class="form-label">Segundo Apellido</label>
                            <input v-model="form.secondLastName" type="text" class="form-control" id="secondLastName" maxlength="150" placeholder="Ingrese Segundo Apellido" :class="{ 'is-invalid': errors.secondLastName }">
                            <span v-if="errors.secondLastName" class="text-danger" style="font-size: .875em;">{{ errors.secondLastName }}</span>
                        </div>
                        <div class="col-12 mt-2">
                            <label for="fullName" class="form-label text-muted">
                                Nombre ingresado:
                                <span v-if="form.firstName || form.otherName || form.firstLastName || form.secondLastName" 
                                    class="fw-bold border-bottom">
                                    {{
                                        [form.firstName, form.otherName, form.firstLastName, form.secondLastName]
                                        .filter(Boolean)
                                        .join(' ')
                                    }}
                                </span>
                                <span v-else class="text-muted">...</span>
                            </label>
                        </div>
                    </template>
                    <!-- Campo de persona jurídica -->
                    <template v-else>
                        <div class="col-12">
                            <label for="companyName" class="form-label">Razón Social <span class="text-danger ms-1">*</span></label>
                            <input v-model="form.companyName" type="text" class="form-control" id="companyName" maxlength="150" placeholder="Ingrese Razón Social" :class="{ 'is-invalid': errors.companyName }">
                            <span v-if="errors.companyName" class="text-danger" style="font-size: .875em;">{{ errors.companyName }}</span>
                        </div>
                    </template>
                </div>
                <div class="row g-3">
                    <!-- País -->
                    <div class="col-4">
                        <label for="pais">País<span class="text-danger ms-1">*</span></label>
                        <Select2
                            v-model="form.countryId"
                            :options="countries"
                            value-field="id"
                            text-field="description"
                            placeholder="Seleccione..."
                        />
                        <span v-if="errors.countryId" class="text-danger" style="font-size: .875em;">{{ errors.countryId }}</span>
                    </div>

                    <!-- Departamento -->
                    <div class="col-4">
                        <label for="departamento">Departamento<span class="text-danger ms-1">*</span></label>
                        <Select2
                            v-model="form.regionId"
                            :options="regions"
                            :disabled="selectDisabled"
                            value-field="id"
                            text-field="description"
                            placeholder="Seleccione..."
                        />
                        <span v-if="errors.regionId" class="text-danger" style="font-size: .875em;">{{ errors.regionId }}</span>
                    </div>

                    <!-- Ciudad -->
                    <div class="col-4" v-if="showCities">
                        <label for="ciudad">Ciudad<span class="text-danger ms-1">*</span></label>
                        <Select2
                            v-model="form.cityId"
                            :options="filteredCities"
                            :disabled="selectDisabled"
                            value-field="id"
                            text-field="description"
                            placeholder="Seleccione..."
                        />
                        <span v-if="errors.cityId" class="text-danger" style="font-size: .875em;">{{ errors.cityId }}</span>
                    </div>
                    <!-- Dirección -->
                    <div class="col-12">
                        <label for="address" class="form-label">Dirección<span class="text-danger ms-1">*</span></label>
                        <input v-model="form.address" type="text" class="form-control" id="address" maxlength="150" placeholder="Ingrese Dirección" :class="{ 'is-invalid': errors.address }">
                        <span v-if="errors.address" class="text-danger" style="font-size: .875em;">{{ errors.address }}</span>
                    </div>
                </div>
            </div>
            <!-- Fin Sección -->
            <!-- Sección Contacto -->
            <div class="mb-2">
                <div class="mt-3 pb-3 border-top-dashed border-top py-3">
                    <h4 class="card-title mb-0 flex-grow-1">Información Contacto</h4>
                    <!-- <p class="text-muted mb-0">Información fundamental para el contacto.</p> -->
                </div>
                <div class="row g-3 mb-3">
                        <!-- Correo Electrónico -->
                        <div class="col-12">
                            <label for="email" class="form-label">Correo Electrónico<span class="text-danger ms-1">*</span></label>
                            <input v-model="form.email" type="text" class="form-control" id="email" maxlength="150" placeholder="Ingrese Correo Electrónico" :class="{ 'is-invalid': errors.email }">
                            <span v-if="errors.email" class="text-danger" style="font-size: .875em;">{{ errors.email }}</span>
                        </div>
                </div>
                <div class="row g-3">
                    <!-- Telefono 1 -->
                    <div class="col-6">
                        <label for="phoneCode" class="form-label">Teléfono 1<span class="text-danger ms-1">*</span></label>
                        <!-- <input v-model="form.phoneCode" type="text" class="form-control" id="phoneCode" maxlength="150" placeholder="Ingrese Indicativo Telefono"> -->
                        <InputDialingCodes
                            v-model:selectedCode="form.phoneCode"
                            v-model="form.phoneNumber"
                            :class="{ 'is-invalid': errors.phoneNumber }"
                        />
                        <span v-if="errors.phoneNumber" class="text-danger" style="font-size: .875em;">{{ errors.phoneNumber }}</span>
                    </div>
                    <!-- Telefono 2 -->
                    <div class="col-6">
                        <label for="contactPhoneCode" class="form-label">Teléfono 2</label>
                        <!-- <input v-model="form.contactPhoneCode" type="text" class="form-control" id="contactPhoneCode" maxlength="150" placeholder="Ingrese Indicativo Telefono"> -->
                        <InputDialingCodes
                            v-model:selectedCode="form.contactPhoneCode"
                            v-model="form.contactPhoneNumber"
                        />
                    </div>
                </div>
            </div>
            <!-- Fin Sección -->
            <!-- Sección Facturación -->
            <div class="mb-2">
                <div class="mt-3 pb-3 border-top-dashed border-top py-3">
                    <h4 class="card-title mb-0 flex-grow-1">Información Facturación</h4>
                    <!-- <p class="text-muted mb-0">Información fundamental para la facturación.</p> -->
                </div>
                <div class="row g-3 mb-3">
                        <!-- Tipo Tercero -->
                        <div class="col-6">
                            <label for="sel_tipo_tercero" class="form-label">Tipo Tercero<span class="text-danger ms-1">*</span></label>
                            <Select2
                                v-model="form.thirdPartyTypesId"
                                :options="thirdPartyTypes"
                                :multiple="true"
                                value-field="id"
                                text-field="description"
                                placeholder="Seleccione..."
                            />
                            <span v-if="errors.thirdPartyTypesId" class="text-danger" style="font-size: .875em;">{{ errors.thirdPartyTypesId }}</span>
                        </div>
                        <!-- Tipo Régimen -->
                        <div class="col-6">
                            <label for="sel_tipo_regimen">Tipo Régimen<span class="text-danger ms-1">*</span></label>
                            <Select2
                                v-model="form.taxRegimeTypeId"
                                :options="taxRegimeTypes"
                                :multiple="true"
                                :enable-disabled-options="true"
                                :disabled-option-ids="disabledTaxRegimeTypeIds"
                                value-field="id"
                                text-field="description"
                                placeholder="Seleccione..."
                            />
                            <span v-if="errors.taxRegimeTypeId" class="text-danger" style="font-size: .875em;">{{ errors.taxRegimeTypeId }}</span>
                        </div>
                </div>
                <div class="row g-3">
                    <!-- Tipo Contribuyente (informativo: NIT → Persona Jurídica, otro → Persona Natural) -->
                    <div class="col-6">
                        <label for="tipo_contribuyente_info" class="form-label">Tipo Contribuyente</label>
                        <input
                            type="text"
                            id="tipo_contribuyente_info"
                            class="form-control bg-light"
                            :value="taxpayerTypeByIdentificationType.label || '—'"
                            readonly
                        >
                    </div>
                    <!-- Tipo Legislación -->
                    <div class="col-6">
                        <label for="sel_tipo_contribuyente">Tipo Legislación<span class="text-danger ms-1">*</span></label>
                        <Select2
                            v-model="form.legislationTypeId"
                            :options="legislationTypes"
                            value-field="id"
                            text-field="description"
                            placeholder="Seleccione..."
                        />
                        <span v-if="errors.legislationTypeId" class="text-danger" style="font-size: .875em;">{{ errors.legislationTypeId }}</span>
                    </div>
                </div>
                <!-- Correo Facturación -->
                <div class="row g-3 mt-1">
                    <div class="col-6">
                        <label for="billingEmail" class="form-label">Correo Facturación<span class="text-danger ms-1">*</span></label>
                        <input v-model="form.billingEmail" type="text" class="form-control" id="billingEmail" maxlength="150" placeholder="Ingrese Correo Facturación" :class="{ 'is-invalid': errors.billingEmail }">
                        <span v-if="errors.billingEmail" class="text-danger" style="font-size: .875em;">{{ errors.billingEmail }}</span>
                    </div>
                    <div class="col-6">
                        <label for="vatResponsibility" class="form-label">Responsabilidad IVA<span class="text-danger ms-1">*</span></label>
                        <Select2
                            v-model="form.vatResponsibility"
                            :options="vatResponsibilities"
                            value-field="id"
                            text-field="description"
                            placeholder="Seleccione..."
                        />
                        <span v-if="errors.vatResponsibility" class="text-danger" style="font-size: .875em;">{{ errors.vatResponsibility }}</span>
                    </div>
                </div>
            </div>
            <!-- Fin Sección -->
            <!-- Sección Estado -->
            <div class="row">
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
        </div>
        </div>
        </div>
        <div class="col-3 pe-0" v-if="!isCompany">
            <div class="card">
                <div class="card-body p-0">
                    <div class="accordion-item border-0">
                        <h2 id="headingTwo" class="accordion-header border-bottom">
                            <div role="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo" class="d-flex align-items-center p-2 shadow-none p-3">
                                <i class="ri-attachment-line fs-16 text-primary me-2"></i>
                                <h5 class="card-title mb-0 flex-fill">Adjuntos</h5>
                                <i class="ri ri-arrow-down-s-line fs-18 ms-auto"></i>
                            </div>
                        </h2>
                        <div id="collapseTwo" aria-labelledby="headingOne" data-bs-parent="#accordionExample" class="accordion-collapse collapse show">
                            <div class="accordion-body p-3">
                                <DropZone @file-selected="$emit('add-attachment', $event)"/>
                                <p></p>
                                <FileList :files="files" :limit="3" @delete-file="$emit('delete-file', $event)" :actions="{ view: false, delete: true, download: false }" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
    }
</style>