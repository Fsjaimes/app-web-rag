<script>
import { router } from '@inertiajs/vue3'
import Layout from '@/Layouts/main.vue'
import PageHeader from '@/Components/page-header.vue'
import { useFetchPetition } from '@/Composables/useFetchPetition.js'
import { useAlert } from '@/Composables/useSweetAlert.js'

const { fetchPetition, uploadFile } = useFetchPetition()
const { showAlert, showConfirm } = useAlert()

export default {
    name: 'AcademicDocumentsIndex',
    components: { Layout, PageHeader },

    props: {
        items: {
            type: Array,
            default: () => [],
        },
    },

    data() {
        return {
            searchQuery: '',
            showModal: false,
            uploading: false,
            form: {
                title: '',
                file: null,
                fileName: '',
            },
            formErrors: {},
        }
    },

    computed: {
        filtered() {
            const q = this.searchQuery.toLowerCase().trim()
            if (!q) return this.items
            return this.items.filter(doc =>
                doc.title.toLowerCase().includes(q) ||
                doc.filename.toLowerCase().includes(q) ||
                doc.status.toLowerCase().includes(q)
            )
        },
        stats() {
            return {
                total:   this.items.length,
                indexed: this.items.filter(d => d.status === 'indexed').length,
                pending: this.items.filter(d => ['pending', 'processing'].includes(d.status)).length,
                error:   this.items.filter(d => d.status === 'error').length,
            }
        },
    },

    methods: {
        openModal() {
            this.form = { title: '', file: null, fileName: '' }
            this.formErrors = {}
            this.showModal = true
        },

        closeModal() {
            if (this.uploading) return
            this.showModal = false
        },

        onFileChange(e) {
            const file = e.target.files[0]
            if (!file) return
            this.form.file = file
            this.form.fileName = file.name
            this.formErrors.file = null
        },

        validateForm() {
            this.formErrors = {}
            if (!this.form.title.trim() || this.form.title.trim().length < 3) {
                this.formErrors.title = 'El título debe tener al menos 3 caracteres.'
            }
            if (!this.form.file) {
                this.formErrors.file = 'Debes seleccionar un archivo PDF.'
            } else if (this.form.file.type !== 'application/pdf') {
                this.formErrors.file = 'Solo se permiten archivos PDF.'
            } else if (this.form.file.size > 20 * 1024 * 1024) {
                this.formErrors.file = 'El archivo no puede superar los 20 MB.'
            }
            return Object.keys(this.formErrors).length === 0
        },

        async uploadDocument() {
            if (!this.validateForm()) return

            this.uploading = true
            const formData = new FormData()
            formData.append('title', this.form.title.trim())
            formData.append('file', this.form.file)

            const { ok, data } = await uploadFile('/documentos-academicos', formData)
            this.uploading = false

            if (ok) {
                this.showModal = false
                showAlert('success', '¡Documento subido!', 'El PDF está siendo indexado. El estado se actualizará en unos momentos.', 3000)
                router.reload({ only: ['items'] })
            } else {
                const msg = data?.message || 'No se pudo subir el documento. Intenta de nuevo.'
                showAlert('error', 'Error al subir', msg, 3000)
            }
        },

        async deleteDocument(uuid, title) {
            const confirmed = await showConfirm(
                'warning',
                '¿Eliminar documento?',
                `"${title}" se eliminará de la base de datos y de ChromaDB. Esta acción no se puede deshacer.`,
                'Sí, eliminar'
            )
            if (!confirmed) return

            const { ok } = await fetchPetition(`/documentos-academicos/${uuid}`, { method: 'DELETE' })

            if (ok) {
                showAlert('success', 'Eliminado', 'El documento fue eliminado correctamente.', 2000)
                router.reload({ only: ['items'] })
            } else {
                showAlert('error', 'Error', 'No se pudo eliminar el documento.', 2500)
            }
        },

        formatSize(bytes) {
            if (bytes >= 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
            if (bytes >= 1024) return (bytes / 1024).toFixed(0) + ' KB'
            return bytes + ' B'
        },

        formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString('es-ES', {
                day: '2-digit', month: 'short', year: 'numeric',
            })
        },

        statusLabel(status) {
            return { indexed: 'Indexado', processing: 'Procesando', pending: 'Pendiente', error: 'Error' }[status] ?? status
        },

        statusClass(status) {
            return {
                indexed:    'badge bg-success-subtle text-success',
                processing: 'badge bg-warning-subtle text-warning',
                pending:    'badge bg-info-subtle text-info',
                error:      'badge bg-danger-subtle text-danger',
            }[status] ?? 'badge bg-secondary-subtle text-secondary'
        },

        statusIcon(status) {
            return {
                indexed:    'ri-checkbox-circle-fill',
                processing: 'ri-loader-4-line',
                pending:    'ri-time-line',
                error:      'ri-error-warning-fill',
            }[status] ?? 'ri-question-line'
        },
    },
}
</script>

<template>
    <Layout>
        <PageHeader title="Documentos Académicos" pageTitle="Gestión" />

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="doc-stat doc-stat--total">
                    <div class="doc-stat__icon"><i class="ri-file-list-3-line"></i></div>
                    <div>
                        <p class="doc-stat__label">Total</p>
                        <h4 class="doc-stat__value">{{ stats.total }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="doc-stat doc-stat--indexed">
                    <div class="doc-stat__icon"><i class="ri-checkbox-circle-line"></i></div>
                    <div>
                        <p class="doc-stat__label">Indexados</p>
                        <h4 class="doc-stat__value">{{ stats.indexed }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="doc-stat doc-stat--pending">
                    <div class="doc-stat__icon"><i class="ri-time-line"></i></div>
                    <div>
                        <p class="doc-stat__label">Pendientes</p>
                        <h4 class="doc-stat__value">{{ stats.pending }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="doc-stat doc-stat--error">
                    <div class="doc-stat__icon"><i class="ri-error-warning-line"></i></div>
                    <div>
                        <p class="doc-stat__label">Con error</p>
                        <h4 class="doc-stat__value">{{ stats.error }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <div class="row g-3 align-items-center">
                            <div class="col-sm">
                                <h5 class="card-title mb-0">Documentos indexados en ChromaDB</h5>
                            </div>
                            <div class="col-sm-auto">
                                <button class="btn btn-primary" @click="openModal">
                                    <i class="ri-upload-cloud-2-line me-1"></i> Subir PDF
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card-body border-bottom-dashed">
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="form-control"
                            placeholder="Buscar por título, archivo o estado..."
                        />
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="doc-table__head">
                                    <tr>
                                        <th>Título</th>
                                        <th class="d-none d-md-table-cell">Archivo</th>
                                        <th class="d-none d-md-table-cell">Tamaño</th>
                                        <th>Estado</th>
                                        <th class="d-none d-lg-table-cell">Fecha</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="filtered.length === 0">
                                        <td colspan="6" class="text-center py-5">
                                            <div class="doc-empty">
                                                <i class="ri-file-search-line doc-empty__icon"></i>
                                                <p class="doc-empty__text">
                                                    {{ items.length === 0
                                                        ? 'Aún no hay documentos. Sube el primero para activar el asistente.'
                                                        : 'No hay resultados para tu búsqueda.' }}
                                                </p>
                                                <button v-if="items.length === 0" class="btn btn-primary btn-sm" @click="openModal">
                                                    <i class="ri-upload-cloud-2-line me-1"></i> Subir primer documento
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-for="doc in filtered" :key="doc.uuid">
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="doc-file-icon">
                                                    <i class="ri-file-pdf-2-fill"></i>
                                                </div>
                                                <span class="fw-medium">{{ doc.title }}</span>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell text-muted small">{{ doc.filename }}</td>
                                        <td class="d-none d-md-table-cell text-muted small">{{ formatSize(doc.size_bytes) }}</td>
                                        <td>
                                            <span :class="statusClass(doc.status)">
                                                <i :class="[statusIcon(doc.status), 'me-1']"></i>
                                                {{ statusLabel(doc.status) }}
                                            </span>
                                            <p v-if="doc.error_message" class="mb-0 mt-1 text-danger" style="font-size:0.72rem;">
                                                {{ doc.error_message }}
                                            </p>
                                        </td>
                                        <td class="d-none d-lg-table-cell text-muted small">{{ formatDate(doc.created_at) }}</td>
                                        <td class="text-center">
                                            <button
                                                class="btn btn-ghost-danger btn-sm"
                                                title="Eliminar"
                                                @click="deleteDocument(doc.uuid, doc.title)"
                                            >
                                                <i class="ri-delete-bin-5-line fs-15"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de subida -->
        <Teleport to="body">
            <div v-if="showModal" class="doc-modal-overlay" @click.self="closeModal">
                <div class="doc-modal">
                    <div class="doc-modal__header">
                        <h5 class="mb-0 text-cream">
                            <i class="ri-upload-cloud-2-line me-2"></i>Subir documento PDF
                        </h5>
                        <button class="doc-modal__close" @click="closeModal" :disabled="uploading">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>

                    <div class="doc-modal__body">
                        <div class="mb-4">
                            <label class="form-label fw-medium">
                                Título del documento <span class="text-danger">*</span>
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                class="form-control"
                                :class="{ 'is-invalid': formErrors.title }"
                                placeholder="Ej: Reglamento Estudiantil 2024"
                                :disabled="uploading"
                            />
                            <div v-if="formErrors.title" class="invalid-feedback">{{ formErrors.title }}</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-medium">
                                Archivo PDF <span class="text-danger">*</span>
                            </label>
                            <div
                                class="doc-dropzone"
                                :class="{ 'doc-dropzone--error': formErrors.file, 'doc-dropzone--filled': form.fileName }"
                                @click="$refs.fileInput.click()"
                            >
                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept=".pdf"
                                    class="d-none"
                                    :disabled="uploading"
                                    @change="onFileChange"
                                />
                                <i :class="form.fileName ? 'ri-file-pdf-2-fill text-danger' : 'ri-upload-cloud-2-line'" class="doc-dropzone__icon"></i>
                                <p class="doc-dropzone__text">{{ form.fileName || 'Haz clic para seleccionar un PDF' }}</p>
                                <p class="doc-dropzone__hint">Máximo 20 MB</p>
                            </div>
                            <div v-if="formErrors.file" class="text-danger mt-1" style="font-size:0.85rem;">{{ formErrors.file }}</div>
                        </div>

                        <div class="doc-modal__info">
                            <i class="ri-information-line me-2"></i>
                            El documento se procesa en segundo plano. El estado cambiará de
                            <strong>Pendiente</strong> a <strong>Indexado</strong> cuando FastAPI termine.
                        </div>
                    </div>

                    <div class="doc-modal__footer">
                        <button class="btn btn-light" @click="closeModal" :disabled="uploading">Cancelar</button>
                        <button class="btn btn-primary" @click="uploadDocument" :disabled="uploading">
                            <span v-if="uploading">
                                <span class="spinner-border spinner-border-sm me-1"></span>Subiendo...
                            </span>
                            <span v-else>
                                <i class="ri-upload-cloud-2-line me-1"></i>Subir e indexar
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </Layout>
</template>

<style scoped>
/* ── Stats ───────────────────────────────────────────────── */
.doc-stat {
    background: var(--vz-card-bg);
    border-radius: 0.875rem;
    padding: 1.1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    border: 1px solid var(--vz-border-color);
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.doc-stat__icon {
    width: 46px; height: 46px;
    border-radius: 0.75rem;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0;
}
.doc-stat__label { font-size: 0.78rem; color: var(--vz-secondary-color); margin: 0; }
.doc-stat__value { font-size: 1.5rem; font-weight: 700; margin: 0; line-height: 1.1; }
.doc-stat--total   .doc-stat__icon { background: rgba(16,44,38,.10); color: #102C26; }
.doc-stat--indexed .doc-stat__icon { background: rgba(81,181,102,.12); color: #51B566; }
.doc-stat--pending .doc-stat__icon { background: rgba(255,190,11,.12); color: #ffbe0b; }
.doc-stat--error   .doc-stat__icon { background: rgba(240,101,72,.12); color: #f06548; }

/* ── Tabla ───────────────────────────────────────────────── */
.doc-table__head th {
    background: rgba(16,44,38,.04);
    font-size: 0.78rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.04em;
    color: var(--vz-secondary-color);
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--vz-border-color);
}
.doc-file-icon {
    width: 34px; height: 34px;
    border-radius: 0.5rem;
    background: rgba(240,101,72,.10); color: #f06548;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
}

/* ── Empty ───────────────────────────────────────────────── */
.doc-empty { display: flex; flex-direction: column; align-items: center; gap: 0.75rem; padding: 1.5rem; }
.doc-empty__icon { font-size: 3rem; color: var(--vz-secondary-color); opacity: 0.5; }
.doc-empty__text { color: var(--vz-secondary-color); margin: 0; }

/* ── Modal ───────────────────────────────────────────────── */
.doc-modal-overlay {
    position: fixed; inset: 0;
    background: rgba(16,44,38,.45);
    backdrop-filter: blur(3px);
    z-index: 1050;
    display: flex; align-items: center; justify-content: center;
    padding: 1rem;
}
.doc-modal {
    background: var(--vz-card-bg);
    border-radius: 1rem;
    width: 100%; max-width: 520px;
    box-shadow: 0 20px 60px rgba(16,44,38,.25);
    overflow: hidden;
    animation: modal-in 0.2s ease;
}
.doc-modal__header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1.125rem 1.5rem;
    background: #102C26; color: #F7E7CE;
}
.text-cream { color: #F7E7CE; }
.doc-modal__close {
    background: none; border: none;
    color: rgba(247,231,206,.7); font-size: 1.25rem;
    cursor: pointer; padding: 0; line-height: 1;
    transition: color 0.15s;
}
.doc-modal__close:hover { color: #F7E7CE; }
.doc-modal__body { padding: 1.5rem; }
.doc-modal__footer {
    display: flex; justify-content: flex-end; gap: 0.625rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--vz-border-color);
}

/* ── Dropzone ────────────────────────────────────────────── */
.doc-dropzone {
    border: 2px dashed var(--vz-border-color);
    border-radius: 0.75rem; padding: 2rem;
    text-align: center; cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
}
.doc-dropzone:hover           { border-color: #102C26; background: rgba(16,44,38,.04); }
.doc-dropzone--filled         { border-color: #51B566; background: rgba(81,181,102,.05); }
.doc-dropzone--error          { border-color: #f06548; }
.doc-dropzone__icon           { font-size: 2.25rem; color: var(--vz-secondary-color); display: block; margin-bottom: 0.5rem; }
.doc-dropzone--filled .doc-dropzone__icon { color: #f06548; }
.doc-dropzone__text           { font-weight: 500; margin: 0; font-size: 0.9rem; word-break: break-all; }
.doc-dropzone__hint           { font-size: 0.75rem; color: var(--vz-secondary-color); margin: 0.25rem 0 0; }

/* ── Info box ────────────────────────────────────────────── */
.doc-modal__info {
    background: rgba(16,44,38,.07);
    border-left: 3px solid #102C26;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    font-size: 0.82rem;
    color: var(--vz-body-color);
}

@keyframes modal-in {
    from { opacity: 0; transform: translateY(-12px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
</style>
