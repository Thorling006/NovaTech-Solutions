<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    venta: Object,
});

const formatDate = (dateString) => {
    const d = new Date(dateString);
    return d.toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head :title="`Detalle de Venta #${venta.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detalle de Venta #{{ venta.id }}</h2>
                <Link
                    :href="route('ventas.index')"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded"
                >
                    Volver al Historial
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Información del Cliente -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Información del Cliente</h3>
                        <div class="space-y-2">
                            <p><span class="font-semibold text-gray-600">Nombre:</span> {{ venta.cliente?.nombre }}</p>
                            <p><span class="font-semibold text-gray-600">Correo:</span> {{ venta.cliente?.correo }}</p>
                            <p><span class="font-semibold text-gray-600">Teléfono:</span> {{ venta.cliente?.telefono || 'N/A' }}</p>
                        </div>
                    </div>

                    <!-- Información de la Venta -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Detalles de la Transacción</h3>
                        <div class="space-y-2">
                            <p><span class="font-semibold text-gray-600">Fecha:</span> {{ formatDate(venta.created_at) }}</p>
                            <p><span class="font-semibold text-gray-600">Estado:</span> <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs uppercase font-bold">{{ venta.estado }}</span></p>
                            <p class="text-xl mt-4"><span class="font-semibold text-gray-600">Total Pagado:</span> <span class="font-bold text-blue-600">${{ venta.total }}</span></p>
                        </div>
                    </div>
                </div>

                <!-- Productos Comprados -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Productos en esta Venta</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left table-auto min-w-max">
                            <thead>
                                <tr>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50">Producto</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50 text-center">Cantidad</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50 text-right">Precio Unitario</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="detalle in venta.detalles" :key="detalle.id" class="hover:bg-gray-50">
                                    <td class="p-4 border-b border-gray-50">
                                        <div class="flex items-center gap-3">
                                            <img v-if="detalle.producto?.imagen" :src="`/storage/${detalle.producto.imagen}`" class="w-10 h-10 object-cover rounded" />
                                            <div v-else class="w-10 h-10 bg-gray-200 rounded flex items-center justify-center text-xs text-gray-500">N/A</div>
                                            <div>
                                                <p class="font-semibold">{{ detalle.producto?.nombre }}</p>
                                                <p class="text-xs text-gray-500">{{ detalle.producto?.codigo }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 border-b border-gray-50 text-center font-bold">{{ detalle.cantidad }}</td>
                                    <td class="p-4 border-b border-gray-50 text-right">${{ detalle.precio_unitario }}</td>
                                    <td class="p-4 border-b border-gray-50 text-right font-bold">${{ detalle.subtotal }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="p-4 text-right font-bold text-gray-700">Total:</td>
                                    <td class="p-4 text-right font-black text-xl text-blue-600">${{ venta.total }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
