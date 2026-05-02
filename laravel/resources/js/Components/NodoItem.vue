<script>
export default {
    name: 'NodoItem',
    components: {
        NodoItem: () => import('./NodoItem.vue')
    },
    props: {
        node: {
            type: Object,
            required: true
        },
        level: {
            type: Number,
            default: 0
        },
        canCreateChild: {
            type: Function,
            default: () => true
        },
        selectedNode: { // NUEVA PROP para manejar la selección desde el padre
            type: [Object, null],
            default: null
        },
        searchTerm: {
            type: String,
            default: ''
        }
    },
    data() {
        return {
            isExpanded: this.node.expanded !== undefined ? this.node.expanded : false,
            nodeId: `node-${this.level}-${this.node.code}-${Date.now()}`,
        }
    },
    computed: {

        hasChildren() {
            return this.node.children && (
                (Array.isArray(this.node.children) && this.node.children.length > 0) ||
                (typeof this.node.children === 'object' && Object.keys(this.node.children).length > 0)
            );
        },

        hasMenu() {
            return this.node.hasMenu !== undefined ? this.node.hasMenu : true
        },
        showNewButton() {
            return this.node.showNewButton || false
        },
        uniqueId() {
            return `accordion-${this.level}-${this.node.uuid ? this.node.uuid.replace(/\s+/g, '-') : 'default'}`;
        },
        iconColor() {
            const colors = ['bg-opacity-75 bg-primary', 'bg-info', 'bg-warning', 'bg-success']
            return colors[this.level % colors.length]
        },
        iconClass() {
            const icons = [
                'ri-building-line',
                'ri-settings-4-line',
                'ri-coins-line',
                'ri-money-dollar-circle-line'
            ]
            return icons[this.level % icons.length]
        },
        fontSize() {
            return this.level >= 3 ? 'fs-14' : 'fs-15'
        },
        isActive() {
            // Permite que el padre marque seleccionados
            if (this.selectedNode && this.selectedNode.uuid && this.node.uuid) {
                return this.selectedNode.uuid === this.node.uuid;
            }
            return this.node.isActive || false;
        },
        isInactive() {
            // status = 0 o '0' → nodo inactivo (solo afecta visual, no la navegación)
            return Number(this.node.status) === 0;
        },
        canCreate() {
            return this.canCreateChild(this.node, this.level)
        },
        displayedCode() {
            return this.node.prefix ? `${this.node.prefix}${this.node.code ?? ''}` : `${this.node.code ?? ''}`;
        },
        highlightedCode() {
            return this.highlightMatch(this.displayedCode, 'code');
        },
        highlightedName() {
            return this.highlightMatch(this.node.name ?? '', 'name');
        }
    },
    watch: {
        // Cuando cambie la selección el padre, expandimos para mostrarlo si es un hijo
        selectedNode(newVal) {
            if (newVal && newVal.uuid && this.node.children) {
                // Ver si selectedNode es hijo directo
                let children = Array.isArray(this.node.children) ? this.node.children : Object.values(this.node.children);
                if (children.some(child => child.uuid === newVal.uuid)) {
                    this.isExpanded = true;
                }
            }
            // Expande si el nodo actual es el seleccionado
            if (newVal && newVal.uuid === this.node.uuid) {
                this.isExpanded = true;
            }
        },
        // Nuevo watch para observar cambios en node.expanded
        'node.expanded'(newVal) {
            if (newVal !== undefined) {
                this.isExpanded = newVal;
            }
        },
    },
    methods: {
        toggleCollapse() {
            this.isExpanded = !this.isExpanded
        },
        handleMenuAction(action) {
            // Emitimos el nodo activo al padre para seleccionarlo
            this.$emit('menu-action', {
                action,
                node: this.node
            })
            // Selección inmediata localmente, para mejor UX hasta que padre lo marque
            // (El padre será fuente de la verdad)
            if (action === 'view' || action === 'edit') {
                this.$emit('select-node', this.node)
            }
        },
        handleNew() {
            this.$emit('new-node', this.node)
            // Selección inmediata localmente, luego el padre actualiza
            this.$emit('select-node', this.node)
        },
        escapeHtml(text) {
            return String(text)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#39;');
        },
        escapeRegex(text) {
            return String(text).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        },
        highlightMatch(text, target = 'name') {
            const rawText = String(text ?? '');
            const term = String(this.searchTerm ?? '').trim();
            const safeText = this.escapeHtml(rawText);
            if (!term) return safeText;

            const pattern = this.escapeRegex(term);
            if (!pattern) return safeText;
            const regex = new RegExp(`(${pattern})`, 'ig');
            const highlightClass = target === 'code'
                ? 'search-highlight-code bg-soft-light'
                : 'search-highlight-name';
            return safeText.replace(regex, `<span class="${highlightClass}">$1</span>`);
        }
    }
}
</script>

<template>
    <div class="accordion-item border-0">
        <div class="d-flex align-items-start" :class="{ 'expanded-node': isExpanded }">
            <div class="flex-grow-1">
                <h2 class="accordion-header" :id="`heading-${uniqueId}`">
                    <button
                        class="accordion-button p-2 shadow-none w-100 text-start"
                        :class="{ 'collapsed': !isExpanded, 'expanded-button': isExpanded }"
                        type="button"
                        :data-bs-toggle="hasChildren ? 'collapse' : null"
                        :data-bs-target="hasChildren ? `#${uniqueId}` : null"
                        :aria-expanded="isExpanded ? 'true' : 'false'"
                        :aria-controls="uniqueId"
                        @click="toggleCollapse"
                    >
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <!-- <span
                                    class="badge rounded-pill ms-1"
                                    :class="isActive ? 'bg-primary' : iconColor"
                                > -->
                                <span
                                    class="badge rounded-pill ms-1 fs-11"
                                    :class="[
                                        iconColor,
                                        { 'badge-active': isActive },
                                        { 'badge-inactive': isInactive }
                                    ]"
                                    v-html="highlightedCode"
                                >
                                </span>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6
                                    :class="[fontSize, 'mb-0', 'fw-semibold', 'text-uppercase', { 'text-inactive': isInactive }]"
                                    v-html="highlightedName"
                                ></h6>
                            </div>
                            <div v-if="hasChildren" class="flex-shrink-0 ms-2">
                                <i
                                    :class="isExpanded ? 'ri-arrow-down-s-line' : 'ri-arrow-right-s-line'"
                                    class="fs-16 text-muted"
                                ></i>
                            </div>
                        </div>
                    </button>
                </h2>
            </div>
            <div v-if="hasMenu" class="dropdown ms-2 pt-2">
                <a
                    class="text-reset fs-16 px-3"
                    href="#"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                    @click.prevent
                >
                    <i class="mdi mdi-dots-vertical align-middle"></i>
                </a>
                <div class="dropdown-menu dropdownmenu-primary">
                    <a
                        class="dropdown-item"
                        href="#"
                        @click.prevent="handleMenuAction('view')"
                    >
                        Ver
                    </a>
                    <a
                        class="dropdown-item"
                        href="#"
                        @click.prevent="handleMenuAction('edit')"
                    >
                        Editar
                    </a>
                    <a
                        v-if="canCreate"
                        class="dropdown-item"
                        href="#"
                        @click.prevent="handleMenuAction('new')"
                    >
                        Nuevo
                    </a>
                </div>
            </div>
        </div>
        <div v-if="hasChildren" :id="uniqueId" class="accordion-collapse collapse" :class="{ 'show': isExpanded }" :aria-labelledby="`heading-${uniqueId}`">
            <div class="accordion-body ps-5 py-0 pe-0">
                <div v-if="node.children && (Array.isArray(node.children) ? node.children.length > 0 : Object.keys(node.children).length > 0)">
                    <div class="accordion accordion-flush" :id="`accordion-${level + 1}`">
                        <NodoItem
                            v-for="(child, index) in (Array.isArray(node.children) ? node.children : Object.values(node.children))"
                            :key="`${child.code}-${index}`"
                            :node="child"
                            :level="level + 1"
                            :canCreateChild="canCreateChild"
                            :selectedNode="selectedNode"
                            :searchTerm="searchTerm"
                            @menu-action="$emit('menu-action', $event)"
                            @new-node="$emit('new-node', $event)"
                            @select-node="$emit('select-node', $event)"
                        />
                        <div v-if="showNewButton && canCreate" class="accordion-item border-0">
                            <div class="accordion-header" :id="`heading-new-${uniqueId}`">
                                <button class="accordion-button p-2 shadow-none collapsed w-100 text-start" type="button" @click.prevent="handleNew">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 avatar-xs">
                                            <div class="avatar-title bg-light text-success rounded-circle material-shadow">
                                                <i class="ri-play-list-add-line"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="fs-15 mb-0 fw-semibold">Nuevo</h6>
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-muted fst-italic ps-3">
                    Sin elementos.
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.accordion-button:not(.collapsed) {
    box-shadow: none;
    background-color: #f8f9fa;
}

.accordion-button::after {
    margin-left: auto;
}

.material-shadow {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.expanded-node {
    background-color: #f8f9fa;
    border-radius: 4px;
    padding: 4px 0;
}

.badge-active {
    outline: 2px solid #0d6efd;
    outline-offset: 2px;
    font-weight: 600;
}

/* Nodos inactivos (status = 0): solo visual, no cambia navegación */
.badge-inactive {
    background-color: #9b9b9b !important;
    color: #fff !important;
}

.text-inactive {
    color: #adb5bd !important;
}

:deep(.search-highlight) {
    background-color: #fff3b0;
    border-radius: 3px;
    padding: 0 2px;
}

:deep(.search-highlight-code) {
    border-radius: 3px;
    padding: 0 3px;
    color: #495057;
}

:deep(.search-highlight-name) {
    background-color: #fff3b0;
    border-radius: 3px;
    padding: 0 2px;
}

/* .active-nodo {
    border-left: 5px solid #E6E6E6;
} */
/* .expanded-button {
    background-color: #eef1f4 !important;
}

.expanded-button:hover {
    background-color: #dee2e6 !important;
} */

</style>
