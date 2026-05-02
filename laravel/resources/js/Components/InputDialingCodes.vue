<script>
import { onMounted, onBeforeUnmount } from 'vue';
import { useFetchPetition } from '@/Composables/useFetchPetition.js';

const { fetchPetition } = useFetchPetition();

export default {
    name: 'InputDialingCodes',

    props: {
        modelValue: { type: String, default: '' },
        selectedCode: { type: String, default: '' },
        /** Resalta borde de error (mismo estilo que Select2 / form-control--validation-error). */
        invalid: { type: Boolean, default: false },
    },

    emits: ['update:modelValue', 'update:selectedCode'],

    data() {
        return {
            dialingCodes: [],
            searchTerm: '',
            loading: false,
            error: null,
            showDropdown: false,
        };
    },

    computed: {
        filteredDialingCodes() {
            const term = this.searchTerm.toLowerCase();
            return this.dialingCodes.filter(
                code =>
                    code.description.toLowerCase().includes(term) ||
                    code.code.toLowerCase().includes(term)
            );
        },

        getSelectedDescription() {
            const selected = this.dialingCodes.find(c => c.code === this.selectedCode);
            return selected ? selected.description : null;
        },
        filteredDialingCodes() {
            const term = this.searchTerm.toLowerCase();
            return this.dialingCodes.filter(
                code =>
                    code.description.toLowerCase().includes(term) ||
                    code.code.toLowerCase().includes(term)
            );
        },
    },

    mounted() {
        this.getDialingCodes();
        // document.addEventListener('click', this.handleClickOutside);
    },

    beforeUnmount() {
        // document.removeEventListener('click', this.handleClickOutside);
    },

    methods: {
        async getDialingCodes() {
            this.loading = true;
            this.error = null;
            try {
                const response = await fetchPetition('/dialing-codes', { method: 'GET' });
                this.dialingCodes = response?.data.data.data || [];
                if (!this.selectedCode || this.selectedCode === '' || this.selectedCode === '+') {
                    const defaultCode = this.dialingCodes.find(c => c.code === '+57' || c.code === '57');
                    const codeToEmit = defaultCode ? defaultCode.code : '+57';
                    this.$emit('update:selectedCode', codeToEmit);
                }
            } catch (err) {
                console.error('Error al obtener los Dialing Codes:', err);
                this.error = 'No se pudieron cargar los códigos telefónicos.';
            } finally {
                this.loading = false;
            }
        },

        updateCode(code) {
            this.$emit('update:selectedCode', code.code);
            this.showDropdown = false;
        },

        updateInput(event) {
            this.$emit('update:modelValue', event.target.value);
        },

        // handleClickOutside(event) {
        //     if (this.showDropdown && !this.$el.contains(event.target)) {
        //         this.showDropdown = false;
        //     }
        // },
    },
};
</script>

<template>
    <div class="input-group position-relative">
        <!-- Botón de prefijo -->
        <button
            type="button"
            class="btn btn-light border accion d-flex align-items-center"
            @click="showDropdown = !showDropdown"
            :disabled="loading || error"
        >
            <span v-if="selectedCode">{{ selectedCode }}</span>
            <span v-else class="text-muted">
                {{ loading ? 'Cargando...' : error ? 'Error' : '+ Indicativo' }}
            </span>
        </button>

        <!-- Input del número -->
        <input
            type="number"
            class="form-control rounded-end flag-input accion"
            :class="{ 'form-control--validation-error': invalid }"
            :value="modelValue"
            placeholder="Ingresar Teléfono"
            @input="updateInput"
            :disabled="loading"
        />

        <!-- Dropdown dinámico -->
        <div
            v-if="showDropdown"
            class="dropdown-menu show shadow w-50"
            style="position: absolute; top: 100%; left: 0; z-index: 1000;"
        >
            <div class="p-2 px-3 pt-1 searchlist-input">
                <input
                    type="text"
                    v-model="searchTerm"
                    placeholder="Buscar..."
                    class="form-control form-control-sm border search-countryList w-100"
                />
            </div>

            <ul class="list-unstyled dropdown-menu-list mb-0" style="max-height: 220px; overflow-y: auto;">
                <li
                    v-for="code in filteredDialingCodes"
                    :key="code.id || code.code"
                    class="dropdown-item d-flex align-items-center"
                    :class="{ 'selected-code': code.description === getSelectedDescription }"
                    style="cursor: pointer;"
                    @click="updateCode(code)"
                >
                    <div class="d-flex align-items-center w-100">
                        <span class="country-name">{{ code.description }}</span>
                        <span class="countrylist-codeno text-muted ms-2">{{ code.code }}</span>
                    </div>
                </li>

                <li v-if="filteredDialingCodes.length === 0" class="dropdown-item text-muted text-center small">
                    No se encontraron resultados
                </li>
            </ul>
        </div>
    </div>

    <!-- Mensaje de error -->
    <div v-if="error" class="text-danger mt-1 small">{{ error }}</div>
</template>

<style scoped>
.input-group {
    width: 100%;
}

/* Fondo gris claro para el código seleccionado */
.selected-code {
    background-color: #f1f1f1 !important;
    font-weight: 500;
}

/* Hover */
.dropdown-menu-list li:hover {
    background-color: #f8f9fa;
}

/* Búsqueda */
.searchlist-input input {
    font-size: 0.875rem;
}

/* Ajuste visual del dropdown */
.dropdown-menu {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}
</style>
