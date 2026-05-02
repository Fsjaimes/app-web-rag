<script>
import Select2 from '@/Components/Select2.vue';
import { useFetchPetition } from '@/Composables/useFetchPetition.js';
import "leaflet/dist/leaflet.css";
import { LMap, LTileLayer, LMarker } from "@vue-leaflet/vue-leaflet";
const { fetchPetition } = useFetchPetition();

export default {
    name: 'AddressModal',

    components: {
        Select2,
        LMap,
        LTileLayer,
        LMarker
    },

    props: {
        modelValue: {
            type: Boolean,
            default: false
        },
        cities: {
            type: Array,
            default: () => []
        },
        initialValue: {
            type: Object,
            default: null
        },
        asInput: {
            type: Boolean,
            default: true
        },
        inputId: {
            type: String,
            default: 'address'
        },
    },

    emits: ['update:modelValue', 'confirm'],

    data() {
        return {
            modalId: `address-modal-${Math.floor(Math.random() * 100000)}`,
            loadedCities: [],
            loadingCities: false,
            streetTypes: [
                'Avenida',
                'Avenida Calle',
                'Avenida Carrera',
                'Calle',
                'Carrera',
                'Circular',
                'Circunvalar',
                'Diagonal',
                'Finca',
                'Manzana',
                'Transversal',
                'Kilómetro',
                'Vereda',
                'Via',
                'Otro'
            ],
            form: {
                city: '',
                cityName: '',
                streetType: '',
                streetName: '',
                noNumber: false,
                numberOne: '',
                numberTwo: '',
                complement: ''
            },
            map: {
                zoom: 15,
                center: [4.6097, -74.0817], // Coordenadas por defecto (ej. Bogotá)
                marker: [4.6097, -74.0817],
                timeout: null // Para el debounce
            },
        };
    },

    computed: {
        sourceCities() {
            return this.cities.length > 0 ? this.cities : this.loadedCities;
        },
        cityOptions() {
            return this.normalizeOptions(this.sourceCities);
        },
        streetTypeOptions() {
            return this.streetTypes.map((streetType) => ({
                id: streetType,
                text: streetType
            }));
        },
        internalDisplayValue() {
            const initial = this.initialValue || {};
            if (initial.full_address) {
                return initial.full_address;
            }

            const street = [initial.type_of_street, initial.street].filter(Boolean).join(' ').trim();
            const hasNumber = String(initial.has_number ?? '1') !== '0';
            const number = hasNumber ? [initial.number_1, initial.number_2].filter(Boolean).join('-').trim(): '';
            const complement = (initial.additional_information || '').trim();
            const city = initial.city_name || this.cityLabelForId(initial.city_id) || '';

            return [street, number, complement, city].filter(Boolean).join(', ');
        },
        resolvedInputId() {
            return this.inputId || `address-input-${this.modalId}`;
        }
    },

    watch: {
        cities: {
            handler(newCities) {
                const normalized = this.normalizeOptions(newCities);
                const hasCity = normalized.some((option) => String(option.id) === String(this.form.city));
                if (!hasCity) {
                    this.form.city = null;
                }
                this.$nextTick(() => this.syncCityNameFromSelection());
            },
            deep: false
        },
        loadedCities: {
            handler(newCities) {
                if (this.cities.length > 0) {
                    return;
                }
                const normalized = this.normalizeOptions(newCities);
                const hasCity = normalized.some((option) => String(option.id) === String(this.form.city));
                if (!hasCity) {
                    this.form.city = null;
                }
                this.$nextTick(() => this.syncCityNameFromSelection());
            },
            deep: false
        },
        modelValue: {
            handler(isOpen) {
                if (isOpen) {
                    this.citiesLoaded();
                    this.hydrateForm();
                }
            },
            immediate: true
        },
        'form.city': 'debouncedSearch',
        'form.city': {
            handler() {
                this.syncCityNameFromSelection();
            }
        },
        'form.streetName': 'debouncedSearch',
        'form.numberOne': 'debouncedSearch',
        'form.numberTwo': 'debouncedSearch',
    },

    methods: {
        debouncedSearch() {
            clearTimeout(this.map.timeout);
            this.map.timeout = setTimeout(() => {
                this.updateMapFromAddress();
            }, 800); // Espera 800ms después de que el usuario deje de escribir
        },

        async updateMapFromAddress() {
            // Construimos una dirección simplificada para el buscador
            const query = `${this.form.streetType} ${this.form.streetName} ${this.form.numberOne}, ${this.form.cityName}, Colombia`;
            
            if (!this.form.streetName || !this.form.city) return;

            try {
                const response = await fetch(
                    `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`
                );
                const data = await response.json();

                if (data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);
                    this.map.center = [lat, lon];
                    this.map.marker = [lat, lon];
                }
            } catch (error) {
                console.error("Error geocodificando:", error);
            }
        },
        cityLabelForId(cityId) {
            if (cityId === null || cityId === undefined || cityId === '') {
                return '';
            }
            const opt = this.cityOptions.find((o) => String(o.id) === String(cityId));
            return opt?.text || '';
        },
        syncCityNameFromSelection() {
            if (this.form.city === null || this.form.city === undefined || this.form.city === '') {
                this.form.cityName = '';
                return;
            }
            const fromOption = this.cityLabelForId(this.form.city);
            if (fromOption) {
                this.form.cityName = fromOption;
            }
        },
        normalizeOptions(options) {
            return (options || []).map((option) => {
                if (option !== null && typeof option === 'object') {
                    const id = option.id ?? option.value ?? option.uuid ?? option.code ?? '';
                    const text = option.text ?? option.label ?? option.name ?? String(id);
                    return { id, text };
                }

                return {
                    id: option,
                    text: option
                };
            });
        },
        close() {
            this.$emit('update:modelValue', false);
        },
        open() {
            this.$emit('update:modelValue', true);
        },
        async citiesLoaded() {
            if (this.cities.length > 0 || this.loadedCities.length > 0 || this.loadingCities) {
                return;
            }

            this.loadingCities = true;
            try {
                const response = await fetchPetition('/addresses', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`Error cargando ciudades: ${response.status}`);
                }

                const payload = response.data;
                this.loadedCities = Array.isArray(payload) ? payload : (payload?.data || []);
            } catch (error) {
                console.error(error);
                this.loadedCities = [];
            } finally {
                this.loadingCities = false;
            }
        },
        hydrateForm() {
            const initial = this.initialValue || {};
            const cityId = initial.city_id ?? '';
            const cityName = initial.city_name || this.cityLabelForId(cityId) || '';
            this.form = {
                city: cityId,
                cityName,
                streetType: initial.type_of_street ?? '',
                streetName: initial.street ?? '',
                noNumber: String(initial.has_number ?? '1') === '0',
                numberOne: initial.number_1 ?? '',
                numberTwo: initial.number_2 ?? '',
                complement: initial.additional_information ?? ''
            };
        },
        buildFullAddress() {
            const street = [this.form.streetType, this.form.streetName].filter(Boolean).join(' ').trim();
            const number = this.form.noNumber ? '' : [this.form.numberOne, this.form.numberTwo].filter(Boolean).join('-').trim();
            const complement = (this.form.complement || '').trim();
            const city = this.form.cityName || '';
            return [street, number, complement, city].filter(Boolean).join(', ');
        },
        submitAddress() {
            const payload = {
                city_id: this.form.city || null,
                city_name: this.form.cityName || null,
                type_of_street: this.form.streetType || null,
                street: this.form.streetName || null,
                has_number: this.form.noNumber ? '0' : '1',
                number_1: this.form.noNumber ? null : (this.form.numberOne || null),
                number_2: this.form.noNumber ? null : (this.form.numberTwo || null),
                additional_information: this.form.complement || null,
                full_address: this.buildFullAddress() || null,
                status: '1'
            };
            this.$emit('confirm', payload);
        }
    }
};
</script>

<template>
    <div v-if="asInput" class="address-modal-trigger">
        <label :for="resolvedInputId" class="form-label">Dirección</label>
        <input
            :id="resolvedInputId"
            :value="internalDisplayValue"
            type="text"
            class="form-control"
            maxlength="150"
            placeholder="Haz clic para editar dirección"
            readonly
            @click="open"
            @focus="open"
            style="cursor: pointer;"
        >
    </div>

    <div v-if="modelValue" :id="modalId" class="address-modal-overlay" @click.self="close">
        <div class="address-modal card">
            <div class="address-modal__header">
                <div>
                    <h5 class="mb-0 fw-semibold">Dirección</h5>
                    <small class="text-muted">Completa los campos</small>
                </div>
                <button type="button" class="btn-close" aria-label="Cerrar" @click="close"></button>
            </div>
            <div class="address-modal__content">
                <section class="map-placeholder">
                    <div style="height: 100%; width: 100%; min-height: 375px; z-index: 1;">
                        <l-map 
                            ref="leafletMap" 
                            v-model:zoom="map.zoom" 
                            :center="map.center" 
                            :use-global-leaflet="false"
                        >
                            <l-tile-layer
                                url="https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png"
                                layer-type="base"
                                name="OpenStreetMap"
                            ></l-tile-layer>
                            <l-marker :lat-lng="map.marker"></l-marker>
                        </l-map>
                    </div>
                </section>
                <section class="address-form">
                    <div class="mb-3">
                        <label class="form-label">Ciudad</label>
                        <Select2
                            v-model="form.city"
                            :options="cityOptions"
                            placeholder="Seleccione una ciudad..."
                            :modal-id="modalId"
                        />
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo de calle</label>
                            <Select2
                                v-model="form.streetType"
                                :options="streetTypeOptions"
                                placeholder="Seleccione..."
                                :modal-id="modalId"
                            />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ form.streetType || 'Calle'}}</label>
                            <input
                                v-model="form.streetName"
                                type="text"
                                class="form-control"
                                placeholder="5E"
                            >
                        </div>
                    </div>
                    <div class="form-check my-3">
                        <input id="no-number" v-model="form.noNumber" class="form-check-input" type="checkbox">
                        <label class="form-check-label" for="no-number">
                            No tengo número
                        </label>
                    </div>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label">Número</label>
                            <input
                                v-model="form.numberOne"
                                type="text"
                                class="form-control"
                                placeholder="28A"
                                :disabled="form.noNumber"
                            >
                        </div>
                        <div class="col-md-2 d-flex justify-content-center align-items-end">
                            <span class="text-muted mb-2" style="font-size:1rem;">-</span>
                        </div>
                        <div class="col-md-5">
                            <input
                                v-model="form.numberTwo"
                                type="text"
                                class="form-control"
                                placeholder="56"
                                :disabled="form.noNumber"
                            >
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Complemento</label>
                        <input
                            v-model="form.complement"
                            type="text"
                            class="form-control"
                            placeholder="Ej. Barrio, Torre 7, apto 3, etc."
                        >
                    </div>
                </section>
            </div>
            <div class="address-modal__footer">
                <button type="button" class="btn btn-primary" @click="submitAddress">
                    Guardar
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.address-modal-overlay {
    position: fixed;
    inset: 0;
    background-color: rgba(14, 26, 48, 0.45);
    z-index: 1060;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.address-modal {
    width: 100%;
    max-width: 1040px;
    border: 0;
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 20px 48px rgba(0, 0, 0, 0.2);
}

.address-modal__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e7e9ef;
    gap: 1rem;
}

.address-modal__content {
    display: grid;
    grid-template-columns: minmax(320px, 1fr) minmax(320px, 1fr);
    gap: 1rem;
    padding: 1rem 1.25rem;
}

.map-placeholder {
    border: 1px solid #e2e5ec;
    border-radius: 0.35rem;
    background: #f8f9fc;
    min-height: 375px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
}

.map-placeholder__tabs {
    padding: 0.5rem;
    border-bottom: 1px solid #e2e5ec;
    display: flex;
    gap: 0.5rem;
}

.map-placeholder__tabs .active {
    background-color: #fff;
    border-color: #d5dae5;
    font-weight: 600;
}

.map-placeholder__body {
    flex: 1;
    display: grid;
    place-items: center;
    text-align: center;
    padding: 1rem;
}

.map-placeholder__icon {
    width: 56px;
    height: 56px;
    border-radius: 999px;
    background-color: #ffe6ea;
    color: #e60023;
    display: grid;
    place-items: center;
    font-size: 1.5rem;
    margin-bottom: 0.65rem;
}

.address-form .form-label {
    margin-bottom: 0.4rem;
    font-weight: 600;
    color: #384458;
}

.address-modal__footer {
    border-top: 1px solid #e7e9ef;
    padding: 0.85rem 1.25rem;
    display: flex;
    justify-content: flex-end;
}

@media (max-width: 991px) {
    .address-modal__content {
        grid-template-columns: 1fr;
    }

    .map-placeholder {
        min-height: 260px;
    }
}

:deep(.vue-leaflet__container) {
    height: 100% !important;
    width: 100% !important;
}
</style>
