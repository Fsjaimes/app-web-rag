
<template>
  <div
    @dragenter.prevent="setActive(true)"
    @dragleave.prevent="setActive(false)"
    @dragover.prevent
    @drop.prevent="handleDrop"
    :class="{ 'active-dropzone': active }"
    class="dropzone position-relative d-flex align-items-center justify-content-center border border-dashed border-primary rounded"
  >
    <label for="dropzoneFile" class="stretched-link p-0 m-0 cursor-pointer" style="font-weight: 400;">
      <p class="fs-12 text-center mb-0 text-primary">
        <i class="ri-folder-open-fill me-1"></i> Suelte los archivos aquí o haga clic para cargarlos.
      </p>
    </label>
    <input
      type="file"
      id="dropzoneFile"
      multiple
      class="d-none"
      :accept="inputAccept || undefined"
      @change="handleFileChange"
    />
  </div>
</template>

<script>
  import { ref, computed } from "vue";
  import { useAlert } from "@/Composables/useSweetAlert.js";

  /** @type {Record<string, string[]>} */
  const EXTENSIONS_BY_CATEGORY = {
    images: [
      "jpg",
      "jpeg",
      "png",
      "gif",
      "webp",
      "svg",
      "bmp",
      "ico",
      "heic",
      "heif",
      "avif",
      "tif",
      "tiff",
    ],
    documents: ["pdf", "doc", "docx", "dot", "dotx", "rtf", "odt", "txt", "md", "msg"],
    spreadsheets: ["xls", "xlsx", "xlsm", "xlsb", "csv", "ods"],
    presentations: ["ppt", "pptx", "pptm", "potx", "odp"],
    video: ["mp4", "webm", "mov", "avi", "mkv", "mpeg", "mpg", "m4v", "wmv"],
    audio: ["mp3", "wav", "ogg", "m4a", "flac", "aac", "wma"],
    archives: ["zip", "rar", "7z", "tar", "gz", "bz2"],
  };

  /** Alias de prop → categoría canónica */
  const FILE_TYPE_ALIASES = {
    images: "images",
    image: "images",
    img: "images",
    documents: "documents",
    docs: "documents",
    spreadsheets: "spreadsheets",
    sheets: "spreadsheets",
    presentations: "presentations",
    slides: "presentations",
    office: "office",
    video: "video",
    videos: "video",
    audio: "audio",
    archives: "archives",
    zip: "archives",
  };

  const CATEGORY_LABELS = {
    images: "imágenes",
    documents: "documentos de texto o PDF",
    spreadsheets: "hojas de cálculo",
    presentations: "presentaciones",
    office: "documentos de oficina (Word, Excel, PowerPoint, PDF, etc.)",
    video: "vídeo",
    audio: "audio",
    archives: "archivos comprimidos",
  };

  function normalizeFileTypeKey(raw) {
    if (raw == null || String(raw).trim() === "") {
      return null;
    }
    const k = String(raw).toLowerCase().trim();
    return FILE_TYPE_ALIASES[k] ?? null;
  }

  function getAllowedExtensions(canonical) {
    if (!canonical) {
      return null;
    }
    if (canonical === "office") {
      return [
        ...EXTENSIONS_BY_CATEGORY.documents,
        ...EXTENSIONS_BY_CATEGORY.spreadsheets,
        ...EXTENSIONS_BY_CATEGORY.presentations,
      ];
    }
    return EXTENSIONS_BY_CATEGORY[canonical] ?? null;
  }

  function extensionFromFileName(name) {
    if (!name || typeof name !== "string") {
      return "";
    }
    const i = name.lastIndexOf(".");
    if (i < 0 || i === name.length - 1) {
      return "";
    }
    return name.slice(i + 1).toLowerCase();
  }

  function buildAcceptAttribute(canonical) {
    if (!canonical) {
      return "";
    }
    if (canonical === "images") {
      return "image/*";
    }
    if (canonical === "video") {
      return "video/*";
    }
    if (canonical === "audio") {
      return "audio/*";
    }
    const exts = getAllowedExtensions(canonical);
    if (!exts?.length) {
      return "";
    }
    return exts.map((e) => `.${e}`).join(",");
  }

  export default {
    name: "Dropzone",
    emits: ["file-selected"],
    props: {
      /**
       * Categoría de archivos permitidos. Valores (también alias): images|img,
       * documents|docs, spreadsheets|sheets, presentations|slides, office,
       * video|videos, audio, archives|zip. Vacío o null = cualquier archivo.
       */
      fileType: {
        type: String,
        default: null,
        validator(value) {
          if (value == null || String(value).trim() === "") {
            return true;
          }
          return normalizeFileTypeKey(value) !== null;
        },
      },
    },

    setup(props, { emit }) {
      const { showWarning } = useAlert();
      const active = ref(false);

      const canonicalType = computed(() => normalizeFileTypeKey(props.fileType));

      const inputAccept = computed(() => buildAcceptAttribute(canonicalType.value));

      const setActive = (value) => {
        active.value = value;
      };

      const emitAcceptedFiles = (files) => {
        Array.from(files).forEach((file) => {
          emit("file-selected", file);
        });
      };

      const processFiles = (fileList) => {
        const category = canonicalType.value;
        if (!category) {
          emitAcceptedFiles(fileList);
          return;
        }

        const allowedList = getAllowedExtensions(category);
        const allowed = new Set(allowedList ?? []);
        const accepted = [];
        const rejectedNames = [];

        for (const file of Array.from(fileList || [])) {
          const ext = extensionFromFileName(file.name);
          if (ext && allowed.has(ext)) {
            accepted.push(file);
          } else {
            rejectedNames.push(file.name || "archivo sin nombre");
          }
        }

        emitAcceptedFiles(accepted);

        if (rejectedNames.length) {
          const label = CATEGORY_LABELS[category] ?? category;
          const title = "Tipo de archivo no permitido";
          let text;
          if (rejectedNames.length === 1) {
            text = `«${rejectedNames[0]}» no es válido. Solo se admiten ${label}.`;
          } else {
            text = `Estos archivos no son válidos: ${rejectedNames.join(", ")}. Solo se admiten ${label}.`;
          }
          showWarning(title, text);
        }
      };

      const handleFileChange = (event) => {
        processFiles(event.target.files);
        event.target.value = "";
      };

      const handleDrop = (event) => {
        setActive(false);
        processFiles(event.dataTransfer?.files);
      };

      return {
        active,
        setActive,
        handleFileChange,
        handleDrop,
        inputAccept,
      };
    },
  };
</script>

<style scoped>
  .dropzone {
    border-color: var(--vz-primary, var(--bs-primary)) !important;
    background-color: rgba(var(--vz-primary-rgb), 0.05) !important;
    height: 80px !important;
    min-height: 80px !important;
  }

  .dropzone.active-dropzone {
    background-color: rgba(var(--vz-primary-rgb), 0.12) !important;
  }
</style>
