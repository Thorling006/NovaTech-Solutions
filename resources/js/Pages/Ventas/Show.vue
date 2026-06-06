<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({ venta: Object });
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
                <h2 class="font-semibold text-xl text-white leading-tight">Venta #{{ venta.id }}</h2>
                <Link :href="route('ventas.index')" class="btn-secondary text-sm">← Volver</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Cliente -->
                    <div class="card p-6 animate-fade-in-up">
                        <h3 class="text-xs font-medium uppercase tracking-wider text-zinc-500 mb-4 pb-3 border-b border-zinc-800/50">Información del Cliente</h3>
                        <div class="space-y-3">
                            <p class="text-sm"><span class="text-zinc-500">Nombre:</span> <span class="text-zinc-200 ml-2">{{ venta.cliente?.nombre }}</span></p>
                            <p class="text-sm"><span class="text-zinc-500">Correo:</span> <span class="text-zinc-200 ml-2">{{ venta.cliente?.correo }}</span></p>
                            <p class="text-sm"><span class="text-zinc-500">Teléfono:</span> <span class="text-zinc-200 ml-2">{{ venta.cliente?.telefono || 'N/A' }}</span></p>
                        </div>
                    </div>

                    <!-- Transacción -->
                    <div class="card p-6 animate-fade-in-up" style="animation-delay: 0.1s">
                        <h3 class="text-xs font-medium uppercase tracking-wider text-zinc-500 mb-4 pb-3 border-b border-zinc-800/50">Detalles de la Transacción</h3>
                        <div class="space-y-3">
                            <p class="text-sm"><span class="text-zinc-500">Fecha:</span> <span class="text-zinc-200 ml-2">{{ formatDate(venta.created_at) }}</span></p>
                            <p class="text-sm"><span class="text-zinc-500">Código de Seguimiento:</span> <span class="font-mono text-xs text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded border border-rose-500/20 font-bold ml-2 select-all">{{ venta.tracking_id || 'N/A' }}</span></p>
                            <p class="text-sm"><span class="text-zinc-500">Estado:</span> <span class="badge badge-success ml-2">{{ venta.estado }}</span></p>
                            <p class="text-xl mt-4"><span class="text-zinc-500 text-sm">Total:</span> <span class="font-bold text-white ml-2">${{ venta.total }}</span></p>
                        </div>
                    </div>
                </div>

                <!-- Productos -->
                <div class="card p-6 animate-fade-in-up" style="animation-delay: 0.2s">
                    <h3 class="text-xs font-medium uppercase tracking-wider text-zinc-500 mb-4 pb-3 border-b border-zinc-800/50">Productos en esta Venta</h3>
                    <div class="overflow-x-auto">
                        <table class="table-minimal">
                            <thead><tr><th>Producto</th><th class="text-center">Cantidad</th><th class="text-right">Precio Unit.</th><th class="text-right">Subtotal</th></tr></thead>
                            <tbody>
                                <tr v-for="detalle in venta.detalles" :key="detalle.id">
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <img v-if="detalle.producto?.imagen" :src="`/storage/${detalle.producto.imagen}`" class="w-10 h-10 object-cover rounded-lg border border-zinc-800" />
                                            <div v-else class="w-10 h-10 bg-zinc-800 rounded-lg flex items-center justify-center text-xs text-zinc-600">—</div>
                                            <div>
                                                <p class="font-medium text-zinc-200">{{ detalle.producto?.nombre }}</p>
                                                <p class="text-xs text-zinc-600">{{ detalle.producto?.codigo }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center font-medium text-zinc-200">{{ detalle.cantidad }}</td>
                                    <td class="text-right">${{ detalle.precio_unitario }}</td>
                                    <td class="text-right font-medium text-zinc-200">${{ detalle.subtotal }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-right font-medium text-zinc-400 !border-t !border-zinc-800">Total:</td>
                                    <td class="text-right font-bold text-xl text-white !border-t !border-zinc-800">${{ venta.total }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
