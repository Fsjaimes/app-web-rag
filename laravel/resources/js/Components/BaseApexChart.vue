<script>
    import { computed } from "vue";

    export default {
        name: "BaseApexChart",
        props: {
            series: {
                type: Array,
                required: true,
            },
            options: {
                type: Object,
                required: true,
            },
            height: {
                type: [Number, String],
                default: 300,
            },
            legendPosition: {
                type: String,
                default: 'bottom',
                validator: (val) => ['top', 'left', 'bottom', 'right'].includes(val),
            },
            title: {
                type: String,
                default: "",
            }
        },
        setup(props) {
            const chartHeight = computed(() => props.height);

            // Adaptar a formato pie/donut: si la serie es tipo [{name, data}], extraer a arrays planas para Apex donut
            // Permitir entrada tanto en formato [{name, data:number}] como [number] (Apex acepta ambos)
            const normalizedSeries = computed(() => {
                if (Array.isArray(props.series) && typeof props.series[0] === 'number') {
                    return props.series;
                }
                if (Array.isArray(props.series) && typeof props.series[0] === 'object' && props.series[0] !== null) {
                    if (Array.isArray(props.series[0].data)) {
                        return props.series.map(serie => Array.isArray(serie.data) ? serie.data.reduce((a, b) => Number(a) + Number(b), 0) : (typeof serie.data === "number" ? serie.data : 0));
                    }
                    return props.series.map(serie => typeof serie.data === "number" ? serie.data : 0);
                }
                return [];
            });

            const normalizedLabels = computed(() => {
                if (Array.isArray(props.series) && typeof props.series[0] === 'object' && props.series[0] !== null && props.series[0].name) {
                    return props.series.map(serie => serie.name || '');
                }
                if (Array.isArray(props.series) && typeof props.series[0] === 'number') {
                    return [];
                }
                return [];
            });

            // Forzamos SIEMPRE tipo donut (no pie) y bordes más redondeados
            const mergedOptions = computed(() => {
                const userOptions = JSON.parse(JSON.stringify(props.options || {}));
                userOptions.chart = userOptions.chart || {};
                userOptions.chart.type = "donut"; // Forzar disco con centro vacío

                userOptions.legend = userOptions.legend || {};
                userOptions.legend.position = props.legendPosition;

                // Bordes redondeados: stroke y dropShadow (para suavidad extra)
                userOptions.plotOptions = userOptions.plotOptions || {};
                userOptions.plotOptions.pie = userOptions.plotOptions.pie || {};
                // Aplica "borderRadius" para ApexCharts >= v3.36.0 (si disponible).
                // Si no está soportado, stroke y dropShadow suavizan el borde.
                userOptions.plotOptions.pie.donut = userOptions.plotOptions.pie.donut || {};
                userOptions.plotOptions.pie.expandOnClick = userOptions.plotOptions.pie.expandOnClick ?? true;
                userOptions.plotOptions.pie.donut.labels = userOptions.plotOptions.pie.donut.labels || {};

                // Borde más suave
                userOptions.stroke = userOptions.stroke || {};
                userOptions.stroke.show = true;
                userOptions.stroke.width = 3;
                userOptions.stroke.colors = ['#fff'];
                userOptions.stroke.lineCap = 'round'; // Para esquinas redondas

                // Drop shadow para mayor suavidad (opcional)
                userOptions.plotOptions.pie.dropShadow = {
                    enabled: true,
                    top: 2,
                    left: 0,
                    blur: 5,
                    color: "#000",
                    opacity: 0.12
                };

                // Intentar borderRadius si la versión lo soporta (seguro no rompe nada en versiones antiguas)
                userOptions.plotOptions.pie.dataLabels = userOptions.plotOptions.pie.dataLabels || {};
                userOptions.plotOptions.pie.borderRadius = 12;

                // Etiquetas (labels)
                if (normalizedLabels.value && normalizedLabels.value.length > 0) {
                    userOptions.labels = normalizedLabels.value;
                }

                userOptions.chart.height = props.height;

                // --- Tooltip formateado a moneda ---
                userOptions.tooltip = userOptions.tooltip || {};
                userOptions.tooltip.y = userOptions.tooltip.y || {};
                userOptions.tooltip.y.formatter = function (val) {
                    if (typeof val !== "number") return val;
                    return "$ " + val.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                };

                return userOptions;
            });

            return {
                chartHeight,
                mergedOptions,
                series: normalizedSeries,
                title: props.title,
            };
        },
    };
</script>

<template>
    <div class="card h-100 d-flex flex-column mb-3">
        <div class="card-header bg-light-subtle" v-if="title">
            <h5 class="card-title mb-0">{{ title }}</h5>
        </div>

        <div class="card-body p-0 flex-grow-1 d-flex">
            <apexchart
                class="apex-charts w-100 h-100"
                dir="ltr"
                :series="series"
                :options="mergedOptions"
                :height="chartHeight"
            />
        </div>
    </div>
</template>

<style scoped>
    /* Bordes mucho más redondeados para la tarjeta y el gráfico */
    .apex-charts,
    .apexcharts-canvas,
    .apexcharts-inner,
    .apexcharts-svg {
        height: 100% !important;
        border-radius: 1.5rem !important;
        border-top-left-radius: 0 !important;
        /* Esto ayuda visualmente, aunque el SVG es redondo si stroke.lineCap=round y dropShadow están activos */
        overflow: visible;
    }
</style>
<!-- 
    Explicación de uso:

    El componente <BaseApexChart /> ahora siempre renderiza el gráfico dentro de un Card. 
    Recibe las siguientes props:
        - series: Array (obligatorio). Para disco/donut, puede ser:
            - [10, 20, 30, 40]    // Donut clásico
            - [{ name: "A", data: 10 }, { name: "B", data: 20 }]   // Extrae name/data
        - options: Objeto de configuración de ApexCharts (obligatorio)
        - height: Altura del gráfico (opcional, por defecto 300)
        - legendPosition: (opcional) Permite elegir dónde se muestra la leyenda del gráfico: 'top', 'left', 'bottom' o 'right'. Por defecto es 'bottom'.
        - title: (opcional) El título que se muestra en el encabezado del Card. Si no se indica, no se muestra encabezado.

    El tamaño del Card (ancho, margen, etc) lo define el componente padre al envolver <BaseApexChart />.

    Ejemplo de implementación en un componente padre:

    <template>
        <div style="min-width: 350px; max-width: 100%;">
            <BaseApexChart
                :series="series"
                :options="chartOptions"
                :height="350"
                legend-position="top"
                title="Mi Gráfico de Reparto"
            />
        </div>
    </template>

    <script setup>
    import BaseApexChart from "@/Components/BaseApexChart.vue";

    // Ejemplo 1: Serie como array simple
    const series = [10, 20, 30, 40];

    // Ejemplo 2: Serie como [{ name, data }]
    // const series = [
    //     { name: "Gastos", data: 30 },
    //     { name: "Ingresos", data: 70 }
    // ];

    const chartOptions = {
        labels: ["Enero", "Febrero", "Marzo", "Abril"], // opcional, se ignora si hay name en series
        // No es necesario especificar legend.position aquí, lo maneja el componente
    };
    </script>
-->
