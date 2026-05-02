<script>
import { nextTick } from 'vue'

export default {
    name: 'DataTableComponent',

    props: {
        id: {
            type: String,
            default: 'datatable'
        },
        headers: {
            type: Array,
            required: true
        },
        items: {
            type: Array,
            required: true
        },
        pageLength: {
            type: Number,
            default: 10
        },
        orderBy: {
            type: String,
            default: null
        },
        orderDir: {
            type: String,
            default: 'asc'
        },
        clickableRow: {
            type: Boolean,
            default: false
        },
        itemKey: {
            type: String,
            default: null,
        },

        pagingType: {
            type: String,
            default: 'simple_numbers'
        },

        paginationLabels: { // OPCIONES: simple, simple_numbers, numbers, full_numbers
            type: Object,
            default: () => ({
                // first: "Primero",
                // last: "Último",
                // next: "Siguiente",
                // previous: "Anterior"
                first: '<i class="bx bx-chevrons-left"></i>',
                last: '<i class="bx bx-chevrons-right"></i>',
                previous: '<i class="bx bx-chevron-left"></i>',
                next: '<i class="bx bx-chevron-right"></i>',
            })
        }
    },

    data() {
        return {
            selectedRow: null,
        }
    },

    mounted() {
        let orderIndex = 0

        if (this.orderBy) {
            const index = this.headers.findIndex(h => h.key === this.orderBy)
            orderIndex = index >= 0 ? index : 0
        }

        this.initDataTable(this.id, this.pageLength, orderIndex)
    },

    watch: {
        items(newItems, oldItems) {
            if (newItems !== oldItems) {
                this.reInitDataTable()
            }
        },
        orderBy() {
            this.reInitDataTable()
        },

        pagingType() {
            this.reInitDataTable()
        },
        paginationLabels: {
            deep: true,
            handler() {
                this.reInitDataTable()
            }
        }
    },

    computed: {
        hasRowSelectedListener() {
            return this.$listeners && this.$listeners['row-selected'] !== undefined;
        },

        hasCustomWidths() {
            return this.headers.some(header => Boolean(header.width))
        }
    },

    methods: {
        getColumnAlignmentClass(header) {
            const align = header.align || 'start'

            return {
                'text-start': align === 'start',
                'text-center': align === 'center',
                'text-end': align === 'end',
            }
        },

        getColumnStyle(header) {
            if (!header.width) return {}

            return {
                width: header.width,
                minWidth: header.width,
                maxWidth: header.width,
            }
        },

        getCellValueClasses(header) {
            return {
                'fw-bold text-primary': Boolean(header.highlight)
            }
        },

        rowKey(item, index) {
            if (this.itemKey && item != null && item[this.itemKey] != null) {
                return String(item[this.itemKey])
            }
            return index
        },

        getDataTableColumns() {
            return this.headers.map((header) => {
                if (!header.width) {
                    return null
                }

                return {
                    width: header.width
                }
            })
        },

        getNonOrderableColumnTargets() {
            const targets = []
            this.headers.forEach((header, index) => {
                if (header.orderable === false) {
                    targets.push(index)
                }
            })
            return targets
        },

        initDataTable(id, pageLength = 10, orderIndex = 0) {
            if ($.fn.dataTable.isDataTable(`#${id}`)) {
                let table = $(`#${id}`).DataTable()
                table.destroy()
            }

            const nonOrderableTargets = this.getNonOrderableColumnTargets()
            const columnDefs = nonOrderableTargets.length
                ? [{ orderable: false, targets: nonOrderableTargets }]
                : undefined

            nextTick(() => {
                $(`#${id}`).DataTable({
                    searching: false,
                    destroy: true,
                    autoWidth: false,
                    pageLength: pageLength,
                    bLengthChange: false,
                    bInfo: true,
                    bFilter: true,
                    order: [[orderIndex, this.orderDir]],
                    pagingType: this.pagingType,
                    columns: this.getDataTableColumns(),
                    ...(columnDefs ? { columnDefs } : {}),
                    language: {
                        decimal: "",
                        emptyTable: "No hay información",
                        info: "Mostrando _START_ a _END_ de _TOTAL_ entradas",
                        infoEmpty: "Mostrando 0 a 0 de 0 entradas",
                        infoFiltered: "(Filtrado de _MAX_ total de entradas)",
                        thousands: ",",
                        lengthMenu: "Mostrar _MENU_ entradas",
                        loadingRecords: "Cargando...",
                        processing: "Procesando...",
                        search: "Buscar:",
                        zeroRecords: "Sin resultados encontrados",

                        paginate: {
                            first: this.paginationLabels.first ?? "Primero",
                            last: this.paginationLabels.last ?? "Último",
                            next: this.paginationLabels.next ?? "Siguiente",
                            previous: this.paginationLabels.previous ?? "Anterior"
                        }
                    },
                })
            })
        },

        reInitDataTable() {
            let orderIndex = 0

            if (this.orderBy) {
                const index = this.headers.findIndex(h => h.key === this.orderBy)
                orderIndex = index >= 0 ? index : 0
            }

            this.initDataTable(this.id, this.pageLength, orderIndex)
        },

        onRowClick(event, item) {
            const isInteractive = event.target.closest('a, button, .dropdown, [data-bs-toggle="dropdown"], input, select');
            if (isInteractive) return;

            if (this.clickableRow) {
                this.$emit('row-click', item);
                return;
            }
            this.handleRowClick(item);
        },

        handleRowClick(item) {
            if (this.selectedRow === item) {
                this.selectedRow = null;
            } else {
                this.selectedRow = item;
            }
            this.$emit('row-selected', this.selectedRow);
        },
    }
}
</script>

<template>
    <div class="datatable-root w-100">
        <div class="table-responsive mb-1 w-100">
            <table
                class="table table-hover table-striped table-sm table-nowrap align-middle w-100"
                :class="{ 'table-fixed-layout': hasCustomWidths }"
                :id="id"
            >
                <thead class="table-light text-muted table-header-high">
                    <tr>
                        <th
                            v-for="(header, index) in headers"
                            :key="index"
                            :style="getColumnStyle(header)"
                            :class="getColumnAlignmentClass(header)"
                        >
                            <slot :name="`head-${header.key}`" :header="header">
                                {{ header.label }}
                            </slot>
                        </th>
                    </tr>
                </thead>

                <tbody class="list form-check-all">
                    <tr
                        class="text-uppercase"
                        v-for="(item, i) in items"
                        :key="rowKey(item, i)"
                        @click="onRowClick($event, item)"
                        :class="[
                            { 'table-light': selectedRow === item },
                            { 'cursor-pointer': clickableRow }
                        ]"
                    >
                        <td
                            v-for="(header, index) in headers"
                            :key="index"
                            :style="getColumnStyle(header)"
                            :class="getColumnAlignmentClass(header)"
                        >
                            <slot :name="`cell-${header.key}`" :item="item" :header="header">
                                <span :class="[getCellValueClasses(header), getColumnAlignmentClass(header)]">
                                    {{ item[header.key] }}
                                </span>
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<style scoped>
    table.dataTable {
        margin-top: 0px !important;
    }
    .table-fixed-layout {
        table-layout: fixed;
    }
    :deep(.dataTables_wrapper) {
        width: 100%;
    }
    :deep(table.dataTable) {
        width: 100% !important;
    }
    .table-header-high > tr > th {
        padding-top: 0.75rem !important;
        padding-bottom: 0.75rem !important;
        vertical-align: middle;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .table > tbody > tr > td {
        padding-top: 0.4rem !important;
        padding-bottom: 0.4rem !important;
        vertical-align: middle;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .cursor-pointer {
        cursor: pointer;
    }
    /* Contenedor general (simula tu wrapper) */
    :deep(.dataTables_wrapper .dataTables_paginate) {
        display: flex;
        justify-content: flex-end;
        padding: 4px 0;
    }

    /* Reset base */
    :deep(.dataTables_paginate .paginate_button) {
        border: none !important;
        background: transparent !important;
        padding: 0 !important;
        margin: 0 0.20rem !important; /* 👈 espacio izquierda y derecha */
    }

    /* Estilo tipo Bootstrap page-link */
    :deep(.dataTables_paginate .paginate_button) {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        padding: 4px 10px;
        margin: 0 2px;

        font-size: 0.875rem;
        border-radius: 6px;

        color: #6c757d !important;
        background-color: transparent;
        cursor: pointer;
    }

    /* Hover */
    :deep(.dataTables_paginate .paginate_button:hover) {
        background-color: #f1f3f5 !important;
        color: #495057 !important;
    }

    /* Disabled */
    :deep(.dataTables_paginate .paginate_button.disabled) {
        opacity: 0.5;
        pointer-events: none;
    }

    /* Flechas separación */
    :deep(.dataTables_paginate .paginate_button.previous) {
        margin-right: 4px;
    }

    :deep(.dataTables_paginate .paginate_button.next) {
        margin-left: 4px;
    }

    /* Info alineada (opcional para parecerse más) */
    :deep(.dataTables_wrapper .dataTables_info) {
        font-size: 0.875rem;
        color: #6c757d;
        padding-top: 4px;
    }
</style>