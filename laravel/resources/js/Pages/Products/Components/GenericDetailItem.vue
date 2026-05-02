<script>
import SelectableCell from '@/Components/SelectableCell.vue';
import EditableCell from '@/Components/EditableCell.vue';
import { useAlert } from '@/Composables/useSweetAlert.js';
const { showConfirm } = useAlert();
export default {
  name: 'GenericDetailItem',
  components: { SelectableCell, EditableCell },
  props: {
    index: Number,
    row: Object,
    columns: Array,
    optionSources: Object,
    disabled: Boolean,
    rowActions: Boolean,
    boxColor: {
      type: String,
      default: 'var(--bs-primary)',
    },
    bgColor: {
      type: String,
      default: 'var(--bs-primary-bg-subtle)',
    },
  },
  methods: {
    rowId() { return this.row?.tempUuid || this.row?.uuid || `row-${this.index}`; },
    optionsFor(column) { return column.options || this.optionSources?.[column.optionsKey] || []; },
    onChange(column, value) { this.row[column.model] = value; this.$emit('row-updated', { tempUuid: this.rowId() }); },
    async deleteRow() {
      const ok = await showConfirm('warning', '¡Alerta!', '¿Está seguro que desea eliminar detalle?', 'Sí, eliminar');
      if (ok) this.$emit('delete-row', this.rowId());
    },
    duplicateRow() { this.$emit('duplicate-row', this.rowId()); },
    handleNavigate(currentCellEl, direction) {
      const cellEl = currentCellEl?.nodeType ? currentCellEl : null;
      if (!cellEl || !cellEl.closest) return;
      const tableEl = cellEl.closest('table');
      if (!tableEl) return;
      const tbodyEl = tableEl.querySelector('tbody') || tableEl;
      const rowEl = cellEl.closest('tr');
      const rows = Array.from(tbodyEl.querySelectorAll('tr'));
      const isEnabled = (el) => el && !el.classList.contains('disabled');
      const getRowCells = (r) => Array.from(r.querySelectorAll('[data-nav-cell="true"]'));
      const getEnabledRowCells = (r) => getRowCells(r).filter(isEnabled);
      const currentRowIndex = rowEl ? rows.indexOf(rowEl) : -1;
      const rowCells = rowEl ? getRowCells(rowEl) : [];
      const colIndex = rowCells.indexOf(cellEl);
      const cellAtColumn = (row, preferredIdx) => {
        const c = getRowCells(row);
        if (!c.length) return null;
        const idx = Math.min(Math.max(0, preferredIdx), c.length - 1);
        if (isEnabled(c[idx])) return c[idx];
        for (let i = idx; i < c.length; i++) if (isEnabled(c[i])) return c[i];
        for (let i = idx - 1; i >= 0; i--) if (isEnabled(c[i])) return c[i];
        return null;
      };
      let nextCell = null;
      if (direction === 'next') {
        for (let i = colIndex + 1; i < rowCells.length; i++) if (isEnabled(rowCells[i])) { nextCell = rowCells[i]; break; }
        if (!nextCell) for (let r = currentRowIndex + 1; r < rows.length; r++) { const enabled = getEnabledRowCells(rows[r]); if (enabled.length) { nextCell = enabled[0]; break; } }
      } else if (direction === 'prev') {
        for (let i = colIndex - 1; i >= 0; i--) if (isEnabled(rowCells[i])) { nextCell = rowCells[i]; break; }
        if (!nextCell) for (let r = currentRowIndex - 1; r >= 0; r--) { const enabled = getEnabledRowCells(rows[r]); if (enabled.length) { nextCell = enabled[enabled.length - 1]; break; } }
      } else if (direction === 'down') {
        for (let r = currentRowIndex + 1; r < rows.length; r++) { const cand = cellAtColumn(rows[r], colIndex); if (cand) { nextCell = cand; break; } }
      } else if (direction === 'up') {
        for (let r = currentRowIndex - 1; r >= 0; r--) { const cand = cellAtColumn(rows[r], colIndex); if (cand) { nextCell = cand; break; } }
      }
      if (!nextCell) {
        const firstEnabled = getEnabledRowCells(rows[0] || null);
        nextCell = firstEnabled[0] || null;
      }
      if (nextCell) nextCell.click();
    },
  },
};
</script>

<template>
  <tr :data-detail-temp-uuid="rowId()">
    <td v-if="rowActions" class="border-left-0 text-center">
      <div class="dropdown">
        <button class="btn btn-soft-primary btn-sm dropdown" type="button" data-bs-toggle="dropdown"><i class="ri-more-fill align-middle"></i></button>
        <ul class="dropdown-menu dropdown-menu-start">
          <li><a class="dropdown-item cursor-pointer d-flex justify-content-between" @click.prevent="duplicateRow"><span>Duplicar</span><span class="text-muted fs-11">Alt+D</span></a></li>
          <li><a class="dropdown-item cursor-pointer d-flex justify-content-between" @click.prevent="deleteRow"><span>Eliminar</span><span class="text-muted fs-11">Alt+Delete</span></a></li>
        </ul>
      </div>
    </td>
    <td class="text-center px-1">{{ (index || 0) + 1 }}</td>
    <template v-for="column in (columns || [])" :key="column.key">
      <SelectableCell
        v-if="column.type === 'select' || column.type === 'autocomplete'"
        :model-value="row?.[column.model]"
        @update:model-value="(value) => onChange(column, value)"
        :is-select="column.type === 'select'"
        :options="optionsFor(column)"
        :value-field="column.valueField || 'id'"
        :primary-field="column.primaryField || 'description'"
        :secondary-field="column.secondaryField || ''"
        :max-width="column.maxWidth || 250"
        :disabled="disabled || !!column.disabled"
        :portal-target="column.portalTarget || '#card-branches'"
        @move-to-cell="handleNavigate"
        :box-color="column.boxColor || boxColor"
        :bg-color="column.bgColor || bgColor"
      />
      <EditableCell
        v-else
        :model-value="row?.[column.model]"
        @update:model-value="(value) => onChange(column, value)"
        :type="column.type === 'number_thousands' ? 'thousands' : 'text'"
        :max-width="column.maxWidth || 200"
        :disabled="disabled || !!column.disabled"
        :class-name="column.align === 'end' ? 'text-end' : 'text-start'"
        @move-to-cell="handleNavigate"
        :box-color="column.boxColor || boxColor"
        :bg-color="column.bgColor || bgColor"
      />
    </template>
  </tr>
</template>