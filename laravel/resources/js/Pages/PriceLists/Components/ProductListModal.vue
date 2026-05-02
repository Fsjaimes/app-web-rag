<script>
    // =======================
    // IMPORTS
    // =======================
    import { ref } from 'vue';

    export default {
        // =======================
        // CONFIG
        // =======================
        name: 'ProductListModal',
        emits: ['close', 'selected-items'],

        // =======================
        // DATA
        // =======================
        data() {
            return {
                headers: [
                    { label: 'Codigo', key: 'code', highlight: true },
                    { label: 'Producto', key: 'name' },
                ],
                itemsBackup: [],
                items: [
                    { id: 1, code: '1234567890', name: 'Producto 1' },
                    { id: 2, code: '1234567890', name: 'Producto 2' },
                    { id: 3, code: '1234567890', name: 'Producto 3' },
                    { id: 4, code: '1234567890', name: 'Producto 4' },
                    { id: 5, code: '1234567890', name: 'Producto 5' },
                    { id: 6, code: '1234567890', name: 'Producto 6' },
                    { id: 7, code: '1234567890', name: 'Producto 7' },
                    { id: 8, code: '1234567890', name: 'Producto 8' },
                    { id: 9, code: '1234567890', name: 'Producto 9' },
                    { id: 10, code: '1234567890', name: 'Producto 10' },
                    { id: 11, code: '1234567890', name: 'Producto 11' },
                    { id: 12, code: '1234567890', name: 'Producto 12' },
                    { id: 13, code: '1234567890', name: 'Producto 13' },
                ],
                selectedItems: [],
                pageLength: 10,
            };
        },

        // =======================
        // METHODS
        // =======================
        methods: {

            /**
             * Inicializar la tabla de productos para seleccionar
             */
            initDataTable() {
                const vm = this;
                $('#products-list-modal').DataTable({
                    searching: false,
                    destroy: true,
                    pageLength: this.pageLength,
                    bLengthChange: false,
                    bInfo: true,
                    bFilter: true,
                    drawCallback() {
                        vm.syncCheckboxesFromSelection();
                    },
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
                    },
                });

                this.syncCheckboxesFromSelection();
            },

            /**
             * Sincronizar los checkboxes con los items seleccionados
             * para que se refleje el estado de los checkboxes en la tabla
             */
            syncCheckboxesFromSelection() {
                const selected = new Set(this.selectedItems);
                $('#products-list-modal tbody .row-check').each(function () {
                    const idAttr = this.id || '';
                    const match = idAttr.match(/^row-check-(.+)$/);
                    if (!match) {
                        return;
                    }
                    const rowId = match[1];
                    $(this).prop('checked', selected.has(Number(rowId)) || selected.has(rowId));
                });
                const allSelected =
                    this.items.length > 0 &&
                    this.items.every((item) => selected.has(item.id));
                $('#select-all').prop('checked', allSelected);
            },

            /**
             * Manejar el cambio de estado del checkbox de un item
             * @param id - El id del item a seleccionar
             * @param event - El evento de cambio de estado del checkbox
             */
            onRowCheckboxChange(id, event) {
                const checked = event.target.checked;
                if (checked) {
                    if (!this.selectedItems.includes(id)) {
                        this.selectedItems.push(id);
                    }
                } else {
                    this.selectedItems = this.selectedItems.filter((item) => item !== id);
                }
                this.syncCheckboxesFromSelection();
            },

            /**
             * Seleccionar un item
             * @param id - El id del item a seleccionar
             */
            selectItem(id) {
                const idx = this.selectedItems.indexOf(id);
                if (idx !== -1) {
                    this.selectedItems.splice(idx, 1);
                } else {
                    this.selectedItems.push(id);
                }
                this.syncCheckboxesFromSelection();
            },

            /**
             * Seleccionar todos los items
             * @param event - El evento de cambio de estado del checkbox
             */
            selectAllItems(event) {
                const checked = event.target.checked;
                if (checked) {
                    this.selectedItems = this.items.map((item) => item.id);
                } else {
                    this.selectedItems = [];
                }
                this.syncCheckboxesFromSelection();
            },

            /**
             * Enviar los items seleccionados al componente padre
             */
            sendSelectedItems() {
                const selectedProducts = this.items
                    .filter((item) => this.selectedItems.includes(item.id))
                    .map((item) => ({
                        id: item.id,
                        code: item.code,
                        name: item.name,
                        price: 0,
                    }));
                this.$emit('selected-items', selectedProducts);
                this.$emit('close');
            },

            searchItems(event) {
                const term = event.target.value.toLowerCase().trim();

                this.items = this.itemsBackup.filter((item) => {
                    return (
                        item.name.toLowerCase().includes(term) ||
                        item.code.toLowerCase().includes(term)
                    );
                });
                console.log(this.items);
            }
        },

        // =======================
        // MOUNTED
        // =======================
        mounted() {
            this.itemsBackup = [...this.items]; 
            this.initDataTable();
        },
    };
</script>

<template>
    <div class="modal modal-xl fade show d-block" tabindex="-1" aria-labelledby="myModalLabel" aria-modal="true" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myModalLabel">Agregar Productos</h5>
                    <button
                        type="button"
                        class="btn-close"
                        aria-label="Close"
                        @click="$emit('close')"
                    ></button>
                </div>
                <div class="modal-body">
                    <div class="form-icon mb-3" style="width: 100%;">
                        <input type="text" class="form-control form-control-icon" id="iconInput" placeholder="Buscar..." @input="searchItems($event)">
                        <i class="ri-search-line"></i>
                    </div>
                    <div class="table-responsive table-card mb-1">
                        <table class="table table-hover table-striped table-sm align-middle" id="products-list-modal">
                            <thead class="table-light text-muted table-header-high">
                                <tr style="height: 40px;">
                                    <th style="width:6%;">
                                        <div class="form-check">
                                            <input style="width: 17px; height: 17px;" class="form-check-input" type="checkbox" id="select-all" @click.stop @change.stop="selectAllItems($event)">
                                        </div>
                                    </th>
                                    <th style="width:14%;" class="text-start">Código</th>
                                    <th class="text-start">Producto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items" :key="item.id" @click="selectItem(item.id)" style="cursor: pointer; height: 40px;">
                                    <td>
                                        <div class="form-check">
                                            <input style="width: 17px; height: 17px;" class="form-check-input row-check" type="checkbox" :id="'row-check-' + item.id" @click.stop @change.stop="onRowCheckboxChange(item.id, $event)">
                                        </div>
                                    </td>
                                    <td class="text-start fw-bold text-primary">{{ item.code }}</td>
                                    <td class="text-uppercase">{{ item.name }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-light"
                        @click="$emit('close')"
                    >
                        Cerrar
                    </button>
                    <button type="button" class="btn btn-primary"  :disabled="selectedItems.length === 0" @click="sendSelectedItems()">
                        Agregar selecciones
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
</template>
