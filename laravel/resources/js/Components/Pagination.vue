<script>
    export default {
    name: "Pagination",

    props: {
        modelValue: {
        type: Number,
        default: 1,
        },
        totalItems: {
        type: Number,
        required: true,
        },
        itemsPerPage: {
        type: Number,
        default: 10,
        },
    },

    computed: {
        totalPages() {
        return Math.max(1, Math.ceil(this.totalItems / this.itemsPerPage));
        },
        currentPage() {
        return this.modelValue;
        },
        startItem() {
            if (this.totalItems === 0) return 0;
            return (this.currentPage - 1) * this.itemsPerPage + 1;
        },
        endItem() {
            return Math.min(this.currentPage * this.itemsPerPage, this.totalItems)
        },
        currentItems() {
            if (this.totalItems === 0) return 0;
            return this.endItem - this.startItem + 1;
        }
    },

    methods: {
        goToFirst() {
        this.$emit("update:modelValue", 1);
        },

        goToPrev() {
        if (this.currentPage > 1) {
            this.$emit("update:modelValue", this.currentPage - 1);
        }
        },

        goToNext() {
        if (this.currentPage < this.totalPages) {
            this.$emit("update:modelValue", this.currentPage + 1);
        }
        },

        goToLast() {
        this.$emit("update:modelValue", this.totalPages);
        },
    },
    };
</script>

<template>
    <div>
        <div class="pagination-container">
            <div class="page-info-container">
                <span class="page-info">Mostrando {{ startItem }} a {{ endItem }} de {{ totalItems }} registros</span>
            </div>
            <!-- Ir al inicio -->
             <div class="pagination-actions">
            <button
                class="page-btn"
                :disabled="currentPage === 1"
                @click="goToFirst"
            >
                <span class="bx bxs-chevrons-left"></span>
            </button>
        
            <!-- Página anterior -->
            <button
                class="page-btn"
                :disabled="currentPage === 1"
                @click="goToPrev"
            >
                <span class="bx bxs-chevron-left"></span>
            </button>
        
            <!-- Página actual -->
            <span class="page-info text-center">
                Pág {{ currentPage }} de {{ totalPages }}
            </span>
        
            <!-- Página siguiente -->
            <button
                class="page-btn"
                :disabled="currentPage === totalPages"
                @click="goToNext"
            >
                <span class="bx bxs-chevron-right"></span>
            </button>
            <!-- Ir al final -->
            <button
            class="page-btn"
            :disabled="currentPage === totalPages"
            @click="goToLast"
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
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
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
    display: flex;
    align-items: center;
    gap: 8px;
}
</style>