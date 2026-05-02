<script>
import { CountTo } from "vue3-count-to";

// Helper para parsear px, rem, em, etc, a número en px
function parsePixelSize(str, defaultValue = 100) {
    if (!str) return defaultValue;
    if (typeof str === "number") return str;
    if (typeof str === "string") {
        // '120px', '8rem' etc.
        if (str.endsWith('px')) return parseFloat(str);
        // fallback: intenta parsear sólo números
        let n = parseFloat(str);
        return isNaN(n) ? defaultValue : n;
    }
    return defaultValue;
}

// Mapeo posición a clases de utilidad Bootstrap/propio para colocar el `text`
const TEXT_POSITIONS = {
    "top-right":   { top: "12px", right: "20px", bottom: "auto", left: "auto", transform: "none" },
    "top-left":    { top: "12px", left: "20px", bottom: "auto", right: "auto", transform: "none" },
    "bottom-right":{ bottom: "12px", right: "20px", top: "auto", left: "auto", transform: "none" },
    "bottom-left": { bottom: "12px", left: "20px", top: "auto", right: "auto", transform: "none" },
    "center":      { top: "50%", left: "50%", transform: "translate(-50%, -50%)" },
};

export default {
    name: "StatMetricCard",
    components: { CountTo },

    props: {
        title: {
            type: String,
            required: true,
        },
        value: {
            type: Number,
            required: true,
        },
        text: {
            type: String,
            default: null,
        },
        textIcon: {
            // Nuevo prop para el icono junto al texto flotante (puede ser un componente, un string para clase, o null)
            type: [Object, Function, String],
            default: null,
        },
        textPosition: {
            type: String,
            default: "top-right", // Nueva prop para decidir posición
            validator: v => [
                "top-right",
                "top-left",
                "bottom-right",
                "bottom-left",
                "center"
            ].includes(v),
        },
        duration: {
            type: Number,
            default: 2000,
        },
        height: {
            type: String,
            default: null,
        },
        color: {
            type: String,
            default: "#85BAC9",
        },
        showSvg: {
            type: Boolean,
            default: true,
        },
        clickable: {
            // Si true, pone cursor pointer (hover/click visual only)
            type: Boolean,
            default: false,
        },
    },

    computed: {
        cardStyle() {
            let style = this.height ? { height: this.height } : {};
            if (this.clickable) {
                style.cursor = "pointer";
            }
            return style;
        },
        svgHeight() {
            // Calcula sólo el alto para el SVG, el ancho siempre 100%
            const defaultHeight = 120;
            return parsePixelSize(this.height, defaultHeight);
        },
        floatingTextStyle() {
            // Calcula el estilo de posición para el texto "flotante"
            const pos = TEXT_POSITIONS[this.textPosition] || TEXT_POSITIONS["top-right"];
            // z-index: 5 para que quede arriba del resto de contenido; fuente y colores acorde estilo actual
            return {
                position: "absolute",
                zIndex: 5,
                ...pos,
                fontSize: "0.9rem",
                color: "#6c757d", // muted
                fontWeight: 500,
                background: "rgba(255,255,255,0.88)",
                borderRadius: "0.35em",
                padding: "0.18em 0.7em",
                lineHeight: "1.2",
                pointerEvents: "none", // que no estorbe click
                maxWidth: "80%",
                whiteSpace: "nowrap",
                textOverflow: "ellipsis",
                overflow: "hidden",
            };
        }
    }
};
</script>

<template>
    <BCard
        ref="card"
        no-body
        class="p-0 card-animate overflow-hidden position-relative"
        :class="{ 'stat-metric-card-clickable': clickable }"
        :style="{...cardStyle, marginBottom: '0 !important'}"
        tabindex="0"
    >
        <!-- Fondo SVG alternativo, estilo 'onda' decorativa, ocupa todo el ancho -->
        <div 
            v-if="showSvg"
            class="position-absolute start-0 top-0 w-100"
            style="z-index: 0; left: 0; right: 0;"
        >
            <svg
                version="1.1"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 200 120"
                width="100%"
                :height="svgHeight"
                preserveAspectRatio="none"
                style="display: block;"
            >
                <defs>
                    <linearGradient :id="'metricwavegrad-' + color.replace('#','')" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" :stop-color="color || '#85BAC9'" stop-opacity="0.09"/>
                        <stop offset="100%" :stop-color="color || '#85BAC9'" stop-opacity="0.01"/>
                    </linearGradient>
                </defs>
                <path
                    :style="{ fill: 'url(#metricwavegrad-' + (color ? color.replace('#','') : '85BAC9') + ')' }"
                    d="M0,80 Q40,100 80,90 Q120,70 160,90 Q180,100 200,80 L200,120 L0,120 Z"
                ></path>
            </svg>
        </div>

        <!-- Texto flotante (valor informativo) -->
        <div v-if="text"
            :style="floatingTextStyle"
            class="stat-metric-card-floating-text">
                <i :class="textIcon"></i>
                <span class="ms-2">{{ text }}</span>
        </div>

        <!-- Icono -->
        <div
            class="position-absolute d-flex align-items-center justify-content-center h-100"
            style="width: 60px; z-index: 2;"
        >
            <slot name="icon" />
        </div>

        <!-- Contenido principal -->
        <BCardBody :style="{...cardStyle, paddingLeft: '60px', position:'relative', zIndex:3}">
            <div class="d-flex align-items-center h-100">
                <div class="flex-grow-1 overflow-hidden">
                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0 pb-0">
                        {{ title }}
                    </p>
                    <h4 class="fs-22 fw-semibold ff-secondary">
                        $
                        <CountTo :startVal="0" :endVal="value" :duration="duration" :decimals="2" separator="." decimal=","/>
                    </h4>
                </div>
            </div>
        </BCardBody>
    </BCard>
</template>

<style scoped>
.stat-metric-card-clickable {
    transition: box-shadow 0.07s;
}
.stat-metric-card-clickable:hover,
.stat-metric-card-clickable:focus {
    cursor: pointer;
    box-shadow: 0 0 0 0.08rem #00ff2f60;
}
/* Opcional: para facilitar testing por clase-scoped */
.stat-metric-card-floating-text {
    user-select: text;
    padding: 0px !important;
}
</style>

<!--
Ejemplo de implementación general del componente desde el padre:

<StatMetricCard
    title="Título de la métrica"
    :value="miValor"
    height="100px"
    color="#ABCDEF"
    text="Algún texto"
    textIcon="i ri-arrow-up-line text-success"     // Ejemplo con string como clase
    textPosition="top-right"
    :showSvg="false"
    :clickable="true"
>
    <template #icon>
        <i class="ri-algún-icono fs-24 text-primary"></i>
    </template>
</StatMetricCard>

Donde:
- `title` es el texto o nombre de la métrica a mostrar.
- `value` es el número que se mostrará animado.
- `text` es el texto adicional, ubicado flotante según `textPosition`
- `textIcon` es el icono que se muestra junto al texto flotante (puede ser un componente, función o string para clase).
- `textPosition` puede ser: 'top-right', 'top-left', 'bottom-right', 'bottom-left', 'center'
- `height` es opcional para ajustar la altura de la tarjeta (por ejemplo, "100px").
- `color` es opcional, para personalizar el color del fondo SVG (por defecto "#85BAC9").
- `showSvg` es opcional (por defecto `true`) para mostrar/ocultar el fondo SVG decorativo.
- `clickable` es opcional (por defecto `false`), si es true el card muestra cursor pointer al pasar el mouse (no emite ningún evento).
- El slot `icon` es opcional para mostrar un icono decorativo.
-->
