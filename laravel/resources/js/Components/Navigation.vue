<script>
import { Tooltip } from 'bootstrap';
export default {
    name: "Navigation",

    props: {
        modelValue: {
            type: Number,
            default: 1,
        },
        totalItems: {
            type: Number,
            required: true,
        },
        hasPrevious: {
            type: Boolean,
            default: null,
        },
        hasNext: {
            type: Boolean,
            default: null,
        },
        showEdges: {
            type: Boolean,
            default: true,
        },
    },

    emits: ["update:modelValue", "first", "prev", "next", "last"],
    mounted() {
        this.initTooltips();
    },
    beforeUnmount() {
        this.destroyTooltips();
    },
    computed: {
        currentItem() {
            return this.modelValue;
        },
        isNeighborMode() {
            return this.hasPrevious !== null || this.hasNext !== null;
        },
        canGoPrev() {
            return this.isNeighborMode
                ? Boolean(this.hasPrevious)
                : this.currentItem > 1;
        },
        canGoNext() {
            return this.isNeighborMode
                ? Boolean(this.hasNext)
                : this.currentItem < this.totalItems;
        },
    },

    methods: {
        destroyTooltips() {
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltipTriggerList.forEach(el => {
                const instance = Tooltip.getInstance(el);
                if (instance) {
                    instance.dispose();
                }
            });
        },
        initTooltips() {
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltipTriggerList.forEach(el => {
                new Tooltip(el);
            });
        },
        goToFirst() {
            if (!this.canGoPrev) return;

            if (this.isNeighborMode) {
                this.$emit("first");
                return;
            }

            this.$emit("update:modelValue", 1);
        },

        goToPrev() {
            if (!this.canGoPrev) return;

            if (this.isNeighborMode) {
                this.$emit("prev");
                return;
            }

            this.$emit("update:modelValue", this.currentItem - 1);
        },

        goToNext() {
            if (!this.canGoNext) return;

            if (this.isNeighborMode) {
                this.$emit("next");
                return;
            }

            this.$emit("update:modelValue", this.currentItem + 1);
        },

        goToLast() {
            if (!this.canGoNext) return;

            if (this.isNeighborMode) {
                this.$emit("last");
                return;
            }

            this.$emit("update:modelValue", this.totalItems);
        },
    },
};
</script>

<template>
    <div>
        <div class="pagination-container">
            <!-- <div class="page-info-container">
                <span class="page-info">Mostrando {{ startItem }} a {{ endItem }} de {{ totalItems }} registros</span>
            </div> -->
            <!-- Ir al inicio -->
            <div class="pagination-actions">
            <button
                v-if="showEdges"
                class="page-btn"
                :disabled="!canGoPrev"
                @click="goToFirst"
                data-bs-toggle="tooltip"
                data-bs-trigger="hover"
                data-bs-placement="top"
                title="Ir al inicio"
            >
                <span class="bx bxs-chevrons-left"></span>
            </button>
        
            <!-- Página anterior -->
            <button
                class="page-btn"
                :disabled="!canGoPrev"
                @click="goToPrev"
                data-bs-toggle="tooltip"
                data-bs-trigger="hover"
                data-bs-placement="top"
                title="Ir a la página anterior"
            >
                <span class="bx bxs-chevron-left"></span>
            </button>
        
            <!-- Página actual -->
            <span class="page-info text-center">
                Registro {{ currentItem }} de {{ totalItems }}
            </span>
        
            <!-- Página siguiente -->
            <button
                class="page-btn"
                :disabled="!canGoNext"
                @click="goToNext"
                data-bs-toggle="tooltip"
                data-bs-trigger="hover"
                data-bs-placement="top"
                title="Ir a la página siguiente"
            >
                <span class="bx bxs-chevron-right"></span>
            </button>
            <!-- Ir al final -->
            <button
            v-if="showEdges"
            class="page-btn"
            :disabled="!canGoNext"
            @click="goToLast"
            data-bs-toggle="tooltip"
            data-bs-trigger="hover"
            data-bs-placement="top"
            title="Ir al final"
            >
            <span class="bx bxs-chevrons-right"></span>
        </button>
    </div>
        </div>
    </div>
</template>
<style scoped>
    .pagination-container {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        padding: 8px 12px;
        /* border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05); */
    }
    
    .page-btn {
        border: 1px solid #e2e8f0;
        background: #f8f9fa;
        color: #495057;
        padding: 6px 10px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .page-btn:hover:not(:disabled) {
        background: #e9ecef;
    }
    
    .page-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .page-info {
        font-size: 14px;
        font-weight: 500;
        color: #343a40;
        padding: 0 6px;
    }

    .page-info-container {
        text-align: left;
    }

    .pagination-actions {
    margin-left: auto;
    /* margin-right: auto; */
    display: flex;
    align-items: center;
    gap: 8px;
}
</style>