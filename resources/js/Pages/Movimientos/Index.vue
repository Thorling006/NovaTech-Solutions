<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({ movimientos: Array });
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
                <h2 class="font-semibold text-xl text-white leading-tight">Historial de Movimientos</h2>
                <Link :href="route('movimientos.create')" class="btn-primary text-sm">+ Registrar Movimiento</Link>
            </div>
        </template>
        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="card overflow-hidden">
                    <div class="p-6 overflow-x-auto">
                        <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 flash-success">{{ $page.props.flash.success }}</div>
                        <table class="table-minimal">
                            <thead><tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th class="text-right">Cantidad</th><th class="text-right">Stock Ant.</th><th class="text-right">Stock Res.</th><th>Usuario</th></tr></thead>
                            <tbody>
                                <tr v-for="mov in movimientos" :key="mov.id">
                                    <td class="text-xs text-zinc-500">{{ formatDate(mov.fecha) }}</td>
                                    <td class="text-zinc-200">{{ mov.producto?.nombre }} <span class="text-xs text-zinc-600">({{ mov.producto?.codigo }})</span></td>
                                    <td><span :class="mov.tipo === 'entrada' ? 'badge-success' : 'badge-danger'" class="badge">{{ mov.tipo }}</span></td>
                                    <td class="text-right font-medium text-zinc-200">{{ mov.cantidad }}</td>
                                    <td class="text-right text-zinc-500">{{ mov.stock_anterior }}</td>
                                    <td class="text-right font-medium text-sky-400">{{ mov.stock_resultante }}</td>
                                    <td class="text-zinc-400 text-sm">{{ mov.user?.name }}</td>
                                </tr>
                                <tr v-if="movimientos.length === 0"><td colspan="7" class="text-center text-zinc-600 py-8">No hay movimientos registrados.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
