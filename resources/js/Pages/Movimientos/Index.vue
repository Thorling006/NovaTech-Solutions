<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    entradas: Array,
    salidas: Array,
    ventas: Array
});

const activeTab = ref('entradas');
const deleteForm = useForm({});

const formatDate = (dateString) => {
    const d = new Date(dateString);
    return d.toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const formatSchedule = (schedule) => {
    switch (schedule) {
        case 'morning':
            return 'Mañana (8:00 AM - 12:00 MD)';
        case 'afternoon':
            return 'Tarde (1:00 PM - 5:00 PM)';
        case 'evening':
            return 'Noche (6:00 PM - 9:00 PM)';
        default:
            return schedule || 'N/A';
    }
};

const deleteMovimiento = (id) => {
    if (confirm('¿Estás seguro de que deseas eliminar este movimiento? El stock del producto se revertirá correspondientemente.')) {
        deleteForm.delete(route('movimientos.destroy', id));
    }
};

const deleteVenta = (id) => {
    if (confirm('¿Estás seguro de que deseas eliminar esta venta? El stock de todos los productos comprados se devolverá al inventario.')) {
        deleteForm.delete(route('ventas.destroy', id));
    }
};
</script>

<template>
    <Head title="Historial de Movimientos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="font-semibold text-xl text-white leading-tight">Control de Movimientos</h2>
                
                <!-- Botón para registrar entradas/salidas manuales -->
                <Link 
                    v-if="activeTab !== 'ventas'" 
                    :href="route('movimientos.create')" 
                    class="btn-primary text-sm font-semibold flex items-center gap-1.5"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    + Registrar Movimiento Manual
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Pestañas (Subapartados) -->
                <div class="border-b border-zinc-800 flex gap-6">
                    <button 
                        @click="activeTab = 'entradas'"
                        class="pb-4 text-sm font-medium transition-all relative"
                        :class="activeTab === 'entradas' ? 'text-white font-bold' : 'text-zinc-500 hover:text-zinc-300'"
                    >
                        Entradas Manuales
                        <span v-if="entradas.length > 0" class="ml-1.5 px-1.5 py-0.5 rounded-full bg-zinc-800 text-zinc-400 text-[10px] font-bold">
                            {{ entradas.length }}
                        </span>
                        <div v-if="activeTab === 'entradas'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-white"></div>
                    </button>
                    
                    <button 
                        @click="activeTab = 'salidas'"
                        class="pb-4 text-sm font-medium transition-all relative"
                        :class="activeTab === 'salidas' ? 'text-white font-bold' : 'text-zinc-500 hover:text-zinc-300'"
                    >
                        Salidas Manuales
                        <span v-if="salidas.length > 0" class="ml-1.5 px-1.5 py-0.5 rounded-full bg-zinc-800 text-zinc-400 text-[10px] font-bold">
                            {{ salidas.length }}
                        </span>
                        <div v-if="activeTab === 'salidas'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-white"></div>
                    </button>

                    <button 
                        @click="activeTab = 'ventas'"
                        class="pb-4 text-sm font-medium transition-all relative"
                        :class="activeTab === 'ventas' ? 'text-white font-bold' : 'text-zinc-500 hover:text-zinc-300'"
                    >
                        Ventas (Clientes)
                        <span v-if="ventas.length > 0" class="ml-1.5 px-1.5 py-0.5 rounded-full bg-zinc-800 text-zinc-400 text-[10px] font-bold">
                            {{ ventas.length }}
                        </span>
                        <div v-if="activeTab === 'ventas'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-white"></div>
                    </button>
                </div>

                <!-- Mensaje de éxito flash -->
                <div v-if="$page.props.flash && $page.props.flash.success" class="flash-success">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash && $page.props.flash.error" class="flash-error">
                    {{ $page.props.flash.error }}
                </div>
                <div v-if="deleteForm.errors.error" class="flash-error">
                    {{ deleteForm.errors.error }}
                </div>

                <!-- TAB: ENTRADAS MANUALES -->
                <div v-if="activeTab === 'entradas'" class="card overflow-hidden animate-fade-in">
                    <div class="p-6 overflow-x-auto">
                        <table class="table-minimal">
                            <thead>
                                <tr>
                                    <th>ID / Código</th>
                                    <th>Fecha</th>
                                    <th>Producto</th>
                                    <th class="text-right">Cantidad</th>
                                    <th class="text-right">Stock Anterior</th>
                                    <th class="text-right">Stock Resultante</th>
                                    <th>Registrado Por</th>
                                    <th v-if="$page.props.auth.user.role_id === 1" class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="mov in entradas" :key="mov.id">
                                    <td class="text-xs font-mono font-bold text-white">{{ mov.id }}</td>
                                    <td class="text-xs text-zinc-500 font-mono">{{ formatDate(mov.fecha) }}</td>
                                    <td>
                                        <div class="font-medium text-zinc-200">{{ mov.producto?.nombre }}</div>
                                        <div class="text-[11px] text-zinc-650 font-mono">{{ mov.producto?.codigo }}</div>
                                    </td>
                                    <td class="text-right font-semibold text-emerald-450">+{{ mov.cantidad }}</td>
                                    <td class="text-right text-zinc-550">{{ mov.stock_anterior }}</td>
                                    <td class="text-right font-medium text-zinc-200">{{ mov.stock_resultante }}</td>
                                    <td class="text-zinc-400 text-sm">{{ mov.user?.name }}</td>
                                    <td v-if="$page.props.auth.user.role_id === 1" class="text-right space-x-3 whitespace-nowrap">
                                        <Link :href="route('movimientos.edit', mov.id)" class="link-action text-xs font-bold uppercase">Editar</Link>
                                        <button @click="deleteMovimiento(mov.id)" class="link-action-danger text-xs font-bold uppercase">Eliminar</button>
                                    </td>
                                </tr>
                                <tr v-if="entradas.length === 0">
                                    <td colspan="8" class="text-center text-zinc-600 py-10">No hay entradas manuales de inventario registradas.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB: SALIDAS MANUALES -->
                <div v-if="activeTab === 'salidas'" class="card overflow-hidden animate-fade-in">
                    <div class="p-6 overflow-x-auto">
                        <table class="table-minimal">
                            <thead>
                                <tr>
                                    <th>ID / Código</th>
                                    <th>Fecha</th>
                                    <th>Producto</th>
                                    <th class="text-right">Cantidad</th>
                                    <th class="text-right">Stock Anterior</th>
                                    <th class="text-right">Stock Resultante</th>
                                    <th>Registrado Por</th>
                                    <th v-if="$page.props.auth.user.role_id === 1" class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="mov in salidas" :key="mov.id">
                                    <td class="text-xs font-mono font-bold text-white">{{ mov.id }}</td>
                                    <td class="text-xs text-zinc-500 font-mono">{{ formatDate(mov.fecha) }}</td>
                                    <td>
                                        <div class="font-medium text-zinc-200">{{ mov.producto?.nombre }}</div>
                                        <div class="text-[11px] text-zinc-650 font-mono">{{ mov.producto?.codigo }}</div>
                                    </td>
                                    <td class="text-right font-semibold text-rose-455">-{{ mov.cantidad }}</td>
                                    <td class="text-right text-zinc-550">{{ mov.stock_anterior }}</td>
                                    <td class="text-right font-medium text-zinc-200">{{ mov.stock_resultante }}</td>
                                    <td class="text-zinc-400 text-sm">{{ mov.user?.name }}</td>
                                    <td v-if="$page.props.auth.user.role_id === 1" class="text-right space-x-3 whitespace-nowrap">
                                        <Link :href="route('movimientos.edit', mov.id)" class="link-action text-xs font-bold uppercase">Editar</Link>
                                        <button @click="deleteMovimiento(mov.id)" class="link-action-danger text-xs font-bold uppercase">Eliminar</button>
                                    </td>
                                </tr>
                                <tr v-if="salidas.length === 0">
                                    <td colspan="8" class="text-center text-zinc-600 py-10">No hay salidas manuales de inventario registradas.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB: VENTAS (CLIENTES) -->
                <div v-if="activeTab === 'ventas'" class="card overflow-hidden animate-fade-in">
                    <div class="p-6 overflow-x-auto">
                        <table class="table-minimal">
                            <thead>
                                <tr>
                                    <th>Ticket / Fecha</th>
                                    <th>Cliente</th>
                                    <th>Detalles de Entrega</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="venta in ventas" :key="venta.id">
                                    <td>
                                        <div class="font-mono font-bold text-white text-xs">{{ venta.id }}</div>
                                        <div class="text-xs text-zinc-600">{{ formatDate(venta.created_at) }}</div>
                                    </td>
                                    <td>
                                        <div class="font-medium text-zinc-200">{{ venta.cliente?.nombre }}</div>
                                        <div class="text-xs text-zinc-500 font-mono">{{ venta.cliente?.correo }}</div>
                                        <div class="text-[11px] text-zinc-600">{{ venta.cliente?.telefono || 'Sin teléfono' }}</div>
                                    </td>
                                    <td>
                                        <div class="text-zinc-300 text-xs font-light line-clamp-1 max-w-xs">{{ venta.direccion }}</div>
                                        <div class="flex items-center gap-3 mt-1.5">
                                            <!-- Badge Horario -->
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-zinc-800 text-zinc-400">
                                                {{ formatSchedule(venta.horario_entrega) }}
                                            </span>
                                            <!-- Enlace Mapa -->
                                            <a 
                                                v-if="venta.latitud"
                                                :href="`https://www.google.com/maps?q=${venta.latitud},${venta.longitud}`"
                                                target="_blank"
                                                class="inline-flex items-center gap-1 text-[10px] text-sky-400 hover:text-sky-300 transition-colors font-semibold"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                Ver ubicación
                                            </a>
                                        </div>
                                    </td>
                                    <td class="text-right font-black text-white text-base">${{ parseFloat(venta.total).toFixed(2) }}</td>
                                    <td class="text-right space-x-3 whitespace-nowrap">
                                        <Link :href="route('ventas.show', venta.id)" class="link-action text-xs font-bold uppercase">Detalles</Link>
                                        <Link v-if="$page.props.auth.user.role_id === 1" :href="route('ventas.edit', venta.id)" class="link-action text-xs font-bold uppercase">Editar</Link>
                                        <button v-if="$page.props.auth.user.role_id === 1" @click="deleteVenta(venta.id)" class="link-action-danger text-xs font-bold uppercase">Eliminar</button>
                                    </td>
                                </tr>
                                <tr v-if="ventas.length === 0">
                                    <td colspan="5" class="text-center text-zinc-600 py-10">No hay ventas registradas.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
