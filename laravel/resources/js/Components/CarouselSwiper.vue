<script>
import { Navigation, Thumbs } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/vue";

import "swiper/css";
import "swiper/css/navigation";

const toNumberOr = (value, fallback) => {
  const n = Number(value);
  return Number.isFinite(n) ? n : fallback;
};

const normalizeImages = (items) => {
  const list = Array.isArray(items) ? items : [];
  const normalized = list
    .filter((item) => item && item.url)
    .map((item, index) => {
      const fallbackId = item.id ?? item.uuid ?? `${item.name || "img"}-${index}`;
      return {
        id: fallbackId,
        name: item.name ?? `Imagen ${index + 1}`,
        url: item.url,
        extension: item.extension ?? "",
        size: item.size ?? null,
        pending: Boolean(item.pending),
        isFavorite: Boolean(item.isFavorite),
        position: toNumberOr(item.position, index + 1),
      };
    });

  if (!normalized.length) {
    return [];
  }

  const hasFavorite = normalized.some((image) => image.isFavorite);
  if (!hasFavorite) {
    normalized[0].isFavorite = true;
  } else {
    let firstFound = false;
    normalized.forEach((image) => {
      if (image.isFavorite && !firstFound) {
        firstFound = true;
        return;
      }
      if (image.isFavorite && firstFound) {
        image.isFavorite = false;
      }
    });
  }

  return normalized;
};

const sortImages = (items) => {
  return [...items].sort((a, b) => {
    if (a.isFavorite !== b.isFavorite) {
      return a.isFavorite ? -1 : 1;
    }
    if (a.position !== b.position) {
      return a.position - b.position;
    }
    return String(a.id).localeCompare(String(b.id));
  });
};

export default {
  name: "CarouselSwiper",
  components: {
    Swiper,
    SwiperSlide,
  },
  props: {
    modelValue: {
      type: Array,
      default: () => [],
    },
    maxFiles: {
      type: Number,
      default: null,
    },
    showArrows: {
      type: Boolean,
      default: true,
    },
    allowFavorite: {
      type: Boolean,
      default: true,
    },
  },
  emits: ["update:modelValue", "remove", "favorite-change"],
  data() {
    return {
      thumbsSwiper: null,
      Navigation,
      Thumbs,
    };
  },
  computed: {
    normalizedImages() {
      return normalizeImages(this.modelValue);
    },
    orderedImages() {
      return sortImages(this.normalizedImages);
    },
    visibleImages() {
      if (this.maxFiles == null) {
        return this.orderedImages;
      }
      const limit = Math.max(0, Number(this.maxFiles));
      return this.orderedImages.slice(0, limit);
    },
    hasImages() {
      return this.visibleImages.length > 0;
    },
    shouldShowArrows() {
      return this.showArrows && this.visibleImages.length > 1;
    },
  },
  watch: {
    orderedImages: {
      immediate: true,
      deep: true,
      handler(nextList) {
        const current = JSON.stringify(this.modelValue ?? []);
        const normalized = JSON.stringify(nextList);
        if (current !== normalized) {
          this.$emit("update:modelValue", nextList);
        }
      },
    },
  },
  methods: {
    setThumbsSwiper(swiper) {
      this.thumbsSwiper = swiper;
    },
    markAsFavorite(targetId) {
      if (!this.allowFavorite) {
        return;
      }
      const next = this.orderedImages.map((image) => ({
        ...image,
        isFavorite: image.id === targetId,
      }));
      this.$emit("update:modelValue", next);
      this.$emit("favorite-change", targetId);
    },
    removeImage(targetId) {
      const next = this.orderedImages.filter((image) => image.id !== targetId);
      const normalized = normalizeImages(next);
      this.$emit("update:modelValue", sortImages(normalized));
      this.$emit("remove", targetId);
    },
  },
};
</script>

<template>
  <div class="product-img-slider sticky-side-div">
    <div v-if="hasImages">
      <swiper
        :modules="[Navigation, Thumbs]"
        class="product-thumbnail-slider p-2 rounded bg-light"
        :navigation="shouldShowArrows ? { nextEl: '.carousel-swiper-next', prevEl: '.carousel-swiper-prev' } : false"
        :thumbs="{ swiper: thumbsSwiper }"
      >
        <swiper-slide v-for="image in visibleImages" :key="`main-${image.id}`">
          <img :src="image.url" :alt="image.name" class="img-fluid d-block" />
        </swiper-slide>
      </swiper>

      <div v-if="shouldShowArrows" class="carousel-swiper-next swiper-button-next bg-white shadow"></div>
      <div v-if="shouldShowArrows" class="carousel-swiper-prev swiper-button-prev bg-white shadow"></div>

      <swiper
        :modules="[Thumbs]"
        class="product-nav-slider mt-2"
        :loop="false"
        :space-between="10"
        :slides-per-view="Math.min(4, Math.max(1, visibleImages.length))"
        :free-mode="true"
        watch-slides-progress
        @swiper="setThumbsSwiper"
      >
        <swiper-slide v-for="image in visibleImages" :key="`thumb-${image.id}`">
          <div class="nav-slide-item">
            <div class="nav-slide-img-wrapper">
              <img :src="image.url" :alt="image.name" class="img-fluid d-block" />

              <div class="carousel-swiper-actions">
                <!-- Favorito -->
                <button
                  v-if="allowFavorite"
                  type="button"
                  class="btn btn-ghost-primary btn-icon btn-sm favourite-btn"
                  :class="{ active: image.isFavorite }"
                  @click.stop="markAsFavorite(image.id)"
                >
                  <i class="ri-star-fill fs-13 align-bottom"></i>
                </button>

                <!-- Eliminar -->
                <button
                  type="button"
                  class="btn btn-ghost-danger btn-icon btn-sm"
                  @click.stop="removeImage(image.id)"
                >
                  <i class="ri-delete-bin-fill fs-13 align-bottom"></i>
                </button>
              </div>
            </div>
          </div>
        </swiper-slide>
      </swiper>
    </div>

    <div v-else class="carousel-swiper-empty border border-dashed rounded p-4 text-center text-muted">
      No hay imágenes para previsualizar.
    </div>
  </div>
</template>

<style scoped>
.carousel-swiper-empty {
  background-color: rgba(var(--bs-primary-rgb), 0.03);
}

.product-thumbnail-slider img {
  width: 410px;
  height: 400px;
  object-fit: cover;
  object-position: center;
  border-radius: 0.5rem;
}

.product-nav-slider .nav-slide-item {
  width: 100%;
  
}

/* Wrapper con posición relativa para anclar los botones encima */
.product-nav-slider .nav-slide-img-wrapper {
  position: relative;
  width: 100%;
  height: 170px;
  overflow: hidden;
  border-radius: 0.35rem;
}

.product-nav-slider .nav-slide-img-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
}

/* Botones superpuestos en la esquina superior derecha */
.carousel-swiper-actions {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  display: flex;
  justify-content: flex-end;
  text-align: right;
  gap: 0.25rem;
  padding: 0.25rem;
  z-index: 2;
  opacity: 0;
  transition: opacity 0.2s ease;
}

/* Mostrar botones al hacer hover sobre la miniatura */
.nav-slide-img-wrapper:hover .carousel-swiper-actions {
  opacity: 1;
}

/* Si la imagen es favorita, mantener visible el botón de estrella siempre */
.nav-slide-img-wrapper .favourite-btn.active {
  opacity: 1;
}

.carousel-swiper-actions .btn {
  padding: 0.2rem;
  line-height: 1;
  backdrop-filter: blur(4px);
  background-color: rgba(255, 255, 255, 0.75);
  border: none;
}

.carousel-swiper-actions .btn:hover {
  background-color: rgba(255, 255, 255, 0.95);
}

.carousel-swiper-actions .favourite-btn.active i {
  color: var(--bs-warning);
}
</style>