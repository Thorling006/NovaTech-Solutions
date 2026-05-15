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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Métricas Principales -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex items-center justify-between border-l-4 border-blue-500">
                        <div>
                            <p class="text-sm text-gray-500 uppercase font-semibold">Total Productos</p>
                            <p class="text-3xl font-bold text-gray-800">{{ metricas.totalProductos }}</p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full text-blue-500">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        </div>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex items-center justify-between border-l-4 border-green-500">
                        <div>
                            <p class="text-sm text-gray-500 uppercase font-semibold">Total Categorías</p>
                            <p class="text-3xl font-bold text-gray-800">{{ metricas.totalCategorias }}</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full text-green-500">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex items-center justify-between border-l-4 border-purple-500">
                        <div>
                            <p class="text-sm text-gray-500 uppercase font-semibold">Ventas Simuladas</p>
                            <p class="text-3xl font-bold text-gray-800">{{ metricas.totalVentas }}</p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full text-purple-500">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Alertas de Stock -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Alertas de Inventario</h3>
                        
                        <div v-if="metricas.productosAgotados.length > 0" class="mb-4">
                            <h4 class="text-md font-semibold text-red-600 mb-2 flex items-center">
                                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                Productos Agotados
                            </h4>
                            <ul class="space-y-2">
                                <li v-for="prod in metricas.productosAgotados" :key="'agotado-'+prod.id" class="text-sm flex justify-between items-center bg-red-50 p-2 rounded">
                                    <span>{{ prod.nombre }} <span class="text-gray-500 text-xs">({{ prod.codigo }})</span></span>
                                    <Link :href="route('movimientos.create')" class="text-blue-500 hover:underline text-xs">Reabastecer</Link>
                                </li>
                            </ul>
                        </div>

                        <div v-if="metricas.productosStockBajo.length > 0">
                            <h4 class="text-md font-semibold text-yellow-600 mb-2 flex items-center">
                                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Productos con Stock Bajo
                            </h4>
                            <ul class="space-y-2">
                                <li v-for="prod in metricas.productosStockBajo" :key="'bajo-'+prod.id" class="text-sm flex justify-between items-center bg-yellow-50 p-2 rounded">
                                    <span>{{ prod.nombre }} <span class="text-gray-500 text-xs">({{ prod.codigo }})</span></span>
                                    <span class="font-bold text-yellow-700">Stock: {{ prod.stock_actual }}</span>
                                </li>
                            </ul>
                        </div>

                        <div v-if="metricas.productosAgotados.length === 0 && metricas.productosStockBajo.length === 0" class="text-center text-gray-500 py-4">
                            <p>Todos los productos tienen un stock saludable.</p>
                        </div>
                    </div>

                    <!-- Últimos Movimientos -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4 border-b pb-2">
                            <h3 class="text-lg font-semibold text-gray-800">Últimos Movimientos</h3>
                            <Link :href="route('movimientos.index')" class="text-sm text-blue-500 hover:underline">Ver todos</Link>
                        </div>
                        
                        <div v-if="metricas.ultimosMovimientos.length > 0" class="space-y-3">
                            <div v-for="mov in metricas.ultimosMovimientos" :key="mov.id" class="flex items-start justify-between p-3 bg-gray-50 rounded">
                                <div>
                                    <p class="font-semibold text-sm">{{ mov.producto?.nombre }}</p>
                                    <p class="text-xs text-gray-500">{{ mov.user?.name }} - {{ formatDate(mov.fecha) }}</p>
                                </div>
                                <div :class="mov.tipo === 'entrada' ? 'text-green-600 bg-green-100' : 'text-red-600 bg-red-100'" class="px-2 py-1 rounded text-xs font-bold uppercase flex items-center">
                                    <span v-if="mov.tipo === 'entrada'">+</span>
                                    <span v-else>-</span>
                                    {{ mov.cantidad }}
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center text-gray-500 py-4">
                            <p>No hay movimientos registrados recientes.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
