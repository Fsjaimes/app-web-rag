<script>
    import FileViewerModal from '@/Components/FileViewerModal.vue';
    import { Fancybox } from "@fancyapps/ui/dist/fancybox/";
    import "@fancyapps/ui/dist/fancybox/fancybox.css";
    export default {
        components: {
            FileViewerModal
        },
        props: {
            files: {
                type: Array,
                required: true,
            },
            limit: {
                type: Number,
                default: null
            },
            actions: {
                type: Object,
                default: () => ({
                    view: true,
                    delete: true,
                    download: true,
                    favorite: false,
                })
            },
            /**
             * stack: lista vertical, filas a ancho completo (compatible con Sat / usos existentes).
             * wrap: fila horizontal con salto de línea, tarjetas al ancho del contenido.
             */
            layout: {
                type: String,
                default: 'stack',
                validator: (value) => ['stack', 'wrap'].includes(value),
            },
            imageFocus: {
                type: Boolean,
                default: false,
            },
        },
        emits: ['deleteFile', 'favoriteFile'],
        data() {
            return {
                showAllAttachments: false,
                icons: [
                    { extension: 'pdf', icon: 'ri-file-pdf-fill' },
                    { extension: 'mp4', icon: 'ri-video-line' },
                    { extension: 'xlxs', icon: 'ri-file-excel-fill' },
                    { extension: 'xls', icon: 'ri-file-excel-fill' },
                    { extension: 'xlsm', icon: 'ri-file-excel-fill' },
                    { extension: 'docx', icon: 'ri-folder-fill' },
                    { extension: 'png', icon: 'ri-image-2-fill' },
                    { extension: 'jpg', icon: 'ri-image-2-fill' },
                    { extension: 'jpeg', icon: 'ri-image-2-fill' },
                    { extension: 'zip', icon: 'ri-folder-zip-line' },
                    { extension: 'ppt', icon: 'ri-file-ppt-fill' },
                    { extension: 'pptx', icon: 'ri-file-ppt-fill' },
                    { extension: 'doc', icon: 'ri-file-word-fill' },
                    { extension: 'xlsx', icon: 'ri-file-excel-fill' },
                    { extension: 'txt', icon: 'ri-file-text-fill' },
                    { extension: 'dwg', icon: 'ri-file-download-line' },
                    { extension: 'msg', icon: 'ri-mail-send-line' },
                    { extension: 'default', icon: 'ri-file-unknow-fill' }  // Para casos por defecto
                ],
                fileUrl: null,
                fileExtension: null,
                showFileViewer: false,
            }
        },
        methods: {
            getIcon(extension) {
                return this.icons.find(icon => icon.extension == extension)?.icon || 'ri-file-unknow-fill';
            },
            isImageExtension(extension) {
                const ext = String(extension || '').toLowerCase();
                return ["jpg", "jpeg", "png", "webp", "gif"].includes(ext);
            },
            downloadFile(file) {
                if (!file?.url) {
                    return;
                }
                const link = document.createElement('a');
                link.href = file.url;
                link.target = '_blank';
                link.download = file.name || 'adjunto';
                link.click();
            },
            deleteFile(uuid) {
                this.$emit('deleteFile', uuid);
            },
            markFavorite(file) {
                if (!this.actions.favorite) {
                    return;
                }
                const id = file?.uuid ?? file?.id;
                if (id == null || id === '') {
                    return;
                }
                this.$emit('favoriteFile', id);
            },
            isImageFile(file) {
                return Boolean(file?.url) && this.isImageExtension(file?.extension);
            },
            viewFile(file) {
                const ext = String(file?.extension || '').toLowerCase();
                if (this.isImageExtension(ext)) {
                    Fancybox.show([
                        {
                        src: file.url,
                        type: "image"
                        }
                    ]);
                    return;
                }
                if (file?.pending) {
                    this.downloadFile(file);
                    return;
                }
                {
                    this.fileUrl = file.url;
                    this.fileExtension = file.extension;
                    this.showFileViewer = true;
                }
            },
        },
    }
</script>

<template>
    <div>
        <FileViewerModal v-if="showFileViewer" :url="fileUrl" :extension="fileExtension" @close="showFileViewer = false" />
        <div class="file-list-root">
            <div
                class="file-list-items"
                :class="layout === 'wrap' ? 'd-flex flex-wrap align-items-start gap-2' : 'vstack gap-2'"
            >
            <template v-for="file in files.slice(0, (showAllAttachments || !limit) ? files.length : limit)" :key="file.uuid ?? file.id">
                <div
                    v-if="isImageFile(file)"
                    class="file-list-item border rounded border-dashed p-2"
                    :class="{
                        'file-list-item--wrap': layout === 'wrap',
                        'w-100': layout === 'stack',
                        'file-list-item--image-focus': imageFocus,
                    }"
                >
                    <div
                        class="file-list-image-row"
                        :class="{
                            'file-list-image-row--three-cols': !imageFocus,
                            'file-list-image-row--focus': imageFocus,
                        }"
                    >
                        <div
                            class="file-list-image-col"
                            :class="{
                                'cursor-pointer': actions.view,
                            }"
                            @click="actions.view ? viewFile(file) : null"
                        >
                            <div
                                class="file-list-image-thumb bg-light rounded overflow-hidden position-relative"
                                :class="{ 'file-list-image-thumb--focus': imageFocus }"
                            >
                                <img
                                    :src="file.url"
                                    :alt="file.name"
                                    class="file-list-image-thumb-img"
                                    loading="lazy"
                                />

                                <!-- BOTÓN Estrella para favorito -->
                                <button
                                    v-if="actions.favorite && imageFocus"
                                    type="button"
                                    class="btn btn-ghost-primary btn-icon btn-sm favourite-btn file-list-favourite-btn--overlay"
                                    :class="{ active: file.isFavorite }"
                                    title="Imagen principal"
                                    @click.stop.prevent="markFavorite(file)"
                                >
                                    <i class="ri-star-fill fs-13 align-bottom"></i>
                                </button>
                                <a
                                    v-if="actions.delete && imageFocus"
                                    href="javascript:void(0);"
                                    class="file-list-close-btn file-list-close-btn--overlay d-flex align-items-center"
                                    title="Eliminar"
                                    @click.stop.prevent="deleteFile(file.uuid)"
                                >
                                    <i class="fs-14 fw-bold ri-close-line text-gray"></i>
                                </a>
                            </div>
                        </div>
                        <div
                            v-if="!imageFocus"
                            class="file-list-name-col overflow-hidden min-w-0"
                            :class="{ 'cursor-pointer': actions.view }"
                            @click="actions.view ? viewFile(file) : null"
                        >
                            <h5 class="fs-13 mb-1"><span class="text-body text-truncate d-block">{{ file.name }}</span></h5>
                            <div class="text-muted fs-12">{{ file.size }}MB</div>
                        </div>
                        <div
                            class="file-list-actions-col"
                            @click.stop
                        >
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <!-- Botón de favorito con una estrella -->
                                <!-- <button
                                    v-if="actions.favorite && !imageFocus"
                                    type="button"
                                    class="btn btn-ghost-primary btn-sm fs-16 favourite-btn"
                                    :class="{ active: file.isFavorite }"
                                    title="Destacar"
                                    @click.stop.prevent="markFavorite(file)"
                                >
                                    <i class="ri-star-fill fs-13 align-bottom"></i>
                                </button> -->
                                <button
                                    v-if="actions.favorite && !imageFocus"
                                    type="button"
                                    class="btn btn-ghost-primary btn-sm fs-16 favourite-btn"
                                    :class="{ active: file.isFavorite }"
                                    title="Destacar"
                                    @click.stop.prevent="markFavorite(file)"
                                >
                                    <span v-if="file.isFavorite" class="icon-on">Principal</span>
                                    <span v-else class="icon-off text-muted">Destacar</span>
                                </button>
                                <a
                                    v-if="actions.delete && !imageFocus"
                                    type="button"
                                    class="btn btn-icon text-danger btn-sm fs-16"
                                    title="Eliminar"
                                    @click.stop.prevent="deleteFile(file.uuid)"
                                ><i class="ri-delete-bin-fill"></i></a>
                                <a
                                    v-if="actions.download && file.url && !imageFocus"
                                    type="button"
                                    class="btn btn-icon text-muted btn-sm fs-16"
                                    title="Descargar"
                                    :href="file.url"
                                    target="_blank"
                                    download
                                    @click.stop
                                ><i class="ri-download-2-fill"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    v-else
                    class="file-list-item border rounded border-dashed p-2"
                    :class="{ 'file-list-item--wrap': layout === 'wrap', 'w-100': layout === 'stack' }"
                >
                    <div class="d-flex align-items-center" :class="{ 'flex-nowrap': layout === 'wrap' }">
                        <div class="flex-shrink-0 me-3" @click="actions.view ? viewFile(file) : null" style="cursor: pointer;">
                            <div class="avatar-sm">
                                <div class="avatar-title bg-light text-primary rounded fs-24">
                                    <i :class="getIcon(file.extension)"></i>
                                </div>
                            </div>
                        </div>
                        <div
                            class="overflow-hidden min-w-0"
                            :class="layout === 'wrap' ? 'file-list-item-text' : 'flex-grow-1'"
                            @click="actions.view ? viewFile(file) : null"
                            style="cursor: pointer;"
                        >
                            <h5 class="fs-13 mb-1"><a class="text-body text-truncate d-block">{{ file.name }}</a></h5>
                            <div>{{ file.size }}MB</div>
                        </div>
                        <div class="flex-shrink-0 ms-2">
                            <div class="d-flex gap-1">
                                <a v-if="actions.download" type="button" class="btn btn-icon text-muted btn-sm fs-16" title="Descargar" :href="file.url" target="_blank" download><i class="ri-download-2-fill"></i></a>
                                <a v-if="actions.delete" type="button" class="btn btn-icon text-danger btn-sm fs-16" title="Eliminar" @click="deleteFile(file.uuid)"><i class="ri-delete-bin-fill"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            </div>
            <div
                class="mt-2 text-center"
                :class="{ 'w-100 pt-1 border-top border-top-dashed': layout === 'wrap' }"
                v-show="files.length > limit && limit != null"
            >
                <a class="text-primary text-decoration-underline cursor-pointer" @click="showAllAttachments = !showAllAttachments">Ver {{ showAllAttachments ? 'menos' : 'más' }}</a>
            </div>
        </div>
    </div>
</template>

<style scoped>
    .cursor-pointer {
        cursor: pointer;
    }

    .file-list-root {
        width: 100%;
    }

    .file-list-items {
        width: 100%;
    }

    .file-list-item {
        max-width: 100%;
        min-width: 0;
    }

    .file-list-item--wrap {
        flex: 0 0 auto;
        background-color: rgba(var(--bs-primary-rgb), 0.03);
    }

    .file-list-item-text {
        flex: 0 1 auto;
        min-width: 0;
        max-width: min(20rem, 100%);
    }

    .file-list-image-thumb {
        width: 64px;
        height: 64px;
        flex-shrink: 0;
    }

    .file-list-image-row--three-cols {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        column-gap: 0.75rem;
    }

    .file-list-image-col {
        min-width: 64px;
    }

    .file-list-name-col {
        min-width: 0;
    }

    .file-list-actions-col {
        min-width: 0;
    }

    .file-list-favourite-btn--overlay {
        position: absolute;
        bottom: 6px;
        left: 6px;
        z-index: 2;
        padding: 0.2rem;
        line-height: 1;
        backdrop-filter: blur(4px);
        background-color: rgba(255, 255, 255, 0.75);
        border: none;
    }

    .file-list-favourite-btn--overlay:hover {
        background-color: rgba(255, 255, 255, 0.95);
    }

    .favourite-btn.active i {
        color: var(--bs-warning);
    }

    .file-list-image-thumb--focus {
        width: 106px;
        height: 106px;
    }

    .file-list-item--image-focus {
        position: relative;
        background-color: rgba(var(--bs-primary-rgb), 0.02);
        padding: 12px !important;
    }

    .file-list-image-row {
        position: static;
    }

    .file-list-close-btn--overlay {
        position: absolute;
        top: 6px;
        right: 6px;
        z-index: 2;

        border: none;
        color: #ffffff;
        font-size: 1.2rem;
        line-height: 1;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        text-decoration: none;

        display: flex;
        align-items: center;
        justify-content: center;
    }

    .file-list-image-row--focus {
        justify-content: center;
        padding-top: 20px;
    }

    .file-list-image-row--focus > .flex-shrink-0 {
        /* width: 100%; */
        display: flex;
        /* justify-content: center; */
    }

    .file-list-image-thumb-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .text-gray {
        color: #6c757d;
    }
</style>