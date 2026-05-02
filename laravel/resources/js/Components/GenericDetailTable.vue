<script>
import GenericDetailItem from '@/Components/GenericDetailItem.vue';
import { useCurrencyFormatter } from '@/Composables/useCurrencyFormatter.js';
const { formatCurrency } = useCurrencyFormatter();
export default {
  name: 'GenericDetailTable',
  components: { GenericDetailItem },
  props: {
    details: Array,
    columns: Array,
    optionSources: Object,
    perPage: Number,
    disabled: Boolean,
    scopeContainerSelector: {
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
  emits: ['create-detail', 'delete-detail', 'duplicate-detail', 'detail-updated'],
  data() {
    return {
      currentPage: 1,
      pendingFocusNewRow: false,
      pendingFocusKnownUuids: [],
      pendingFocusAfterDeleteUuid: null,
      lastFocusedRowId: null,
    };
  },
  computed: {
    rowsPerPage() { return this.perPage || 10; },
    totalPages() { return Math.max(1, Math.ceil((this.details || []).length / this.rowsPerPage)); },
    totalItems() { return (this.details || []).length; },
    pageStart() { return this.totalItems ? ((this.currentPage - 1) * this.rowsPerPage) + 1 : 0; },
    pageEnd() { return Math.min(this.currentPage * this.rowsPerPage, this.totalItems); },
    paginatedDetails() {
      const start = (this.currentPage - 1) * this.rowsPerPage;
      return (this.details || []).slice(start, start + this.rowsPerPage);
    },
    footerColumns() { return (this.columns || []).filter((c) => c.showInFooterTotal); },
  },
  watch: {
    'details.length'(newLen, oldLen) {
      if (!this.pendingFocusNewRow || newLen <= oldLen) return;
      this.pendingFocusNewRow = false;
      const added = (this.details || []).find((row) => {
        const id = this.getRowId(row);
        return id && !this.pendingFocusKnownUuids.includes(id);
      });
      this.pendingFocusKnownUuids = [];
      this.$nextTick(() => this.focusRowFirstCell(this.getRowId(added) || null));
    },
    details() {
      if (!this.pendingFocusAfterDeleteUuid) return;
      const target = this.pendingFocusAfterDeleteUuid;
      this.pendingFocusAfterDeleteUuid = null;
      this.$nextTick(() => this.focusRowFirstCell(target));
    },
    totalPages(newTotalPages) {
      if (this.currentPage > newTotalPages) this.currentPage = newTotalPages;
      if (this.currentPage < 1) this.currentPage = 1;
    },
  },
  mounted() {
    window.addEventListener('keydown', this.onGlobalKeydown, true);
    this.$el?.addEventListener('focusin', this.onTableFocusIn, true);
    this.$el?.addEventListener('click', this.onTableClick, true);
  },
  beforeUnmount() {
    window.removeEventListener('keydown', this.onGlobalKeydown, true);
    this.$el?.removeEventListener('focusin', this.onTableFocusIn, true);
    this.$el?.removeEventListener('click', this.onTableClick, true);
  },
  methods: {
    formatCurrency,
    getHeaderAlignClass(column = null) {
      const classMap = {
        start: 'text-start',
        end: 'text-end',
        center: 'text-center',
      };
      return classMap[column?.align] || classMap.center;
    },
    inferScopeContainerSelector() {
      const firstColumnScope = (this.columns || [])
        .map((column) => column?.portalTarget)
        .find((target) => typeof target === 'string' && target.trim().length > 0);
      return firstColumnScope || '';
    },
    resolveScopeContainer() {
      const selector = (this.scopeContainerSelector || this.inferScopeContainerSelector() || '').trim();
      if (!selector) return null;
      if (this.$el?.closest) {
        const closestMatch = this.$el.closest(selector);
        if (closestMatch) return closestMatch;
      }
      return document.querySelector(selector);
    },
    shouldHandleShortcut(event) {
      if (this.disabled || !this.$el) return false;
      const targetEl = event?.target?.nodeType === 1 ? event.target : null;
      const activeEl = document.activeElement?.nodeType === 1 ? document.activeElement : null;
      const focusedElement = targetEl || activeEl;
      if (!focusedElement) return false;
      if (!this.$el.contains(focusedElement)) return false;

      const scopeContainer = this.resolveScopeContainer();
      if (!scopeContainer) return true;
      return scopeContainer.contains(focusedElement) && scopeContainer.contains(this.$el);
    },
    getRowId(row, index = 0) { return row?.tempUuid || row?.uuid || `row-${index}`; },
    createRow() {
      if (this.disabled) return;
      this.pendingFocusNewRow = true;
      this.pendingFocusKnownUuids = (this.details || []).map((row, index) => this.getRowId(row, index));
      this.$emit('create-detail');
    },
    onDeleteRow(id) {
      const currentIndex = (this.details || []).findIndex((row, index) => this.getRowId(row, index) === id);
      if (currentIndex >= 0) {
        const previous = this.details[currentIndex - 1] || null;
        const next = this.details[currentIndex + 1] || null;
        this.pendingFocusAfterDeleteUuid = this.getRowId(previous) || this.getRowId(next) || null;
      }
      this.$emit('delete-detail', id);
    },
    onDuplicateRow(id) { this.$emit('duplicate-detail', id); },
    onRowUpdated(payload) { this.$emit('detail-updated', payload); },
    getFocusedRowId() {
      const active = document.activeElement;
      const row = active?.closest?.('tr[data-detail-temp-uuid]');
      const rowId = row?.getAttribute('data-detail-temp-uuid') || null;
      if (rowId) this.lastFocusedRowId = rowId;
      return rowId || this.lastFocusedRowId || null;
    },
    updateLastFocusedRowIdFromEvent(event) {
      const el = event?.target?.nodeType === 1 ? event.target : null;
      const row = el?.closest?.('tr[data-detail-temp-uuid]');
      const rowId = row?.getAttribute('data-detail-temp-uuid') || null;
      if (rowId) this.lastFocusedRowId = rowId;
    },
    onTableFocusIn(event) {
      this.updateLastFocusedRowIdFromEvent(event);
    },
    onTableClick(event) {
      this.updateLastFocusedRowIdFromEvent(event);
    },
    focusRowFirstCell(rowId = null) {
      const tbody = this.$el?.querySelector('tbody');
      const row = rowId
        ? tbody?.querySelector(`tr[data-detail-temp-uuid="${rowId}"]`)
        : tbody?.querySelector('tr:last-child');
      const resolvedRowId = row?.getAttribute?.('data-detail-temp-uuid') || null;
      if (resolvedRowId) this.lastFocusedRowId = resolvedRowId;
      const firstCell = row?.querySelector('[data-nav-cell="true"]');
      if (firstCell && typeof firstCell.click === 'function') firstCell.click();
    },
    async flushActiveEditableValue() {
      const active = document.activeElement;
      const isInsideTable = !!(active && this.$el?.contains(active));
      const isEditableField = !!active?.matches?.('input, textarea, [contenteditable="true"]');
      if (!isInsideTable || !isEditableField || typeof active.blur !== 'function') return;
      active.blur();
      await this.$nextTick();
    },
    async duplicateFocusedRow() {
      await this.flushActiveEditableValue();
      const rowId = this.getFocusedRowId();
      if (!rowId || this.disabled) return;
      this.$emit('duplicate-detail', rowId);
    },
    async deleteFocusedRow() {
      await this.flushActiveEditableValue();
      const rowId = this.getFocusedRowId();
      if (!rowId || this.disabled) return;
      this.onDeleteRow(rowId);
    },
    goToPage(page) {
      const parsed = Number(page);
      if (!Number.isFinite(parsed)) return;
      this.currentPage = Math.min(this.totalPages, Math.max(1, Math.trunc(parsed)));
    },
    goToPreviousPage() {
      this.goToPage(this.currentPage - 1);
    },
    goToNextPage() {
      this.goToPage(this.currentPage + 1);
    },
    onGlobalKeydown(e) {
      if (!this.shouldHandleShortcut(e)) return;
      const key = String(e.key || '').toLowerCase();
      const isAltA = e.altKey && (e.code === 'KeyA' || key === 'a' || key === 'á');
      if (isAltA) {
        e.preventDefault();
        e.stopPropagation();
        this.createRow();
        return;
      }
      const isAltD = e.altKey && (e.code === 'KeyD' || key === 'd' || key === '∂');
      if (isAltD) {
        e.preventDefault();
        e.stopPropagation();
        this.duplicateFocusedRow();
        return;
      }
      const isAltDelete = e.altKey && (e.key === 'Delete' || e.key === 'Backspace' || e.code === 'Delete' || e.code === 'Backspace');
      if (isAltDelete) {
        e.preventDefault();
        e.stopPropagation();
        this.deleteFocusedRow();
      }
    },
  },
};
</script>
<template>
  <div class="table-card mb-1 table-responsive">
    <div>
      <table class="table table-bordered table-centered align-middle table-nowrap mb-0">
        <thead class="text-muted table-light">
          <tr>
            <th class="border-left-0 text-center pe-2"></th>
            <th class="text-center pe-2">#</th>
            <th
              v-for="column in (columns || [])"
              :key="column.key"
              :class="getHeaderAlignClass(column)"
            >
              {{ column.label }}
            </th>
          </tr>
        </thead>
        <tbody>
          <GenericDetailItem
            v-for="(row, index) in paginatedDetails"
            :key="row?.tempUuid || row?.uuid || index"
            :index="index + ((currentPage - 1) * rowsPerPage)"
            :row="row"
            :columns="columns || []"
            :option-sources="optionSources || {}"
            :disabled="disabled"
            :row-actions="true"
            @delete-row="onDeleteRow"
            @duplicate-row="onDuplicateRow"
            @row-updated="onRowUpdated"
            :box-color="boxColor"
            :bg-color="bgColor"
          />
        </tbody>
        <tfoot class="bg-light">
          <tr>
            <td :colspan="2 + Math.max((columns || []).length - footerColumns.length, 0)" class="py-2 border-left-0">
              <h6 class="m-0 text-primary fs-12 cursor-pointer w-100" @click="createRow">
                <i class="ri ri-add-line fs-13 align-middle"></i> Nuevo detalle
              </h6>
            </td>
            <td v-for="column in footerColumns" :key="`f-${column.key}`" class="py-2 text-end pe-1 fs-13">
              ${{ formatCurrency((details || []).reduce((sum, row) => sum + (Number(row?.[column.model]) || 0), 0)) }}
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
    <div class="d-flex align-items-center border-top bg-white px-2 py-1">
      <div class="col-sm-12 col-md-5">
        <div class="dataTables_info pt-1 pb-0 text-muted">
          Mostrando {{ pageStart }} a {{ pageEnd }} de {{ totalItems }} entradas
        </div>
      </div>
      <div class="col-sm-12 col-md-7">
        <div class="dataTables_paginate paging_simple_numbers pt-1 pb-0">
          <ul class="pagination justify-content-end mb-0">
            <li class="page-item" :class="{ disabled: currentPage === 1 }">
              <a href="#" class="page-link me-1 px-2" @click.prevent="goToPreviousPage">
                <i class="ri-arrow-left-s-line"></i>
              </a>
            </li>
            <li class="page-item active">
              <a href="#" class="page-link" @click.prevent="goToPage(currentPage)">{{ currentPage }}</a>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
              <a href="#" class="page-link ms-1 px-2" @click.prevent="goToNextPage">
                <i class="ri-arrow-right-s-line"></i>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>
