<script>
  import axios from 'axios'
  import { Tooltip } from 'bootstrap'

  /**
   * Copia local de `SelectableCell` para listas de precio (portal, Tab, auto-apertura).
   * Mapa de memoria de búsqueda independiente del componente global.
   */
  const searchMemoryByType = new Map();

  export default {
    name: 'PriceListProductSelectableCell',
    inheritAttrs: false,

    emits: ['change-option', 'update:modelValue', 'change', 'move-to-cell'],
  
    props: {
      options: { type: Array, default: () => [] },
  
      showMultiple: { type: Boolean, default: false },

      isSelect: { type: Boolean, default: false },
  
      primaryField: { type: String, default: 'description' },
      secondaryField: { type: String, default: '' },
      valueField: { type: String, default: 'id' },
  
      modelValue: [String, Number],
  
      disabled: { type: Boolean, default: false },
  
      /** Portal del desplegable (por defecto el host de la tabla de lista de precio). */
      portalTarget: { type: String, default: '#price-list-create-products' },
      
      hasError: { type: Boolean, default: false },

      minChars: { type: Number, default: 3 },

      searchUrl: { type: String, default: '' },

      fields: { type: Array, default: () => [] },

      limit: { type: Number, default: 10 },

      filters: { type: Object, default: () => ({}) },

      initialItem: { type: Object, default: null },

      selectFields: {
          type: Array,
          default: () => []
      },
      maxWidth: {
        type: Number,
        default: null
      },

      /** Muestra el icono de advertencia al final de la celda (no abre el selector). */
      showTrailingAlert: { type: Boolean, default: false },

      /** Texto del tooltip Bootstrap; si está vacío, solo se muestra el icono sin tooltip. */
      trailingAlertMessage: { type: String, default: '' },

      boxColor: {
        type: String,
        default: 'var(--vz-primary, #006a84)',
      },
      bgColor: {
        type: String,
        default: 'rgba(var(--vz-primary-rgb), 0.08)',
      },
    },
  
    data() {
      return {
        isDropdownVisible: false,
        searchQuery: '',
        results: [],
        dropdownStyle: {},
        selectedItem: null,
        isFocused: false,
        shouldWrap: false,
        observer: null,
        highlightedIndex: -1,
        itemRefs: [],
        instanceId: `price-list-selectable-${Math.random().toString(36).slice(2)}`,
        trailingTooltipInstance: null,
        /** Evita abrir por foco cuando el usuario va a abrir/cerrar con clic (mousedown → focus → click). */
        skipAutoOpenOnNextFocus: false,
      };
    },
  
    computed: {
      resolvedPortalTarget() {
        if (typeof window === 'undefined') {
          return 'body';
        }
        if (!this.portalTarget || this.portalTarget === 'body') {
          return 'body';
        }
        const target = document.querySelector(this.portalTarget);
        return target ? this.portalTarget : 'body';
      },
      /** Atributos del padre (p. ej. `class`, `data-*`) aplicados al `<td>`, sin duplicar `class`. */
      tdAttrsWithoutClass() {
        const { class: _c, ...rest } = this.$attrs;
        return rest;
      },
      searchTypeKey() {
        // Si tiene endpoint de búsqueda, es la mejor llave para separar tipos
        if (this.searchUrl) return `url:${this.searchUrl}`;
        // Fallback para selects locales
        return `local:${this.primaryField}|${this.secondaryField}|${this.valueField}`;
      },
      primaryValue() {
        this.checkOverflow();
        if (this.isSelect) {
          const option = this.options.find(opt => opt[this.valueField] === this.modelValue);
          return option ? option[this.primaryField] : '';
        }
        return this.selectedItem
          ? this.selectedItem[this.primaryField]
          : '';
      },

      secondaryValue() {
        this.checkOverflow();
        if (this.isSelect) {
          const option = this.options.find(opt => opt[this.valueField] === this.modelValue);
          return this.showMultiple && option
            ? option[this.secondaryField]
            : '';
        }
        return this.showMultiple && this.selectedItem
          ? this.selectedItem[this.secondaryField]
          : '';
      },
      wrapStyle() {
        if (!this.shouldWrap) return {}

        return {
          maxWidth: `${this.maxWidth}px`,
          width: `${this.maxWidth + 10}px`
        }
      },
      editableStyles() {
        const styles = {
          '--editable-box-color': this.boxColor,
          '--editable-bg-color': this.bgColor,
        };

        if (this.maxWidth) {
          styles.maxWidth = `${this.maxWidth}px`;
        }

        return styles;
      },
    },
  
    watch: {
      initialItem: {
        immediate: true,
        deep: true,
        handler(val) {
          if (!val) {
            this.selectedItem = null;
            this.checkOverflow();
            return;
          }
          if (!this.selectedItem || this.selectedItem[this.valueField] != val[this.valueField]) {
            this.selectedItem = val;
            this.checkOverflow();
          }
        }
      },
      searchQuery(value) {
          searchMemoryByType.set(this.searchTypeKey, value ?? '');
          if (this.isSelect) {
            this.searchOptions(value)
            return false;
          }
          if (value.length < this.minChars) {
            this.results = [];
            return false;
          }
          this.searchRequest(value)
      },
      // Si isSelect es true, llenar results con las options recibidas por props
      isSelect: {
        immediate: true,
        handler(val) {
          if (val && this.options && Array.isArray(this.options)) {
            this.results = [...this.options];
          }
        }
      },
      results() {
        this.$nextTick(() => {
          if (!this.isDropdownVisible || !this.results.length) {
            this.highlightedIndex = -1;
            return;
          }
          this.highlightedIndex = 0;
          this.scrollHighlightedIntoView();
        });
      },

      showTrailingAlert: 'syncTrailingAlertTooltip',
      trailingAlertMessage: 'syncTrailingAlertTooltip',
    },
  
    methods: {
      /* ----------------------------
       * Dropdown lifecycle
       * ---------------------------- */
  
      toggleDropdown() {
        if (this.disabled) return;
        this.skipAutoOpenOnNextFocus = false;
        this.isFocused = false;
        this.$refs.trigger.focus(); // 👈 CLAVE

        this.isDropdownVisible ? this.hideDropdown() : this.showDropdown();
      },
      checkOverflow() {
        this.$nextTick(() => {
          const el = this.$refs.content
          if (!el || !this.maxWidth) return

          const prevWhiteSpace = el.style.whiteSpace
          const prevPosition = el.style.position

          // forzar medición real
          el.style.whiteSpace = 'nowrap'
          el.style.position = 'absolute'

          const realWidth = el.scrollWidth

          // restaurar
          el.style.whiteSpace = prevWhiteSpace
          el.style.position = prevPosition

          this.shouldWrap = realWidth > this.maxWidth
        })
      },
  
      showDropdown() {
        if (this.disabled) return;

        // Asegurar que solo una celda selectable quede activa/abierta a la vez.
        window.dispatchEvent(new CustomEvent('selectable-cell-opened', {
          detail: { instanceId: this.instanceId }
        }));
  
        this.isDropdownVisible = true;
        this.highlightedIndex = this.results.length ? 0 : -1;

        // Recuperar búsqueda previa del mismo tipo de selector
        const rememberedQuery = searchMemoryByType.get(this.searchTypeKey);
        if (rememberedQuery != null) {
          this.searchQuery = rememberedQuery;
        }
  
        this.$nextTick(() => {
          this.positionDropdown();
          // this.$refs.searchInput?.focus();
          if (this.$refs.searchInput) {
            this.$refs.searchInput.focus({ preventScroll: true });
            this.$refs.searchInput.select();
          }
        });
      },
  
      hideDropdown() {
        this.isDropdownVisible = false;
        this.highlightedIndex = -1;
        // this.searchQuery = '';
        // this.results = [];
      },
  
      /* ----------------------------
       * Posicionamiento respecto al portal (`#price-list-create-products`):
       * el portal está fuera de `.table-responsive` para no recortar el panel.
       * ---------------------------- */
      positionDropdown() {
          if (!this.isDropdownVisible || !this.$refs.trigger) {
              return;
          }

          const trigger = this.$refs.trigger;
          const triggerRect = trigger.getBoundingClientRect();

          const portalTarget = this.resolvedPortalTarget;
          const container =
              portalTarget === 'body'
                  ? document.body
                  : document.querySelector(portalTarget);

          if (!container) {
              return;
          }

          const containerRect =
              container === document.body
                  ? { top: 0, left: 0 }
                  : container.getBoundingClientRect();

          const top =
              triggerRect.bottom - containerRect.top + container.scrollTop;

          const left =
              triggerRect.left - containerRect.left + container.scrollLeft;

          const width = Math.max(triggerRect.width, 300);

          this.dropdownStyle = {
              position: 'absolute',
              top: `${top}px`,
              left: `${left}px`,
              width: `${width}px`,
              zIndex: 9999,
          };
      },

  
      /* ----------------------------
       * Filtering & selection
       * ---------------------------- */
      filterItems() {
        // const q = this.searchQuery.toLowerCase();
  
        // this.results = this.options.filter(opt =>
        //   opt[this.primaryField]?.toLowerCase().includes(q) ||
        //   (this.showMultiple &&
        //     opt[this.secondaryField]?.toLowerCase().includes(q))
        // );
      },
  
      selectOption(option) {
        if (!option) return;
        this.selectedItem = option; // 👈 guardar objeto completo
        this.$emit('update:modelValue', option[this.valueField]);
        this.$emit('change', option[this.valueField]);
        this.$emit('change-option', option);
        this.hideDropdown();
        // Mantener el foco visual en la celda, incluso si el click del usuario
        // hace que el <td> pierda el foco por unos milisegundos.
        this.$nextTick(() => {
          this.$refs.trigger?.focus?.();
          this.isFocused = true;
        });
      },
      setItemRef(el, index) {
        if (!el) return;
        this.itemRefs[index] = el;
      },
      scrollHighlightedIntoView() {
        const el = this.itemRefs[this.highlightedIndex];
        if (el && typeof el.scrollIntoView === 'function') {
          el.scrollIntoView({ block: 'nearest' });
        }
      },
      moveHighlight(direction) {
        if (!this.isDropdownVisible || !this.results.length) return;
        if (this.highlightedIndex === -1) {
          this.highlightedIndex = 0;
          this.scrollHighlightedIntoView();
          return;
        }
        const lastIndex = this.results.length - 1;
        const next = this.highlightedIndex + direction;
        this.highlightedIndex = next < 0 ? lastIndex : (next > lastIndex ? 0 : next);
        this.scrollHighlightedIntoView();
      },
      onSearchKeydown(e) {
        if (e.key === 'ArrowDown') {
          if (this.results.length) {
            e.preventDefault();
            this.moveHighlight(1);
          } else {
            e.preventDefault();
            this.hideDropdown();
            this.$emit('move-to-cell', this.$refs.trigger, 'down');
          }
          return;
        }
        if (e.key === 'ArrowUp') {
          if (this.results.length) {
            e.preventDefault();
            this.moveHighlight(-1);
          } else {
            e.preventDefault();
            this.hideDropdown();
            this.$emit('move-to-cell', this.$refs.trigger, 'up');
          }
          return;
        }
        if (e.key === 'Enter') {
          e.preventDefault();
          if (this.highlightedIndex >= 0) {
            this.selectOption(this.results[this.highlightedIndex]);
          }
          return;
        }
        if (e.key === 'Escape') {
          e.preventDefault();
          this.hideDropdown();
          this.$nextTick(() => {
            this.$refs.trigger?.focus?.({ preventScroll: true });
            this.isFocused = true;
          });
          return;
        }
        if (e.key === 'Tab') {
          e.preventDefault();
          this.hideDropdown();
          this.$emit('move-to-cell', this.$refs.trigger, e.shiftKey ? 'prev' : 'next');
        }
      },
      handleTabFromCell(e) {
        if (this.disabled) return;
        e.preventDefault();
        this.hideDropdown();
        this.$emit('move-to-cell', this.$refs.trigger, e.shiftKey ? 'prev' : 'next');
      },

      onTriggerKeydown(e) {
        if (this.disabled) return;

        if ((e.key === 'Delete' || e.key === 'Backspace') && !this.isDropdownVisible) {
          e.preventDefault();
          this.clearSelection();
          return;
        }

        if (this.isDropdownVisible) return;
        const map = {
          ArrowDown: 'down',
          ArrowUp: 'up',
        };
        const dir = map[e.key];
        if (!dir) return;
        e.preventDefault();
        this.$emit('move-to-cell', this.$refs.trigger, dir);
      },

  
      /* ----------------------------
       * Global listeners
       * ---------------------------- */
      onClickOutside(e) {
        if (!this.$refs.trigger) {
          return;
        }
        if (
          this.isDropdownVisible &&
          !this.$refs.trigger.contains(e.target) &&
          !this.$refs.dropdown?.contains(e.target)
        ) {
          this.hideDropdown();
        }
        if (!this.$refs.trigger.contains(e.target)) {
          this.isFocused = false;
        }
      },
      onOtherSelectableOpened(e) {
        const openedId = e?.detail?.instanceId || null;
        if (!openedId || openedId === this.instanceId) return;
        this.hideDropdown();
        this.isFocused = false;
      },
      focusCell() {
        if (this.disabled) return;
        this.isFocused = true;
        this.$refs.trigger.focus(); // 👈 FOCO REAL
      },

      /** Tras foco programático (p. ej. fila nueva): `relatedTarget` no suele ser input de la tabla, así que no dispara la auto-apertura de `onTriggerFocus`. */
      ensureDropdownOpenAfterFocus() {
        if (this.disabled || this.isDropdownVisible) {
          return;
        }
        this.showDropdown();
      },

      onTriggerMouseDown() {
        this.skipAutoOpenOnNextFocus = true;
      },

      onTriggerFocus(e) {
        this.isFocused = true;
        if (this.skipAutoOpenOnNextFocus) {
          return;
        }
        if (this.disabled || this.isDropdownVisible) {
          return;
        }
        const rt = e.relatedTarget;
        if (rt && this.$refs.dropdown?.contains?.(rt)) {
          return;
        }
        if (this.shouldAutoOpenDropdownFromRelatedTarget(rt)) {
          this.showDropdown();
        }
      },

      onTriggerBlur() {
        this.isFocused = false;
        this.skipAutoOpenOnNextFocus = false;
      },

      /** Foco llegando desde input/celda de la grilla de lista de precios (Tab), no desde el propio desplegable. */
      shouldAutoOpenDropdownFromRelatedTarget(rt) {
        if (!rt || rt.nodeType !== 1 || typeof rt.closest !== 'function') {
          return false;
        }
        const table = rt.closest('table.product-ledger-table');
        if (!table) {
          return false;
        }
        return typeof rt.matches === 'function'
          && rt.matches('input:not([type="hidden"]):not([disabled]), textarea:not([disabled])');
      },
      clearSelection() {
        this.selectedItem = null;

        this.$emit('update:modelValue', null);
        this.$emit('change', null);
        this.$emit('change-option', null);
      },
      onKeyDown(e) {
        if (e.key === 'Escape') {
          if (this.isDropdownVisible) {
            e.preventDefault();
            this.hideDropdown();
            this.$nextTick(() => {
              this.$refs.trigger?.focus?.({ preventScroll: true });
              this.isFocused = true;
            });
          }
        }
         // DELETE → borrar selección previa (si hay foco)
        if ((e.key === 'Delete' || e.key === 'Backspace') && this.isFocused) {
          e.preventDefault();
          this.clearSelection();
        }
      },
      async searchRequest(value) {
            this.itemRefs = []
            this.highlightedIndex = -1

            try {
              const response = await axios.get(this.searchUrl, {
                params: {
                    q: value,
                    fields: this.fields,
                    limit: this.limit,
                    filters: this.filters,
                    selectFields: this.selectFields
                }
              });

              this.results = response.data.data ?? [];
            } catch (e) {
              console.error(e)
            }
      },
      async searchOptions(value) {
        //buscar en el array de options
        const options = this.options.filter(opt => opt[this.primaryField]?.toLowerCase().includes(value.toLowerCase()));
        this.results = options;
      },

      disposeTrailingAlertTooltip() {
        if (this.trailingTooltipInstance) {
          this.trailingTooltipInstance.dispose();
          this.trailingTooltipInstance = null;
        }
      },

      syncTrailingAlertTooltip() {
        this.disposeTrailingAlertTooltip();
        this.$nextTick(() => {
          if (!this.showTrailingAlert || !String(this.trailingAlertMessage ?? '').trim()) {
            return;
          }
          const el = this.$refs.trailingAlertEl;
          if (!el) return;
          this.trailingTooltipInstance = new Tooltip(el, {
            title: this.trailingAlertMessage,
            container: 'body',
            placement: 'top',
            trigger: 'hover focus',
          });
        });
      },
    },
  
    mounted() {
      this.checkOverflow();
      this.syncTrailingAlertTooltip();
      document.addEventListener('mousedown', this.onClickOutside);
      document.addEventListener('keydown', this.onKeyDown);
      window.addEventListener('scroll', this.positionDropdown, true);
      window.addEventListener('resize', this.positionDropdown);
      window.addEventListener('selectable-cell-opened', this.onOtherSelectableOpened);
    },
  
    beforeUnmount() {
      this.disposeTrailingAlertTooltip();
      document.removeEventListener('mousedown', this.onClickOutside);
      document.removeEventListener('keydown', this.onKeyDown);
      window.removeEventListener('scroll', this.positionDropdown, true);
      window.removeEventListener('resize', this.positionDropdown);
      window.removeEventListener('selectable-cell-opened', this.onOtherSelectableOpened);
    },
};
</script>
  
<template>
    <!-- TRIGGER (TD REAL) -->
    <td
      ref="trigger"
      data-nav-cell="true"
      :tabindex="disabled ? -1 : 0"
      @mousedown.capture="onTriggerMouseDown"
      @click="toggleDropdown"
      @dblclick.prevent="focusCell"
      @focus="onTriggerFocus"
      @blur="onTriggerBlur"
      @keydown="onTriggerKeydown"
      @keydown.tab="handleTabFromCell"
      :style="editableStyles"
      v-bind="tdAttrsWithoutClass"
      :class="['selectable-cell', 'price-list-product-cell', $attrs.class, { disabled, 'has-error': hasError, focused: isFocused || isDropdownVisible }]"
    >
      <div class="selectable-cell-inner d-flex align-items-center w-100 gap-1">
        <div
          ref="content"
          class="selectable-cell-text flex-grow-1 min-w-0"
          :class="{ 'wrap-text': shouldWrap }"
          :style="wrapStyle"
        >
          <div class="lh-1">
            <span class="fs-12 primary-value">
              {{ primaryValue }}
            </span>
          </div>

          <div class="lh-1" v-if="showMultiple">
            <span class="fs-12 text-muted">
              {{ secondaryValue }}
            </span>
          </div>
        </div>

        <span
          v-if="showTrailingAlert"
          ref="trailingAlertEl"
          class="selectable-cell-trailing-alert text-info ms-auto flex-shrink-0"
          @click.stop
          @mousedown.stop
        >
          <i class="ri-error-warning-line align-middle" aria-hidden="true"></i>
        </span>
      </div>
    </td>
  
    <!-- PORTAL REAL -->
    <Teleport :to="resolvedPortalTarget">
      <div
        v-show="isDropdownVisible"
        ref="dropdown"
        class="selectable-dropdown portal"
        :style="dropdownStyle"
        @click.stop
      >
        <input
          ref="searchInput"
          type="text"
          v-model="searchQuery"
          class="form-control bg-light"
          placeholder="Buscar..."
          @input="filterItems"
          @keydown="onSearchKeydown"
        />
  
        <div class="header">Selecciona una opción</div>
  
        <ul>
          <li
            v-for="(option, index) in results"
            :key="option[valueField]"
            :ref="(el) => setItemRef(el, index)"
            :class="{ 'option-focused': highlightedIndex === index }"
            @mousedown.prevent
            @click.stop="selectOption(option)"
            @mouseenter="highlightedIndex = index"
          >
            <div>
              <div class="lh-1">
                <span class="fs-12 primary-value">
                  {{ option[primaryField] }}
                </span>
              </div>
              <div class="lh-1" v-if="showMultiple">
                <span class="fs-12 text-muted">
                  {{ option[secondaryField] }}
                </span>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </Teleport>
</template>
  
<style scoped>
  .selectable-cell {
    cursor: pointer;
    position: relative;
    padding: 0.5rem !important;
    vertical-align: middle;
  }

  .selectable-cell-inner {
    min-height: 100%;
  }

  .selectable-cell-trailing-alert {
    line-height: 1;
    cursor: pointer;
    pointer-events: auto;
    user-select: none;
  }

  /* El foco por teclado/click debe verse sutil y consistente en toda la tabla */
  .selectable-cell:focus {
    outline: none;
  }

  .selectable-cell.focused {
    box-shadow: inset 0 0 0 0.04rem var(--editable-box-color);
    background-color: var(--editable-bg-color);
  }
  
  .selectable-cell.disabled {
    pointer-events: none;
    opacity: 0.6;
  }
  
  .primary-value {
    font-weight: 410;
  }
  
  .selectable-dropdown.portal {
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 2px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
  }
  
  .selectable-dropdown input {
    border: none;
    border-bottom: 1px solid #eee;
    padding: 8px;
  }
  
  .selectable-dropdown .header {
    font-size: 12px;
    padding: 6px 8px;
    color: #666;
    border-bottom: 1px solid #eee;
  }
  
  .selectable-dropdown ul {
    list-style: none;
    margin: 0;
    padding: 0;
    max-height: 180px;
    overflow-y: auto;
  }
  
  .selectable-dropdown li {
    padding: 6px 8px;
    cursor: pointer;
  }
  
  .selectable-dropdown li:hover {
    background: #e5e7eb91;
  }

  .selectable-dropdown li.option-focused {
    background: #e5e7eb91 !important;
  }
  
  .selectable-cell.has-error {
    background-color: #fef0f0 !important;
    position: relative;
    /* Usar solo box-shadow para evitar duplicación de bordes entre celdas adyacentes */
    box-shadow: 
      inset 0 0 0 0.1rem #dc354647 !important;
  }
  
  .selectable-cell.has-error:hover {
    background-color: #fef0f0 !important; 
  }

  .wrap-text {
    display: inline-block;
    white-space: normal;
    word-break: break-word;
  }

  /* Misma idea que ProductFormattedNumberInput: borde en la “caja”, no sombra en todo el td */
  td.price-list-product-cell.selectable-cell.focused:not(.has-error),
  td.price-list-product-cell.selectable-cell:focus-visible:not(.has-error) {
    box-shadow: none !important;
    background-color: transparent !important;
    outline: none;
  }

  td.price-list-product-cell.selectable-cell.focused:not(.has-error) {
    z-index: 2;
  }

  td.price-list-product-cell.selectable-cell.focused:not(.has-error) .selectable-cell-inner,
  td.price-list-product-cell.selectable-cell:focus-visible:not(.has-error) .selectable-cell-inner {
    border: 1px solid var(--vz-primary) !important;
    background-color: rgba(var(--vz-primary-rgb), 0.05) !important;
    box-shadow: none !important;
    outline: none !important;
    position: relative;
    z-index: 2;
  }
</style>