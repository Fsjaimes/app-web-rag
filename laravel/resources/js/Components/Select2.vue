<script>
export default {
    name: 'Select2',

    props: {
        modelValue: [String, Number, Array],
        options: { type: Array, default: () => [] },
        placeholder: { type: String, default: 'Seleccione...' },
        disabled: { type: Boolean, default: false },
        multiple: { type: Boolean, default: false },
        id: { type: String, default: () => `select2-${Math.floor(Math.random() * 100000)}` },
        width: { type: String, default: '100%' },

        // ⚙️ Campos configurables
        valueField: { type: String, default: 'id' },
        textField: { type: String, default: 'text' },

        // ⚙️ Plantilla personalizada para mostrar texto (opcional)
        textTemplate: {
            type: Function,
            default: null,
        },

        // Prop para pasar el id del contenedor del modal u offcanvas
        modalId: { type: String, default: '' },

        allOption: { type: Boolean, default: false },

        // Habilita/deshabilita la lógica de deshabilitar opciones individuales
        enableDisabledOptions: { type: Boolean, default: false },
        // Puede recibir un id o un arreglo de ids para deshabilitar
        disabledOptionIds: { type: [Array, String, Number], default: () => [] },

        /** Resalta borde rojo en el contenedor Select2 (sin usar is-invalid en el select nativo). */
        showValidationError: { type: Boolean, default: false },
    },

    emits: ['update:modelValue', 'change'],

    mounted() {
        this.initSelect2()
    },

    watch: {
        options: {
            deep: true,
            handler() {
                this.reinitSelect2()
            },
        },
        modelValue(value) {
            const synced =
                this.multiple && Array.isArray(value)
                    ? this.normalizeMultipleValue(value)
                    : value
            $(this.$el).val(synced).trigger('change.select2')

            const $container = $(this.$el).next('.select2-container')
            if ($container && $container.length) {
                const hasValue = (val) => {
                    if (Array.isArray(val)) return val.length > 0
                    return val !== null && val !== undefined && String(val) !== ''
                }

                if (hasValue(synced)) {
                    $container.addClass('select2-container--has-value')
                } else {
                    $container.removeClass('select2-container--has-value')
                }
                this.applyValidationBorderClass()
            }
        },
        disabled(newValue) {
            this.reinitSelect2(); // Re-inicializa el select2
        },
        enableDisabledOptions() {
            this.reinitSelect2();
        },
        disabledOptionIds: {
            deep: true,
            handler() {
                this.reinitSelect2();
            },
        },
        showValidationError() {
            this.$nextTick(() => this.applyValidationBorderClass());
        },
    },

    beforeUnmount() {
        $(this.$el).off().select2('destroy')
    },

    methods: {
        getDisabledOptionIdsSet() {
            if (!this.enableDisabledOptions) return new Set();

            const rawIds = Array.isArray(this.disabledOptionIds)
                ? this.disabledOptionIds
                : [this.disabledOptionIds];

            return new Set(
                rawIds
                    .filter(id => id !== null && id !== undefined && String(id).trim() !== '')
                    .map(id => String(id))
            );
        },
        isOptionDisabled(opt) {
            const id = opt?.[this.valueField];
            if (id === null || id === undefined) return false;

            return this.getDisabledOptionIdsSet().has(String(id));
        },

        /**
         * En modo múltiple, el <option> vacío y valores 0 (p. ej. “Todos”) no deben quedar en el v-model.
         */
        normalizeMultipleValue(raw) {
            if (!Array.isArray(raw)) {
                return [];
            }
            return raw.filter((v) => {
                if (v === '' || v === null || v === undefined) {
                    return false;
                }
                const n = Number(v);
                if (Number.isInteger(n) && n === 0) {
                    return false;
                }
                return true;
            });
        },
        applyValidationBorderClass() {
            const $el = $(this.$el);
            const $c = $el.next('.select2-container');
            if (!$c.length) return;
            if (this.showValidationError) {
                $c.addClass('select2-container--validation-error');
            } else {
                $c.removeClass('select2-container--validation-error');
            }
        },
        initSelect2() {
        const vm = this

        const hasValue = (val) => {
            if (Array.isArray(val)) return val.length > 0
            return val !== null && val !== undefined && String(val) !== ''
        }

        const dropdownParent = this.modalId ? $(`#${this.modalId}`) : 'body';

        const $el = $(this.$el)

        $el
            .select2({
                placeholder: this.placeholder,
                allowClear: !this.multiple, // Solo permitir clear cuando NO es múltiple
                // data: this.options.map(opt => ({
                //     id: opt[this.valueField],
                //     text: this.textTemplate
                //         ? this.textTemplate(opt)
                //         : opt[this.textField],
                // })),
                disabled: this.disabled,
                multiple: this.multiple,
                width: this.width,
                language: {
                    noResults: () => 'Sin resultados',
                    searching: () => 'Buscando...',
                },
                dropdownParent: dropdownParent,
            })

        // 🔹 Setear valor inicial (multiselect: sin 0 ni vacíos que Select2 mezcla con la opción “Todos” / placeholder)
        const initialVal =
            vm.multiple && Array.isArray(vm.modelValue)
                ? vm.normalizeMultipleValue(vm.modelValue)
                : vm.modelValue
        $el.val(initialVal).trigger('change.select2')
        $el.on('select2:clear', function () {
            // Cierra el dropdown si estaba abierto
            $(this).select2('close')
        })
        // 🔹 Referencia al contenedor visual de Select2

        const container = $el.next('.select2-container')

        // 🔹 Aplicar clase al cargar
        if (hasValue(initialVal)) {
            container.addClass('select2-container--has-value')
        } else {
            container.removeClass('select2-container--has-value')
        }

        // 🔹 Cuando cambia
        $el.on('change', function () {
            let value = $(this).val()
            if (vm.multiple) {
                const raw = value == null ? [] : Array.isArray(value) ? value : [value]
                value = vm.normalizeMultipleValue(raw)
            }

            vm.$emit('update:modelValue', value)
            vm.$emit('change', value)

            const container = $(this).next('.select2-container')

            if (Array.isArray(value) ? value.length > 0 : value !== null && value !== undefined && String(value) !== '') {
                container.addClass('select2-container--has-value')
            } else {
                container.removeClass('select2-container--has-value')
            }
            vm.applyValidationBorderClass()
        })
        this.$nextTick(() => {
            vm.applyValidationBorderClass()
        })
    },

        reinitSelect2() {
            const el = this.$el;

            // Si no hay elemento, salir
            if (!el) return;

            // Si NO existe select2 en este elemento, simplemente inicialízalo
            if (!$(el).data('select2')) {
                this.$nextTick(() => this.initSelect2());
                return;
            }

            // Si SI existe, destruirlo y volverlo a crear
            try {
                $(el).select2('destroy');
            } catch (e) {
                console.warn("Select2 intentó destruirse sin estar inicializado");
            }

            // Esperar a que Vue actualice el DOM con las nuevas <option>
            this.$nextTick(() => {
                this.initSelect2();
            });
        },

    },
}
</script>

<template>
    <select class="form-control" :id="id" :disabled="disabled" :class="{ 'select2-disabled-light': disabled }" style="width: 100%">
        <!-- Placeholder vacío solo en single; en multiple provoca tags fantasma / cruce con value 0 (“Todos”) -->
        <option v-if="!multiple" value=""></option>
        <option :value="0" selected v-if="allOption && !multiple">Todos</option>
        <option
            v-for="opt in options"
            :key="opt[valueField]"
            :value="opt[valueField]"
            :disabled="isOptionDisabled(opt)"
        >
            {{ textTemplate ? textTemplate(opt) : opt[textField] }}
        </option>
    </select>
</template>

<style >
    /* Forzamos que luzca igual a Bootstrap form-control */
    .select2-container .select2-selection--single {
        position: relative !important; /* Referencia para posicionar el clear a la derecha */
        height: 38px !important;
        display: flex !important;
        align-items: center !important;
        border: 1px solid #ced4da !important;
        border-radius: 0.375rem !important;
        font-size: 0.9375rem !important;
        background-color: #fff !important;
        padding-left: 0.5rem !important;
        padding-right: 2.25rem !important; /* Espacio fijo para la X de clear */
    }

    /* Flecha alineada correctamente */
    .select2-container--default .select2-selection__arrow {
        height: 38px !important;
        right: 8px !important;
    }

    /* Texto centrado verticalmente */
    .select2-container--default .select2-selection__rendered {
        margin: 5px;
        color: #212529 !important;
    }
    /* 🌤️ Estado disabled con estilo light */
    .select2-disabled-light ~ .select2-container .select2-selection {
        background-color: #e9ecef !important;    /* Bootstrap disabled bg */
        border-color: #ced4da !important;        /* Bootstrap default border */
        color: #6c757d !important;               /* Bootstrap disabled text */
        cursor: not-allowed !important;
        opacity: 1 !important;
    }

    /* Deshabilitar el hover */
    .select2-disabled-light ~ .select2-container .select2-selection:hover {
        background-color: #e9ecef !important;
    }

    /* 🔹 TAGS del Select2 múltiple */
    .select2-container .select2-selection--multiple .select2-selection__choice {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.25rem !important;
        background-color: var(--vz-primary) !important;
        border: 1px solid #ced4da !important;
        border-radius: 0.25rem !important;
        color: var(--vz-white) !important;
        margin: 3px 3px !important;
        padding: 1px 6px !important;
        font-size: 0.8rem !important;
        line-height: 1rem !important;
    }

    /* Compatibilidad con Select2 4.1+ (usa botón + display internos) */
    .select2-container .select2-selection--multiple .select2-selection__choice__display {
        color: inherit !important;
        padding: 0 !important;
    }

    .select2-container .select2-selection--multiple .select2-selection__choice__remove {
        position: static !important;
        order: -1 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 14px !important;
        height: 14px !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        border-radius: 2px !important;
        background: transparent !important;
        color: #ffffff !important;
        font-size: 0.9rem !important;
        line-height: 1 !important;
        cursor: pointer !important;
    }

    .select2-container .select2-selection--multiple .select2-selection__choice__remove:hover {
        background-color: rgba(255, 255, 255, 0.2) !important;
        color: #ffffff !important;
    }

    .select2-container .select2-selection--multiple {
        min-height: 38px !important; /* Altura estándar de Bootstrap */
        height: auto !important;     /* Permite que crezca si hay muchos tags */
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        align-content: flex-start !important;
        padding: 0 4px !important;
        border: 1px solid #ced4da !important;
        border-radius: 0.375rem !important;
        background-color: #fff !important;
        cursor: pointer !important;
        box-sizing: border-box !important;
    }

    .select2-container .select2-selection--multiple .select2-search__field {
        margin: 3px 2px !important;
        height: 24px !important;
        line-height: 24px !important;
        font-size: 0.90rem !important;
        cursor: pointer !important;
    }

    /* Ocultar flecha cuando el select tiene valor */
    .select2-container--has-value .select2-selection__arrow {
        display: none !important;
    }

    /* Ocultar la X cuando NO hay valor */
    .select2-container:not(.select2-container--has-value)
    .select2-selection__clear {
        display: none !important;
    }

    /* X de clear fija a la derecha del select single (relativa a .select2-selection--single) */
    .select2-container .select2-selection--single .select2-selection__clear {
        position: absolute !important;
        right: 0.5rem !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        margin: 0 !important;
        font-size: 1.125rem !important;
        line-height: 1 !important;
        cursor: pointer !important;
        color: rgb(82, 84, 80) !important;
        padding: 0.25rem !important;
        transition: color 0.15s;
    }

    .select2-container .select2-selection--single .select2-selection__clear:hover {
        color: #dc3545 !important;
    }

    /* Ocultar la X de clear cuando el select es múltiple */
    .select2-container--default.select2-container--focus .select2-selection--multiple .select2-selection__clear,
    .select2-container--default .select2-selection--multiple .select2-selection__clear {
        display: none !important;
    }

    /*
     * Validación: borde rojo (mismo criterio que Sat).
     * Mayor especificidad + prefijo body para ganar al tema (_select2.scss) y al CSS
     * por defecto de Select2 en :focus / :open (borde oscuro).
     */
    body .select2-container--default.select2-container--validation-error .select2-selection--single,
    body .select2-container--default.select2-container--validation-error .select2-selection--multiple {
        border-color: #dc3545 !important;
        box-shadow: none !important;
    }

    body .select2-container--default.select2-container--validation-error.select2-container--focus .select2-selection--single,
    body .select2-container--default.select2-container--validation-error.select2-container--open .select2-selection--single,
    body .select2-container--default.select2-container--validation-error.select2-container--focus .select2-selection--multiple,
    body .select2-container--default.select2-container--validation-error.select2-container--open .select2-selection--multiple {
        border-color: #dc3545 !important;
        box-shadow: none !important;
        outline: none !important;
    }

    .select2-container.select2-container--validation-error.select2-container--default .select2-selection--single,
    .select2-container.select2-container--validation-error.select2-container--default .select2-selection--multiple {
        border-color: #dc3545 !important;
        box-shadow: none !important;
    }

    .select2-container.select2-container--validation-error.select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container.select2-container--validation-error.select2-container--default.select2-container--open .select2-selection--single,
    .select2-container.select2-container--validation-error.select2-container--default.select2-container--focus .select2-selection--multiple,
    .select2-container.select2-container--validation-error.select2-container--default.select2-container--open .select2-selection--multiple {
        border-color: #dc3545 !important;
        box-shadow: none !important;
        outline: none !important;
    }

    .form-control.form-control--validation-error {
        border-color: #dc3545 !important;
        box-shadow: none !important;
    }

    .form-control.form-control--validation-error:focus {
        border-color: #dc3545 !important;
        box-shadow: none !important;
    }

</style>
