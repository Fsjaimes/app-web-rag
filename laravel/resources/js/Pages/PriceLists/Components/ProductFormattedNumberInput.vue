<script>
import Cleave from 'cleave.js';

/**
 * Formato numérico con Cleave (miles/decimales es-CO), solo el input (form-control).
 */
export default {
    name: 'ProductFormattedNumberInput',

    props: {
        modelValue: {
            type: [String, Number, null],
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
        max: {
            type: Number,
            default: null,
        },
        prefix: {
            type: String,
            default: '',
        },
        suffix: {
            type: String,
            default: '',
        },
    },

    emits: ['update:modelValue', 'keydown'],

    data() {
        return {
            cleave: null,
        };
    },

    computed: {
        controlClass() {
            const parts = ['form-control', 'product-fn-price-input'];
            if (this.hasPrefix) {
                parts.push('product-fn-price-input--has-prefix');
            }
            if (this.hasSuffix) {
                parts.push('product-fn-price-input--has-suffix');
            }
            const extra = this.inputClass.trim();
            if (extra) {
                parts.push(extra);
            }
            return parts.join(' ');
        },
        hasPrefix() {
            return this.prefix.trim() !== '';
        },
        hasSuffix() {
            return this.suffix.trim() !== '';
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
                const normalizedValue = this.toCanonicalNumberString(event.target.rawValue);
                const clampedValue = this.clampToMax(normalizedValue);
                if (clampedValue !== normalizedValue) {
                    this.cleave.setRawValue(clampedValue);
                }
                this.$emit('update:modelValue', clampedValue);
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
        max() {
            const currentRaw = this.normalizeRawValue(this.cleave?.getRawValue());
            const clamped = this.clampToMax(currentRaw);
            if (clamped !== currentRaw) {
                this.setFormattedValue(clamped);
                this.$emit('update:modelValue', clamped);
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

            const normalizedValue = this.clampToMax(this.toCanonicalNumberString(value));
            this.cleave.setRawValue(normalizedValue);
        },
        clampToMax(value) {
            if (this.max === null || this.max === undefined || value === '') {
                return value;
            }
            const numericValue = Number(value);
            if (!Number.isFinite(numericValue)) {
                return '';
            }
            if (numericValue <= this.max) {
                return value;
            }
            return String(this.max);
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
    <div class="product-fn-input-wrap">
        <span v-if="hasPrefix" class="product-fn-affix product-fn-affix--prefix">{{ prefix }}</span>
        <input
            ref="input"
            type="text"
            inputmode="decimal"
            autocomplete="off"
            :class="controlClass"
            :placeholder="placeholder"
            @keydown="$emit('keydown', $event)"
        >
        <span v-if="hasSuffix" class="product-fn-affix product-fn-affix--suffix">{{ suffix }}</span>
    </div>
</template>

<style scoped>
.product-fn-input-wrap {
    position: relative;
    width: 100%;
}

.product-fn-price-input {
    border: 1px solid #f3f3f3;
    width: 100%;
    border-radius: 0;
    font-size: 0.75rem;
    min-height: 2.75rem;
    padding: 0.25rem 0.45rem;
}

.product-fn-price-input--has-prefix {
    padding-left: 1.35rem;
}

.product-fn-price-input--has-suffix {
    padding-right: 1.35rem;
}

.product-fn-price-input:focus {
    border-color: var(--vz-primary);
    background-color: rgba(var(--vz-primary-rgb), 0.05);
    z-index: 2;
    position: relative;
}

.product-fn-affix {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 3;
    color: #6c757d;
    font-size: 0.75rem;
    pointer-events: none;
    line-height: 1;
}

.product-fn-affix--prefix {
    left: 0.45rem;
}

.product-fn-affix--suffix {
    right: 0.45rem;
}
</style>
