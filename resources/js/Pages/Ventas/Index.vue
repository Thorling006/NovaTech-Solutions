<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    ventas: Array,
});

const formatDate = (dateString) => {
    const d = new Date(dateString);
    return d.toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head title="Ventas Simuladas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Historial de Ventas Simuladas</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 overflow-x-auto">
                        <table class="w-full text-left table-auto min-w-max">
                            <thead>
                                <tr>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50">ID / Fecha</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50">Cliente</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50">Estado</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50 text-right">Total</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="venta in ventas" :key="venta.id" class="hover:bg-gray-50">
                                    <td class="p-4 border-b border-gray-50">
                                        <div class="font-bold">#{{ venta.id }}</div>
                                        <div class="text-xs text-gray-500">{{ formatDate(venta.created_at) }}</div>
                                    </td>
                                    <td class="p-4 border-b border-gray-50">
                                        <div>{{ venta.cliente?.nombre }}</div>
                                        <div class="text-xs text-gray-500">{{ venta.cliente?.correo }}</div>
                                    </td>
                                    <td class="p-4 border-b border-gray-50">
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs uppercase font-bold">
                                            {{ venta.estado }}
                                        </span>
                                    </td>
                                    <td class="p-4 border-b border-gray-50 text-right font-bold text-lg">${{ venta.total }}</td>
                                    <td class="p-4 border-b border-gray-50 text-right">
                                        <Link :href="route('ventas.show', venta.id)" class="text-blue-500 hover:underline">Ver Detalles</Link>
                                    </td>
                                </tr>
                                <tr v-if="ventas.length === 0">
                                    <td colspan="5" class="p-4 text-center text-gray-500">No hay ventas registradas.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
