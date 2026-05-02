<template>
    <div class="modal fade show" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen" role="document">
            <div class="modal-content">
                <div class="modal-header bg-light pb-3">
                    <h5 class="modal-title">Detalles del documento</h5>
                    <button type="button" class="btn btn-sm btn-close" aria-label="Close" @click="$emit('close')"></button>
                </div>
                <div class="modal-body">
                    <iframe id="view-file" class="w-100 h-100" src=""></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" @click="$emit('close')">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</template>
  
<script>
    import { useAlert } from '@/Composables/useSweetAlert.js';
    const { showWarning } = useAlert();
  export default {
    name: "FileViewerModal",
    components: {
        showWarning
    },
    props: {
      url: {
        type: String,
        required: true,
      },
      extension: {
        type: String,
        required: true,
      },
    },
    methods: {
        async showFile(url, extension) {
            if (extension == "dwg") {
                await showWarning(
                    '¡Alerta!',
                    `Por favor descargue el archivo para visualizarlo.`,
                    3500
                );
                this.$emit('close');
                return;
            }
            let viewer = "";
            if (
                extension != "pdf" &&
                extension != "png" &&
                extension != "jpg" &&
                extension != "PDF" &&
                extension != "jpeg"
            ) {
                viewer = "https://view.officeapps.live.com/op/view.aspx?src=";
            } else {
                viewer = "";
            }
            $("#view-file").attr("src", viewer + url);
        },
    },
    mounted() {
        this.showFile(this.url, this.extension);
    },
  };
</script>
  
<style scoped>
</style>