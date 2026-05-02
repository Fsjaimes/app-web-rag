

<script>
    export default {
    name: 'SearchInput',

    props: {
        modelValue: String,
        placeholder: {
        type: String,
        default: 'Buscar...'
        },
        delay: {
        type: Number,
        default: 300
        }
    },

    emits: ['update:modelValue'],

    data() {
        return {
        internalValue: this.modelValue,
        debounceTimer: null,
        }
    },

    watch: {
        modelValue(val) {
        this.internalValue = val;
        console.log(val);
        },

        internalValue(val) {
        clearTimeout(this.debounceTimer);

        this.debounceTimer = setTimeout(() => {
            this.$emit('update:modelValue', val);
        }, this.delay);
        }
    },

    methods: {
        clearInput() {
        this.internalValue = '';
        }
    }
    }
</script>
<template>
    <div class="search-input-wrapper">
        <span class="bx bx-search-alt-2 search-icon"></span>

        <input
        type="text"
        class="form-control search-input"
        :placeholder="placeholder"
        v-model="internalValue"
        />

        <span 
        v-if="internalValue"
        class="clear-icon"
        @click="clearInput"
        >
        ✖
        </span>
    </div>
</template>
<style scoped>
.search-input-wrapper {
    position: relative;
}

.search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    opacity: 0.6;
    font-size: 18px;
}

.search-input {
    padding-left: 32px;
    padding-right: 30px;
    border-radius: 8px;
    border: 1px solid #ddd;
    transition: all 0.2s ease;
}

.search-input:focus {
    border-color: #42bdff;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15);
}

.clear-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 12px;
    opacity: 0.6;
}

.clear-icon:hover {
    opacity: 1;
}
</style>