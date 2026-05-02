<script>
  import { Tooltip } from 'bootstrap';
  import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';
  import InputDecimalThousands from '@/Components/InputDecimalThousands.vue';

  const { formatCurrency } = useCurrencyFormatter();
  export default {
    name: 'EditableCell',
    emits: ['update:modelValue', 'move-to-cell', 'apply-pending'],
    components: {
      InputDecimalThousands,
    },
    props: {
      modelValue: {
        type: [String, Number, null],
        required: false,
        default: null,
      },
      type: {
        type: String,
        default: 'text', // 'text' o 'number'
      },
      className: {
        type: String,
        default: 'text-start',
      },
      hasError: {
        type: Boolean,
        default: false,
      },
      maxWidth: {
        type: Number,
        default: null,
      },
      disabled: { type: Boolean, default: false },
      /** Si el padre tiene un valor pendiente (ej. impuesto desde base), al hacer clic se emite apply-pending y luego se abre el input con el valor ya asignado */
      pendingValue: {
        type: [String, Number, null],
        default: null,
      },
      /** Texto del tooltip Bootstrap al pasar el cursor; vacío = sin tooltip */
      tooltip: {
        type: String,
        default: '',
      },
      boxColor: {
        type: String,
        default: 'var(--bs-primary)',
      },
      bgColor: {
        type: String,
        default: 'var(--bs-primary-bg-subtle)',
      },
    },
    data() {
      return {
        isEditing: false,
        inputValue: this.modelValue || '', // Inicializar con el valor de modelValue o vacío
        tooltipInstance: null,
      };
    },
    watch: {
      modelValue(newValue) {
        if (this.isEditing) return;
        this.inputValue = newValue || ''; // Actualizar inputValue, vacío si es null o undefined
      },
      tooltip: 'syncCellTooltip',
    },
    computed: {
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
    methods: {
      // startEdit() {
      //   this.isEditing = true;
      //   this.inputValue = this.modelValue || ''; // Iniciar la edición con valor o vacío si es null
      //   this.$nextTick(() => {
      //     const input = this.$refs.input;
      //     if (!input) return;

      //     // Si es un componente con método focus público
      //     if (typeof input.focus == 'function') {
      //         input.focus();
      //         return;
      //     }

      //     // Si el input está en el elemento raíz del componente
      //     input.$el?.focus?.();
      //   });
      // },
      startEdit() {
        if (this.disabled) return;
        if (this.isEditing) return;
        if (this.pendingValue != null) {
          this.$emit('apply-pending');
          // Esperar a que el padre actualice el modelo y Vue propague el valor antes de abrir el input,
          // para que Cleave reciba el número correcto y formatee bien (evita ej. 46270.74 → 4627074).
          setTimeout(() => {
            this.$nextTick(() => {
              this._doStartEditAndFocus();
            });
          }, 100);
          return;
        }
        this._doStartEditAndFocus();
      },
      _doStartEditAndFocus() {
        this.isEditing = true;
        // Normalizar para Cleave cuando sea "thousands"
        if (this.type === 'thousands' && this.modelValue != null) {
          let normalized = String(this.modelValue);
          // Si tiene decimales y > 0, dejar los decimales (y reemplaza . por ,); si no, quitar decimales
          if (normalized.includes('.')) {
            const [entero, decimal] = normalized.split('.');
            if (Number(decimal) > 0) {
              normalized = `${entero},${decimal}`;
            } else {
              normalized = entero;
            }
          }
          this.inputValue = normalized;
        } else {
          this.inputValue = this.modelValue || '';
        }
        this.$nextTick(() => {
          const input = this.$refs.input;
          if (!input) return;

          if (typeof input.focus === 'function') {
            input.focus();
          } else {
            input.$el?.focus?.();
          }
        });
      },
      commitEdit() {
        if (this.disabled) return;
        if (!this.isEditing) return;
        // Emitir siempre el string vacío si inputValue es vacío, pero si es igual al modelo, no emitir.
        if (this.inputValue !== (this.modelValue ?? '')) {
          this.$emit('update:modelValue', this.inputValue); // Emitir el valor actualizado
        }
        this.isEditing = false;
      },
      onInputKeydown(e) {
        if (this.disabled || !this.isEditing) return;
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          this.commitEdit();
          this.$nextTick(() => {
            this.$emit('move-to-cell', this.$el, 'down');
          });
          return;
        }
        if (e.key === 'ArrowUp') {
          e.preventDefault();
          this.commitEdit();
          this.$nextTick(() => {
            this.$emit('move-to-cell', this.$el, 'up');
          });
        }
      },
      cancelEdit() {
        if (this.disabled) return;
        this.isEditing = false;
        this.inputValue = this.modelValue || ''; // Revertir al valor original o vacío
      },
      handleEnter(e) {
        if (this.disabled) return;
        e.preventDefault();
        this.commitEdit();
        this.$nextTick(() => {
          this.$el?.focus?.({ preventScroll: true });
        });
      },
      handleEscape(e) {
        if (this.disabled) return;
        e.preventDefault();
        e.stopPropagation();
        this.cancelEdit();
        this.$nextTick(() => {
          this.$el?.focus?.({ preventScroll: true });
        });
      },
      handleTab(e) {
        if (this.disabled) return;
        e.preventDefault();
        e.stopPropagation();
        const direction = e.shiftKey ? 'prev' : 'next';
        this.commitEdit();
        this.$nextTick(() => {
          this.$emit('move-to-cell', this.$el, direction);
        });
      },
      handleTabFromTd(e) {
        if (this.disabled) return;
        if (e.target !== this.$el) return;
        e.preventDefault();
        e.stopPropagation();
        const direction = e.shiftKey ? 'prev' : 'next';
        this.$nextTick(() => {
          this.$emit('move-to-cell', this.$el, direction);
        });
      },
      formatCurrency(value) {
        if (value == null || value == undefined || value == '') {
          return '';
        }
        return formatCurrency(value);
      },
      disposeCellTooltip() {
        if (this.tooltipInstance) {
          this.tooltipInstance.dispose();
          this.tooltipInstance = null;
        }
      },
      syncCellTooltip() {
        this.disposeCellTooltip();
        this.$nextTick(() => {
          if (!String(this.tooltip ?? '').trim()) {
            return;
          }
          const el = this.$el;
          if (!el || el.nodeType !== 1) {
            return;
          }
          this.tooltipInstance = new Tooltip(el, {
            title: this.tooltip,
            container: 'body',
            placement: 'top',
            trigger: 'hover focus',
          });
        });
      },
    },
    mounted() {
      this.syncCellTooltip();
    },
    beforeUnmount() {
      this.disposeCellTooltip();
    },
  };
</script>
<template>
  <td
    data-nav-cell="true"
    :tabindex="disabled ? -1 : 0"
    :class="['editable', className, { 'has-error': hasError, disabled }]"
    :style="editableStyles"
    @click="startEdit"
    @keydown.tab="handleTabFromTd"
  >
    <div v-if="!isEditing" class="fs-13 text-truncate" :style="maxWidth ? { maxWidth: `${maxWidth}px` } : {}">
      {{ type == 'number' || type == 'thousands' ? formatCurrency(modelValue || '') : modelValue || '' }}
    </div>
    <input
      v-if="isEditing && type != 'thousands' && !disabled"
      ref="input"
      :type="type"
      v-model="inputValue"
      :class="['floating-input', className]"
      @blur="commitEdit"
      @keydown.enter="handleEnter"
      @keydown.esc="handleEscape"
      @keydown="onInputKeydown"
      @keydown.tab="handleTab"
      @click.stop
    />
    <InputDecimalThousands 
      v-if="isEditing && type == 'thousands' && !disabled" 
      ref="input"
      v-model="inputValue"
      class="floating-input" 
      :decimal-scale="2"
      :text-align="'right'"
      @blur="commitEdit"
      @keydown.enter="handleEnter"
      @keydown.esc="handleEscape"
      @keydown="onInputKeydown"
      @keydown.tab="handleTab"
      @click.stop/>
  </td>
</template>

<style scoped>
  .editable {
    position: relative;
    padding: 8px;
    cursor: pointer;
  }

  /* Foco: celda sola (p. ej. tras Enter) o mientras el input interno tiene foco */
  .editable:focus,
  .editable:focus-within {
    outline: none;
    box-shadow: inset 0 0 0 0.04rem var(--editable-box-color);
    background-color: var(--editable-bg-color);
  }

  .editable.disabled {
    pointer-events: none;
    opacity: 0.6;
  }

  .floating-input {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    padding: 8px 12px;
    font-size: 12px;
    border: none; /* Eliminar bordes del input */
    background: transparent; /* Hacer el fondo transparente */
    color: inherit; /* Heredar el color del texto de la celda */
    font-family: inherit; /* Heredar la tipografía de la celda */
    box-sizing: border-box;
    z-index: 100;
    /* Bordes menos marcados para que el foco se vea sutil */
    border: 1px solid rgba(0, 0, 0, 0.12);
    border-radius: 4px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
  }

  .floating-input:focus {
    outline: none;
    box-shadow: none; /* Quitar cualquier sombra del input */
  }

  .floating-input::-webkit-input-placeholder {
    color: transparent; /* Si es necesario, puedes hacer el placeholder transparente */
  }

  .floating-input:focus::-webkit-input-placeholder {
    color: transparent;
  }
  
  .editable.has-error {
    background-color: #fef0f0 !important;
    position: relative;
    /* Usar solo box-shadow para evitar duplicación de bordes entre celdas adyacentes */
    box-shadow: 
      inset 0 0 0 0.1rem #dc35466c !important;
  }
  
  .editable.has-error:hover {
    background-color: #fef0f0 !important;
  }
</style>
