<script setup>
import { computed } from 'vue'
import ApexChart from 'vue3-apexcharts'

/**
 * PROPIEDADES (PROPS)
 * - indicators: Array<{ label: string, color: string, data: [number|null|string|undefined] }>
 *      - Cada indicador debe tener un `label`, un `color`, y un arreglo `data` de 12 meses (Ene-Dic)
 * - showLegend: Boolean (por defecto: false)
 */
const props = defineProps({
    title: {
        type: String,
        default: ''
    },
    indicators: {
        type: Array,
        default: () => []
    },
    showLegend: {
        type: Boolean,
        default: false
    },
})

// Nombres cortos de los meses (editables, por defecto en español)
const monthNames = [
    'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
    'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'
]

// Computed para transformar los indicadores a series de ApexCharts
const apexSeries = computed(() => {
    // Si no existen indicadores, retorna array vacío para evitar errores
    if (!Array.isArray(props.indicators) || !props.indicators.length) return []

    return props.indicators.map(indicator => {
        return {
            name: indicator.label,
            data: Array.from({ length: 12 }).map((_, i) => {
                // Se aceptan null, 0, '', undefined y los transforma en null para Apex
                let v = Array.isArray(indicator.data) ? indicator.data[i] : null
                if (v === '' || v === undefined) return null
                // Si es null explícito, deja null (Apex lo muestra como hueco)
                if (v === null) return null
                // Si es string numérico, lo convierte a número
                if (typeof v === 'string' && v !== '') {
                    const parsed = Number(v)
                    return isNaN(parsed) ? null : parsed
                }
                // Si es número, lo retorna, cualquier otro valor null
                return typeof v === 'number' ? v : null
            })
        }
    })
})

// Computed para colores de cada indicador
const colors = computed(() => {
    // Paleta de colores por defecto si no se especifica color en el indicador
    const defaultPalette = [
        '#5470c6', '#91cc75', '#fac858', '#ee6666',
        '#73c0de', '#3ba272', '#fc8452', '#9a60b4',
        '#ea7ccc', '#ffb980', '#2ec7c9', '#b6a2de'
    ];
    return props.indicators.map((ind, idx) =>
        ind.color || defaultPalette[idx % defaultPalette.length] || '#ccc'
    );
});

// Computed para opciones de configuración de ApexCharts
const chartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: 350,
        toolbar: { show: false }
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '45%',
            endingShape: 'rounded'
        }
    },
    dataLabels: { enabled: false },
    xaxis: {
        categories: monthNames,
        labels: {
            show: true,
            style: {
                fontWeight: 500
            }
        },
        axisBorder: { show: false },
        axisTicks: { show: false }
    },
    yaxis: {
        labels: {
            style: {
                fontWeight: 500
            },
            formatter: function(val) {
                // Aquí forzamos siempre 2 decimales
                return typeof val === 'number'
                    ? val.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    : '';
            }
        }
    },
    colors: colors.value,
    legend: {
        show: props.showLegend,
        position: 'top',
        fontWeight: 600
    },
    // Mostrar el signo pesos antes del valor en el tooltip al hacer hover sobre la barra
    tooltip: {
        y: {
            formatter: function (val) {
                return typeof val === 'number'
                    ? '$' + val.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    : '-'
            }
        }
    },
    grid: {
        strokeDashArray: 4,
        borderColor: "#ececec",
    }
}))
</script>

<template>
    <div class="current-year-trends">
        <div v-if="props.title" class="cyt-title h5 mb-3">{{ props.title }}</div>
        <div
            v-if="props.indicators && props.indicators.length"
            class="cyt-indicators d-flex gap-3 mb-2 flex-wrap borde"
        >
            <!-- <span class="text-uppercase fw-bold">
                {{ new Date().toLocaleString('es-CO', { month: 'long' }).charAt(0).toUpperCase() + new Date().toLocaleString('es-CO', { month: 'long' }).slice(1) }}
            </span> -->
            <!-- Renderiza cada indicador en la leyenda -->
            <div
                v-for="(indicator, idx) in props.indicators"
                :key="`indicator-${idx}`"
                class="cyt-indicator d-flex align-items-center"
            >
                <span
                    class="cyt-indicator-dot me-2"
                    :style="{ backgroundColor: indicator.color || '#ccc' }"
                ></span>
                <span class="cyt-indicator-label text-muted">
                    {{ indicator.label }}
                </span>
                <span
                    v-if="typeof indicator.value !== 'undefined'"
                    class="cyt-indicator-value ms-1 fw-semibold"
                >$
                    {{ Number(indicator.value).toLocaleString('es-CO', {minimumFractionDigits:2, maximumFractionDigits:2}) }}
                </span>
            </div>
        </div>
        <!-- Gráfico de barras de ApexCharts -->
        <ApexChart
            type="bar"
            :height="350"
            :options="chartOptions"
            :series="apexSeries"
        />
    </div>
</template>

<style scoped>
.current-year-trends {
    width: 100%;
}

/* Estilo para el título */
.cyt-title {
    font-weight: 500;
}

/* Leyenda de indicadores */
.cyt-indicators {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.cyt-indicator {
    display: flex;
    align-items: center;
}

.cyt-indicator-dot {
    display: inline-block;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 1.5px solid #fff;
    box-shadow: 0 0 0 1px rgba(0,0,0,.06);
    margin-right: 6px;
}

.cyt-indicator-label {
    font-size: 0.96em;
    color: #6c757d;
}

.cyt-indicator-value {
    font-size: 1.02em;
}

/* Responsivo para pantallas pequeñas */
@media (max-width: 575.98px) {
    .cyt-title {
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
    }
    .cyt-indicators {
        gap: 1rem;
        font-size: 0.98em;
    }
    .cyt-indicator-dot {
        width: 12px;
        height: 12px;
        margin-right: 4px;
    }
}
</style>
