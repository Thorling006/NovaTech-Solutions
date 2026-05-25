<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    metricas: Object,
});

const formatDate = (dateString) => {
    const d = new Date(dateString);
    return d.toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-white leading-tight">Dashboard</h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Métricas Principales -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 stagger">
                    <div class="metric-card">
                        <div>
                            <p class="text-xs text-zinc-500 uppercase tracking-wider font-medium">Total Productos</p>
                            <p class="text-3xl font-bold text-white mt-1">{{ metricas.totalProductos }}</p>
                        </div>
                        <div class="metric-icon bg-sky-500/10 text-sky-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        </div>
                    </div>
                    
                    <div class="metric-card" style="animation-delay: 0.1s">
                        <div>
                            <p class="text-xs text-zinc-500 uppercase tracking-wider font-medium">Total Categorías</p>
                            <p class="text-3xl font-bold text-white mt-1">{{ metricas.totalCategorias }}</p>
                        </div>
                        <div class="metric-icon bg-emerald-500/10 text-emerald-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        </div>
                    </div>

                    <div class="metric-card" style="animation-delay: 0.2s">
                        <div>
                            <p class="text-xs text-zinc-500 uppercase tracking-wider font-medium">Ventas Simuladas</p>
                            <p class="text-3xl font-bold text-white mt-1">{{ metricas.totalVentas }}</p>
                        </div>
                        <div class="metric-icon bg-violet-500/10 text-violet-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Alertas de Stock -->
                    <div class="card p-6 animate-fade-in-up" style="animation-delay: 0.15s">
                        <h3 class="text-sm font-medium uppercase tracking-wider text-zinc-500 mb-4 pb-3 border-b border-zinc-800/50">Alertas de Inventario</h3>
                        
                        <div v-if="metricas.productosAgotados.length > 0" class="mb-4">
                            <h4 class="text-sm font-medium text-rose-400 mb-2 flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                Productos Agotados
                            </h4>
                            <ul class="space-y-2">
                                <li v-for="prod in metricas.productosAgotados" :key="'agotado-'+prod.id" class="text-sm flex justify-between items-center bg-rose-500/5 border border-rose-500/10 p-3 rounded-xl">
                                    <span class="text-zinc-300">{{ prod.nombre }} <span class="text-zinc-600 text-xs">({{ prod.codigo }})</span></span>
                                    <Link :href="route('movimientos.create')" class="text-sky-400 hover:text-sky-300 text-xs transition-colors">Reabastecer</Link>
                                </li>
                            </ul>
                        </div>

                        <div v-if="metricas.productosStockBajo.length > 0">
                            <h4 class="text-sm font-medium text-amber-400 mb-2 flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Stock Bajo
                            </h4>
                            <ul class="space-y-2">
                                <li v-for="prod in metricas.productosStockBajo" :key="'bajo-'+prod.id" class="text-sm flex justify-between items-center bg-amber-500/5 border border-amber-500/10 p-3 rounded-xl">
                                    <span class="text-zinc-300">{{ prod.nombre }} <span class="text-zinc-600 text-xs">({{ prod.codigo }})</span></span>
                                    <span class="badge badge-warning">Stock: {{ prod.stock_actual }}</span>
                                </li>
                            </ul>
                        </div>

                        <div v-if="metricas.productosAgotados.length === 0 && metricas.productosStockBajo.length === 0" class="text-center text-zinc-600 py-6">
                            <svg class="w-8 h-8 mx-auto mb-2 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-sm">Todos los productos tienen stock saludable.</p>
                        </div>
                    </div>

                    <!-- Últimos Movimientos -->
                    <div class="card p-6 animate-fade-in-up" style="animation-delay: 0.25s">
                        <div class="flex justify-between items-center mb-4 pb-3 border-b border-zinc-800/50">
                            <h3 class="text-sm font-medium uppercase tracking-wider text-zinc-500">Últimos Movimientos</h3>
                            <Link :href="route('movimientos.index')" class="text-xs text-zinc-600 hover:text-white transition-colors duration-200">Ver todos →</Link>
                        </div>
                        
                        <div v-if="metricas.ultimosMovimientos.length > 0" class="space-y-3">
                            <div v-for="mov in metricas.ultimosMovimientos" :key="mov.id" class="flex items-start justify-between p-3 bg-zinc-800/30 rounded-xl transition-colors duration-200 hover:bg-zinc-800/50">
                                <div>
                                    <p class="font-medium text-sm text-zinc-200">{{ mov.producto?.nombre }}</p>
                                    <p class="text-xs text-zinc-600 mt-0.5">{{ mov.user?.name }} · {{ formatDate(mov.fecha) }}</p>
                                </div>
                                <span :class="mov.tipo === 'entrada' ? 'badge-success' : 'badge-danger'" class="badge">
                                    <span v-if="mov.tipo === 'entrada'">+</span>
                                    <span v-else>−</span>
                                    {{ mov.cantidad }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="text-center text-zinc-600 py-6">
                            <p class="text-sm">No hay movimientos recientes.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
