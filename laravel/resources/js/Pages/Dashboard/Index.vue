<script>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/main.vue';
import PageHeader from '@/Components/page-header.vue';

export default {
    name: 'DashboardIndex',
    components: {
        Layout,
        PageHeader,
        Link,
    },
    data() {
        return {
            quickLinks: [
                { label: 'Productos', href: null, route: 'productos.index', icon: 'ri-box-3-line', color: 'success' },
                { label: 'Bodegas', href: null, route: 'warehouses.index', icon: 'ri-store-2-line', color: 'primary' },
                { label: 'Lotes de inventario', href: null, route: 'inventoryLots.index', icon: 'ri-stack-line', color: 'info' },
                { label: 'Listas de precios', href: null, route: '', icon: 'ri-price-tag-3-line', color: 'warning' },
                { label: 'Demo Velzon (ecommerce)', href: '/plantilla/velzon-ecommerce', route: null, icon: 'ri-layout-grid-line', color: 'secondary' },
            ],
        };
    },
};
</script>

<template>
    <Layout>
        <PageHeader title="Dashboard" pageTitle="RAG" />

        <BRow>
            <BCol cols="12">
                <div class="dashboard-welcome mb-4">
                    <div class="dashboard-welcome__bg">
                        <span class="dashboard-welcome__circle dashboard-welcome__circle--1"></span>
                        <span class="dashboard-welcome__circle dashboard-welcome__circle--2"></span>
                        <span class="dashboard-welcome__circle dashboard-welcome__circle--3"></span>
                    </div>
                    <div class="d-flex align-items-center position-relative">
                        <div class="dashboard-welcome__icon flex-shrink-0">
                            <i class="ri-rocket-2-line"></i>
                        </div>
                        <div class="flex-grow-1 ps-3">
                            <h5 class="text-white mb-1 fw-semibold">Bienvenido al panel principal</h5>
                            <p class="text-white text-opacity-90 mb-0 small">Desde aquí puedes acceder a los módulos del sistema.</p>
                        </div>
                    </div>
                </div>
            </BCol>
        </BRow>

        <BRow>
            <BCol xl="8">
                <BCard no-body class="dashboard-card dashboard-card--links shadow-sm border-0 h-100">
                    <BCardHeader class="border-0 bg-transparent pt-4 pb-2">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <span class="dashboard-card__icon dashboard-card__icon--primary me-2">
                                <i class="ri-links-line"></i>
                            </span>
                            Accesos rápidos
                        </h5>
                    </BCardHeader>
                    <BCardBody class="pt-0 pb-4">
                        <div class="row g-3">
                            <div v-for="(link, index) in quickLinks" :key="index" class="col-sm-6">
                                <Link
                                    :href="link.route ? route(link.route) : link.href"
                                    :class="['dashboard-quicklink', `dashboard-quicklink--${link.color}`]"
                                >
                                    <span class="dashboard-quicklink__icon">
                                        <i :class="link.icon"></i>
                                    </span>
                                    <span class="dashboard-quicklink__label">{{ link.label }}</span>
                                    <i class="ri-arrow-right-s-line dashboard-quicklink__arrow"></i>
                                </Link>
                            </div>
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
            <BCol xl="4">
                <BCard no-body class="dashboard-card dashboard-card--system shadow-sm border-0 h-100">
                    <BCardHeader class="border-0 bg-transparent pt-4 pb-2">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <span class="dashboard-card__icon dashboard-card__icon--success me-2">
                                <i class="ri-pie-chart-line"></i>
                            </span>
                            Sistema
                        </h5>
                    </BCardHeader>
                    <BCardBody class="pt-0 pb-0">
                        <ul class="dashboard-system-list">
                            <li class="dashboard-system-list__item dashboard-system-list__item--status">
                                <span class="dashboard-system-list__dot"></span>
                                <span class="ms-4">Aplicación en línea</span>
                            </li>
                            <li class="dashboard-system-list__item">
                                <span class="dashboard-system-list__brand me-2">T</span>
                                <span>RAG</span>
                            </li>
                            <li class="dashboard-system-list__item">
                                <i class="ri-code-box-line text-primary me-2 fs-16"></i>
                                <span>V 1.0.0</span>
                            </li>
                            <li class="dashboard-system-list__item">
                                <i class="ri-calendar-line text-primary me-2 fs-16"></i>
                                <span>{{
                                    new Date().toLocaleDateString('es-ES', {
                                        day: '2-digit',
                                        month: 'short',
                                        year: 'numeric'
                                    })
                                }}</span>
                            </li>
                        </ul>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
    </Layout>
</template>

<style scoped>
.dashboard-welcome {
    position: relative;
    padding: 1.25rem 1.5rem;
    border-radius: 1rem;
    overflow: hidden;
    background: linear-gradient(135deg, var(--vz-primary) 0%, #3b7ddd 50%, #2c6bcc 100%);
    box-shadow: 0 10px 40px -10px rgba(var(--vz-primary-rgb), 0.45);
}

.dashboard-welcome__bg {
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.dashboard-welcome__circle {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
}

.dashboard-welcome__circle--1 {
    width: 180px;
    height: 180px;
    top: -60px;
    right: -40px;
}

.dashboard-welcome__circle--2 {
    width: 100px;
    height: 100px;
    bottom: -30px;
    right: 20%;
}

.dashboard-welcome__circle--3 {
    width: 60px;
    height: 60px;
    top: 50%;
    left: -20px;
}

.dashboard-welcome__icon {
    width: 56px;
    height: 56px;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    color: #fff;
}

.dashboard-card {
    border-radius: 1rem;
}

.dashboard-card__icon {
    width: 36px;
    height: 36px;
    border-radius: 0.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

.dashboard-card__icon--primary {
    background: rgba(var(--vz-primary-rgb), 0.12);
    color: var(--vz-primary);
}

.dashboard-card__icon--success {
    background: rgba(var(--vz-success-rgb), 0.12);
    color: var(--vz-success);
}

.dashboard-quicklink {
    display: flex;
    align-items: center;
    padding: 1rem 1.25rem;
    border-radius: 0.75rem;
    text-decoration: none;
    color: inherit;
    border: 1px solid transparent;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background 0.2s ease;
}

.dashboard-quicklink:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px -8px rgba(0, 0, 0, 0.15);
}

.dashboard-quicklink__icon {
    width: 44px;
    height: 44px;
    border-radius: 0.625rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.dashboard-quicklink__label {
    flex-grow: 1;
    font-weight: 600;
    margin-left: 1rem;
}

.dashboard-quicklink__arrow {
    font-size: 1.25rem;
    color: var(--vz-secondary);
    opacity: 0.7;
    transition: transform 0.2s ease;
}

.dashboard-quicklink:hover .dashboard-quicklink__arrow {
    transform: translateX(4px);
}

.dashboard-quicklink--primary {
    background: rgba(var(--vz-primary-rgb), 0.08);
    border-color: rgba(var(--vz-primary-rgb), 0.2);
}
.dashboard-quicklink--primary .dashboard-quicklink__icon {
    background: rgba(var(--vz-primary-rgb), 0.15);
    color: var(--vz-primary);
}
.dashboard-quicklink--primary:hover {
    background: rgba(var(--vz-primary-rgb), 0.12);
    border-color: rgba(var(--vz-primary-rgb), 0.35);
}

.dashboard-quicklink--success {
    background: rgba(var(--vz-success-rgb), 0.08);
    border-color: rgba(var(--vz-success-rgb), 0.2);
}
.dashboard-quicklink--success .dashboard-quicklink__icon {
    background: rgba(var(--vz-success-rgb), 0.15);
    color: var(--vz-success);
}
.dashboard-quicklink--success:hover {
    background: rgba(var(--vz-success-rgb), 0.12);
    border-color: rgba(var(--vz-success-rgb), 0.35);
}

.dashboard-quicklink--info {
    background: rgba(var(--vz-info-rgb), 0.08);
    border-color: rgba(var(--vz-info-rgb), 0.2);
}
.dashboard-quicklink--info .dashboard-quicklink__icon {
    background: rgba(var(--vz-info-rgb), 0.15);
    color: var(--vz-info);
}
.dashboard-quicklink--info:hover {
    background: rgba(var(--vz-info-rgb), 0.12);
    border-color: rgba(var(--vz-info-rgb), 0.35);
}

.dashboard-quicklink--warning {
    background: rgba(var(--vz-warning-rgb), 0.08);
    border-color: rgba(var(--vz-warning-rgb), 0.2);
}
.dashboard-quicklink--warning .dashboard-quicklink__icon {
    background: rgba(var(--vz-warning-rgb), 0.15);
    color: var(--vz-warning);
}
.dashboard-quicklink--warning:hover {
    background: rgba(var(--vz-warning-rgb), 0.12);
    border-color: rgba(var(--vz-warning-rgb), 0.35);
}

.dashboard-quicklink--secondary {
    background: rgba(var(--vz-secondary-rgb), 0.08);
    border-color: rgba(var(--vz-secondary-rgb), 0.2);
}
.dashboard-quicklink--secondary .dashboard-quicklink__icon {
    background: rgba(var(--vz-secondary-rgb), 0.15);
    color: var(--vz-secondary);
}
.dashboard-quicklink--secondary:hover {
    background: rgba(var(--vz-secondary-rgb), 0.12);
    border-color: rgba(var(--vz-secondary-rgb), 0.35);
}

.dashboard-system-list {
    list-style: none;
    padding: 0;
    margin: 0;
    text-transform: uppercase;
    font-size: 0.8125rem;
}

.dashboard-system-list__item {
    display: flex;
    align-items: center;
    padding: 0.65rem 0;
    border-bottom: 1px dashed var(--vz-border-color);
}

.dashboard-system-list__item:last-child {
    border-bottom: none;
}

.dashboard-system-list__item--status {
    position: relative;
}

.dashboard-system-list__dot {
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--vz-success);
    box-shadow: 0 0 0 3px rgba(var(--vz-success-rgb), 0.25);
    animation: dashboard-pulse 2s ease-in-out infinite;
}

.dashboard-system-list__brand {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--vz-warning);
    color: #fff;
    font-weight: 700;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

@keyframes dashboard-pulse {
    0%, 100% { box-shadow: 0 0 0 3px rgba(var(--vz-success-rgb), 0.25); }
    50% { box-shadow: 0 0 0 6px rgba(var(--vz-success-rgb), 0.1); }
}
</style>
