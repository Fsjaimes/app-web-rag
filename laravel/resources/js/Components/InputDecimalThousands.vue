<script>
import Cleave from 'cleave.js'

export default {
    name: 'InputDecimalThousands',

    props: {
        modelValue: [String, Number],
        placeholder: { type: String, default: '0' },
        decimalScale: { type: Number, default: 2 }, // decimales dinámicos
        textAlign: {
            type: String,
            default: 'left',
            validator: (v) => ['left', 'right', 'center'].includes(v),
        },
    },

    emits: ['update:modelValue'],

    data() {
        return {
            cleaveInstance: null,
            displayValue: this.modelValue ?? '',
        }
    },

    watch: {
        modelValue(newVal) {
            if (this.cleaveInstance) {
                const raw = this.cleaveInstance.getRawValue()
                if (String(newVal) !== raw) {
                    this.cleaveInstance.setRawValue(newVal)
                    this.displayValue = this.cleaveInstance.getFormattedValue()
                }
            }
        },
    },

    mounted() {
        console.log(this.modelValue);
        
        // 🔥 Inicializa usando la función numeralFormatting interna
        this.numeralFormatting(this.$refs.inputRef, this.decimalScale > 0)
    },

    beforeUnmount() {
        if (this.cleaveInstance) {
            this.cleaveInstance.destroy()
            this.cleaveInstance = null
        }
    },

    methods: {
        numeralFormatting(el, allowDecimals = true) {
            // Si ya existe, destruir
            if (el.cleave) el.cleave.destroy()

            // Crear nueva instancia Cleave
            el.cleave = new Cleave(el, {
                numeral: true,
                numeralThousandsGroupStyle: 'thousand',
                delimiter: '.',
                numeralDecimalMark: ',',
                numeralDecimalScale: allowDecimals ? this.decimalScale : 0,
                numeralIntegerScale: 15,
                onValueChanged: (e) => {
                    console.log(e.target.value);
                    
                    this.displayValue = e.target.value
                    // 🔥 Emitir valor limpio (sin formato)
                    this.$emit('update:modelValue', e.target.rawValue)
                },
            })

            this.cleaveInstance = el.cleave
        },
        focus() {
        this.$refs.inputRef?.focus();
    },
    },
}
</script>

<template>
    <input
        ref="inputRef"
        type="text"
        :placeholder="placeholder"
        class="form-control"
        :class="{
            'text-start': textAlign === 'left',
            'text-end': textAlign === 'right',
            'text-center': textAlign === 'center',
        }"
        :value="displayValue"
    />
</template>

<style scoped>
/* sin estilos adicionales */
</style>