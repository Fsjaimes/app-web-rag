<script>
import axios from 'axios'

export default {
    name: 'AutocompleteSearchInput',

    props: {
        modelValue: {
            // En Vue 3, `type` debe contener constructores válidos (no `null`)
            type: [Array, Number, String],
            default: null
        },

        searchUrl: {
            type: String,
            required: true
        },

        fields: {
            type: Array,
            default: () => []
        },

        placeholder: {
            type: String,
            default: 'Buscar...'
        },

        limit: {
            type: Number,
            default: 10
        },

        minChars: {
            type: Number,
            default: 3
        },

        labelField: {
            type: String,
            default: 'search_name'
        },

        labelTemplate: {
            type: String,
            default: null
        },

        showSelectedLabel: {
            type: Boolean,
            default: true // comportamiento actual por defecto
        },

        disabled: {
            type: Boolean,
            default: false,
        },

        blockBy: {
            type: String,
            default: null, // null = usa valueField (id)
        },

        valueField: {
            type: String,
            default: 'id'
        },

        multiple: {
            type: Boolean,
            default: true
        },

        filters: {
            type: Object,
            default: () => ({})
        },

        selectFields: {
            type: Array,
            default: () => []
        },

        optionHeaderField: {
            type: String,
            default: null
        },

        optionBodyFields: {
            type: Array,
            default: () => []
        },

        initialOptions: {
            type: [Array, Object],
            default: null
        }
    },

    data() {
        return {
            search: '',
            results: [],
            loading: false,
            selectedItems: [],
            hasSingleSelected: false,
            clearOnNextType: false,
            suppressSearch: false,
            highlightedIndex: -1,
            itemRefs: [],
            isFocused: false,
            /** Estilos inline para lista en Teleport (position: fixed), evita recorte por overflow de tablas/modales */
            dropdownFixedStyle: null,
            portalListenersBound: false,
        }
    },

    computed: {
        isSingle() {
            return !this.multiple
        },
        hasSelection() {
            if (this.multiple) {
                return Array.isArray(this.modelValue) && this.modelValue.length > 0
            }

            // ✅ single: null/undefined/"" => NO selección
            return this.modelValue !== null &&
                this.modelValue !== undefined &&
                this.modelValue !== ''
        }
    },

    watch: {

        disabled(newVal) {
            if (newVal) {
                this.results = [];
                this.search = '';
                this.highlightedIndex = -1;
            }
        },

        search(value) {
            if (this.disabled) return;
            if (this.suppressSearch) {
                this.suppressSearch = false
                return
            }

            if (value.length < this.minChars) {
                this.results = []
                return
            }

            this.searchRequest(value)
        },

        modelValue: {
            immediate: true,
            handler(value) {
                if (!this.multiple) {
                    const hasValidSingleValue =
                        value !== null &&
                        value !== undefined &&
                        value !== ''

                    if (!hasValidSingleValue) {
                        this.selectedItems = []
                        this.hasSingleSelected = false
                        this.clearOnNextType = false
                        this.suppressSearch = true
                        this.search = ''
                        this.results = []
                        this.highlightedIndex = -1
                        return
                    }

                    this.selectedItems = this.selectedItems.filter(
                        i => i[this.valueField] === value
                    )
                }
            }
        },
        initialOptions: {
            immediate: true,
            handler(value) {
                if (!value) return

                const options = Array.isArray(value) ? value : [value]
                const model = Array.isArray(this.modelValue) ? this.modelValue : [this.modelValue]

                const selectedItems = options.filter(opt =>
                    model.some(m => m == opt[this.valueField])
                )

                this.selectedItems = selectedItems

                // En modo single: mostrar la opción en el input (no hay v-for de badges)
                if (!this.multiple && selectedItems.length > 0) {
                    const opt = selectedItems[0]
                    this.hasSingleSelected = true
                    this.clearOnNextType = true
                    this.suppressSearch = true
                    this.search = this.showSelectedLabel ? this.buildLabel(opt) : ''
                }
            }
        },

        results: {
            handler(val) {
                if (!val || val.length === 0) {
                    this.dropdownFixedStyle = null
                    this.unbindPortalReposition()
                    return
                }
                this.bindPortalReposition()
            },
        },
    },

    methods: {
        async searchRequest(value) {
            if (this.disabled || !this.searchUrl) return;
            this.itemRefs = []
            this.highlightedIndex = -1
            this.loading = true

            // console.log(this.selectFields);
            try {
                const { disabled_ids: _omitDisabledIds, ...serverFilters } = this.filters || {}

                // Los objetos anidados en GET suelen serializarse mal; JSON evita filtros rotos en Laravel.
                const response = await axios.get(this.searchUrl, {
                    params: {
                        q: value,
                        fields: JSON.stringify(this.fields ?? []),
                        selectFields: JSON.stringify(this.selectFields ?? []),
                        limit: this.limit,
                        filters: JSON.stringify(serverFilters ?? {}),
                    },
                })

                this.results = response.data.data ?? []
            } catch (e) {
                console.error(e)
            } finally {
                this.loading = false
            }
        },

        selectItem(item) {
            if (this.disabled) return;
            const blockValue = this.getBlockValue(item)
            const value = item[this.valueField]

            if (
                this.multiple &&
                this.selectedItems.some(
                    selected => this.getBlockValue(selected) === blockValue
                )
            ) {
                return
            }

            if (!this.multiple) {
                this.selectedItems = [item]
                this.hasSingleSelected = true
                this.clearOnNextType = true
                this.suppressSearch = true
                this.search = this.showSelectedLabel
                    ? this.buildLabel(item)
                    : ''

                this.$emit('update:modelValue', value)
                this.$emit('change-option', item)
                this.results = []
                this.highlightedIndex = -1
                return
            }

            // if (this.modelValue.includes(value)) return

            this.selectedItems.push(item)
            this.$emit('update:modelValue', [...this.modelValue, value])
            this.$emit('change-option', item)
            this.suppressSearch = true
            this.search = ''
            this.results = []
            this.highlightedIndex = -1
        },

        // Define la identidad real del item (DOM + lógica).
        // Debe ser ÚNICO entre resultados, no necesariamente el id de BD.
        // (Por ejemplo cuando se van a seleccionar varios items, de diferentes tablas, caso en el que el id puede estar repetido)
        getBlockValue(item) {
            const field = this.blockBy || this.valueField
            return item[field]
        },

        removeItem(blockValue) {
            const field = this.blockBy || this.valueField

            // Encuentra el item que va a ser removido
            const removedItem = this.selectedItems.find(
                item => item[field] === blockValue
            );

            this.selectedItems = this.selectedItems.filter(
                item => item[field] !== blockValue
            )

            const newModelValue = this.selectedItems.map(
                item => item[this.valueField]
            )

            this.$emit('update:modelValue', newModelValue)
            this.$emit('change-option', null, removedItem) // null y luego el registro removido
        },

        handleKeydown(e) {
            if (this.disabled) {
                e.preventDefault();
                return;
            }
            if (this.isSingle && this.hasSingleSelected && this.clearOnNextType) {
                const controlKeys = [
                    'Shift','Control','Alt','Meta','Tab','Escape',
                    'ArrowUp','ArrowDown','ArrowLeft','ArrowRight','Enter'
                ]

                if (!controlKeys.includes(e.key)) {
                    this.selectedItems = []
                    this.hasSingleSelected = false
                    this.clearOnNextType = false
                    this.suppressSearch = false
                    this.search = ''
                    this.$emit('update:modelValue', null)
                    this.$emit('change-option', null)
                }
            }

            if (!this.results.length) return

            if (e.key === 'ArrowDown') {
                e.preventDefault()
                e.stopPropagation()
                this.highlightedIndex =
                    (this.highlightedIndex + 1) % this.results.length
                this.scrollToHighlighted()
            }

            if (e.key === 'ArrowUp') {
                e.preventDefault()
                e.stopPropagation()
                this.highlightedIndex =
                    (this.highlightedIndex - 1 + this.results.length) %
                    this.results.length
                this.scrollToHighlighted()
            }

            if (e.key === 'Enter') {
                e.preventDefault()
                e.stopPropagation()
                const item = this.results[this.highlightedIndex]
                if (item && !this.isDisabled(item)) {
                    this.selectItem(item)
                }
            }

            if (e.key === 'Escape') {
                e.stopPropagation()
                this.results = []
                this.highlightedIndex = -1

                const hasTypedText = String(this.search ?? '').trim().length > 0
                if (!hasTypedText) return

                if (this.multiple) {
                    this.suppressSearch = true
                    this.search = ''
                    return
                }

                if (!this.hasSelection) {
                    this.suppressSearch = true
                    this.search = ''
                    this.$emit('update:modelValue', null)
                    this.$emit('change-option', null)
                }
            }
        },

        handleClickOutside(e) {
            if (this.$el.contains(e.target)) {
                return
            }
            const portal = this.$refs.portalDropdown
            if (portal && typeof portal.contains === 'function' && portal.contains(e.target)) {
                return
            }

            this.results = []
            this.highlightedIndex = -1

            const hasTypedText = String(this.search ?? '').trim().length > 0

            if (!hasTypedText) return

            if (this.multiple) {
                this.suppressSearch = true
                this.search = ''
                return
            }

            if (!this.hasSelection) {
                this.suppressSearch = true
                this.search = ''
                this.$emit('update:modelValue', null)
                this.$emit('change-option', null)
            }
        },

        isDisabled(item) {
            const blockValue = this.getBlockValue(item)

            // 🔒 deshabilitados desde el padre
            if (
                this.filters?.disabled_ids &&
                this.filters.disabled_ids.includes(blockValue)
            ) {
                return true
            }

            // 🔒 ya seleccionado (multiple)
            if (this.multiple) {
                return this.selectedItems.some(
                    selected => this.getBlockValue(selected) === blockValue
                )
            }

            // 🔒 single
            return this.getBlockValue(this.selectedItems[0] ?? {}) === blockValue
        },

        focusInput() {
            this.$refs.input.focus()
        },

        highlightMatch(text) {
            const label = String(text ?? '')
            const q = String(this.search ?? '').trim()

            if (!q) return label

            const escaped = q.replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&')
            const regex = new RegExp(`(${escaped})`, 'ig')

            return label.replace(regex, '<span class="autocomplete-highlight">$1</span>')
        },

        setItemRef(el, index) {
            if (el) {
                this.itemRefs[index] = el
            }
        },

        scrollToHighlighted() {
            this.$nextTick(() => {
                const el = this.itemRefs[this.highlightedIndex]
                if (!el) return

                el.scrollIntoView({
                    block: 'nearest'
                })
            })
        },

        buildLabel(item) {
            if (!this.labelTemplate) {
                return item[this.labelField] ?? ''
            }

            let label = this.labelTemplate

            label = label.replace(/\{(\w+)\}/g, (_, key) => {
                return item[key] !== undefined && item[key] !== null
                    ? String(item[key])
                    : ''
            })

            return label
        },

        getHeader(item) {
            return this.optionHeaderField
                ? item[this.optionHeaderField]
                : this.buildLabel(item)
        },

        getBodyLines(item) {
            return this.optionBodyFields
                .map(field => item[field])
                .filter(Boolean)
        },

        positionDropdown() {
            const input = this.$refs.input
            if (!input || !this.results.length) {
                this.dropdownFixedStyle = null
                return
            }
            const r = input.getBoundingClientRect()
            const gap = 4
            const maxH = Math.min(308, Math.max(120, window.innerHeight - r.bottom - gap - 12))
            const width = Math.max(r.width, 200)
            this.dropdownFixedStyle = {
                position: 'fixed',
                top: `${r.bottom + gap}px`,
                left: `${r.left}px`,
                width: `${width}px`,
                maxHeight: `${maxH}px`,
                overflowY: 'auto',
                zIndex: 1070,
                boxSizing: 'border-box',
            }
        },

        onPortalRepositionScroll() {
            if (this.results.length) {
                this.positionDropdown()
            }
        },

        bindPortalReposition() {
            if (!this.portalListenersBound) {
                this.portalListenersBound = true
                window.addEventListener('scroll', this.onPortalRepositionScroll, true)
                window.addEventListener('resize', this.positionDropdown)
            }
            this.$nextTick(() => this.positionDropdown())
        },

        unbindPortalReposition() {
            if (!this.portalListenersBound) {
                return
            }
            this.portalListenersBound = false
            window.removeEventListener('scroll', this.onPortalRepositionScroll, true)
            window.removeEventListener('resize', this.positionDropdown)
        },
    },
    mounted() {
        document.addEventListener('mousedown', this.handleClickOutside)
    },

    beforeUnmount() {
        document.removeEventListener('mousedown', this.handleClickOutside)
        this.unbindPortalReposition()
    }
}
</script>

<template>
    <div class="autocomplete position-relative">
        <div
            class="autocomplete-input-wrap d-flex flex-wrap align-items-center gap-1"
            :class="{ 'autocomplete-disabled': disabled }"
            @click="!disabled && focusInput()"
        >
            <span
                v-if="multiple"
                v-for="item in selectedItems"
                :key="getBlockValue(item)"
                class="badge bg-primary d-flex align-items-center"
            >
                {{ buildLabel(item) }}
                <span
                    class="ms-2 cursor-pointer"
                    @click.stop="removeItem(getBlockValue(item))"
                >
                    ✕
                </span>
            </span>

            <input
                :disabled="disabled"
                ref="input"
                v-model="search"
                @focus="isFocused = true"
                @keydown="handleKeydown"
                class="autocomplete-input-inner"
                :placeholder="placeholder"
            />
        </div>

        <Teleport to="body">
            <ul
                v-if="results.length && dropdownFixedStyle"
                ref="portalDropdown"
                class="list-group autocomplete-dropdown autocomplete-dropdown--portal"
                :style="dropdownFixedStyle"
            >
                <li
                    v-for="(item, index) in results"
                    :key="getBlockValue(item)"
                    :ref="el => setItemRef(el, index)"
                    class="list-group-item autocomplete-item"
                    :class="{
                        active: index === highlightedIndex,
                        disabled: isDisabled(item)
                    }"
                    @click="!isDisabled(item) && selectItem(item)"
                >
                    <!-- Header -->
                    <div class="fw-semibold">
                        <span
                            v-html="highlightMatch(getHeader(item))"
                        ></span>
                    </div>

                    <!-- Body -->
                    <div
                        v-for="(line, i) in getBodyLines(item)"
                        :key="i"
                        class="autocomplete-body-line"
                        v-html="highlightMatch(line)"
                    ></div>
                </li>
            </ul>
        </Teleport>
    </div>
</template>

<style scoped>
    .autocomplete {
        width: 100%;
    }
    .autocomplete-input-wrap {
        min-height: 38px;
        padding: 0.25rem 0.5rem;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        background-color: #fff;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .autocomplete-input-wrap:focus-within {
        border-color: #c7ddff;
    }

    .autocomplete-input-inner {
        flex: 1 1 auto;
        min-width: 80px;
        border: none !important;
        outline: none !important;
        background: transparent !important;
        box-shadow: none !important;
        padding: 0.125rem 0;
        font-size: inherit;
        line-height: 1.5;
    }

    .autocomplete-input-inner::placeholder {
        color: #6c757d;
    }

    .autocomplete-disabled {
        background-color: #f1f5f9;
        cursor: not-allowed;
    }

    .autocomplete-disabled input {
        cursor: not-allowed;
    }

    .autocomplete-dropdown {
        max-height: 308px;
        overflow-y: auto;
        border-radius: 3px;
        border: 1px solid #bcbcbc;
        background-color: #fff;
        z-index: 1000;
    }

    .autocomplete-dropdown--portal {
        margin-top: 0;
        box-shadow: 0 10px 40px rgba(15, 23, 42, 0.14);
    }

    .autocomplete-dropdown::-webkit-scrollbar {
        width: 6px;
    }

    .autocomplete-dropdown::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 4px;
    }

    .autocomplete-dropdown::-webkit-scrollbar-thumb:hover {
        background-color: #94a3b8;
    }

    .autocomplete-item {
        color: #6c757dd7;
        padding: 5px 14px;
        cursor: pointer;
        border: none;
        font-size: 0.95rem;
        transition: background-color 0.15s ease, color 0.15s ease;
    }

    .autocomplete-item:hover {
        background-color: #f1f5f9;
    }
    .autocomplete-item.active {
        background-color: rgba(var(--vz-primary-rgb), 0.05);;
        color: gray;
    }

    .autocomplete-item.disabled {
        opacity: 0.45;
        cursor: not-allowed;
        background-color: #fff;
    }

    .autocomplete-item.disabled:hover {
        background-color: #fff;
    }

    :deep(.autocomplete-highlight) {
        font-weight: 600;
        color: var(--vz-primary);
    }

    .autocomplete-item.active :deep(.autocomplete-highlight) {
        color: -var(--vz-primary);
        text-decoration: underline;
    }

    .autocomplete-item div:first-child {
        font-size: 0.95rem;
    }

    .autocomplete-item .text-muted {
        line-height: 1.2;
    }

    .autocomplete-item.active .autocomplete-body-line {
        color: gray;
    }

    .autocomplete-item.active .autocomplete-body-line :deep(.autocomplete-highlight) {
        color: #ffffff;
        font-weight: 700;
    }

    .autocomplete-body-line {
        color: #6b7280; /* text-muted real */
        font-size: 0.8rem;
        line-height: 1.25;
    }
</style>