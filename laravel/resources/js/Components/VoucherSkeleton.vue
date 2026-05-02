
<script>
import Skeleton from 'primevue/skeleton';

export default {
    name: "VoucherSkeleton",
    components: {
        Skeleton: Skeleton,
    },
};
</script>

<template>
    <div class="voucher-skeleton" aria-busy="true" aria-label="Cargando comprobante">
        <div class="page-header py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex align-items-center">
                    <div class="btn p-0 border-0 pe-2" aria-hidden="true">
                        <Skeleton width="2.25rem" height="2.25rem" borderRadius="6px" />
                    </div>
                    <div>
                        <Skeleton width="14rem" height="1.25rem" class="mb-2" />
                        <Skeleton width="20rem" height="0.875rem" />
                    </div>
                </div>
                <div class="d-flex align-items-center gap-1 mt-2 mt-md-0" aria-hidden="true">
                    <Skeleton width="2.25rem" height="2.25rem" borderRadius="6px" />
                    <Skeleton width="2.25rem" height="2.25rem" borderRadius="6px" />
                    <Skeleton width="4.5rem" height="1.5rem" class="mx-1" borderRadius="6px" />
                    <Skeleton width="2.25rem" height="2.25rem" borderRadius="6px" />
                    <Skeleton width="2.25rem" height="2.25rem" borderRadius="6px" />
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12" style="padding-top: 15px">
                <div class="row d-flex align-items-stretch">
                    <div class="col-9 d-flex">
                        <div class="card w-100 equal-card d-flex flex-column">
                            <div class="card-body p-0 d-flex flex-column">
                                <div class="accordion-item border-0 d-flex flex-column flex-grow-1">
                                    <h2 class="accordion-header border-bottom">
                                        <div
                                            class="d-flex align-items-center p-2 shadow-none p-3"
                                            aria-hidden="true"
                                        >
                                            <i class="ri ri-file-list-2-line fs-16 text-primary me-2"></i>
                                            <h5 class="card-title mb-0 flex-fill">Información general</h5>
                                            <i class="ri ri-arrow-down-s-line fs-18 ms-auto text-muted opacity-50"></i>
                                        </div>
                                    </h2>
                                    <div class="accordion-collapse collapse show">
                                        <div class="accordion-body p-3">
                                            <div class="row">
                                                <div class="col-4 mb-4">
                                                    <!-- <label class="form-label">Código</label> -->
                                                    <!-- <p class="placeholder col-4 mb-3 bg-primary d-block text-muted"></p> -->
                                                    <Skeleton width="3.5rem" height="0.75rem" class="mb-3"/>
                                                    <Skeleton width="85%" height="1.1rem" />
                                                </div>
                                                <div class="col-4 mb-4">
                                                    <!-- <label class="form-label">Fecha</label> -->
                                                    <!-- <p class="placeholder col-4 mb-3 d-block text-muted"></p> -->
                                                    <Skeleton width="2.5rem" height="0.75rem" class="mb-3" />
                                                    <Skeleton width="70%" height="1.1rem" />
                                                </div>
                                                <div class="col-4 mb-4">
                                                    <!-- <label class="form-label">Estado</label> -->
                                                    <!-- <p class="placeholder col-4 mb-3 d-block text-muted"></p> -->
                                                    <Skeleton width="3rem" height="0.75rem" class="mb-3" />
                                                    <Skeleton width="5rem" height="1.5rem" borderRadius="16px" />
                                                </div>
                                                <div class="col-4 mb-4">
                                                    <!-- <label class="form-label">Sucursal</label> -->
                                                    <!-- <p class="placeholder col-4 mb-3 d-block text-muted"></p> -->
                                                    <Skeleton width="4rem" height="0.75rem" class="mb-3" />
                                                    <Skeleton width="90%" height="1.1rem" />
                                                </div>
                                                <div class="col-4 mb-4">
                                                    <!-- <label class="form-label">Tipo Comprobante</label> -->
                                                    <!-- <p class="placeholder col-4 mb-3 d-block text-muted"></p> -->
                                                    <Skeleton width="7rem" height="0.75rem" class="mb-3" />
                                                    <Skeleton width="88%" height="1.1rem" />
                                                </div>
                                                <div class="col-4 mb-4">
                                                    <!-- <label class="form-label">Tipo Contabilidad</label> -->
                                                    <!-- <p class="placeholder col-4 mb-3 d-block text-muted"></p> -->
                                                    <Skeleton width="6.5rem" height="0.75rem" class="mb-3" />
                                                    <Skeleton width="82%" height="1.1rem" />
                                                </div>
                                                <div class="col-12">
                                                    <!-- <label class="form-label">Descripción</label> -->
                                                    <!-- <p class="placeholder col-6 mb-3 d-block text-muted"></p> -->
                                                    <Skeleton width="5rem" height="0.75rem" class="mb-3" />
                                                    <Skeleton width="100%" height="2.75rem" borderRadius="6px" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Adjuntos (col-3) -->
                    <div class="col-3 d-flex">
                        <div class="card w-100 equal-card">
                            <div class="card-body p-0 min-height-skeleton">
                                <div class="accordion-item border-0">
                                    <h2 class="accordion-header border-bottom">
                                        <div
                                            class="d-flex align-items-center p-2 shadow-none p-3"
                                            aria-hidden="true"
                                        >
                                            <i class="ri-attachment-line fs-16 text-primary me-2"></i>
                                            <h5 class="card-title mb-0 flex-fill">Adjuntos</h5>
                                            <i class="ri ri-arrow-down-s-line fs-18 ms-auto text-muted opacity-50"></i>
                                        </div>
                                    </h2>
                                    <div class="accordion-collapse collapse show">
                                        <div class="accordion-body p-3 d-flex flex-column align-items-center justify-content-center text-center">
                                            <Skeleton width="60px" height="60px" class="mb-2" borderRadius="8px" />
                                            <Skeleton width="9rem" height="0.9rem" class="mb-2" />
                                            <Skeleton width="6rem" height="0.75rem" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalles + tabla -->
        <div class="row">
            <div class="col-12 py-4">
                <div class="card">
                    <div class="card-body p-0">
                        <div class="accordion-item border-0">
                            <h2 class="accordion-header">
                                <div class="d-flex align-items-center py-3 shadow-none px-3 flex-wrap gap-2" aria-hidden="true">
                                    <i class="ri ri-stack-line fs-16 text-primary me-2"></i>
                                    <h5 class="card-title mb-0 flex-fill">Detalles</h5>
                                    <div class="d-flex align-items-center gap-2 ms-md-auto">
                                        <Skeleton width="3rem" height="1.75rem" borderRadius="4px" />
                                        <Skeleton width="3rem" height="1.75rem" borderRadius="4px" />
                                        <Skeleton width="5.5rem" height="1.75rem" borderRadius="4px" />
                                    </div>
                                </div>
                            </h2>
                            <div class="accordion-collapse collapse show">
                                <div class="accordion-body p-3">
                                    <div class="table-card table-responsive mb-1">
                                        <table class="table table-bordered table-centered align-middle table-nowrap mb-0">
                                            <thead class="text-muted table-light">
                                                <tr>
                                                    <th scope="col" class="border-left-0 text-center ps-2 py-3">
                                                        <Skeleton width="1.25rem" height="0.85rem" />
                                                    </th>
                                                    <th scope="col" class="py-3"><Skeleton width="3.5rem" height="1rem" /></th>
                                                    <th scope="col" class="py-3"><Skeleton width="3.5rem" height="1rem" /></th>
                                                    <th scope="col" class="py-3"><Skeleton width="5rem" height="1rem" /></th>
                                                    <th scope="col" class="py-3"><Skeleton width="4rem" height="1rem" /></th>
                                                    <th scope="col" class="py-3"><Skeleton width="4rem" height="1rem" /></th>
                                                    <th scope="col" class="py-3"><Skeleton width="4.5rem" height="1rem" /></th>
                                                    <th scope="col" class="py-3"><Skeleton width="4rem" height="1rem" /></th>
                                                    <th scope="col" class="py-3"><Skeleton width="4.5rem" height="1rem" /></th>
                                                    <th scope="col" class="text-end py-3"><Skeleton width="2.5rem" height="1rem" class="ms-auto" /></th>
                                                    <th scope="col" class="text-end py-3"><Skeleton width="3rem" height="1rem" class="ms-auto" /></th>
                                                    <th scope="col" class="text-end border-right-0 pe-2 py-3">
                                                        <Skeleton width="3.25rem" height="0.85rem" class="ms-auto" />
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="i in 6" :key="i">
                                                    <td class="text-center py-2 ps-2 border-left-0">
                                                        <Skeleton width="1rem" height="0.85rem" class="mx-auto" />
                                                    </td>
                                                    <td class="py-2">
                                                        <Skeleton width="85%" height="0.8rem" class="mb-1" />
                                                        <Skeleton width="65%" height="0.75rem" />
                                                    </td>
                                                    <td class="py-2">
                                                        <Skeleton width="70%" height="0.8rem" class="mb-1" />
                                                        <Skeleton width="55%" height="0.75rem" />
                                                    </td>
                                                    <td class="py-2">
                                                        <Skeleton width="75%" height="0.8rem" class="mb-1" />
                                                        <Skeleton width="50%" height="0.75rem" />
                                                    </td>
                                                    <td class="py-2"><Skeleton width="80%" height="0.85rem" /></td>
                                                    <td class="py-2">
                                                        <Skeleton width="40%" height="0.8rem" class="mb-1" />
                                                        <Skeleton width="70%" height="0.75rem" />
                                                    </td>
                                                    <td class="py-2"><Skeleton width="4rem" height="0.85rem" /></td>
                                                    <td class="py-2"><Skeleton width="3.5rem" height="0.85rem" /></td>
                                                    <td class="py-2"><Skeleton width="90%" height="0.85rem" /></td>
                                                    <td class="text-end py-2 border-right-0 pe-1"><Skeleton width="3.5rem" height="0.85rem" class="ms-auto" /></td>
                                                    <td class="text-end py-2 pe-1"><Skeleton width="3.5rem" height="0.85rem" class="ms-auto" /></td>
                                                    <td class="text-end py-2 border-right-0 pe-2">
                                                        <Skeleton width="3.5rem" height="0.85rem" class="ms-auto" />
                                                    </td>
                                                </tr>
                                            </tbody>
                                            <tfoot class="bg-light">
                                                <tr>
                                                    <td colspan="10" class="py-2 border-left-0"></td>
                                                    <td class="py-2 text-end border-right-0 pe-1">
                                                        <Skeleton width="4.5rem" height="0.95rem" class="ms-auto" />
                                                    </td>
                                                    <td class="py-2 text-end border-right-0 pe-2">
                                                        <Skeleton width="4.5rem" height="0.95rem" class="ms-auto" />
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                        <div class="d-flex align-items-center flex-wrap pt-2 pb-1 px-2 bg-light">
                                            <div class="col-sm-12 col-md-5 mb-2 mb-md-0">
                                                <Skeleton width="16rem" height="0.9rem" />
                                            </div>
                                            <div class="col-sm-12 col-md-7">
                                                <div class="d-flex justify-content-md-end gap-1 flex-wrap">
                                                    <Skeleton width="2rem" height="2rem" borderRadius="4px" />
                                                    <Skeleton width="2rem" height="2rem" borderRadius="4px" />
                                                    <Skeleton width="2rem" height="2rem" borderRadius="4px" />
                                                    <Skeleton width="2rem" height="2rem" borderRadius="4px" />
                                                    <Skeleton width="2rem" height="2rem" borderRadius="4px" />
                                                    <Skeleton width="2rem" height="2rem" borderRadius="4px" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-end mb-4" aria-hidden="true"></div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.border-right-0 {
    border-right: 0;
}
.border-left-0 {
    border-left: 0;
}
.min-height-skeleton {
    min-height: 300px;
}
.align-items-stretch .equal-card {
    min-height: 100%;
}
/* Forzamos que cualquier componente de PrimeVue Skeleton sea visible */
:deep(.p-skeleton) {
    background-color: #e2e2e2 !important; 
    display: block !important;
    overflow: hidden;
    position: relative;
}

/* Añadimos una animación manual por si la de PrimeVue falla */
:deep(.p-skeleton::after) {
    content: "";
    animation: p-skeleton-animation 1.2s infinite;
    background: linear-gradient(90deg, rgba(255, 255, 255, 0), rgba(255, 255, 255, 0.4), rgba(255, 255, 255, 0));
    height: 100%;
    left: 0;
    position: absolute;
    right: 0;
    top: 0;
    transform: translateX(-100%);
    z-index: 1;
}

@keyframes p-skeleton-animation {
    from { transform: translateX(-100%); }
    to { transform: translateX(100%); }
}
</style>
