<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({ ventas: Array });
const formatDate = (dateString) => {
    const d = new Date(dateString);
    return d.toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head title="Ventas Simuladas" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-semibold text-xl text-white leading-tight">Historial de Ventas Simuladas</h2></template>
        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="card overflow-hidden">
                    <div class="p-6 overflow-x-auto">
                        <table class="table-minimal">
                            <thead><tr><th>ID / Fecha</th><th>Cliente</th><th>Estado</th><th class="text-right">Total</th><th class="text-right">Acciones</th></tr></thead>
                            <tbody>
                                <tr v-for="venta in ventas" :key="venta.id">
                                    <td>
                                        <div class="font-medium text-zinc-200">#{{ venta.id }}</div>
                                        <div class="text-xs text-zinc-600">{{ formatDate(venta.created_at) }}</div>
                                    </td>
                                    <td>
                                        <div class="text-zinc-200">{{ venta.cliente?.nombre }}</div>
                                        <div class="text-xs text-zinc-600">{{ venta.cliente?.correo }}</div>
                                    </td>
                                    <td><span class="badge badge-success">{{ venta.estado }}</span></td>
                                    <td class="text-right font-semibold text-lg text-white">${{ venta.total }}</td>
                                    <td class="text-right">
                                        <Link :href="route('ventas.show', venta.id)" class="link-action">Ver Detalles →</Link>
                                    </td>
                                </tr>
                                <tr v-if="ventas.length === 0"><td colspan="5" class="text-center text-zinc-600 py-8">No hay ventas registradas.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
