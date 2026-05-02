<script>
import ProductFormattedNumberInput from '@/Pages/PriceLists/Components/ProductFormattedNumberInput.vue';
import PriceListProductSelectableCell from '@/Pages/PriceLists/Components/PriceListProductSelectableCell.vue';

export default {
    name: 'ProductListDataTable',

    components: {
        ProductFormattedNumberInput,
        PriceListProductSelectableCell,
    },

    props: {
        items: {
            type: Array,
            default: () => [],
        },
        /** Solo lectura: texto plano y sin acciones ni filas vacías por defecto. */
        readonly: {
            type: Boolean,
            default: false,
        },
        /** Enlace en cabecera para abrir el catálogo (emite `add-products`). */
        showCatalogLink: {
            type: Boolean,
            default: false,
        },
        /** Deshabilita el campo de búsqueda (p. ej. vista detalle). */
        searchDisabled: {
            type: Boolean,
            default: false,
        },
        /** Texto del título de la tarjeta */
        cardTitle: {
            type: String,
            default: 'Productos',
        },
        cellErrors: {
            type: Object,
            default: () => ({}),
        },
        validationMessage: {
            type: String,
            default: '',
        },
    },

    emits: ['update:items', 'remove', 'add-products'],

    data() {
        return {
            searchQuery: '',
            currentPage: 1,
            pageSize: 20,
            dismissedValidationAlert: false,
            productNameInputRefs: {},
            productPriceInputRefs: {},
            productDiscountInputRefs: {},
            priceCellRefs: {},
            discountCellRefs: {},
            pendingFocusItemId: null,
            /** Arrastre tipo Excel para copiar precio: { pointerId, handleEl, sourceItemId, fillValue, startIndex, endIndex } */
            priceFillDrag: null,
            /** Arrastre tipo Excel para copiar descuento: { pointerId, handleEl, sourceItemId, fillValue, startIndex, endIndex } */
            discountFillDrag: null,
            /** Coalesce init de dropdowns (requestAnimationFrame). */
            productRowDropdownInitRaf: null,
        };
    },

    watch: {
        validationMessage(newMessage) {
            if (newMessage) {
                this.dismissedValidationAlert = false;
            }
        },
        items: {
            immediate: true,
            handler(newItems) {
                if (this.readonly) {
                    return;
                }
                if (!newItems || newItems.length === 0) {
                    this.$emit('update:items', [this.createEmptyRow()]);
                }
            },
        },
        searchQuery() {
            this.currentPage = 1;
        },
        totalPages(val) {
            if (this.currentPage > val) {
                this.currentPage = Math.max(1, val);
            }
        },
        productRowDropdownSignature() {
            this.scheduleInitProductRowDropdowns();
        },
    },

    computed: {
        colCount() {
            return this.readonly ? 4 : 5;
        },
        displayedItems() {
            const q = this.searchQuery.trim().toLowerCase();
            if (!q) {
                return this.items;
            }
            return this.items.filter((item) => {
                const code = String(item.code ?? '').toLowerCase();
                const name = String(item.name ?? '').toLowerCase();
                return code.includes(q) || name.includes(q);
            });
        },
        totalPages() {
            const n = this.displayedItems.length;
            if (n === 0) {
                return 1;
            }
            return Math.ceil(n / this.pageSize);
        },
        paginationOffset() {
            return (this.currentPage - 1) * this.pageSize;
        },
        paginatedItems() {
            const start = this.paginationOffset;
            return this.displayedItems.slice(start, start + this.pageSize);
        },
        paginationRangeLabel() {
            const total = this.displayedItems.length;
            if (total === 0) {
                return '';
            }
            const from = this.paginationOffset + 1;
            const to = Math.min(this.paginationOffset + this.pageSize, total);
            return `${from}–${to} de ${total}`;
        },
        priceTotal() {
            return this.displayedItems.reduce((sum, item) => sum + this.parsePrice(item.price), 0);
        },
        formattedTotal() {
            return this.formatCurrencyCo(this.priceTotal);
        },
        emptyMessage() {
            if (this.readonly && !this.items.length) {
                return 'No hay productos en la lista.';
            }
            if (!this.readonly && !this.items.length) {
                return '';
            }
            if (!this.displayedItems.length) {
                return 'Ningún producto coincide con la búsqueda.';
            }
            return '';
        },
        showEmptyBodyRow() {
            if (this.readonly) {
                return !this.items.length || !this.displayedItems.length;
            }
            return this.items.length > 0 && !this.displayedItems.length;
        },
        isPriceFillDragging() {
            return this.priceFillDrag !== null;
        },
        isDiscountFillDragging() {
            return this.discountFillDrag !== null;
        },
        showValidationAlert() {
            return Boolean(this.validationMessage) && !this.dismissedValidationAlert;
        },
        /** Firma de filas visibles para reinicializar dropdowns solo cuando cambian las filas (no en cada tecla). */
        productRowDropdownSignature() {
            if (this.readonly) {
                return '';
            }
            return this.paginatedItems.map((row) => row.id).join('\0');
        },
    },

    mounted() {
        window.addEventListener('keydown', this.handleRowShortcuts);
        this.scheduleInitProductRowDropdowns();
    },

    beforeUnmount() {
        window.removeEventListener('keydown', this.handleRowShortcuts);
        if (this.productRowDropdownInitRaf != null) {
            cancelAnimationFrame(this.productRowDropdownInitRaf);
            this.productRowDropdownInitRaf = null;
        }
        this.disposeProductRowDropdowns();
        this.teardownPriceFillDrag();
        this.teardownDiscountFillDrag();
    },

    methods: {
        scheduleInitProductRowDropdowns() {
            if (this.readonly) {
                return;
            }
            if (this.productRowDropdownInitRaf != null) {
                cancelAnimationFrame(this.productRowDropdownInitRaf);
            }
            this.productRowDropdownInitRaf = requestAnimationFrame(() => {
                this.productRowDropdownInitRaf = null;
                this.initProductRowDropdowns();
            });
        },
        initProductRowDropdowns() {
            if (this.readonly || typeof window === 'undefined' || !window.bootstrap?.Dropdown) {
                return;
            }
            this.$nextTick(() => {
                const root = this.$el;
                if (!root || typeof root.querySelectorAll !== 'function') {
                    return;
                }
                const toggles = root.querySelectorAll(
                    '.product-row-actions-dropdown-toggle[data-bs-toggle="dropdown"]',
                );
                toggles.forEach((el) => {
                    const existing = window.bootstrap.Dropdown.getInstance(el);
                    if (existing) {
                        existing.dispose();
                    }
                    // strategy: fixed evita que .table-responsive recorte el menú (overflow)
                    window.bootstrap.Dropdown.getOrCreateInstance(el, {
                        popperConfig: {
                            strategy: 'fixed',
                        },
                    });
                });
            });
        },
        disposeProductRowDropdowns() {
            if (typeof window === 'undefined' || !window.bootstrap?.Dropdown) {
                return;
            }
            const root = this.$el;
            if (!root || typeof root.querySelectorAll !== 'function') {
                return;
            }
            root.querySelectorAll('.product-row-actions-dropdown-toggle[data-bs-toggle="dropdown"]').forEach((el) => {
                const inst = window.bootstrap.Dropdown.getInstance(el);
                if (inst) {
                    inst.dispose();
                }
            });
        },
        createEmptyRow() {
            return {
                id: `draft-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`,
                product_id: null,
                code: '',
                name: '',
                price: '',
                discount: '',
            };
        },
        productRowFilters(item) {
            const disabledIds = this.items
                .filter((row) => row.id !== item.id && row.product_id != null && row.product_id !== '')
                .map((row) => row.product_id);
            // Sin filtro `status` aquí: en muchos datos reales el valor no coincide exactamente con '1'
            // y dejaba el resultado vacío. El modelo ya excluye borrados lógicos (SoftDeletes).
            return disabledIds.length ? { disabled_ids: disabledIds } : {};
        },
        productInitialOption(item) {
            if (item.product_id === null || item.product_id === undefined || item.product_id === '') {
                return null;
            }
            return {
                id: item.product_id,
                code: item.code,
                name: item.name,
            };
        },
        onProductAutocompleteChange(item, option) {
            if (this.readonly) {
                return;
            }
            if (!option) {
                item.product_id = null;
                item.code = '';
                item.name = '';
                return;
            }
            item.product_id = option.id;
            item.code = option.code != null ? String(option.code) : '';
            item.name = option.name != null ? String(option.name) : '';
        },
        /** Navegación Tab/flechas: `PriceListProductSelectableCell` emite `move-to-cell` con dirección lógica. */
        handleProductSelectableMove(itemId, _trigger, direction) {
            const map = {
                next: { key: 'Tab', shiftKey: false },
                prev: { key: 'Tab', shiftKey: true },
                down: { key: 'ArrowDown', shiftKey: false },
                up: { key: 'ArrowUp', shiftKey: false },
            };
            const cfg = map[direction];
            if (!cfg) {
                return;
            }
            const synthetic = {
                key: cfg.key,
                shiftKey: cfg.shiftKey,
                preventDefault() {},
                stopPropagation() {},
            };
            this.handleEditableCellNavigation(synthetic, itemId, 'name');
        },
        parsePrice(value) {
            if (value === null || value === undefined || value === '') {
                return 0;
            }
            const n = Number(String(value).replace(',', '.'));
            return Number.isFinite(n) ? n : 0;
        },
        formatCurrencyCo(amount) {
            if (!Number.isFinite(amount)) {
                return '$0';
            }
            const formatted = amount.toLocaleString('es-CO', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
            });
            return `$${formatted}`;
        },
        formatDisplayPrice(value) {
            return this.formatCurrencyCo(this.parsePrice(value));
        },
        normalizeDiscountValue(value, { allowEmpty = true } = {}) {
            if (value === null || value === undefined || value === '') {
                return allowEmpty ? '' : 0;
            }
            const n = Number(String(value).replace(',', '.'));
            if (!Number.isFinite(n)) {
                return allowEmpty ? '' : 0;
            }
            return Math.max(0, Math.min(100, n));
        },
        formatDisplayDiscount(value) {
            const normalized = this.normalizeDiscountValue(value, { allowEmpty: false });
            return `${normalized}%`;
        },
        onDiscountInput(item, value) {
            item.discount = this.normalizeDiscountValue(value);
        },
        hasCellError(itemId, field) {
            return Boolean(this.cellErrors?.[itemId]?.[field]);
        },
        isProductCellInvalid(item) {
            const hasProduct = item?.product_id !== null
                && item?.product_id !== undefined
                && String(item?.product_id).trim() !== '';
            return this.hasCellError(item?.id, 'product') && !hasProduct;
        },
        isPriceCellInvalid(item) {
            const hasPrice = item?.price !== null
                && item?.price !== undefined
                && String(item?.price).trim() !== '';
            return this.hasCellError(item?.id, 'price') && !hasPrice;
        },
        appendEmptyRow() {
            if (this.readonly) {
                return;
            }
            const newRow = this.createEmptyRow();
            this.pendingFocusItemId = newRow.id;
            this.$emit('update:items', [...this.items, newRow]);
            this.$nextTick(() => {
                this.currentPage = this.totalPages;
                this.$nextTick(() => {
                    const target = this.getFocusableElementFromRef(
                        this.productNameInputRefs[this.pendingFocusItemId],
                        { openPriceListProductDropdown: true },
                    );
                    if (target) {
                        target.focus();
                    }
                    this.pendingFocusItemId = null;
                });
            });
        },
        removeLastRow() {
            if (this.readonly || !this.items.length) {
                return;
            }

            const lastItem = this.items[this.items.length - 1];
            let next = this.items.slice(0, -1);
            if (!next.length) {
                next = [this.createEmptyRow()];
            }
            const focusTargetId = next[next.length - 1]?.id ?? null;

            this.$emit('update:items', next);
            this.$emit('remove', lastItem);

            if (!focusTargetId) {
                return;
            }

            this.pendingFocusItemId = focusTargetId;
            this.$nextTick(() => {
                if (this.currentPage > this.totalPages) {
                    this.currentPage = this.totalPages;
                }
                this.$nextTick(() => {
                    const target = this.getFocusableElementFromRef(
                        this.productNameInputRefs[this.pendingFocusItemId],
                        { openPriceListProductDropdown: true },
                    );
                    if (target) {
                        target.focus();
                    }
                    this.pendingFocusItemId = null;
                });
            });
        },
        getActiveProductRowIdFromDom() {
            const el = document.activeElement;
            if (!el || typeof el.closest !== 'function') {
                return null;
            }
            const tr = el.closest('tr[data-product-row-id]');
            if (!tr) {
                return null;
            }
            return tr.getAttribute('data-product-row-id');
        },
        handleRowShortcuts(event) {
            const t = event.target;
            const productsHost =
                typeof t?.closest === 'function'
                    ? t.closest('[data-product-search-portal-host]')
                    : null;
            // Incluye el desplegable de producto (Teleport dentro del host): antes en `body` quedaba fuera de `$el`.
            const isInsideComponent =
                this.$el?.contains?.(t)
                || (productsHost && this.$el?.contains?.(productsHost));
            if (!isInsideComponent) {
                return;
            }

            if (!this.readonly && event.altKey && !event.ctrlKey && !event.metaKey && !event.shiftKey) {
                const rowId = this.getActiveProductRowIdFromDom();
                if (rowId) {
                    const key = String(event.key || '').toLowerCase();
                    if (key === 'd') {
                        event.preventDefault();
                        const item = this.items.find((row) => String(row.id) === String(rowId));
                        if (item) {
                            this.duplicateItem(item);
                        }
                        return;
                    }
                    if (event.code === 'Delete' || key === 'delete') {
                        event.preventDefault();
                        const item = this.items.find((row) => String(row.id) === String(rowId));
                        if (item) {
                            this.removeItem(item);
                        }
                        return;
                    }
                }
            }

            if (this.readonly || event.altKey || event.ctrlKey || event.metaKey || !event.shiftKey) {
                return;
            }

            const key = String(event.key || '').toLowerCase();
            const isAddShortcut = key === 'a';
            const isRemoveShortcut = ['delete', 'backspace'].includes(key)
                || ['Delete', 'Backspace'].includes(event.code);

            if (isAddShortcut) {
                event.preventDefault();
                this.appendEmptyRow();
                return;
            }

            if (isRemoveShortcut) {
                event.preventDefault();
                this.removeLastRow();
            }
        },
        setProductNameInputRef(el, itemId) {
            if (el) {
                this.productNameInputRefs[itemId] = el;
                return;
            }
            delete this.productNameInputRefs[itemId];
        },
        setProductPriceInputRef(el, itemId) {
            if (el) {
                this.productPriceInputRefs[itemId] = el;
                return;
            }
            delete this.productPriceInputRefs[itemId];
        },
        setProductDiscountInputRef(el, itemId) {
            if (el) {
                this.productDiscountInputRefs[itemId] = el;
                return;
            }
            delete this.productDiscountInputRefs[itemId];
        },
        getFocusableElementFromRef(refValue, options = {}) {
            if (!refValue) {
                return null;
            }
            // `PriceListProductSelectableCell` (copia con `<td>` + Teleport) puede no exponer `$el` fiable como `<td>`.
            if (typeof refValue.focusCell === 'function') {
                refValue.focusCell();
                if (
                    options.openPriceListProductDropdown
                    && typeof refValue.ensureDropdownOpenAfterFocus === 'function'
                ) {
                    refValue.ensureDropdownOpenAfterFocus();
                }
                return null;
            }
            // Elemento DOM nativo
            if (refValue.nodeType === 1 && typeof refValue.focus === 'function') {
                return refValue;
            }
            // Componente Vue: no usar solo $el (p. ej. Autocomplete = div sin tabindex);
            // priorizar input/textarea/select dentro del root.
            const root = refValue.$el;
            if (root && root.nodeType === 1) {
                const inner =
                    typeof root.querySelector === 'function'
                        ? root.querySelector(
                            'input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), select:not([disabled])',
                        )
                        : null;
                if (inner && typeof inner.focus === 'function') {
                    return inner;
                }
                if (typeof root.focus === 'function') {
                    return root;
                }
            }
            if (typeof refValue.focus === 'function') {
                return refValue;
            }
            return null;
        },
        focusEditableCell(itemId, field) {
            const refsByField = field === 'price'
                ? this.productPriceInputRefs
                : field === 'discount'
                    ? this.productDiscountInputRefs
                    : this.productNameInputRefs;
            if (field === 'name') {
                const host = this.$el?.querySelector?.('[data-product-search-portal-host]');
                // Valor entre comillas en selector (no usar CSS.escape: altera p. ej. ids numéricos).
                const attrSafe = String(itemId).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
                const tr = host?.querySelector?.(
                    `tr.product-ledger-row-excel[data-product-row-id="${attrSafe}"]`,
                );
                const productTd = tr?.querySelector?.('td.selectable-cell');
                if (productTd && typeof productTd.focus === 'function' && productTd.tabIndex !== -1) {
                    productTd.focus({ preventScroll: true });
                    return;
                }
            }
            const target = this.getFocusableElementFromRef(refsByField[itemId]);
            if (target) {
                target.focus();
            }
        },
        focusFirstMissingRequiredInput() {
            const firstRowWithMissing = this.items.find((item) => {
                const hasProduct = item?.product_id !== null
                    && item?.product_id !== undefined
                    && String(item?.product_id).trim() !== '';
                const hasPrice = item?.price !== null
                    && item?.price !== undefined
                    && String(item?.price).trim() !== '';
                return !hasProduct || !hasPrice;
            });

            if (!firstRowWithMissing) {
                return false;
            }

            const firstMissingField = (firstRowWithMissing?.product_id === null
                || firstRowWithMissing?.product_id === undefined
                || String(firstRowWithMissing?.product_id).trim() === '')
                ? 'name'
                : 'price';

            const absoluteIndex = this.items.findIndex((item) => item.id === firstRowWithMissing.id);
            if (absoluteIndex === -1) {
                return false;
            }

            this.currentPage = Math.floor(absoluteIndex / this.pageSize) + 1;
            this.$nextTick(() => {
                this.focusEditableCell(firstRowWithMissing.id, firstMissingField);
            });
            return true;
        },
        /**
         * Tab / Shift+Tab: solo entre celdas editables (producto → precio por fila; última celda vuelve a la primera).
         * Flechas: comportamiento tipo hoja de cálculo existente.
         */
        handleEditableCellNavigation(event, itemId, field) {
            if (event.key === 'Tab') {
                const tabRowIndex = this.paginatedItems.findIndex((item) => item.id === itemId);
                if (tabRowIndex === -1) {
                    return;
                }
                const editableFields = ['name', 'price', 'discount'];
                if (!editableFields.includes(field)) {
                    return;
                }
                const n = this.paginatedItems.length;
                if (n === 0) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                let targetRowIndex;
                let targetField;

                if (event.shiftKey) {
                    if (field === 'discount') {
                        targetRowIndex = tabRowIndex;
                        targetField = 'price';
                    } else if (field === 'price') {
                        targetRowIndex = tabRowIndex;
                        targetField = 'name';
                    } else if (tabRowIndex <= 0) {
                        targetRowIndex = n - 1;
                        targetField = 'discount';
                    } else {
                        targetRowIndex = tabRowIndex - 1;
                        targetField = 'discount';
                    }
                } else if (field === 'name') {
                    targetRowIndex = tabRowIndex;
                    targetField = 'price';
                } else if (field === 'price') {
                    targetRowIndex = tabRowIndex;
                    targetField = 'discount';
                } else if (tabRowIndex >= n - 1) {
                    targetRowIndex = 0;
                    targetField = 'name';
                } else {
                    targetRowIndex = tabRowIndex + 1;
                    targetField = 'name';
                }

                const targetRow = this.paginatedItems[targetRowIndex];
                if (!targetRow) {
                    return;
                }
                // Doble tick: refs/DOM de fila destino estables tras saltar de input (p. ej. descuento → producto).
                this.$nextTick(() => {
                    this.$nextTick(() => {
                        this.focusEditableCell(targetRow.id, targetField);
                    });
                });
                return;
            }

            const direction = event.key;
            if (!['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(direction)) {
                return;
            }

            const rowIndex = this.paginatedItems.findIndex((item) => item.id === itemId);
            if (rowIndex === -1) {
                return;
            }

            const editableFields = ['name', 'price', 'discount'];
            const fieldIndex = editableFields.indexOf(field);
            if (fieldIndex === -1) {
                return;
            }

            let targetRowIndex = rowIndex;
            let targetField = field;

            if (direction === 'ArrowUp') {
                targetRowIndex -= 1;
            } else if (direction === 'ArrowDown') {
                targetRowIndex += 1;
            } else if (direction === 'ArrowLeft') {
                targetField = editableFields[Math.max(0, fieldIndex - 1)];
            } else if (direction === 'ArrowRight') {
                targetField = editableFields[Math.min(editableFields.length - 1, fieldIndex + 1)];
            }

            const targetRow = this.paginatedItems[targetRowIndex];
            if (!targetRow) {
                return;
            }

            if (targetRowIndex === rowIndex && targetField === field) {
                return;
            }

            event.preventDefault();
            this.focusEditableCell(targetRow.id, targetField);
        },
        removeItem(itemToRemove) {
            let next = this.items.filter((item) => item.id !== itemToRemove.id);
            if (!this.readonly && next.length === 0) {
                next = [this.createEmptyRow()];
            }
            this.$emit('update:items', next);
            this.$emit('remove', itemToRemove);
        },
        duplicateItem(item) {
            if (this.readonly) {
                return;
            }
            const idx = this.items.findIndex((row) => row.id === item.id);
            if (idx === -1) {
                return;
            }
            const base = this.createEmptyRow();
            const clone = {
                ...base,
                product_id: item.product_id,
                code: item.code,
                name: item.name,
                price: item.price,
                discount: item.discount,
            };
            const next = [...this.items];
            next.splice(idx + 1, 0, clone);
            this.$emit('update:items', next);
        },
        onOpenCatalog() {
            this.$emit('add-products');
        },
        dismissValidationAlert() {
            this.dismissedValidationAlert = true;
        },
        goPrevPage() {
            if (this.currentPage > 1) {
                this.currentPage -= 1;
            }
        },
        goNextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage += 1;
            }
        },
        setPriceCellElRef(el, itemId) {
            if (el) {
                this.priceCellRefs[itemId] = el;
                return;
            }
            delete this.priceCellRefs[itemId];
        },
        setDiscountCellElRef(el, itemId) {
            if (el) {
                this.discountCellRefs[itemId] = el;
                return;
            }
            delete this.discountCellRefs[itemId];
        },
        isPriceFillPreviewRow(itemId) {
            if (!this.priceFillDrag) {
                return false;
            }
            const rowIndex = this.paginatedItems.findIndex((it) => it.id === itemId);
            if (rowIndex === -1) {
                return false;
            }
            const lo = Math.min(this.priceFillDrag.startIndex, this.priceFillDrag.endIndex);
            const hi = Math.max(this.priceFillDrag.startIndex, this.priceFillDrag.endIndex);
            return rowIndex >= lo && rowIndex <= hi;
        },
        getPaginatedPriceRowIndexAtPoint(clientX, clientY) {
            for (let i = 0; i < this.paginatedItems.length; i += 1) {
                const el = this.priceCellRefs[this.paginatedItems[i].id];
                if (!el || typeof el.getBoundingClientRect !== 'function') {
                    continue;
                }
                const r = el.getBoundingClientRect();
                if (
                    clientX >= r.left
                    && clientX <= r.right
                    && clientY >= r.top
                    && clientY <= r.bottom
                ) {
                    return i;
                }
            }
            return null;
        },
        teardownPriceFillDrag() {
            window.removeEventListener('pointermove', this.onPriceFillPointerMove);
            window.removeEventListener('pointerup', this.onPriceFillPointerUp, true);
            window.removeEventListener('pointercancel', this.onPriceFillPointerUp, true);
            if (this.priceFillDrag?.handleEl?.releasePointerCapture) {
                try {
                    this.priceFillDrag.handleEl.releasePointerCapture(this.priceFillDrag.pointerId);
                } catch {
                    // ignore
                }
            }
            this.priceFillDrag = null;
        },
        hasPriceCellData(item) {
            if (!item || item.price === null || item.price === undefined) {
                return false;
            }
            return String(item.price).trim() !== '';
        },
        hasDiscountCellData(item) {
            if (!item || item.discount === null || item.discount === undefined) {
                return false;
            }
            return String(this.normalizeDiscountValue(item.discount)).trim() !== '';
        },
        onPriceFillPointerDown(event, item) {
            if (this.readonly || event.button !== 0) {
                return;
            }
            if (!this.hasPriceCellData(item)) {
                return;
            }
            const startIndex = this.paginatedItems.findIndex((row) => row.id === item.id);
            if (startIndex === -1) {
                return;
            }
            const fillValue = item.price;
            const handleEl = event.currentTarget;
            this.priceFillDrag = {
                pointerId: event.pointerId,
                handleEl,
                sourceItemId: item.id,
                fillValue,
                startIndex,
                endIndex: startIndex,
            };
            window.addEventListener('pointermove', this.onPriceFillPointerMove);
            window.addEventListener('pointerup', this.onPriceFillPointerUp, true);
            window.addEventListener('pointercancel', this.onPriceFillPointerUp, true);
            if (handleEl?.setPointerCapture) {
                try {
                    handleEl.setPointerCapture(event.pointerId);
                } catch {
                    // ignore
                }
            }
            event.preventDefault();
        },
        onPriceFillPointerMove(event) {
            if (!this.priceFillDrag) {
                return;
            }
            const idx = this.getPaginatedPriceRowIndexAtPoint(event.clientX, event.clientY);
            if (idx !== null) {
                this.priceFillDrag.endIndex = idx;
            }
        },
        onPriceFillPointerUp() {
            if (!this.priceFillDrag) {
                return;
            }
            const { startIndex, endIndex, fillValue } = this.priceFillDrag;
            const lo = Math.min(startIndex, endIndex);
            const hi = Math.max(startIndex, endIndex);
            for (let i = lo; i <= hi; i += 1) {
                const row = this.paginatedItems[i];
                if (row) {
                    row.price = fillValue;
                }
            }
            this.teardownPriceFillDrag();
        },
        isDiscountFillPreviewRow(itemId) {
            if (!this.discountFillDrag) {
                return false;
            }
            const rowIndex = this.paginatedItems.findIndex((it) => it.id === itemId);
            if (rowIndex === -1) {
                return false;
            }
            const lo = Math.min(this.discountFillDrag.startIndex, this.discountFillDrag.endIndex);
            const hi = Math.max(this.discountFillDrag.startIndex, this.discountFillDrag.endIndex);
            return rowIndex >= lo && rowIndex <= hi;
        },
        getPaginatedDiscountRowIndexAtPoint(clientX, clientY) {
            for (let i = 0; i < this.paginatedItems.length; i += 1) {
                const el = this.discountCellRefs[this.paginatedItems[i].id];
                if (!el || typeof el.getBoundingClientRect !== 'function') {
                    continue;
                }
                const r = el.getBoundingClientRect();
                if (
                    clientX >= r.left
                    && clientX <= r.right
                    && clientY >= r.top
                    && clientY <= r.bottom
                ) {
                    return i;
                }
            }
            return null;
        },
        teardownDiscountFillDrag() {
            window.removeEventListener('pointermove', this.onDiscountFillPointerMove);
            window.removeEventListener('pointerup', this.onDiscountFillPointerUp, true);
            window.removeEventListener('pointercancel', this.onDiscountFillPointerUp, true);
            if (this.discountFillDrag?.handleEl?.releasePointerCapture) {
                try {
                    this.discountFillDrag.handleEl.releasePointerCapture(this.discountFillDrag.pointerId);
                } catch {
                    // ignore
                }
            }
            this.discountFillDrag = null;
        },
        onDiscountFillPointerDown(event, item) {
            if (this.readonly || event.button !== 0) {
                return;
            }
            if (!this.hasDiscountCellData(item)) {
                return;
            }
            const startIndex = this.paginatedItems.findIndex((row) => row.id === item.id);
            if (startIndex === -1) {
                return;
            }
            const fillValue = this.normalizeDiscountValue(item.discount);
            const handleEl = event.currentTarget;
            this.discountFillDrag = {
                pointerId: event.pointerId,
                handleEl,
                sourceItemId: item.id,
                fillValue,
                startIndex,
                endIndex: startIndex,
            };
            window.addEventListener('pointermove', this.onDiscountFillPointerMove);
            window.addEventListener('pointerup', this.onDiscountFillPointerUp, true);
            window.addEventListener('pointercancel', this.onDiscountFillPointerUp, true);
            if (handleEl?.setPointerCapture) {
                try {
                    handleEl.setPointerCapture(event.pointerId);
                } catch {
                    // ignore
                }
            }
            event.preventDefault();
        },
        onDiscountFillPointerMove(event) {
            if (!this.discountFillDrag) {
                return;
            }
            const idx = this.getPaginatedDiscountRowIndexAtPoint(event.clientX, event.clientY);
            if (idx !== null) {
                this.discountFillDrag.endIndex = idx;
            }
        },
        onDiscountFillPointerUp() {
            if (!this.discountFillDrag) {
                return;
            }
            const { startIndex, endIndex, fillValue } = this.discountFillDrag;
            const lo = Math.min(startIndex, endIndex);
            const hi = Math.max(startIndex, endIndex);
            for (let i = lo; i <= hi; i += 1) {
                const row = this.paginatedItems[i];
                if (row) {
                    row.discount = this.normalizeDiscountValue(fillValue);
                }
            }
            this.teardownDiscountFillDrag();
        },
    },
};
</script>

<template>
    <div class="card product-list-dt-card" style="padding: 0;">
        <div class="card-header">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex align-items-start gap-2 flex-wrap w-100">
                        <h5 class="mb-0" style="padding-top: 5px;">
                            {{ cardTitle }}
                        </h5>
                        <div
                            v-if="showValidationAlert"
                            class="alert alert-danger alert-border-left alert-dismissible fade show py-1 px-2 mb-0 product-list-dt-validation-alert ms-md-auto"
                            role="alert"
                        >
                            <i class="ri-error-warning-line me-2 align-middle"></i>
                            <strong>Alerta</strong> - {{ validationMessage }}
                        </div>
                    </div>
                </div>
                <!-- <div class="col-6">
                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <a
                            v-if="showCatalogLink && !readonly"
                            href="#"
                            class="text-primary product-list-dt-add-link"
                            @click.prevent="onOpenCatalog"
                        >
                            <i class="ri-list-check-2"></i>
                            Catálogo
                        </a>
                    </div>
                </div> -->
            </div>
        </div>
        <div class="card-body p-0" >
            <div
                id="price-list-create-products"
                data-product-search-portal-host
                class="product-ledger-teleport-root"
            >
                <div class="product-list-dt-table-pad">
                <div class="table-card table-responsive">
                    <div class="voucher-detail-table-scroll-group">
                <table
                    class="table table-bordered table-centered align-middle table-nowrap mb-0 product-ledger-table"
                    :class="{
                        'product-ledger-table--excel': !readonly,
                        'product-ledger-table--price-fill-dragging': isPriceFillDragging,
                        'product-ledger-table--discount-fill-dragging': isDiscountFillDragging,
                    }"
                >
                    <thead class="text-muted table-light">
                        <tr>
                            <th
                                v-if="!readonly"
                                class="col-actions-menu border-start-0 text-center pe-2"
                                scope="col"
                            >
                                <span class="visually-hidden">Acciones por fila</span>
                            </th>
                            <th
                                class="col-num text-center pe-2"
                                :class="{ 'border-start-0': readonly }"
                                scope="col"
                            >
                                #
                            </th>
                            <th scope="col">PRODUCTO</th>
                            <th class="col-price text-end pe-2" scope="col">$ PRECIO</th>
                            <th class="col-discount text-end pe-2 border-end-0" scope="col">% DESCUENTO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="showEmptyBodyRow">
                            <td :colspan="colCount" class="empty-row text-center text-muted border-start-0">
                                {{ emptyMessage }}
                            </td>
                        </tr>
                        <template v-else-if="!readonly">
                            <tr
                                v-for="(item, index) in paginatedItems"
                                :key="item.id"
                                class="product-ledger-row-excel"
                                :data-product-row-id="item.id"
                            >
                                <td class="td-excel td-excel-actions-menu col-actions-menu border-start-0 text-center">
                                    <div class="dropdown">
                                        <button
                                            class="btn btn-soft-primary btn-sm product-row-actions-dropdown-toggle"
                                            type="button"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false"
                                            :aria-label="`Acciones fila ${paginationOffset + index + 1}`"
                                        >
                                            <i class="ri-more-fill align-middle"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-start">
                                            <li>
                                                <a
                                                    class="dropdown-item cursor-pointer d-flex justify-content-between"
                                                    href="#"
                                                    @click.prevent="removeItem(item)"
                                                >
                                                    <span>Eliminar</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                                <td class="col-num td-excel td-excel-num text-center px-1">{{ paginationOffset + index + 1 }}</td>
                                <PriceListProductSelectableCell
                                    :ref="(el) => setProductNameInputRef(el, item.id)"
                                    class="cell-product cell-product-edit td-excel td-excel-product"
                                    search-url="/price-lists/search-products"
                                    :fields="['code', 'name']"
                                    :select-fields="['code', 'name']"
                                    :limit="10"
                                    :min-chars="3"
                                    primary-field="code"
                                    secondary-field="name"
                                    value-field="id"
                                    :show-multiple="true"
                                    :filters="productRowFilters(item)"
                                    :initial-item="productInitialOption(item)"
                                    :model-value="item.product_id"
                                    :disabled="readonly || searchDisabled"
                                    :has-error="isProductCellInvalid(item)"
                                    @update:model-value="item.product_id = $event"
                                    @change-option="onProductAutocompleteChange(item, $event)"
                                    @move-to-cell="(_trigger, dir) => handleProductSelectableMove(item.id, _trigger, dir)"
                                />
                                <td
                                    class="col-price td-excel td-excel-price"
                                    :ref="(el) => setPriceCellElRef(el, item.id)"
                                >
                                    <div class="product-price-cell-wrap">
                                        <ProductFormattedNumberInput
                                            v-model="item.price"
                                            :ref="(el) => setProductPriceInputRef(el, item.id)"
                                            placeholder="Precio"
                                            input-class="text-end"
                                            prefix="$"
                                            @keydown="handleEditableCellNavigation($event, item.id, 'price')"
                                        />
                                        <div
                                            v-if="priceFillDrag && isPriceFillPreviewRow(item.id)"
                                            class="product-price-fill-preview-overlay"
                                            aria-hidden="true"
                                        >
                                            {{ formatDisplayPrice(priceFillDrag.fillValue) }}
                                        </div>
                                        <span
                                            v-if="hasPriceCellData(item)"
                                            class="product-price-fill-handle"
                                            title="Arrastrar para copiar el precio a otras filas"
                                            aria-hidden="true"
                                            @pointerdown.stop="onPriceFillPointerDown($event, item)"
                                        />
                                    </div>
                                </td>
                                <td
                                    class="col-discount td-excel td-excel-discount border-end-0"
                                    :ref="(el) => setDiscountCellElRef(el, item.id)"
                                >
                                    <div class="product-price-cell-wrap">
                                        <ProductFormattedNumberInput
                                            :model-value="item.discount"
                                            :ref="(el) => setProductDiscountInputRef(el, item.id)"
                                            input-class="text-end"
                                            :max="100"
                                            suffix="%"
                                            @update:model-value="onDiscountInput(item, $event)"
                                            @keydown="handleEditableCellNavigation($event, item.id, 'discount')"
                                        />
                                        <div
                                            v-if="discountFillDrag && isDiscountFillPreviewRow(item.id)"
                                            class="product-price-fill-preview-overlay"
                                            aria-hidden="true"
                                        >
                                            {{ formatDisplayDiscount(discountFillDrag.fillValue) }}
                                        </div>
                                        <span
                                            v-if="hasDiscountCellData(item)"
                                            class="product-price-fill-handle"
                                            title="Arrastrar para copiar el descuento a otras filas"
                                            aria-hidden="true"
                                            @pointerdown.stop="onDiscountFillPointerDown($event, item)"
                                        />
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template v-else>
                            <tr v-for="(item, index) in paginatedItems" :key="item.id">
                                <td class="col-num border-start-0 text-center px-1">{{ paginationOffset + index + 1 }}</td>
                                <td class="cell-product">
                                    <div class="cell-primary">{{ item.code }}</div>
                                    <div class="cell-secondary">{{ item.name }}</div>
                                </td>
                                <td class="col-price text-end">
                                    <span class="cell-price-readonly">{{ formatDisplayPrice(item.price) }}</span>
                                </td>
                                <td class="col-discount text-end border-end-0">
                                    <span class="cell-price-readonly">{{ formatDisplayDiscount(item.discount) }}</span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot v-if="items.length" class="bg-light">
                        <tr v-if="!readonly" class="product-list-dt-foot-actions">
                            <td :colspan="colCount" class="py-2 border-start-0 product-list-dt-foot-actions-cell">
                                <h6
                                    class="m-0 text-primary fs-12 cursor-pointer w-100 mb-0"
                                    @click.prevent="appendEmptyRow"
                                >
                                    <i class="ri ri-add-line fs-13 align-middle"></i>
                                    Agregar producto
                                </h6>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                    </div>
                </div>
                </div>
            </div>
            <nav
                v-if="!showEmptyBodyRow"
                class="product-list-dt-pagination d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 py-2 border-top"
                aria-label="Paginación de productos"
            >
                <span class="text-muted small mb-0">{{ paginationRangeLabel }}</span>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item" :class="{ disabled: currentPage <= 1 }">
                        <a
                            class="page-link"
                            href="#"
                            :tabindex="currentPage <= 1 ? -1 : 0"
                            @click.prevent="goPrevPage"
                        >Anterior</a>
                    </li>
                    <li class="page-item disabled">
                        <span class="page-link text-body">
                            Página {{ currentPage }} / {{ totalPages }}
                        </span>
                    </li>
                    <li class="page-item" :class="{ disabled: currentPage >= totalPages }">
                        <a
                            class="page-link"
                            href="#"
                            :tabindex="currentPage >= totalPages ? -1 : 0"
                            @click.prevent="goNextPage"
                        >Siguiente</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</template>

<style scoped>
.product-list-dt-search-wrap {
    width: 100%;
}

.product-list-dt-add-link {
    font-size: 14px;
}

.product-list-dt-validation-alert {
    font-size: 0.75rem;
}

.product-list-dt-validation-alert .btn-close {
    padding: 0.45rem;
}

/* Ancla del Teleport del buscador de producto: sin overflow, para no recortar el panel (hijo hermano de .table-responsive). */
.product-ledger-teleport-root {
    position: relative;
}

/* Padding interno: evita que el margen negativo de .table-card “sangre” fuera de la tarjeta */
.product-list-dt-table-pad {
    min-width: 0;
}

/* Misma jerarquía visual que tablas de detalle (voucher): table-card + grupo de scroll */
.product-ledger-teleport-root .table-card {
    background: #fff;
    position: relative;
    margin-left: 0;
    margin-right: 0;
    margin-top: 0;
    margin-bottom: 0;
}

.voucher-detail-table-scroll-group {
    min-width: 0;
}

.product-ledger-table {
    width: 100%;
    margin: 0;
    border-collapse: collapse;
    font-size: 0.75rem;
    color: #212529;
}

.product-ledger-table th,
.product-ledger-table td {
    padding: 0.35rem 0.45rem;
    vertical-align: middle;
}

.product-ledger-table thead th {
    height: 35px;
    font-weight: 600;
    text-align: left;
    border-color: var(--vz-border-color, #dee2e6);
    font-size: 0.72rem;
}

.product-ledger-table thead th.col-num {
    text-align: center;
}

.product-ledger-table thead th.col-price {
    text-align: right;
}

.product-ledger-table thead th.col-discount {
    text-align: right;
}

.product-ledger-table thead th.col-actions-menu {
    text-align: center;
    width: 2.75rem;
    min-width: 2.75rem;
}

/* Modo hoja de cálculo: celdas sin relleno, controles al ras del borde */
.product-ledger-table--excel {
    table-layout: fixed;
}

.product-ledger-table--excel tbody td.td-excel {
    padding: 0;
    vertical-align: middle;
}

.product-ledger-table--excel .td-excel-num {
    width: 2.25rem;
    min-width: 2.25rem;
    background: #fafafa;
    color: #444;
    font-variant-numeric: tabular-nums;
    vertical-align: middle;
    text-align: center;
    border: 1px solid #f3f3f3;
    font-size: 0.7rem;
    line-height: 1.2;
}

.product-ledger-table--excel .td-excel-product {
    min-width: 0;
    vertical-align: middle;
}

/* Celda producto con `SelectableCell`: aspecto alineado al resto de celdas Excel */
.product-ledger-table--excel tbody :deep(td.td-excel-product.selectable-cell) {
    padding: 0 !important;
}

/* Misma caja base que `.product-fn-price-input` (precio / descuento) */
.product-ledger-table--excel tbody :deep(td.td-excel-product.selectable-cell .selectable-cell-inner) {
    min-height: 2.75rem;
    padding: 0.25rem 0.45rem;
    border: 1px solid #f3f3f3;
    border-radius: 0;
    font-size: 0.75rem;
    line-height: 1.25;
    background: #fff;
    color: #111;
    box-sizing: border-box;
}

.product-ledger-table--excel tbody :deep(td.td-excel-product.selectable-cell .primary-value) {
    font-size: 0.75rem;
}

.table-cell-error-danger :deep(.input-group),
.table-cell-error-danger :deep(.form-control),
.table-cell-error-danger :deep(input) {
    border-color: #fb00194a !important;
}

.table-cell-error-danger :deep(.selectable-cell-inner) {
    border-color: #fb00194a !important;
}

.product-list-dt-excel-input {
    display: block;
    width: 100%;
    min-height: 1.75rem;
    height: 2.75rem;
    margin: 0;
    border-radius: 0;
    box-shadow: none;
    border: 1px solid #f3f3f3;
    padding: 0.2rem 0.4rem;
    font-size: 0.75rem;
    line-height: 1.25;
    background: #fff;
    color: #111;
    outline: none;
}

.product-list-dt-excel-input::placeholder {
    color: #9ca3af;
    font-size: 0.72rem;
}

.product-list-dt-excel-input:focus {
    border-color: var(--vz-primary);
    background-color: rgba(var(--vz-primary-rgb), 0.05);
    position: relative;
    z-index: 1;
}

.product-ledger-table--excel .td-excel-price {
    vertical-align: middle;
}

.product-ledger-table--excel .td-excel-discount {
    vertical-align: middle;
}

.product-ledger-table--price-fill-dragging {
    user-select: none;
    -webkit-user-select: none;
}

.product-ledger-table--discount-fill-dragging {
    user-select: none;
    -webkit-user-select: none;
}

.product-price-cell-wrap {
    position: relative;
    display: block;
    min-height: 2.75rem;
}

.product-price-fill-handle {
    position: absolute;
    right: 0;
    bottom: 0;
    width: 0;
    height: 0;
    z-index: 4;
    cursor: crosshair;
    touch-action: none;
    border-style: solid;
    border-width: 0 0 8px 8px;
    border-color: transparent transparent var(--vz-primary, #006a84) transparent;
}

.product-price-fill-handle:hover {
    filter: brightness(1.1);
}

/* Vista previa al arrastrar: inset 1px para no tapar el borde del input (#f3f3f3 del diseño) */
.product-price-fill-preview-overlay {
    position: absolute;
    top: 1px;
    right: 1px;
    bottom: 1px;
    left: 1px;
    z-index: 3;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 0.2rem 0.35rem;
    font-size: 0.75rem;
    font-weight: 500;
    font-variant-numeric: tabular-nums;
    pointer-events: none;
    color: rgba(var(--vz-primary-rgb), 0.45);
    background: rgba(255, 255, 255, 0.88);
}

.product-ledger-table--excel .td-excel-actions-menu {
    width: 2.75rem;
    min-width: 2.75rem;
    vertical-align: middle;
    padding: 0 !important;
}

.product-ledger-table--excel .td-excel-actions-menu .dropdown {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 2.75rem;
    height: 2.75rem;
    width: 100%;
    border: 1px solid #f3f3f3;
    box-sizing: border-box;
}

.product-ledger-table--excel .td-excel-actions-menu .btn {
    line-height: 1;
}

.col-num {
    width: 3rem;
    text-align: center;
    background: #f3f3f3;
    font-variant-numeric: tabular-nums;
}

.cell-product {
    min-width: 12rem;
}

.cell-primary {
    color: #212529;
    line-height: 1.35;
}

.cell-secondary {
    margin-top: 0.15rem;
    font-size: 0.78rem;
    color: #6c757d;
    line-height: 1.3;
}

.col-price {
    width: 14rem;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

.col-discount {
    width: 10rem;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

.product-ledger-table--excel .col-price {
    width: 18%;
}

.product-ledger-table--excel .col-discount {
    width: 12%;
}

.col-actions-menu {
    width: 2.75rem;
    min-width: 2.75rem;
}

.cell-price-readonly {
    font-weight: 500;
}


.empty-row {
    padding: 1.5rem 1rem;
}

.product-ledger-tfoot td {
    height: 40px;
    border: 1px solid #f3f3f3;
    background: #fafafa;
    font-weight: 600;
}

.product-list-dt-foot-actions-cell {
    height: 30px;
    cursor: pointer;
}

.product-list-dt-append-btn {
    min-width: 11rem;
}

.product-list-dt-pagination {
    padding: 10px;
    background: #fafafa;
    border-color: #d9d9d9 !important;
}

.product-list-dt-pagination .page-link {
    color: #006a84;
}

.product-list-dt-pagination .page-item.disabled .page-link {
    color: #6c757d;
}
</style>
