<script>
import { nextTick } from 'vue';
import { useAlert } from '@/Composables/useSweetAlert.js';

const { showAlert, showLoading } = useAlert();

export default {
    name: 'ExportVoucherModal',

    props: {
        modelValue: {
            type: Boolean,
            required: true
        },
        voucherUuid: {
            type: String,
            required: true
        }
    },

    emits: ['update:modelValue', 'exported'],

    data() {
        return {
            internalShow: this.modelValue,
            loading: false,
            typeActive: null,
        };
    },

    watch: {
        modelValue(val) {
            this.internalShow = val;
        }
    },

    methods: {
        close() {
            this.internalShow = false;
            this.$emit('update:modelValue', false);
            this.$emit('exported');
        },

        async generate(type) {
            this.loading = true;
            this.typeActive = type;
            try {
                const url = route("vouchers.export", {
                    type,
                    uuid: this.voucherUuid,
                });

                const response = await fetch(url, {
                    headers: { Accept: "application/pdf" }
                });

                if (!response.ok) {
                    throw new Error("Error al generar el PDF");
                }

                const blob = await response.blob();
                const blobUrl = URL.createObjectURL(blob);

                const newTab = window.open(blobUrl, '_blank');
                    if (!newTab) {
                        showAlert('error', '¡Error!', 'No se pudo abrir la nueva pestaña. Verifique la configuración del navegador.');
                        return;
                    }

                    // Si el navegador bloquea la apertura de la nueva pestaña, muestra un mensaje
                newTab.focus();

                const link = document.createElement("a");
                link.href = blobUrl;
                const date = new Date();

                const day = String(date.getDate()).padStart(2, '0');

                const months = [
                    'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
                    'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'
                ];

                const month = months[date.getMonth()];
                const year = date.getFullYear();
                // this.$emit('exported');
            } catch (error) {

                showAlert(
                    "error",
                    "Error",
                    "Ocurrió un problema al generar el PDF"
                );

                console.error(error);
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>

<template>
    <!-- <div v-if="internalShow"> -->
        <!-- Backdrop -->
        <!-- <div class="soft-backdrop"></div> -->

        <!-- Modal -->
        <div v-if="internalShow" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header p-3 bg-light">
                        <h5 class="modal-title">
                            Imprimir comprobante
                        </h5>
                        <button
                            type="button"
                            class="btn-close"
                            @click="close"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 avatar-sm">
                                <span class=" avatar-title rounded-2 bg-primary-subtle text-primary fs-5">
                                    <i class="ri-file-list-3-line"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Comprobante General</h6>
                                <p class="text-muted mb-0">Resumen completo</p>
                            </div>
                            <div class="flex-shrink-0 avatar-sm">
                                <span class="avatar-title rounded-2 bg-light text-dark fs-5" style="cursor: pointer;" @click="generate('general')" :disabled="loading && typeActive == 'general'">
                                    <span v-if="typeActive == 'general' && loading" class="spinner-border spinner-border-sm" role="status"></span>
                                    <i v-else class="fs-5 ri-printer-line"></i>
                                </span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mt-3">
                            <div class="flex-shrink-0 avatar-sm">
                                <span class=" avatar-title rounded-2 bg-primary-subtle text-primary fs-5">
                                    <i class="ri-bank-line"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Por Cuenta</h6>
                                <p class="text-muted mb-0">Detalle por cuentas</p>
                            </div>
                            <div class="flex-shrink-0 avatar-sm">
                                <span class=" avatar-title rounded-2 bg-light text-dark fs-5" style="cursor: pointer;" @click="generate('accounts')" :disabled="loading && typeActive == 'accounts'">
                                    <span v-if="typeActive == 'accounts' && loading" class="spinner-border spinner-border-sm" role="status"></span>
                                    <i v-else class="fs-5 ri-printer-line"></i>
                                </span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mt-3 mb-2">
                            <div class="flex-shrink-0 avatar-sm">
                                <span class=" avatar-title rounded-2 bg-primary-subtle text-primary fs-5">
                                    <i class="ri-user-3-line"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Por Tercero</h6>
                                <p class="text-muted mb-0">Detalle por terceros</p>
                            </div>
                            <div class="flex-shrink-0 avatar-sm">
                                <span class=" avatar-title rounded-2 bg-light text-dark fs-5" style="cursor: pointer;" @click="generate('third_parties')" :disabled="loading && typeActive == 'third_parties'">
                                    <span v-if="typeActive == 'third_parties' && loading" class="spinner-border spinner-border-sm" role="status"></span>
                                    <i v-else class="fs-5 ri-printer-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <!-- </div> -->
</template>

<style scoped>
    .avatar-sm {
        height: 2.6rem;
        width: 2.6rem;
    }
</style>