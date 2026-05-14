<script>
import Layout from '@/Layouts/main.vue';
import PageHeader from '@/Components/page-header.vue';

export default {
    name: 'AssistantsIndex',
    components: {
        Layout,
        PageHeader,
    },
    props: {
        items: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            searchQuery: '',
        };
    },
};
</script>

<template>
    <Layout>
        <PageHeader title="Assistants" pageTitle="Gestión" />
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row g-4 align-items-center">
                            <div class="col-sm">
                                <h5 class="card-title mb-0">Assistants</h5>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-0">Contenido del módulo Assistants. Implementar según requerimientos.</p>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>