<script>
export default {
    name: 'FilterLevel',
    props: {
        items: {
            type: Array,
            default: () => [],
        },
        modelValue: {
            type: [String, Number, null],
            default: null,
        },
        sourceItems: {
            type: Array,
            default: () => [],
        },
        nodeValueKey: {
            type: String,
            default: 'code',
        },
        nodeNameKey: {
            type: String,
            default: 'name',
        },
        childrenKey: {
            type: String,
            default: 'children',
        },
        showAllButton: {
            type: Boolean,
            default: true,
        },
        allLabel: {
            type: String,
            default: 'Todos',
        },
        emptyChildrenLabel: {
            type: String,
            default: 'Sin categorías',
        },
        emptyLabel: {
            type: String,
            default: 'No hay elementos para filtrar.',
        },
        filterByKey: {
            type: String,
            default: 'code',
        },
        filterFn: {
            type: Function,
            default: null,
        },
    },
    emits: ['update:modelValue', 'change', 'filtered'],
    data() {
        return {
            expandedRootValues: [],
            currentRootValue: null,
        };
    },
    computed: {
        filteredSourceItems() {
            if (!Array.isArray(this.sourceItems)) {
                return [];
            }

            if (!this.modelValue) {
                return this.sourceItems;
            }

            if (typeof this.filterFn === 'function') {
                return this.filterFn(this.sourceItems, this.modelValue, this.currentRootValue);
            }

            return this.defaultFilterTree(this.sourceItems, this.modelValue);
        },
    },
    watch: {
        filteredSourceItems: {
            immediate: true,
            handler(value) {
                this.$emit('filtered', value);
            },
        },
    },
    methods: {
        getNodeValue(node) {
            return node?.[this.nodeValueKey] ?? null;
        },
        getNodeName(node) {
            return node?.[this.nodeNameKey] ?? '';
        },
        getNodeChildren(node) {
            const children = node?.[this.childrenKey];
            return Array.isArray(children) ? children : [];
        },
        isRootExpanded(node) {
            const value = this.getNodeValue(node);
            return this.expandedRootValues.includes(value);
        },
        isNodeActive(node) {
            return this.getNodeValue(node) === this.modelValue;
        },
        toggleRoot(node) {
            const value = this.getNodeValue(node);
            if (!value) {
                return;
            }

            if (this.expandedRootValues.includes(value)) {
                this.expandedRootValues = this.expandedRootValues.filter((key) => key !== value);
                return;
            }

            this.expandedRootValues = [...this.expandedRootValues, value];
        },
        clearFilter() {
            this.currentRootValue = null;
            this.$emit('update:modelValue', null);
            this.$emit('change', {
                selected: null,
                root: null,
            });
        },
        selectRoot(root) {
            const rootValue = this.getNodeValue(root);
            this.currentRootValue = rootValue;
            this.toggleRoot(root);
            this.$emit('update:modelValue', rootValue);
            this.$emit('change', {
                selected: root,
                root,
            });
        },
        selectChild(root, child) {
            const rootValue = this.getNodeValue(root);
            const childValue = this.getNodeValue(child);
            this.currentRootValue = rootValue;
            if (!this.isRootExpanded(root)) {
                this.toggleRoot(root);
            }
            this.$emit('update:modelValue', childValue);
            this.$emit('change', {
                selected: child,
                root,
            });
        },
        defaultFilterTree(nodes, selectedValue) {
            if (!selectedValue) {
                return nodes;
            }

            const walk = (list) => {
                return list.reduce((acc, item) => {
                    const children = this.getNodeChildren(item);
                    const itemValue = item?.[this.filterByKey];
                    const filteredChildren = walk(children);
                    const shouldInclude = itemValue === selectedValue || filteredChildren.length > 0;

                    if (shouldInclude) {
                        acc.push({
                            ...item,
                            [this.childrenKey]: filteredChildren,
                        });
                    }

                    return acc;
                }, []);
            };

            return walk(nodes);
        },
    },
};
</script>

<template>
    <div class="category-level-filter-body">
        <button
            v-if="showAllButton"
            type="button"
            class="category-filter-todos w-100 text-start"
            :class="{ active: !modelValue }"
            @click="clearFilter"
        >
            {{ allLabel }}
        </button>

        <div
            v-for="(root, idx) in items"
            :key="`${getNodeValue(root)}-${idx}`"
            class="category-filter-level-block"
        >
            <button
                type="button"
                class="category-filter-level-toggle w-100 text-start"
                :class="{ active: isNodeActive(root) }"
                :aria-expanded="isRootExpanded(root)"
                @click="selectRoot(root)"
            >
                <i
                    class="category-filter-chevron fs-14 text-muted"
                    :class="isRootExpanded(root) ? 'ri-arrow-down-s-line' : 'ri-arrow-right-s-line'"
                    aria-hidden="true"
                ></i>
                <span class="category-filter-node-code text-muted">{{ getNodeValue(root) }}</span>
                <span class="fw-medium text-truncate">{{ getNodeName(root) }}</span>
            </button>

            <div
                v-show="isRootExpanded(root)"
                class="category-filter-level-nodes"
            >
                <button
                    v-for="(child, childIdx) in getNodeChildren(root)"
                    :key="`${getNodeValue(child)}-${childIdx}`"
                    type="button"
                    class="category-filter-node-line w-100 text-start"
                    :class="{ active: isNodeActive(child) }"
                    @click="selectChild(root, child)"
                >
                    <span class="category-filter-node-code text-muted">{{ getNodeValue(child) }}</span>
                    <span class="category-filter-node-name text-truncate">{{ getNodeName(child) }}</span>
                </button>

                <div
                    v-if="!getNodeChildren(root).length"
                    class="text-muted fs-11 px-2 py-1 fst-italic"
                >
                    {{ emptyChildrenLabel }}
                </div>
            </div>
        </div>

        <div
            v-if="!items.length"
            class="text-muted fs-12 px-2 py-2 fst-italic"
        >
            {{ emptyLabel }}
        </div>
    </div>
</template>

<style scoped>
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
</style>
