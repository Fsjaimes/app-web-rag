<script>
import Cleave from 'cleave.js';

export default {
    name: 'FormattedNumberInput',

    props: {
        modelValue: {
            type: [String, Number, null],
            default: '',
        },
        icon: {
            type: String,
            default: '',
        },
        placeholder: {
            type: String,
            default: '',
        },
        inputClass: {
            type: String,
            default: '',
        },
    },

    emits: ['update:modelValue'],

    data() {
        return {
            cleave: null,
        };
    },

    computed: {
        hasIcon() {
            return Boolean(this.icon);
        },
        resolvedInputClass() {
            const baseClass = this.hasIcon ? 'form-control form-control-icon' : 'form-control';
            return `${baseClass} ${this.inputClass}`.trim();
        },
    },

    mounted() {
        this.cleave = new Cleave(this.$refs.input, {
            numeral: true,
            numeralDecimalScale: 2,
            numeralDecimalMark: ',',
            delimiter: '.',
            numeralThousandsGroupStyle: 'thousand',
            numeralPositiveOnly: true,
            onValueChanged: (event) => {
                const normalizedValue = event.target.rawValue.replace(',', '.');
                this.$emit('update:modelValue', normalizedValue);
            },
        });

        this.setFormattedValue(this.modelValue);
    },

    beforeUnmount() {
        if (this.cleave) {
            this.cleave.destroy();
        }
    },

    watch: {
        modelValue(newValue) {
            const normalizedIncoming = this.normalizeRawValue(newValue);
            const currentRaw = this.normalizeRawValue(this.cleave?.getRawValue());

            if (normalizedIncoming !== currentRaw) {
                this.setFormattedValue(newValue);
            }
        },
    },

    methods: {
        normalizeRawValue(value) {
            if (value === null || value === undefined || value === '') {
                return '';
            }

            return this.toCanonicalNumberString(value);
        },
        setFormattedValue(value) {
            if (!this.cleave) {
                return;
            }

            if (value === null || value === undefined || value === '') {
                this.cleave.setRawValue('');
                return;
            }

            const normalizedValue = this.toCanonicalNumberString(value);
            this.cleave.setRawValue(normalizedValue);
        },
        toCanonicalNumberString(value) {
            const stringValue = String(value).trim();

            if (!stringValue) {
                return '';
            }

            if (stringValue.includes(',')) {
                return stringValue.replace(/\./g, '').replace(',', '.');
            }

            return stringValue;
        },
    },
};
</script>

<template>
    <div v-if="hasIcon" class="form-icon">
        <input
            ref="input"
            type="text"
            inputmode="decimal"
            :class="resolvedInputClass"
            :placeholder="placeholder"
        >
        <i :class="icon"></i>
    </div>

    <input
        v-else
        ref="input"
        type="text"
        inputmode="decimal"
        :class="resolvedInputClass"
        :placeholder="placeholder"
    >
</template>
