import './bootstrap';
import '../scss/config/material/app.scss';
import '@vueform/slider/themes/default.css';
import '../scss/mermaid.min.css';
import "datatables.net-bs5";
import "datatables.net-bs5/css/dataTables.bootstrap5.css";
import { createApp, h } from 'vue';
import { createInertiaApp, Link, Head } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';
import BootstrapVueNext from 'bootstrap-vue-next';
import vClickOutside from "click-outside-vue3";
import VueApexCharts from "vue3-apexcharts";
import VueFeather from 'vue-feather';
import VueTheMask from 'vue-the-mask';
import jQuery from "jquery";
window.$ = window.jQuery = jQuery;

import select2 from "select2/dist/js/select2.full.js";
import "select2/dist/css/select2.css";

// Inyecta manualmente el plugin en el mismo contexto de window.jQuery
if (typeof window !== "undefined" && window.jQuery) {
    select2(window.jQuery);
}

import AOS from 'aos';
import 'aos/dist/aos.css';

import store from "./state/store";
import i18n from './i18n'

AOS.init({
    easing: 'ease-out-back',
    duration: 1000
});

createInertiaApp({
    title: title => title ? `${title} | UTS` : 'UTS - RAG Assistant',
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(store)
            .use(i18n)
            .use(ZiggyVue)
            .use(BootstrapVueNext)
            .component('Link', Link)
            .component('Head', Head)
            .use(VueApexCharts)
            .use(VueTheMask)
            .use(vClickOutside)
            .component(VueFeather.type, VueFeather)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
