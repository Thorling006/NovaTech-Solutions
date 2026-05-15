<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    movimientos: Array,
});

const formatDate = (dateString) => {
    const d = new Date(dateString);
    return d.toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head title="Movimientos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Historial de Movimientos</h2>
                <Link
                    :href="route('movimientos.create')"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                    Registrar Movimiento
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 overflow-x-auto">
                        <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 text-green-600 bg-green-100 p-3 rounded">
                            {{ $page.props.flash.success }}
                        </div>

                        <table class="w-full text-left table-auto min-w-max">
                            <thead>
                                <tr>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50">Fecha</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50">Producto</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50">Tipo</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50 text-right">Cantidad</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50 text-right">Stock Anterior</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50 text-right">Stock Resultante</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50">Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="mov in movimientos" :key="mov.id" class="hover:bg-gray-50">
                                    <td class="p-4 border-b border-gray-50 text-sm">{{ formatDate(mov.fecha) }}</td>
                                    <td class="p-4 border-b border-gray-50">{{ mov.producto?.nombre }} <span class="text-xs text-gray-500">({{ mov.producto?.codigo }})</span></td>
                                    <td class="p-4 border-b border-gray-50">
                                        <span :class="mov.tipo === 'entrada' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 py-1 rounded text-xs uppercase font-bold">
                                            {{ mov.tipo }}
                                        </span>
                                    </td>
                                    <td class="p-4 border-b border-gray-50 text-right font-bold">{{ mov.cantidad }}</td>
                                    <td class="p-4 border-b border-gray-50 text-right text-gray-500">{{ mov.stock_anterior }}</td>
                                    <td class="p-4 border-b border-gray-50 text-right font-bold text-indigo-600">{{ mov.stock_resultante }}</td>
                                    <td class="p-4 border-b border-gray-50 text-sm">{{ mov.user?.name }}</td>
                                </tr>
                                <tr v-if="movimientos.length === 0">
                                    <td colspan="7" class="p-4 text-center text-gray-500">No hay movimientos registrados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
