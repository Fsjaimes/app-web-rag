<script>
import Layout from "@/Layouts/main.vue";
// Components
import PageHeader from "@/Components/page-header.vue";
import Select2 from '@/Components/Select2.vue';
import AutocompleteSearchInput from '@/Components/AutocompleteSearchInput.vue';
import Dropzone from '@/Components/widgets/dropZone.vue';
import FileList from '@/Components/FileList.vue';
import DataTable from '@/Components/DataTable.vue';
import GenericDetailTable from '@/Pages/Products/Components/GenericDetailTable.vue';
// Composables
import { router } from '@inertiajs/vue3';
import { useFetchPetition } from '@/Composables/useFetchPetition.js';
import { useAlert } from '@/Composables/useSweetAlert.js';
import { useFormErrorFocus } from '@/Composables/useFormErrorFocus.js';
// Variables
const { fetchPetition } = useFetchPetition();
const { showAlert, showConfirm, showWarning } = useAlert();
const { focusFirstFormError } = useFormErrorFocus();

export default {
    name: 'ProductShow',
    components: {
        Layout,
        PageHeader,
        Select2,
        AutocompleteSearchInput,
        Dropzone,
        FileList,
        DataTable,
        GenericDetailTable,
    },
    props: {
        productCategories: {
            type: Array,
            required: true,
            default: () => [],
        },
        warehousesOptions: {
            type: Array,
            required: true,
            default: () => [
                { id: 2, code: '001', description: 'Bucaramanga' },
                { id: 3, code: '002', description: 'Floridablanca' },
                { id: 4, code: '003', description: 'Girón' },
            ],
        },
        taxes: {
            type: Array,
            required: false,
            default: () => [
                { id: 1, description: 'IVA', percentage: 19 },
                { id: 2, description: 'IVA 5%', percentage: 5 },
                { id: 3, description: 'IVA 0%', percentage: 0 },
                { id: 4, description: 'IVA 8%', percentage: 8 },
                { id: 5, description: 'IVA Exento', percentage: 0 },
                { id: 6, description: 'IVA 16% (Histórico)', percentage: 16 },
            ],
        },
        unitOfMeasures: {
            type: Array,
            required: false,
            default: () => [
                { id: 1, description: 'CANTIDAD'},
                { id: 2, description: 'KILOGRAMO'},
                { id: 3, description: 'GRAMO'},
                { id: 4, description: 'LITRO'},
                { id: 5, description: 'MILILITRO'},
                { id: 6, description: 'METRO'},
                { id: 7, description: 'CENTIMETRO'},
                { id: 8, description: 'PULGADA'},
            ],
        },
        priceLists: {
            type: Array,
            default: () => [],
        },
        productTypes: {
            type: Array,
            required: false,
            default: () => [
                { id: 1, name: 'Producto Administrativo'},
                { id: 2, name: 'Producto de Venta'},
                { id: 3, name: 'Producto Promocional'},
                { id: 4, name: 'Producto Cliente'},
            ],
        },
        providers: {
            type: Array,
            required: false,
            default: () => [
                { id: 1, name: 'Sodeker1'},
                { id: 2, name: 'Sodeker2'},
                { id: 3, name: 'Sodeker3'},
                { id: 4, name: 'Sodeker4'},
                { id: 5, name: 'Sodeker5'},
            ],
        },
        products: {
            type: Array,
            required: true,
            default: () => [],
        },
        product: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            formsProduct: {
                general: {
                    code: '',
                    name: '',
                    description: '',
                    productTypeId: '',
                    categoryId: '',
                    unitOfMeasureId: '',
                    taxId: '',
                    salesAccountCode: '',
                    returnsAccountCode: '',
                    barcode: '',
                    status: true,
                    isSellable: false,
                },
                images: [],
                suppliers: [],
                warehouses: [],
                priceLists: [],
                recipes: [],
                presentations: [],
            },
            priceListId: '',
            saleAccount: null,
            returnAccount: null,
            /** Acciones de FileList (`favorite` = imagen principal). */
            productImageActions: {
                view: true,
                delete: true,
                download: false,
                favorite: true,
            },
            isSubmitting: false,
            codeExists: false,
            validateTimer: null,
            priceListTableHeaders: [
                { label: '#', key: 'lineNo'},
                { label: 'Lista de precios', key: 'listLabel' },
                { label: 'Acciones', key: 'actions', orderable: false},
            ],
            /** Acciones por fila: extensible sin tocar la plantilla de celdas. */
            priceListRowActions: [
                {
                    key: 'remove',
                    icon: 'ri-delete-bin-5-fill',
                    linkClass: 'text-danger',
                    title: 'Quitar de la tabla',
                },
            ],
            /** Errores de validación cliente solo para la card Datos generales (mismo patrón que Sat / ThirdParties). */
            clientErrors: {},
            suppliers: [],
            detailColumnsSuppliers: [
                {
                    key: 'supplierId',
                    label: 'Proveedor',
                    model: 'supplierId',
                    type: 'select',
                    optionsKey: 'suppliers',
                    valueField: 'id',
                    primaryField: 'name',
                    portalTarget: '#accordion-supplier',
                    align: 'start',
                },
                {
                    key: 'reference',
                    label: 'Referencia',
                    model: 'reference',
                    type: 'text',
                    align: 'start',
                },
            ],
            detailOptionSourcesSuppliers: {
                suppliers: [],
            },
            warehouses: [],
            detailColumnsWarehouses: [
                {
                    key: 'warehouse',
                    label: 'Bodega',
                    model: 'warehouseId',
                    type: 'select',
                    optionsKey: 'warehouses',
                    portalTarget: '#accordion-warehouses',
                    valueField: 'id',
                    primaryField: 'name',
                    align: 'start',
                },
                {
                    key: 'stockMinimum',
                    label: 'Stock mínimo',
                    model: 'stockMinimum',
                    type: 'number_thousands',
                    align: 'end',
                },
                {
                    key: 'stockMaximum',
                    label: 'Stock máximo',
                    model: 'stockMaximum',
                    type: 'number_thousands',
                    align: 'end',
                },
                {
                    key: 'stockIdeal',
                    label: 'Stock ideal',
                    model: 'stockIdeal',
                    type: 'number_thousands',
                    align: 'end',
                },
            ],
            detailOptionSources: {
                warehouses: [],
            },
            priceLists: [],
            detailColumnsPriceLists: [
                {
                    key: 'priceListId',
                    label: 'Lista de precios',
                    model: 'priceListId',
                    type: 'select',
                    optionsKey: 'priceLists',
                    valueField: 'id',
                    primaryField: 'name',
                    align: 'start',
                },
                {
                    key: 'price',
                    label: 'Precio',
                    model: 'price',
                    type: 'number_thousands',
                    align: 'end',
                },
                {
                    key: 'discount',
                    label: 'Descuento',
                    model: 'discount',
                    type: 'number_thousands',
                    align: 'end',
                },
            ],
            detailOptionSourcesPriceLists: {
                priceLists: [],
            },
            recipes: [],
            detailColumnsRecipes: [
                {
                    key: 'productId',
                    label: 'Productos',
                    model: 'productId',
                    type: 'select',
                    optionsKey: 'products',
                    valueField: 'id',
                    primaryField: 'name',
                    align: 'start',
                },
                {
                    key: 'quantity',
                    label: 'Cantidad',
                    model: 'quantity',
                    type: 'number_thousands',
                    align: 'end',
                },
                {
                    key: 'unitOfMeasureId',
                    label: 'Unidad',
                    model: 'unitOfMeasureId',
                    type: 'select',
                    optionsKey: 'units',
                    valueField: 'id',
                    primaryField: 'description',
                    align: 'start',
                },
            ],
            detailOptionSourcesRecipes: {
                products: [],
                units: [],
            },
            presentations: [],
            detailColumnsPresentations: [
                {
                    key: 'name',
                    label: 'Nombre',
                    model: 'name',
                    type: 'text',
                    align: 'start',
                },
                {
                    key: 'status',
                    label: 'Estado',
                    model: 'status',
                    type: 'select',
                    optionsKey: 'statuses',
                    valueField: 'id',
                    primaryField: 'description',
                    align: 'start',
                },
                {
                    key: 'unitOfMeasureId',
                    label: 'Unidad',
                    model: 'unitOfMeasureId',
                    type: 'select',
                    optionsKey: 'units',
                    valueField: 'id',
                    primaryField: 'description',
                    align: 'start',
                },
                {
                    key: 'quantity',
                    label: 'Cantidad',
                    model: 'quantity',
                    type: 'number_thousands',
                    align: 'end',
                },
            ],
            detailOptionSourcesPresentations: {
                statuses: [],
                units: [],
            },
            statuses: [
                {
                    id: 1,
                    name: 'Activo',
                    description: 'Activo',
                },
                {
                    id: 2,
                    name: 'Inactivo',
                    description: 'Inactivo',
                },
            ],
        };
    },
    watch: {
        'formsProduct.general.code'(newValue) { // Validar el existencia de producto por código
            // Limpiar timer anterior
            clearTimeout(this.validateTimer);

            // Si está vacío, no validamos
            if (!newValue || newValue.trim() === '') {
                this.codeExists = false;
                return;
            }

            // Ejecutar solo cuando el usuario deja de escribir
            this.validateTimer = setTimeout(() => {
                this.validateIfExists();
            }, 1000); // 1 segundo después de dejar de escribir
        },
        warehouses: {
            immediate: true,
            deep: true,
            handler(newRows) {
                this.syncWarehousesToForm(newRows);
            },
        },
        suppliers: {
            immediate: true,
            deep: true,
            handler(newSuppliers) {
                this.syncSuppliersToForm(newSuppliers);
            },
        },
        priceLists: {
            immediate: true,
            deep: true,
            handler(newPriceLists) {
                this.syncPriceListsToForm(newPriceLists);
            },
        },
        recipes: {
            immediate: true,
            deep: true,
            handler(newRecipes) {
                this.syncRecipesToForm(newRecipes);
            },
        },
        presentations: {
            immediate: true,
            deep: true,
            handler(newPresentations) {
                this.syncPresentationsToForm(newPresentations);
            },
        },
    },
    mounted() {
        this.$nextTick(() => {
            this.hydrateProductData();
        });
    },
    computed:{
        detailOptionSourcesSuppliersFromProps() {
            return {
                ...this.detailOptionSourcesSuppliers,
                suppliers: Array.isArray(this.$props.providers) ? this.$props.providers : [],
            };
        },

        detailOptionSourcesWarehousesFromProps() {
            return {
                ...this.detailOptionSources,
                warehouses: Array.isArray(this.$props.warehousesOptions)
                    ? this.$props.warehousesOptions.filter((b) => b?.code !== '000')
                    : [],
            };
        },

        detailOptionSourcesPriceListsFromProps() {
            return {
                ...this.detailOptionSourcesPriceLists,
                priceLists: Array.isArray(this.$props.priceLists) ? this.$props.priceLists : [],
            };
        },

        detailOptionSourcesRecipesFromProps() {
            return {
                ...this.detailOptionSourcesRecipes,
                products: Array.isArray(this.$props.products) ? this.$props.products : [],
                units: Array.isArray(this.$props.unitOfMeasures) ? this.$props.unitOfMeasures : [],
            };
        },

        detailOptionSourcesPresentationsFromProps() {
            return {
                ...this.detailOptionSourcesPresentations,
                statuses: Array.isArray(this.statuses) ? this.statuses : [],
                units: Array.isArray(this.$props.unitOfMeasures) ? this.$props.unitOfMeasures : [],
            };
        },

        saleAccountFilter() {
            return this.formsProduct.general.returnsAccountCode
                ? { disabled_ids: [this.formsProduct.general.returnsAccountCode] }
                : {}
        },

        returnAccountFilter() {
            return this.formsProduct.general.salesAccountCode
                ? { disabled_ids: [this.formsProduct.general.salesAccountCode] }
                : {}
        },

        /** Opciones del selector de lista: prop `priceLists` o, si está vacío, categorías (comportamiento previo del formulario). */
        priceListSelectOptions() {
            const list = this.$props.priceLists;
            return Array.isArray(list) && list.length > 0 ? list : this.productCategories;
        },
    },

    methods: {
        generateTempUuid() {
            return `tmp-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
        },
        hasMeaningfulValue(value) {
            if (Array.isArray(value)) {
                return value.some((item) => this.hasMeaningfulValue(item));
            }
            if (value && typeof value === 'object') {
                return Object.values(value).some((item) => this.hasMeaningfulValue(item));
            }
            if (typeof value === 'number') {
                return !Number.isNaN(value);
            }
            if (typeof value === 'boolean') {
                return true;
            }
            if (value === null || value === undefined) {
                return false;
            }
            return String(value).trim() !== '';
        },
        sanitizeDetailRows(rows = [], allowedFields = []) {
            if (!Array.isArray(rows)) {
                return [];
            }
            return rows.filter((row) => {
                if (!row || typeof row !== 'object') {
                    return false;
                }
                const fields = allowedFields.length ? allowedFields : Object.keys(row);
                return fields.some((field) => this.hasMeaningfulValue(row[field]));
            });
        },
        makeWarehouseDetailRow() {
            return {
                tempUuid: this.generateTempUuid(),
                warehouseId: '',
                stockMinimum: '',
                stockMaximum: '',
                stockIdeal: '',
            };
        },
        makeSupplierDetailRow() {
            return {
                tempUuid: this.generateTempUuid(),
                supplierId: '',
                reference: '',
            };
        },
        makePriceListDetailRow() {
            return {
                tempUuid: this.generateTempUuid(),
                priceListId: '',
                price: '',
                discount: '',
            };
        },
        makeRecipeDetailRow() {
            return {
                tempUuid: this.generateTempUuid(),
                productId: '',
                quantity: '',
                unitOfMeasureId: '',
            };
        },
        makePresentationDetailRow() {
            return {
                tempUuid: this.generateTempUuid(),
                name: '',
                status: '',
                unitOfMeasureId: '',
                quantity: '',
            };
        },
        ensureDefaultDetailRows() {
            if (!this.suppliers.length) {
                this.suppliers.push(this.makeSupplierDetailRow());
            }
            if (!this.warehouses.length) {
                this.warehouses.push(this.makeWarehouseDetailRow());
            }
            if (!this.priceLists.length) {
                this.priceLists.push(this.makePriceListDetailRow());
            }
            if (!this.recipes.length) {
                this.recipes.push(this.makeRecipeDetailRow());
            }
            if (!this.presentations.length) {
                this.presentations.push(this.makePresentationDetailRow());
            }
            this.handleDetailUpdated();
        },
        createPriceListDetail() {
            this.priceLists.push(this.makePriceListDetailRow());
        },
        createSupplierDetail() {
            this.suppliers.push(this.makeSupplierDetailRow());
        },
        createWorkhouseDetail() {
            this.warehouses.push(this.makeWarehouseDetailRow());
        },
        createRecipeDetail() {
            this.recipes.push(this.makeRecipeDetailRow());
        },
        createPresentationDetail() {
            this.presentations.push(this.makePresentationDetailRow());
        },
        deleteWarehouseDetail(tempUuid) {
            this.warehouses = this.warehouses.filter((detail) => (detail.tempUuid || detail.uuid) !== tempUuid);
        },
        deleteSupplierDetail(tempUuid) {
            this.suppliers = this.suppliers.filter((detail) => (detail.tempUuid || detail.uuid) !== tempUuid);
        },
        deletePriceListDetail(tempUuid) {
            this.priceLists = this.priceLists.filter((detail) => (detail.tempUuid || detail.uuid) !== tempUuid);
        },
        deleteRecipeDetail(tempUuid) {
            this.recipes = this.recipes.filter((detail) => (detail.tempUuid || detail.uuid) !== tempUuid);
        },
        deletePresentationDetail(tempUuid) {
            this.presentations = this.presentations.filter((detail) => (detail.tempUuid || detail.uuid) !== tempUuid);
        },
        duplicateWarehouseDetail(tempUuid) {
            const sourceIndex = this.warehouses.findIndex((detail) => (detail.tempUuid || detail.uuid) === tempUuid);
            if (sourceIndex < 0) return;
            const source = this.warehouses[sourceIndex];
            const copy = {
                ...source,
                tempUuid: this.generateTempUuid(),
            };
            this.warehouses.splice(sourceIndex + 1, 0, copy);
        },
        duplicateSupplierDetail(tempUuid) {
            const sourceIndex = this.suppliers.findIndex((detail) => (detail.tempUuid || detail.uuid) === tempUuid);
            if (sourceIndex < 0) return;
            const source = this.suppliers[sourceIndex];
            const copy = {
                ...source,
                tempUuid: this.generateTempUuid(),
            };
            this.suppliers.splice(sourceIndex + 1, 0, copy);
        },
        duplicatePriceListDetail(tempUuid) {
            const sourceIndex = this.priceLists.findIndex((detail) => (detail.tempUuid || detail.uuid) === tempUuid);
            if (sourceIndex < 0) return;
            const source = this.priceLists[sourceIndex];
            const copy = {
                ...source,
                tempUuid: this.generateTempUuid(),
            };
            this.priceLists.splice(sourceIndex + 1, 0, copy);
        },
        duplicateRecipeDetail(tempUuid) {
            const sourceIndex = this.recipes.findIndex((detail) => (detail.tempUuid || detail.uuid) === tempUuid);
            if (sourceIndex < 0) return;
            const source = this.recipes[sourceIndex];
            const copy = {
                ...source,
                tempUuid: this.generateTempUuid(),
            };
        },
        duplicatePresentationDetail(tempUuid) {
            const sourceIndex = this.presentations.findIndex((detail) => (detail.tempUuid || detail.uuid) === tempUuid);
            if (sourceIndex < 0) return;
            const source = this.presentations[sourceIndex];
            const copy = {
                ...source,
                tempUuid: this.generateTempUuid(),
            };
        },
        handleDetailUpdated() {
            this.syncSuppliersToForm(this.suppliers);
            this.syncWarehousesToForm(this.warehouses);
            this.syncPriceListsToForm(this.priceLists);
            this.syncRecipesToForm(this.recipes);
            this.syncPresentationsToForm(this.presentations);
        },
        syncSuppliersToForm(rows = []) {
            this.formsProduct.suppliers = (Array.isArray(rows) ? rows : []).map((row) => ({
                supplierId: row?.supplierId ?? '',
                reference: row?.reference ?? '',
            }));
        },
        syncWarehousesToForm(rows = []) {
            this.formsProduct.warehouses = (Array.isArray(rows) ? rows : []).map((row) => ({
                warehouseId: row?.warehouseId ?? row?.branchId ?? '',
                stockMinimum: row?.stockMinimum ?? '',
                stockMaximum: row?.stockMaximum ?? '',
                stockIdeal: row?.stockIdeal ?? '',
            }));
        },
        syncPriceListsToForm(rows = []) {
            this.formsProduct.priceLists = (Array.isArray(rows) ? rows : []).map((row) => ({
                priceListId: row?.priceListId ?? '',
                price: row?.price ?? '',
                discount: row?.discount ?? '',
            }));
        },
        syncRecipesToForm(rows = []) {
            this.formsProduct.recipes = (Array.isArray(rows) ? rows : []).map((row) => ({
                productId: row?.productId ?? '',
                quantity: row?.quantity ?? '',
                unitOfMeasureId: row?.unitOfMeasureId ?? '',
            }));
        },
        syncPresentationsToForm(rows = []) {
            this.formsProduct.presentations = (Array.isArray(rows) ? rows : []).map((row) => ({
                name: row?.name ?? '',
                status: row?.status ?? '',
                unitOfMeasureId: row?.unitOfMeasureId ?? '',
                quantity: row?.quantity ?? '',
            }));
        },
        clearClientError(field) {
            if (!(field in this.clientErrors)) {
                return;
            }
            const next = { ...this.clientErrors };
            delete next[field];
            this.clientErrors = next;
        },

        isEmptySelect(value) {
            return value === '' || value === null || value === undefined || value === 0 || value === '0';
        },

        /** Obligatorios de la card Datos generales (según asteriscos en la vista). */
        collectGeneralRequiredFieldErrors() {
            const e = {};
            const f = this.formsProduct.general;
            const z = (v) => this.isEmptySelect(v);

            if (!f.code || String(f.code).trim() === '') {
                e.code = true;
            }
            if (!f.name || String(f.name).trim() === '') {
                e.name = true;
            }
            if (!f.barcode || String(f.barcode).trim() === '') {
                e.barcode = true;
            }
            if (z(f.unitOfMeasureId)) {
                e.unitOfMeasureId = true;
            }
            if (z(f.taxId)) {
                e.taxId = true;
            }
            if (z(f.categoryId)) {
                e.categoryId = true;
            }
            if (z(f.productTypeId)) {
                e.productTypeId = true;
            }
            if (!f.salesAccountCode || String(f.salesAccountCode).trim() === '') {
                e.salesAccountCode = true;
            }
            if (!f.returnsAccountCode || String(f.returnsAccountCode).trim() === '') {
                e.returnsAccountCode = true;
            }

            return e;
        },

        handleImageFileSelected(file) {
            if (!file) {
                return;
            }

            const extension = file.name.includes('.') ? file.name.split('.').pop().toLowerCase() : '';
            const fileSizeMb = (file.size / (1024 * 1024)).toFixed(2);

            const imageId = `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
            const hasFavorite = this.formsProduct.images.some((image) => image?.isFavorite);

            this.formsProduct.images.push({
                id: imageId,
                uuid: imageId,
                name: file.name,
                size: fileSizeMb,
                extension,
                url: URL.createObjectURL(file),
                pending: true,
                isFavorite: !hasFavorite,
                position: this.formsProduct.images.length + 1,
            });
        },

        deleteImageFile(uuid) {
            const removed = this.formsProduct.images.find(
                (f) => String(f.id ?? f.uuid) === String(uuid)
            );
            const wasFavorite = Boolean(removed?.isFavorite);
            this.formsProduct.images = this.formsProduct.images.filter(
                (file) => String(file.id ?? file.uuid) !== String(uuid)
            );
            if (!this.formsProduct.images.length) {
                return;
            }
            const hasFavorite = this.formsProduct.images.some((img) => img?.isFavorite);
            if (!hasFavorite || wasFavorite) {
                this.formsProduct.images = this.formsProduct.images.map((img, idx) => ({
                    ...img,
                    isFavorite: idx === 0,
                }));
            }
        },

        setImageFavorite(uuid) {
            this.formsProduct.images = this.formsProduct.images.map((img) => ({
                ...img,
                isFavorite: String(img.id ?? img.uuid) === String(uuid),
            }));
        },

        onPriceListSelectChange(value) {
            if (value === '' || value === null || value === undefined) {
                return;
            }
            this.addPriceListRowFromSelection();
        },

        addPriceListRowFromSelection() {
            const plId = this.priceListId;
            if (plId === '' || plId === null || plId === undefined) {
                return;
            }
            const dup = this.formsProduct.priceLists.some(
                (r) => String(r.priceListId) === String(plId)
            );
            if (dup) {
                showWarning('¡Alerta!', 'Lista ya agregada.');
                this.$nextTick(() => {
                    this.priceListId = '';
                });
                return;
            }
            const listOpt = this.priceListSelectOptions.find((o) => String(o.id) === String(plId));
            this.formsProduct.priceLists.push({
                rowKey: `pl-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`,
                lineNo: this.formsProduct.priceLists.length + 1,
                priceListId: plId,
                listLabel: listOpt?.description ?? `ID ${plId}`,
            });
            this.priceListId = '';
        },

        runPriceListRowAction(actionKey, item) {
            if (actionKey === 'remove') {
                this.removePriceListRow(item);
            }
        },

        removePriceListRow(item) {
            const key = item?.rowKey;
            this.formsProduct.priceLists = this.formsProduct.priceLists
                .filter((r) => r.rowKey !== key)
                .map((r, i) => ({ ...r, lineNo: i + 1 }));
        },

        hydrateProductData() {
            const payload = this.$props.product && typeof this.$props.product === 'object' ? this.$props.product : {};
            const general = payload.general && typeof payload.general === 'object' ? payload.general : {};

            this.formsProduct.general = {
                ...this.formsProduct.general,
                code: general.code ?? '',
                name: general.name ?? '',
                description: general.description ?? '',
                productTypeId: general.productTypeId ?? '',
                categoryId: general.categoryId ?? '',
                unitOfMeasureId: general.unitOfMeasureId ?? '',
                taxId: general.taxId ?? '',
                salesAccountCode: general.salesAccountCode ?? '',
                returnsAccountCode: general.returnsAccountCode ?? '',
                barcode: general.barcode ?? '',
                status: String(general.status ?? '0') === '1',
                isSellable: String(general.isSellable ?? '0') === '1',
            };

            this.formsProduct.images = Array.isArray(payload.images) ? payload.images : [];
            this.suppliers = Array.isArray(payload.suppliers) ? payload.suppliers : [];
            this.warehouses = Array.isArray(payload.warehouses) ? payload.warehouses : [];
            this.priceLists = Array.isArray(payload.priceLists) ? payload.priceLists : [];
            this.presentations = Array.isArray(payload.presentations) ? payload.presentations : [];
            this.recipes = Array.isArray(payload.productBundleItems) ? payload.productBundleItems : [];

            this.handleDetailUpdated();
        },

        formatValue(value) {
            if (value === null || value === undefined || value === '') {
                return 'Sin información';
            }
            return String(value);
        },

        formatBoolean(value) {
            return value ? 'Sí' : 'No';
        },

        resolveOptionLabel(options, value, keys = ['name', 'description', 'labelDescription']) {
            if (!Array.isArray(options) || value === null || value === undefined || value === '') {
                return 'Sin información';
            }
            const option = options.find((item) => String(item?.id) === String(value));
            if (!option) {
                return this.formatValue(value);
            }
            const label = keys.map((key) => option?.[key]).find((item) => item !== null && item !== undefined && item !== '');
            return this.formatValue(label ?? value);
        },

        async goBack() {
            // const confirmed = await showConfirm(
            //     'warning',
            //     '¡Alerta!',
            //     '¿Está seguro que desea cancelar?',
            //     'Sí, cancelar'
            // );
            // if (!confirmed) {
            //     return;
            // }
            router.visit('/productos');
        },

        async saveProduct() {
            this.clientErrors = {};

            const requiredErrors = this.collectGeneralRequiredFieldErrors();
            if (Object.keys(requiredErrors).length > 0) {
                this.clientErrors = requiredErrors;
                await this.$nextTick();
                focusFirstFormError(this.$refs.productCreateForm, requiredErrors);
                await showAlert(
                    'warning',
                    '¡Alerta!',
                    'Campos sin diligenciar. Revise los campos resaltados.',
                    2500,
                );
                return;
            }

            const confirmed = await showConfirm(
                'warning',
                '¡Alerta!',
                '¿Está seguro que desea crear producto?',
                'Sí, crear'
            );

            if (!confirmed) {
                return;
            }

            this.isSubmitting = true;
            const sanitizedImages = this.sanitizeDetailRows(this.formsProduct.images, [
                'name',
                'extension',
                'url',
            ]);
            const sanitizedPriceLists = this.sanitizeDetailRows(this.formsProduct.priceLists, [
                'priceListId',
                'price',
                'discount',
            ]);
            const sanitizedSuppliers = this.sanitizeDetailRows(this.formsProduct.suppliers, [
                'supplierId',
                'reference',
            ]);
            const sanitizedWarehouses = this.sanitizeDetailRows(this.formsProduct.warehouses, [
                'warehouseId',
                'stockMinimum',
                'stockMaximum',
                'stockIdeal',
            ]);
            const sanitizedRecipes = this.sanitizeDetailRows(this.formsProduct.recipes, [
                'productId',
                'quantity',
                'unitOfMeasureId',
            ]);
            const sanitizedPresentations = this.sanitizeDetailRows(this.formsProduct.presentations, [
                'name',
                'status',
                'unitOfMeasureId',
                'quantity',
            ]);
            const payload = {
                ...this.formsProduct.general,
                images: sanitizedImages,
                suppliers: sanitizedSuppliers,
                priceLists: sanitizedPriceLists,
                warehouses: sanitizedWarehouses,
                recipes: sanitizedRecipes,
                presentations: sanitizedPresentations,
            };
            console.log('payload: ', payload);

            try {
                const response = await fetchPetition('/products', {
                    method: 'POST',
                    body: payload,
                });

                if (response.ok) {
                    showAlert('success', '¡Éxito!', 'Producto creado correctamente', 1500);
                    router.visit('/productos');
                } else {
                    const data = response.data;
                    const message = data?.message || data?.errors ? Object.values(data.errors || {}).flat().join(' ') : 'Error al crear producto';
                    showAlert('error', 'Error', message, 3000);
                }
            } catch (error) {
                showAlert('error', 'Error inesperado', 'Ocurrió un error al crear el producto', 1500);
            } finally {
                this.isSubmitting = false;
            }
        },

        async validateIfExists() { // Validar si el código ya existe
            // Si el número de identificación está vacío, resetear la variable y retornar
            if (!this.formsProduct.general.code || this.formsProduct.general.code.trim() === "") {
                this.codeExists = false;
                return;
            }

            try {
                const response = await fetchPetition('/products/validate-code', {
                    method: 'POST',
                    body: this.formsProduct.general.code,
                });
                // El endpoint retorna { exists: true/false }
                this.codeExists = response.data?.exists;
            } catch (error) {
                console.error('Error validando existencia de producto por código:', error);
                this.codeExists = false;
            }
        },
    },
};
</script>

<template>
    <Layout>
        <PageHeader title="Detalle Producto" pageTitle="Productos" />
        <div class="product-create-form">
            <div class="row">
                <div class="col-8 product-create-stack">

                    <div class="row">
                        <div class="col-12">
                            <div class="card" id="card-branches">
                                <div class="card-body p-0">
                                    <div class="border-0">
                                        <h2 class="card-header py-2">
                                            <div class="d-flex align-items-center shadow-none">
                                                <i class="ri-file-list-line fs-16 text-primary me-2 fw-bold"></i>
                                                <h5 class="card-title mb-0 flex-fill text-black">Datos Generales</h5>
                                                <div class="d-flex justify-content-end align-items-center gap-2">
                                                    <button type="button" class="btn btn-light" @click="goBack">
                                                        Volver
                                                    </button>
                                                </div>
                                            </div>
                                        </h2>
                                        <div class="card-body pt-3 pb-1">
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-6">
                                                    <div
                                                        class="product-general-field-wrap"
                                                        data-form-error-anchor="code"
                                                    >
                                                        <label for="code" class="form-label">Código<span class="text-danger ms-1">*</span></label>
                                                        <p class="form-control-plaintext mb-0">{{ formatValue(formsProduct.general.code) }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div
                                                        class="product-general-field-wrap"
                                                        data-form-error-anchor="name"
                                                    >
                                                        <label for="name" class="form-label">Nombre<span class="text-danger ms-1">*</span></label>
                                                        <p class="form-control-plaintext mb-0">{{ formatValue(formsProduct.general.name) }}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row g-3 mb-3">
                                                <div class="col-md-6">
                                                    <div
                                                        class="product-general-field-wrap"
                                                        data-form-error-anchor="unitOfMeasureId"
                                                    >
                                                        <label for="sel_unit">Unidad Medida<span class="text-danger ms-1">*</span></label>
                                                        <p class="form-control-plaintext mb-0">
                                                            {{ resolveOptionLabel(unitOfMeasures, formsProduct.general.unitOfMeasureId, ['description']) }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="barcode" class="form-label">Código Barras</label>
                                                    <p class="form-control-plaintext mb-0">{{ formatValue(formsProduct.general.barcode) }}</p>
                                                </div>
                                            </div>

                                            <div class="row g-3 mb-3">
                                                <div class="col-12">
                                                    <label for="description" class="form-label">Descripción</label>
                                                    <p class="form-control-plaintext mb-0">{{ formatValue(formsProduct.general.description) }}</p>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-12">
                                                    <div class="switch-card h-100">
                                                        <div class="check-info-row">
                                                            <p class="mb-0 fw-semibold">¿El producto se vende?</p>
                                                            <p class="mb-0 text-muted">{{ formatBoolean(formsProduct.general.isSellable) }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="accordion" id="accordion-supplier">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading-supplier">
                                        <button class="accordion-button d-flex align-items-center shadow-none bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-supplier" aria-expanded="true" aria-controls="collapse-supplier">
                                            <i class="ri-team-line fs-16 text-primary me-2 fw-bold"></i>
                                            <h5 class="card-title mb-0 flex-fill text-black">Proveedores</h5>
                                        </button>
                                    </h2>
                                    <div id="collapse-supplier" class="accordion-collapse collapse show" aria-labelledby="heading-supplier" data-bs-parent="#accordion-supplier">
                                        <div class="accordion-body pt-3 pb-0 px-3">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered align-middle">
                                                    <thead>
                                                        <tr>
                                                            <th>Proveedor</th>
                                                            <th>Referencia</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(item, index) in suppliers" :key="item.tempUuid || item.uuid || index">
                                                            <td>{{ resolveOptionLabel(providers, item.supplierId, ['name']) }}</td>
                                                            <td>{{ formatValue(item.reference) }}</td>
                                                        </tr>
                                                        <tr v-if="!suppliers.length">
                                                            <td colspan="2" class="text-center text-muted">Sin registros</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="accordion" id="accordion-images">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading-images">
                                        <button class="accordion-button d-flex align-items-center shadow-none bg-white broder border-bottom" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-images" aria-expanded="true" aria-controls="collapse-images">
                                            <i class="ri-image-add-line fs-16 text-primary me-2 fw-bold"></i>
                                            <h5 class="card-title mb-0 flex-fill text-black">Imágenes</h5>
                                        </button>
                                    </h2>
                                    <div id="collapse-images" class="accordion-collapse collapse show" aria-labelledby="heading-images" data-bs-parent="#accordion-images">
                                        <div class="accordion-body pt-3 py-0 mt-4">
                                            <div class="row mb-3">
                                                <div class="col-12">
                                                    <ul class="list-group">
                                                        <li
                                                            v-for="(image, index) in formsProduct.images"
                                                            :key="image.uuid || image.id || index"
                                                            class="list-group-item d-flex justify-content-between align-items-center"
                                                        >
                                                            <span>{{ formatValue(image.name || image.fileName || image.url) }}</span>
                                                            <span v-if="image.isFavorite" class="badge bg-primary">Principal</span>
                                                        </li>
                                                        <li v-if="!formsProduct.images.length" class="list-group-item text-muted text-center">
                                                            Sin imágenes
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="accordion" id="accordion-warehouses">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading-warehouses">
                                        <button class="accordion-button d-flex align-items-center shadow-none bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-warehouses" aria-expanded="true" aria-controls="collapse-warehouses">
                                            <i class="ri ri-store-2-line fs-16 text-primary me-2 fw-bold"></i>
                                            <h5 class="card-title mb-0 flex-fill text-black">Bodegas</h5>
                                        </button>
                                    </h2>
                                    <div id="collapse-warehouses" class="accordion-collapse collapse show" aria-labelledby="heading-warehouses" data-bs-parent="#accordion-warehouses">
                                        <div class="accordion-body pt-3 pb-0 px-3">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered align-middle">
                                                    <thead>
                                                        <tr>
                                                            <th>Bodega</th>
                                                            <th class="text-end">Stock mínimo</th>
                                                            <th class="text-end">Stock máximo</th>
                                                            <th class="text-end">Stock ideal</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(item, index) in warehouses" :key="item.tempUuid || item.uuid || index">
                                                            <td>{{ resolveOptionLabel(warehousesOptions, item.warehouseId, ['name', 'description']) }}</td>
                                                            <td class="text-end">{{ formatValue(item.stockMinimum) }}</td>
                                                            <td class="text-end">{{ formatValue(item.stockMaximum) }}</td>
                                                            <td class="text-end">{{ formatValue(item.stockIdeal) }}</td>
                                                        </tr>
                                                        <tr v-if="!warehouses.length">
                                                            <td colspan="4" class="text-center text-muted">Sin registros</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="accordion" id="accordion-price-list-details">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading-price-list-details">
                                        <button class="accordion-button d-flex align-items-center shadow-none bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-price-list-details" aria-expanded="true" aria-controls="collapse-price-list-details">
                                            <i class="ri ri-price-tag-3-line fs-16 text-primary me-2 fw-bold"></i>
                                            <h5 class="card-title mb-0 flex-fill text-black">Lista Precios</h5>
                                        </button>
                                    </h2>
                                    <div id="collapse-price-list-details" class="accordion-collapse collapse show" aria-labelledby="heading-price-list-details" data-bs-parent="#accordion-price-list-details">
                                        <div class="accordion-body pt-3 pb-0 px-3">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered align-middle">
                                                    <thead>
                                                        <tr>
                                                            <th>Lista de precios</th>
                                                            <th class="text-end">Precio</th>
                                                            <th class="text-end">Descuento</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(item, index) in priceLists" :key="item.tempUuid || item.uuid || index">
                                                            <td>{{ resolveOptionLabel($props.priceLists, item.priceListId, ['name', 'description']) }}</td>
                                                            <td class="text-end">{{ formatValue(item.price) }}</td>
                                                            <td class="text-end">{{ formatValue(item.discount) }}</td>
                                                        </tr>
                                                        <tr v-if="!priceLists.length">
                                                            <td colspan="3" class="text-center text-muted">Sin registros</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="accordion" id="accordion-recipe-details">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading-recipe-details">
                                        <button class="accordion-button d-flex align-items-center shadow-none bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-recipe-details" aria-expanded="true" aria-controls="collapse-recipe-details">
                                            <i class="ri ri-file-list-3-line fs-16 text-primary me-2 fw-bold"></i>
                                            <h5 class="card-title mb-0 flex-fill text-black">Items Bundle</h5>
                                        </button>
                                    </h2>
                                    <div id="collapse-recipe-details" class="accordion-collapse collapse show" aria-labelledby="heading-recipe-details" data-bs-parent="#accordion-recipe-details">
                                        <div class="accordion-body pt-3 pb-0 px-3">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered align-middle">
                                                    <thead>
                                                        <tr>
                                                            <th>Producto</th>
                                                            <th class="text-end">Cantidad</th>
                                                            <th>Unidad de medida</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(item, index) in recipes" :key="item.tempUuid || item.uuid || index">
                                                            <td>{{ formatValue(item.productName || resolveOptionLabel(products, item.productId, ['name'])) }}</td>
                                                            <td class="text-end">{{ formatValue(item.quantity) }}</td>
                                                            <td>{{ resolveOptionLabel(unitOfMeasures, item.unitOfMeasureId, ['description']) }}</td>
                                                        </tr>
                                                        <tr v-if="!recipes.length">
                                                            <td colspan="3" class="text-center text-muted">Sin registros</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="accordion mb-3" id="accordion-presentation-details">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading-presentation-details">
                                        <button class="accordion-button d-flex align-items-center shadow-none bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-presentation-details" aria-expanded="true" aria-controls="collapse-presentation-details">
                                            <i class="ri bx bx-cube fs-16 text-primary me-2 fw-bold"></i>
                                            <h5 class="card-title mb-0 flex-fill text-black">Presentaciones</h5>
                                        </button>
                                    </h2>
                                    <div id="collapse-presentation-details" class="accordion-collapse collapse show" aria-labelledby="heading-presentation-details" data-bs-parent="#accordion-presentation-details">
                                        <div class="accordion-body pt-3 pb-0 px-3">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered align-middle">
                                                    <thead>
                                                        <tr>
                                                            <th>Nombre</th>
                                                            <th>Estado</th>
                                                            <th>Unidad de medida</th>
                                                            <th class="text-end">Cantidad</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(item, index) in presentations" :key="item.tempUuid || item.uuid || index">
                                                            <td>{{ formatValue(item.name) }}</td>
                                                            <td>{{ formatValue(item.status) }}</td>
                                                            <td>{{ resolveOptionLabel(unitOfMeasures, item.unitOfMeasureId, ['description']) }}</td>
                                                            <td class="text-end">{{ formatValue(item.quantity) }}</td>
                                                        </tr>
                                                        <tr v-if="!presentations.length">
                                                            <td colspan="4" class="text-center text-muted">Sin registros</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="col-4">
                    <div class="row">
                        <div class="col-12">
                            <div class="card" id="card-general-information-product">
                                <div class="card-body p-0">
                                    <div class="border-0">
                                        <h2 class="card-header card-title-header">
                                            <div class="d-flex align-items-center shadow-none">
                                                <i class="ri-file-list-line fs-16 text-primary me-2 fw-bold"></i>
                                                <h5 class="card-title mb-0 flex-fill text-black">Información Producto</h5>
                                            </div>
                                        </h2>
                                        <div class="card-body pt-0 pb-1">
                                            <div class="row my-3">
                                                <div class="col-md-12">
                                                    <div
                                                        class="product-general-field-wrap"
                                                        data-form-error-anchor="productTypeId"
                                                    >
                                                        <label for="product_type" class="form-label">Tipo Producto<span class="text-danger ms-1">*</span></label>
                                                        <p class="form-control-plaintext mb-0">
                                                            {{ resolveOptionLabel(productTypes, formsProduct.general.productTypeId, ['name']) }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <div
                                                        class="product-general-field-wrap"
                                                        data-form-error-anchor="categoryId"
                                                    >
                                                        <label for="sel_category">Categoría<span class="text-danger ms-1">*</span></label>
                                                        <p class="form-control-plaintext mb-0">
                                                            {{ resolveOptionLabel(productCategories, formsProduct.general.categoryId, ['labelDescription', 'name']) }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-12 d-flex align-items-center">
                                                    <label class="form-check-label me-3">Estado</label>
                                                    <p class="mb-0 text-muted">{{ formatBoolean(formsProduct.general.status) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="card" id="card-general-information-accounting">
                                <div class="card-body p-0">
                                    <div class="border-0">
                                        <h2 class="card-header card-title-header">
                                            <div class="d-flex align-items-center shadow-none">
                                                <i class="ri-file-list-line fs-16 text-primary me-2 fw-bold"></i>
                                                <h5 class="card-title mb-0 flex-fill text-black">Información Contable</h5>
                                            </div>
                                        </h2>
                                        <div class="card-body py-1">
                                            <div class="row my-3">
                                                <div class="col-md-12">
                                                    <div
                                                        class="product-general-field-wrap"
                                                        data-form-error-anchor="taxId"
                                                    >
                                                        <label for="sel_tax">Tipo Impuesto<span class="text-danger ms-1">*</span></label>
                                                        <p class="form-control-plaintext mb-0">
                                                            {{ resolveOptionLabel(taxes, formsProduct.general.taxId, ['description']) }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <div
                                                        class="product-general-field-wrap"
                                                        data-form-error-anchor="salesAccountCode"
                                                    >
                                                        <label>Cuenta Venta<span class="text-danger ms-1">*</span></label>
                                                        <p class="form-control-plaintext mb-0">{{ formatValue(formsProduct.general.salesAccountCode) }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <div
                                                        class="product-general-field-wrap"
                                                        data-form-error-anchor="returnsAccountCode"
                                                    >
                                                        <label>Cuenta Devolución<span class="text-danger ms-1">*</span></label>
                                                        <p class="form-control-plaintext mb-0">{{ formatValue(formsProduct.general.returnsAccountCode) }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>

<style scoped>
    .product-create-stack {
        max-width: 100%;
    }

    .product-general-field-wrap--error :deep(.form-control) {
        border-color: var(--bs-form-invalid-border-color, #dc3545);
    }

    .switch-card {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        border: 1px solid #e9ebec;
        border-radius: 0.5rem;
        padding: 0.85rem 1rem;
        background-color: #fafbfc;
    }

    .check-info-row {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .check-info-row small {
        max-width: 56%;
        line-height: 1.2;
    }
</style>
