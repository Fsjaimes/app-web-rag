<script>
import Layout from '@/Layouts/main.vue'
//Componentes
import PageHeader from '@/Components/page-header.vue'
import ProductCategoryNode from '@/Components/NodoItem.vue'
import AccessDeniedCard from '@/Components/AccessDeniedCard.vue'
//Composables
import { useFetchPetition } from '@/Composables/useFetchPetition.js';
import { useAlert } from '@/Composables/useSweetAlert.js'

const { showAlert, showConfirm } = useAlert();
const { fetchPetition } = useFetchPetition();

export default {
    name: 'ProductCategoriesIndex',
    components: {
        Layout,
        PageHeader,
        ProductCategoryNode,
        AccessDeniedCard,
    },
    props: {
        dataProductCategories: {
            type: Array,
            required: true,
            default: () => []
        },
        canViewModule: {
            type: Boolean,
            required: true,
            default: false,
        },
        permissions: {
            type: Object,
            required: true,
            default: () => ({}),
        },
        message: {
            type: String,
            required: true,
            default: '',
        },
    },
    data() {
        return {
            expandedFilterLevels: {},
            filterCategories: [],
            activeFilterRootUuid: null,
            activeFilterLevel2Uuid: null,
            quickFilterUuid: null,
            searchProductCategory: '',
            showModal: false,
            modalAction: '',
            parentNode: null,
            selectedNode: null,
            viewActionModal: '',
            form: {
                id: null,
                uuid: null,
                prefix: '',
                code: '',
                name: '',
                observations: '',
                parentId: null,
                status: true,
                isLeaf: false,
            },
            productCategories: [],
            initialProductCategories: [],
            savingNode: false,
            formFieldErrors: {
                code: false,
                name: false,
            },
        }
    },
    computed: {
        modalTitle() {
            switch(this.modalAction) {
                case 'view':
                    return 'Ver';
                case 'edit':
                    return 'Editar';
                case 'new':
                    return this.parentNode ? `Nuevo - ${this.parentNode.name}` : 'Nueva Categoría';
                default:
                    return '';
            }
        },
        formattedProductCategories() {
            return this.productCategories.map(opt => {
                return {
                    ...opt,  // Mantiene las propiedades originales
                    combinedText: `${opt.prefix || ''}${opt.code} - ${opt.name}`  // Nueva propiedad combinada
                }
            });
        },
        isTypeFieldDisabled() {
            if (this.viewActionModal !== 'edit' || !this.form.uuid) return false;
            const node = this.findNodeByUuid(this.productCategories, this.form.uuid);
            if (!node || Number(node.type) !== 1) return false;
            const children = node.children;
            return children && (Array.isArray(children) ? children.length > 0 : Object.keys(children).length > 0);
        },

        /** En niveles 1, 2 y 3 solo se permite General; en el resto se ofrecen ambos tipos. */
        typesForForm() {
            const level = this.getCurrentFormProductCategoryLevel();
            if (level <= 3) return [{ id: 1, name: 'General' }];
            return this.types;
        },
        /** No se puede crear hijo cuando la categoría es detalle (isLeaf=true). */
        canCreateChildProductCategory() {
            return (node) => !this.isLeafProductCategory(node)
        },
        isLeafFieldDisabled() {
            if (this.viewActionModal !== 'edit' || !this.form.uuid) return false;
            const currentNode = this.findNodeByUuid(this.productCategories, this.form.uuid);
            return this.nodeHasChildren(currentNode);
        },

        /** Árbol base mostrado: completo o rama hasta el filtro rápido. */
        baseDisplayedProductCategories() {
            if (!this.activeFilterRootUuid) {
                return this.productCategories;
            }
            return this.filterTreeToBranch(this.productCategories, this.activeFilterRootUuid);
        },
        /** Árbol final mostrado: base + expansión por búsqueda en code y name. */
        displayedProductCategories() {
            const search = String(this.searchProductCategory ?? '').trim();
            if (!search) return this.baseDisplayedProductCategories;
            return this.expandTreeBySearch(this.baseDisplayedProductCategories, search);
        },
    },
    async mounted() {
        this.productCategories = this.cloneNodeList(this.dataProductCategories);
        this.initialProductCategories = this.cloneNodeList(this.dataProductCategories);
        await this.loadFilterCategories();
    },
    methods: {
        async loadFilterCategories() {
            const response = await fetchPetition('/product-category/filter-tree', {
                method: 'GET',
            });
            if (response.ok) {
                this.filterCategories = response.data?.dataFilterCategories || [];
                if (this.activeFilterRootUuid && !this.filterCategories.some((c) => c.uuid === this.activeFilterRootUuid)) {
                    this.activeFilterRootUuid = null;
                    this.activeFilterLevel2Uuid = null;
                    this.quickFilterUuid = null;
                    this.expandedFilterLevels = {};
                }
            }
        },

        applyRootFilter(rootNode) {
            if (!rootNode || !rootNode.uuid) return;
            this.searchProductCategory = '';
            this.activeFilterRootUuid = rootNode.uuid;
            this.activeFilterLevel2Uuid = null;
            this.quickFilterUuid = rootNode.uuid;
            this.expandedFilterLevels = {
                [rootNode.uuid]: true,
            };
            this.$nextTick(() => {
                this.expandOnlyNodeAndParents(this.productCategories, rootNode.uuid);
            });
        },

        applyLevel2Filter(rootNode, childNode) {
            if (!rootNode || !rootNode.uuid || !childNode || !childNode.uuid) return;

            this.searchProductCategory = '';
            this.activeFilterRootUuid = rootNode.uuid;
            this.activeFilterLevel2Uuid = childNode.uuid;
            this.quickFilterUuid = rootNode.uuid;
            this.expandedFilterLevels = {
                [rootNode.uuid]: true,
            };

            const selectedTreeNode = this.findNodeByUuid(this.productCategories, childNode.uuid);
            if (!selectedTreeNode) return;

            this.collapseAllNodes(this.productCategories);
            this.expandNodeAndParents(this.productCategories, childNode.uuid);
            this.expandAllDescendants(selectedTreeNode);
            this.selectedNode = selectedTreeNode;
        },

        isFilterRootExpanded(node) {
            return !!(node && node.uuid && this.expandedFilterLevels[node.uuid]);
        },

        expandAllDescendants(node) {
            if (!node) return;
            node.expanded = true;
            if (!node.children) return;
            const children = Array.isArray(node.children) ? node.children : Object.values(node.children);
            for (const child of children) {
                this.expandAllDescendants(child);
            }
        },

        /**
         * Deja solo la rama que contiene targetUuid (en cada nivel se omiten hermanos).
         */
        filterTreeToBranch(nodes, targetUuid) {
            if (!targetUuid || !nodes) return nodes;
            const list = Array.isArray(nodes) ? nodes : Object.values(nodes);
            const out = [];
            for (const node of list) {
                if (node.uuid === targetUuid) {
                    out.push(this.cloneNodeBranch(node));
                    continue;
                }
                if (node.children) {
                    const children = Array.isArray(node.children)
                        ? node.children
                        : Object.values(node.children);
                    const pruned = this.filterTreeToBranch(children, targetUuid);
                    if (pruned.length) {
                        out.push({
                            ...node,
                            children: pruned,
                        });
                    }
                }
            }
            return out;
        },

        cloneNodeBranch(node) {
            if (!node) return null;
            const children = node.children
                ? (Array.isArray(node.children) ? node.children : Object.values(node.children))
                : [];
            return {
                ...node,
                children: children.map((c) => this.cloneNodeBranch(c)),
            };
        },

        cloneNodeList(nodes) {
            if (!nodes) return [];
            const list = Array.isArray(nodes) ? nodes : Object.values(nodes);
            return list.map((node) => this.cloneNodeBranch(node));
        },

        normalizeSearchValue(value) {
            return String(value ?? '').toLowerCase().trim();
        },

        nodeFullCodeAndName(node) {
            if (!node) return '';
            const fullCode = node.prefix ? `${node.prefix}${node.code ?? ''}` : `${node.code ?? ''}`;
            return `${fullCode} ${node.name ?? ''}`.trim();
        },

        nodeMatchesSearch(node, normalizedSearch) {
            if (!node || !normalizedSearch) return false;
            const fullCodeAndName = this.normalizeSearchValue(this.nodeFullCodeAndName(node));
            return fullCodeAndName.includes(normalizedSearch);
        },

        buildSearchExpandedNode(node, normalizedSearch) {
            if (!node) return { clonedNode: null, hasMatchInBranch: false };

            const children = node.children
                ? (Array.isArray(node.children) ? node.children : Object.values(node.children))
                : [];

            const processedChildren = children.map((child) => this.buildSearchExpandedNode(child, normalizedSearch));
            const clonedChildren = processedChildren
                .map((item) => item.clonedNode)
                .filter((child) => !!child);

            const matchesCurrentNode = this.nodeMatchesSearch(node, normalizedSearch);
            const hasMatchInDescendants = processedChildren.some((item) => item.hasMatchInBranch);
            const hasMatchInBranch = matchesCurrentNode || hasMatchInDescendants;

            return {
                clonedNode: {
                    ...node,
                    expanded: hasMatchInBranch ? true : !!node.expanded,
                    children: clonedChildren,
                },
                hasMatchInBranch,
            };
        },

        expandTreeBySearch(nodes, search) {
            if (!nodes) return [];
            const normalizedSearch = this.normalizeSearchValue(search);
            if (!normalizedSearch) return this.cloneNodeList(nodes);

            const list = Array.isArray(nodes) ? nodes : Object.values(nodes);
            return list
                .map((node) => this.buildSearchExpandedNode(node, normalizedSearch).clonedNode)
                .filter((node) => !!node);
        },

        clearQuickCategoryFilter() {
            this.searchProductCategory = '';
            this.activeFilterRootUuid = null;
            this.activeFilterLevel2Uuid = null;
            this.quickFilterUuid = null;
            this.expandedFilterLevels = {};
            this.productCategories = this.cloneNodeList(this.initialProductCategories);
            this.collapseAllNodes(this.productCategories);
        },

        applyQuickCategoryFilter(node) {
            if (!node || !node.uuid) return;
            this.quickFilterUuid = node.uuid;
            this.$nextTick(() => {
                this.expandOnlyNodeAndParents(this.displayedProductCategories, node.uuid);
            });
        },

        nodeDisplayCode(node) {
            if (!node) return '';
            const prefix = node.prefix != null ? String(node.prefix) : '';
            const code = node.code != null ? String(node.code) : '';
            return prefix + code;
        },

        isQuickFilterActiveForNode(node) {
            if (!node || !node.uuid) return false;
            return node.uuid === this.quickFilterUuid || node.uuid === this.activeFilterLevel2Uuid;
        },

        nodeHasChildren(node) {
            if (!node || !node.children) return false;
            if (Array.isArray(node.children)) return node.children.length > 0;
            if (typeof node.children === 'object') return Object.keys(node.children).length > 0;
            return false;                                                                                           
        },

        isLeafProductCategory(node) {
            if (!node) return false;
            const value = node.isLeaf ?? node.is_leaf;
            if (typeof value === 'boolean') return value;
            return String(value) === '1' || String(value).toLowerCase() === 'true';
        },

        resetFormFieldErrors() {
            this.formFieldErrors = { code: false, name: false }
        },

        onRequiredCodeInput() {
            if (String(this.form.code ?? '').trim()) {
                this.formFieldErrors.code = false
            }
        },

        onRequiredNameInput() {
            if (String(this.form.name ?? '').trim()) {
                this.formFieldErrors.name = false
            }
        },

        /** Valida código y nombre; marca inputs y un solo SweetAlert si falta alguno. */
        validateRequiredCategoryFields() {
            const codeEmpty = !String(this.form.code ?? '').trim()
            const nameEmpty = !String(this.form.name ?? '').trim()
            this.formFieldErrors.code = codeEmpty
            this.formFieldErrors.name = nameEmpty
            if (codeEmpty || nameEmpty) {
                showAlert('warning', '¡Alerta!', 'Por favor, ingrese todos los campos requeridos', 3000)
                return false
            }
            return true
        },

        validateDuplicateCode(code, nodes) {
            const formCode = String(code).trim();

            // Convertir nodes a array si viene como objeto
            const list = Array.isArray(nodes)
                ? nodes
                : Object.values(nodes); // convierte {0:{},1:{}} → [{},{}]

            // Buscar coincidencia
            return list.some(n => {
                const nCode = String(n.code ?? '').trim();
                return nCode == formCode;
            });
        },

        handleMenuAction({ action, node }) {
            this.resetFormFieldErrors();
            this.parentNode = node;
            this.form = {
                prefix: (node.prefix != null ? node.prefix : '') + node.code,
                code: '',
                name: '',
                parentId: node.id,
                observations: '',
                status: true,
                isLeaf: false,
            };
            if(action == 'edit' || action == 'view') {
                this.viewNode(node.uuid, action);
            }
            this.viewActionModal = action;
            this.modalAction = action;
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.modalAction = '';
            this.parentNode = null;
            this.resetFormFieldErrors();
        },

        handleNewNode() {
            this.resetFormFieldErrors();
            this.viewActionModal = 'new';
            this.modalAction = 'new';
            this.showModal = true;
            this.form = {
                prefix: '',
                code: '',
                name: '',
                parentId: null,
                observations: '',
                status: true,
                isLeaf: false,
            };
        },

        async viewNode(uuid, action) {
            const response = await fetchPetition(`/product-category/${uuid}`, {
                method: 'GET',
            });
            if (response.ok) {
                this.form = response.data?.data;
                this.form = {
                    ...this.form,
                    status: this.form.status == '1' ? true : false,
                    isLeaf: !!this.form.isLeaf,
                };
                this.resetFormFieldErrors();
            } else {
                const errorData = await response.json();
                showAlert('error', 'Error', 'Error al mostrar categoría de producto', 1500);
            }
        },

        // Nuevo método para colapsar todos los nodos
        collapseAllNodes(nodes) {
            if (!nodes) return;
            const nodeList = Array.isArray(nodes) ? nodes : Object.values(nodes);
            for (const node of nodeList) {
                // Colapsar el nodo actual
                node.expanded = false;
                // Colapsar recursivamente todos los hijos
                if (node.children) {
                    const children = Array.isArray(node.children)
                        ? node.children
                        : Object.values(node.children);
                    this.collapseAllNodes(children);
                }
            }
        },

        // Nuevo método para expandir un nodo y todos sus padres
        expandNodeAndParents(nodes, targetUuid) {
            if (!nodes || !targetUuid) return false;

            const nodeList = Array.isArray(nodes) ? nodes : Object.values(nodes);

            for (const node of nodeList) {
                if (node.uuid === targetUuid) {
                    // Encontramos el nodo, marcarlo como expandido
                    // En Vue 3, simplemente asignamos directamente
                    node.expanded = true;
                    return true;
                }
                // Si tiene hijos, buscar recursivamente
                if (node.children) {
                    const children = Array.isArray(node.children)
                        ? node.children
                        : Object.values(node.children);
                    if (this.expandNodeAndParents(children, targetUuid)) {
                        // Si encontramos el nodo en los hijos, expandir este nodo también
                        node.expanded = true;
                        return true;
                    }
                }
            }
            return false;
        },

        // Método que colapsa todo y luego expande solo el nodo objetivo
        expandOnlyNodeAndParents(nodes, targetUuid) {
            // Primero colapsar todos los nodos
            this.collapseAllNodes(nodes);
            // Luego expandir solo el nodo objetivo y sus padres
            this.expandNodeAndParents(nodes, targetUuid);
        },

        // Nuevo método para encontrar un nodo por UUID
        findNodeByUuid(nodes, targetUuid) {
            if (!nodes || !targetUuid) return null;

            const nodeList = Array.isArray(nodes) ? nodes : Object.values(nodes);

            for (const node of nodeList) {
                if (node.uuid === targetUuid) {
                    return node;
                }
                if (node.children) {
                    const children = Array.isArray(node.children)
                        ? node.children
                        : Object.values(node.children);
                    const found = this.findNodeByUuid(children, targetUuid);
                    if (found) return found;
                }
            }
            return null;
        },

        findNodeById(nodes, targetId) {
            if (!nodes || targetId == null || targetId === '') return null;

            const nodeList = Array.isArray(nodes) ? nodes : Object.values(nodes);

            for (const node of nodeList) {
                if (String(node.id) === String(targetId)) {
                    return node;
                }

                if (node.children) {
                    const children = Array.isArray(node.children)
                        ? node.children
                        : Object.values(node.children);
                    const found = this.findNodeById(children, targetId);
                    if (found) return found;
                }
            }

            return null;
        },

        /** IDs de todas las subcuentas (recursivo), sin incluir el nodo raíz pasado. */
        collectDescendantAccountIds(node) {
            if (!node || !node.children) return [];

            const children = Array.isArray(node.children)
                ? node.children
                : Object.values(node.children);

            const ids = [];
            for (const child of children) {
                if (child.id != null && child.id !== '') {
                    ids.push(child.id);
                }
                ids.push(...this.collectDescendantAccountIds(child));
            }
            return ids;
        },

        // Nuevo método para encontrar un nodo por código y parentId (fallback)
        findNodeByCodeAndParent(nodes, code, parentId) {
            if (!nodes || !code) return null;

            const nodeList = Array.isArray(nodes) ? nodes : Object.values(nodes);

            for (const node of nodeList) {
                // Si coincide el código y el parentId (o ambos son null/undefined)
                const nodeParentId = node.parentId || node.parent_id || null;
                const matchParent = (parentId === null && nodeParentId === null) || (String(nodeParentId) === String(parentId));
                if (String(node.code) === String(code) && matchParent) {
                    return node;
                }
                if (node.children) {
                    const children = Array.isArray(node.children)
                        ? node.children
                        : Object.values(node.children);
                    const found = this.findNodeByCodeAndParent(children, code, parentId);
                    if (found) return found;
                }
            }
            return null;
        },

        async listProductCategories() {
            // Aquí puedes agregar lógica para cargar los datos según el filtro
            const response = await fetchPetition(`/product-category/list`, { // Este método es el original
                method: 'GET',
            });
            if (response.ok) {
                const freshCategories = this.cloneNodeList(response.data?.dataProductCategories || []);
                this.productCategories = freshCategories;
                this.initialProductCategories = this.cloneNodeList(freshCategories);
                await this.loadFilterCategories();
                if (this.quickFilterUuid && !this.findNodeByUuid(this.productCategories, this.quickFilterUuid)) {
                    this.quickFilterUuid = null;
                    this.activeFilterRootUuid = null;
                    this.expandedFilterLevels = {};
                }
            }
        },

        async saveProductCategory() {
            if (!this.validateRequiredCategoryFields()) {
                return;
            }

            // Comparar codigos en el mismo nivel para no duplicarlos en otros nodos
            const nodes = this.parentNode == null ? this.productCategories : this.parentNode.children;

            if (this.validateDuplicateCode(this.form.code, nodes)) {
                showAlert(
                    'warning',
                    '¡Alerta!',
                    'El código no se puede usar porque ya existe en el mismo nivel.'
                );
                return;
            }

            try {
                this.savingNode = true;
                if (this.modalAction == 'new') {
                    const confirmed = await showConfirm(
                        'warning',                // icon
                        '¡Alerta!',           // title
                        '¿Está seguro que desea crear un nuevo nodo?', // text
                        'Sí, crear'               // confirmButtonText
                    );
                    // Si el usuario cancela, detenemos el proceso
                    if (!confirmed) {
                        this.savingNode = false;
                        return;
                    }
                    const response = await fetchPetition('/product-category', { // Este método es el original
                        method: 'POST',
                        body: this.form,
                    });
                    if (response.ok) {
                        showAlert('success', '¡Éxito!', 'Categoría de producto creada correctamente', '', 1500);
                        await this.listProductCategories();
                        await this.$nextTick();

                        // Expandir solo el nodo recién creado
                        const createdNode = response.data?.data;
                        if (createdNode && createdNode.uuid) {
                            setTimeout(() => {
                                this.expandNodeAndParents(this.productCategories, createdNode.uuid);
                                this.selectedNode = createdNode;
                            }, 100);
                        } else {
                            // Si no viene el nodo en la respuesta, intentar buscarlo por parentId y code
                            if (this.form.parentId && this.form.code) {
                                setTimeout(() => {
                                    const foundNode = this.findNodeByCodeAndParent(this.productCategories, this.form.code, this.form.parentId);
                                    if (foundNode) {
                                        this.expandNodeAndParents(this.productCategories, foundNode.uuid);
                                        this.selectedNode = foundNode;
                                    }
                                }, 100);
                            }
                        }
                    } else {
                        const errorData = await response.json();
                        showAlert('error', 'Error', 'Error al crear categoría de producto', 1500);
                    }
                }
                this.closeModal();
            } catch (error) {
                console.error('Error al guardar:', error);
                showAlert('error', 'Error', 'No se pudo guardar el nodo');
            } finally {
                this.savingNode = false;
            }
        },

        async updateNode() {
            if (!this.validateRequiredCategoryFields()) {
                return;
            }

            const currentNode = this.findNodeByUuid(this.productCategories, this.form.uuid);
            if (this.form.isLeaf && this.nodeHasChildren(currentNode)) {
                showAlert(
                    'warning',
                    '¡Alerta!',
                    'No es posible marcar como detalle una categoría que ya tiene subcategorías.'
                );
                return;
            }

            let nodes = [];

            if(this.form.parentId == null){
                nodes = this.productCategories;
            }else{
                // Buscar nodo por id (iterativo, DFS) y obtener sus children
                const stack = Array.isArray(this.productCategories)
                    ? [...this.productCategories]
                    : Object.values(this.productCategories || {});
                let foundChildren = [];

                while (stack.length) {
                    const current = stack.pop();
                    if (!current) continue;

                    if (String(current.id) == String(this.form.parentId)) {
                        foundChildren = Array.isArray(current.children)
                            ? current.children
                            : (current.children ? Object.values(current.children) : []);
                        break;
                    }

                    if (current.children) {
                        const kids = Array.isArray(current.children)
                            ? current.children
                            : Object.values(current.children);
                        for (let i = 0; i < kids.length; i++) stack.push(kids[i]);
                    }
                }

                nodes = foundChildren;
            }

            // Asegurar que nodes sea un array y filtrar el registro actual por id o uuid
            nodes = (Array.isArray(nodes) ? nodes : Object.values(nodes)).filter(n => {
                return String(n.id) != String(this.form.id) && String(n.uuid) != String(this.form.uuid);
            });
            if (this.validateDuplicateCode(this.form.code, nodes)) {
                showAlert(
                    'warning',
                    '¡Alerta!',
                    'El código no se puede usar porque ya existe en el mismo nivel.'
                );
                return;
            }

            try {
                this.savingNode = true;
                if (this.modalAction == 'edit') {
                    const confirmed = await showConfirm(
                        'warning',                // icon
                        '¡Alerta!',           // title
                        '¿Está seguro que desea actualizar este nodo?', // text
                        'Sí, actualizar'               // confirmButtonText
                    );
                    // Si el usuario cancela, detenemos el proceso
                    if (!confirmed) {
                        this.savingNode = false;
                        return;
                    }
                    const response = await fetchPetition(`/product-category/${this.form.uuid}`, { // Este método es el original
                        method: 'PUT',
                        body: this.form,
                    });
                    if (response.ok) {
                        showAlert('success', '¡Éxito!', 'Categoría de producto actualizada correctamente', '', 1500);
                        // Refrescar la lista
                        await this.listProductCategories();
                        await this.$nextTick();

                        // Expandir solo el nodo editado
                        if (this.form.uuid) {
                            setTimeout(() => {
                                const updatedNode = this.findNodeByUuid(this.productCategories, this.form.uuid);
                                if (updatedNode) {
                                    this.expandNodeAndParents(this.productCategories, this.form.uuid);
                                    this.selectedNode = updatedNode;
                                }
                            }, 100);
                        }
                    } else {
                        const errorData = await response.json();
                        showAlert('error', 'Error', 'Error al actualizar categoría de producto', 1500);
                    }
                }
                this.closeModal();
            } catch (error) {
                console.error('Error al actualizar:', error);
                showAlert('error', 'Error', 'No se pudo actualizar el nodo');
            } finally {
                this.savingNode = false;
            }
        },


    },
}
</script>

<template>
    <Layout>
        <PageHeader title="Categorías Productos" pageTitle="Configuración" />
        <!-- <div v-if="!canViewModule" class="row justify-content-center">
            <div class="col-lg-6">
                <AccessDeniedCard :message="message" />
            </div>
        </div> -->
        <div id="app">
            <div class="row category-product-categories-row">
                <div class="col-12">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-stretch category-cards-split">
                            <div
                                id="category-level-filter-panel"
                                class="card category-filter-side-card flex-shrink-0"
                            >
                                <div class="card-header py-3 px-3 d-flex align-items-center justify-content-between">
                                    <div>
                                        <h5 class="card-title mb-0">Filtro por nivel</h5>
                                        <p class="text-muted mb-0 fs-12">Niveles principales del árbol de categorías.</p>
                                    </div>
                                </div>
                                <div class="card-body py-2 px-2 category-level-filter-body">
                                    <button
                                        type="button"
                                        class="category-filter-todos w-100 text-start"
                                        :class="{ active: !quickFilterUuid }"
                                        @click="clearQuickCategoryFilter"
                                    >
                                        Todos
                                    </button>
                                    <div
                                        v-for="(root, idx) in filterCategories"
                                        :key="`${root.uuid}-${idx}`"
                                        class="category-filter-level-block"
                                    >
                                        <button
                                            type="button"
                                            class="category-filter-level-toggle w-100 text-start"
                                            :class="{ active: isQuickFilterActiveForNode(root) }"
                                            :aria-expanded="isFilterRootExpanded(root)"
                                            @click="applyRootFilter(root)"
                                        >
                                            <i
                                                class="category-filter-chevron fs-14 text-muted"
                                                :class="isFilterRootExpanded(root) ? 'ri-arrow-down-s-line' : 'ri-arrow-right-s-line'"
                                                aria-hidden="true"
                                            ></i>
                                            <span class="category-filter-node-code text-muted">{{ nodeDisplayCode(root) }}</span>
                                            <span class="fw-medium text-truncate">{{ root.name }}</span>
                                        </button>
                                        <div
                                            v-show="isFilterRootExpanded(root)"
                                            class="category-filter-level-nodes"
                                        >
                                            <button
                                                v-for="(child, childIdx) in (root.children || [])"
                                                :key="`${child.uuid}-${childIdx}`"
                                                type="button"
                                                class="category-filter-node-line w-100 text-start"
                                                :class="{ active: isQuickFilterActiveForNode(child) }"
                                                @click="applyLevel2Filter(root, child)"
                                            >
                                                <span class="category-filter-node-code text-muted">{{ nodeDisplayCode(child) }}</span>
                                                <span class="category-filter-node-name text-truncate">{{ child.name }}</span>
                                            </button>
                                            <div
                                                v-if="!(root.children || []).length"
                                                class="text-muted fs-11 px-2 py-1 fst-italic"
                                            >
                                                Sin categorías
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        v-if="!filterCategories.length"
                                        class="text-muted fs-12 px-2 py-2 fst-italic"
                                    >
                                        No hay categorías de nivel 1 para filtrar.
                                    </div>
                                </div>
                            </div>
                            <div class="category-tree-card-shell flex-grow-1 min-w-0">
                                <div class="card category-main-tree-card h-100">
                                    <div class="card-header category-tree-card-header py-3 px-3 position-relative overflow-visible">
                                        <div class="row align-items-center">
                                            <div class="col-7">
                                                <h5 class="card-title mb-0">Árbol de categorías</h5>
                                                <p class="text-muted mb-0 fs-12">
                                                    Visualice y gestione las categorías de productos disponibles en el sistema.
                                                </p>
                                            </div>
                                            <div class="col-5">
                                                <input type="text" class="form-control" v-model="searchProductCategory" placeholder="Buscar...">
                                            </div>
                                        </div>
                                    </div>

                                <div class="card-body">
                                    <div class="profile-timeline">
                                        <div class="accordion accordion-flush" id="accordion-product-categories">
                                        <ProductCategoryNode
                                            v-for="(node, index) in displayedProductCategories"
                                            :key="`${node.uuid}-${index}`"
                                            :node="node"
                                            :level="0"
                                            :canCreateChild="canCreateChildProductCategory"
                                            :selectedNode="selectedNode"
                                            :searchTerm="searchProductCategory"
                                            @menu-action="handleMenuAction"
                                            @new-node="handleMenuAction({ action: 'new', node: $event })"
                                            @select-node="selectedNode = $event"
                                        />
                                        <div class="accordion-item border-0">
                                            <div class="accordion-header" id="heading-new-root">
                                                <a
                                                    class="accordion-button p-2 shadow-none"
                                                    href="#collapse-new-root"
                                                    aria-expanded="false"
                                                    @click.prevent="handleNewNode"
                                                >
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-shrink-0 avatar-xs">
                                                            <div class="avatar-title bg-light text-success rounded-circle">
                                                                <i class="ri-play-list-add-line"></i>
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <h6 class="fs-13 mb-0 fw-semibold">Nuevo</h6>
                                                        </div>
                                                    </div>
                                                </a>
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
        <div
            v-if="showModal"
            id="modalProductCategory"
            class="modal fade show d-block"
            tabindex="-1"
            style="background-color: rgba(0, 0, 0, 0.5)"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header p-3 bg-light">
                        <h5 class="modal-title">{{ modalTitle }}</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto">
                        <div v-if="viewActionModal == 'new' || viewActionModal == 'edit'">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="nodeCode" class="form-label">Código</label><span class="text-danger"> *</span>
                                    <div class="input-group">
                                        <span
                                            v-if="form.prefix != '' && form.prefix != null"
                                            class="input-group-text"
                                        >{{ form.prefix }}</span>
                                        <div
                                            class="position-relative flex-grow-1 category-field-input-wrap"
                                            style="min-width: 0"
                                        >
                                            <input
                                                id="nodeCode"
                                                v-model="form.code"
                                                type="text"
                                                class="form-control"
                                                :class="{ 'category-field-invalid': formFieldErrors.code }"
                                                :aria-invalid="formFieldErrors.code"
                                                placeholder="Ingrese código"
                                                @input="onRequiredCodeInput"
                                            />
                                            <i
                                                v-if="formFieldErrors.code"
                                                class="ri-error-warning-line category-field-invalid-icon"
                                                aria-hidden="true"
                                            ></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="nodeName" class="form-label">Nombre</label><span class="text-danger"> *</span>
                                    <div class="position-relative category-field-input-wrap">
                                        <input
                                            id="nodeName"
                                            v-model="form.name"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'category-field-invalid': formFieldErrors.name }"
                                            :aria-invalid="formFieldErrors.name"
                                            placeholder="Ingrese nombre"
                                            @input="onRequiredNameInput"
                                        />
                                        <i
                                            v-if="formFieldErrors.name"
                                            class="ri-error-warning-line category-field-invalid-icon"
                                            aria-hidden="true"
                                        ></i>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="nodeObservations" class="form-label">Observaciones</label>
                                    <textarea
                                        id="nodeObservations"
                                        v-model="form.observations"
                                        class="form-control"
                                        placeholder="Ingrese observaciones"
                                        rows="3"
                                    ></textarea>
                                </div>
                            </div>
                            <div class="row align-items-center">
                                <!-- <div class="col-12 d-flex align-items-center mb-2">
                                    <div class="form-check form-switch form-switch-md mb-0 d-flex align-items-center gap-2 px-0" dir="ltr">
                                        <label class="form-check-label mb-0 me-3" for="customSwitchsizesm">Estado</label>
                                        <input
                                            id="customSwitchsizesm"
                                            v-model="form.status"
                                            type="checkbox"
                                            class="form-check-input m-0"
                                        />
                                    </div>
                                </div> -->

                                <div class="col-12 mb-3">
                                    <div class="switch-card h-100">
                                        <div class="row w-100 align-items-center">
                                            <div class="col-4 px-0">
                                                <div class="form-check form-check-custom m-0">
                                                    <input v-model="form.isLeaf" class="form-check-input" :disabled="isLeafFieldDisabled" type="checkbox" id="isLeaf">
                                                    <label class="form-check-label fw-semibold" for="isLeaf">Es Detalle</label>
                                                </div>
                                            </div>
                                            <div class="col-8 px-0 text-end">
                                                <small class="text-muted text-end">
                                                    <i class="ri-information-line"></i>
                                                    Indica la categoría seleccionable en el sistema.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-6 d-flex align-items-center mb-2 mt-2">
                                    <div class="form-check form-switch form-switch-md mb-0 d-flex align-items-center gap-2 px-0" dir="ltr">
                                        <label class="form-check-label mb-0 me-3" for="customSwitchsizesm">Estado</label>
                                        <input
                                            id="customSwitchsizesm"
                                            v-model="form.status"
                                            type="checkbox"
                                            class="form-check-input m-0"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="viewActionModal == 'view'" class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Código completo</label>
                                <p class="text-muted mb-0">{{ (form.prefix != null ? form.prefix : '') + form.code }}</p>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Nombre</label>
                                <p class="text-muted mb-0">{{ form.name }}</p>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label d-block">Estado</label>
                                <div class="mt-1">
                                    <span v-show="form.status == '1'" class="badge bg-success-subtle text-success">Activo</span>
                                    <span v-show="form.status == '0'" class="badge bg-danger-subtle text-danger">Inactivo</span>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label d-block">Es Detalle</label>
                                <div>
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        :checked="!!form.isLeaf"
                                        disabled
                                    />
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Observaciones</label>
                                <p class="text-muted mb-0">
                                    {{ form.observations && String(form.observations).trim() !== '' ? form.observations : 'Sin observaciones' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" @click="closeModal">Cancelar</button>
                        <button
                            v-if="viewActionModal == 'new'"
                            type="button"
                            class="btn btn-primary"
                            :disabled="savingNode"
                            @click="saveProductCategory()"
                        >
                            Guardar
                        </button>
                        <button
                            v-if="viewActionModal == 'edit'"
                            type="button"
                            class="btn btn-primary"
                            :disabled="savingNode"
                            @click="updateNode()"
                        >
                            Actualizar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>

<style scoped>
.search-box {
    position: relative;
}

.search-icon {
    position: absolute;
    top: 50%;
    right: 12px;
    transform: translateY(-50%);
    color: #74788d;
}

.fs-15 {
    font-size: 0.9375rem;
}

.nav-tabs-custom {
    border-bottom: 0 !important;
}

.form-check-require {
    cursor: pointer;
    transform: scale(1.25);
    -webkit-transform: scale(1.25);
    transform-origin: center;
    -webkit-transform-origin: center;
    margin: 0;
    transition: transform 0.15s ease;
}

.category-field-input-wrap .form-control.category-field-invalid {
    border-color: #d97757;
    padding-right: 2.25rem;
}

.category-field-input-wrap .form-control.category-field-invalid:focus {
    border-color: #d97757;
    box-shadow: 0 0 0 0.2rem rgba(217, 119, 87, 0.22);
}

.category-field-invalid-icon {
    position: absolute;
    top: 50%;
    right: 12px;
    transform: translateY(-50%);
    color: #d97757;
    font-size: 1.125rem;
    line-height: 1;
    pointer-events: none;
}

.category-tree-card-shell {
    position: relative;
    overflow: visible;
    min-width: 0;
    align-self: stretch;
    display: flex;
    flex-direction: column;
}

.category-main-tree-card {
    flex: 1 1 auto;
    min-height: 0;
    overflow: visible;
}

.category-tree-card-header {
    isolation: isolate;
}

.category-product-categories-row {
    overflow: visible;
}

.category-cards-split {
    min-width: 0;
    overflow: visible;
}

.category-filter-side-card {
    width: 100%;
    max-width: 100%;
}

@media (min-width: 992px) {
    .category-filter-side-card {
        width: 268px;
        max-width: 268px;
    }
}

.category-level-filter-body {
    max-height: min(60vh, 520px);
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

@media (min-width: 992px) {
    .category-level-filter-body {
        max-height: min(70vh, 640px);
    }
}

.category-filter-todos,
.category-filter-level-toggle,
.category-filter-node-line {
    border: 0;
    background: transparent;
    font-size: 0.8125rem;
    padding: 0.35rem 0.5rem;
    color: #212529;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    border-radius: 0.25rem;
}

.category-filter-todos:hover,
.category-filter-level-toggle:hover,
.category-filter-node-line:hover {
    background: rgba(13, 110, 253, 0.08);
}

.category-filter-todos.active,
.category-filter-level-toggle.active,
.category-filter-node-line.active {
    background: rgba(13, 110, 253, 0.12);
    color: #0d6efd;
    font-weight: 600;
}

.category-filter-level-toggle {
    padding-left: 0.35rem;
}

.category-filter-chevron {
    width: 1.1rem;
    flex-shrink: 0;
}

.category-filter-level-nodes {
    padding-left: 1.35rem;
    padding-bottom: 0.15rem;
}

.category-filter-node-line {
    flex-direction: column;
    align-items: flex-start;
    gap: 0;
    line-height: 1.25;
}

.category-filter-node-code {
    font-size: 0.75rem;
}

.category-filter-node-name {
    display: block;
    width: 100%;
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
