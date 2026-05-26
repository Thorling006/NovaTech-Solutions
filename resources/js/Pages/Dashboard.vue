<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Bar, Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement)

const props = defineProps({
    metricas: Object,
});

const formatDate = (dateString) => {
    const d = new Date(dateString);
    return d.toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

let refreshInterval = null;
onMounted(() => {
    refreshInterval = setInterval(() => {
        router.reload({ preserveState: true, preserveScroll: true });
    }, 10000);
});
onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});

// Configuración visual global para gráficos
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { color: '#e4e4e7', font: { family: 'Inter', size: 12 } } },
        tooltip: {
            backgroundColor: '#18181b',
            titleColor: '#fff',
            bodyColor: '#a1a1aa',
            borderColor: '#27272a',
            borderWidth: 1,
            padding: 10,
            cornerRadius: 8,
        }
    },
    scales: {
        x: { ticks: { color: '#a1a1aa', font: { family: 'Inter' } }, grid: { color: '#27272a' } },
        y: { ticks: { color: '#a1a1aa', font: { family: 'Inter' } }, grid: { color: '#27272a', borderDash: [5, 5] } }
    }
};

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { color: '#e4e4e7', padding: 20, font: { family: 'Inter' } } },
        tooltip: {
            backgroundColor: '#18181b',
            titleColor: '#fff',
            bodyColor: '#a1a1aa',
            borderColor: '#27272a',
            borderWidth: 1,
        }
    },
    cutout: '75%',
    borderWidth: 0,
};

// Datos para Gráfico de Ventas Mensuales
const salesChartData = computed(() => {
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const data = new Array(12).fill(0);
    
    if (props.metricas.ventasPorMes) {
        props.metricas.ventasPorMes.forEach(item => {
            data[item.mes - 1] = parseFloat(item.total_ventas);
        });
    }

    return {
        labels: meses,
        datasets: [{
            label: 'Ingresos Mensuales ($)',
            backgroundColor: 'rgba(59, 130, 246, 0.2)',
            borderColor: 'rgb(59, 130, 246)',
            borderWidth: 2,
            borderRadius: 6,
            hoverBackgroundColor: 'rgba(59, 130, 246, 0.4)',
            data: data
        }]
    };
});

// Datos para Gráfico de Productos Top
const topProductsChartData = computed(() => {
    const labels = props.metricas.productosMasVendidos ? props.metricas.productosMasVendidos.map(p => p.nombre) : [];
    const data = props.metricas.productosMasVendidos ? props.metricas.productosMasVendidos.map(p => p.total_vendido) : [];
    
    return {
        labels: labels.length ? labels : ['Sin datos'],
        datasets: [{
            label: 'Unidades Vendidas',
            backgroundColor: [
                '#8b5cf6', // Violet
                '#3b82f6', // Blue
                '#10b981', // Emerald
                '#f59e0b', // Amber
                '#ef4444', // Rose
            ],
            borderColor: '#09090b', // Match background
            borderWidth: 4,
            hoverOffset: 4,
            data: data.length ? data : [1]
        }]
    };
});
</script>

<template>
    <Head title="Dashboard Inteligente" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-black text-2xl text-transparent bg-clip-text bg-gradient-to-r from-white via-zinc-300 to-zinc-500 tracking-tight">Dashboard Inteligente</h2>
                    <p class="text-xs text-zinc-500 mt-1 uppercase tracking-widest font-bold">Resumen de Operaciones</p>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Métricas Principales (KPIs) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 animate-fade-in stagger">
                    <div class="card p-6 relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-500/10 rounded-full blur-xl group-hover:bg-blue-500/20 transition-all"></div>
                        <p class="text-xs text-zinc-500 uppercase tracking-wider font-bold mb-1">Ingresos Totales</p>
                        <p class="text-3xl font-black text-white">${{ parseFloat(metricas.ingresosTotales || 0).toFixed(2) }}</p>
                    </div>

                    <div class="card p-6 relative overflow-hidden group" style="animation-delay: 0.1s">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-purple-500/10 rounded-full blur-xl group-hover:bg-purple-500/20 transition-all"></div>
                        <p class="text-xs text-zinc-500 uppercase tracking-wider font-bold mb-1">Total Ventas</p>
                        <p class="text-3xl font-black text-white">{{ metricas.totalVentas }} <span class="text-sm font-medium text-zinc-500">tickets</span></p>
                    </div>

                    <div class="card p-6 relative overflow-hidden group" style="animation-delay: 0.2s">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/10 rounded-full blur-xl group-hover:bg-emerald-500/20 transition-all"></div>
                        <p class="text-xs text-zinc-500 uppercase tracking-wider font-bold mb-1">Productos Catálogo</p>
                        <p class="text-3xl font-black text-white">{{ metricas.totalProductos }}</p>
                    </div>
                    
                    <div class="card p-6 relative overflow-hidden group" style="animation-delay: 0.3s">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-500/10 rounded-full blur-xl group-hover:bg-amber-500/20 transition-all"></div>
                        <p class="text-xs text-zinc-500 uppercase tracking-wider font-bold mb-1">Categorías Activas</p>
                        <p class="text-3xl font-black text-white">{{ metricas.totalCategorias }}</p>
                    </div>
                </div>

                <!-- Gráficos Inteligentes -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-fade-in-up" style="animation-delay: 0.4s">
                    
                    <!-- Gráfico: Ventas por Mes -->
                    <div class="card p-6 lg:col-span-2 flex flex-col">
                        <div class="mb-6 border-b border-zinc-800/50 pb-4">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-white">Análisis de Ingresos</h3>
                            <p class="text-xs text-zinc-500">Histórico de ingresos mensuales en el año actual</p>
                        </div>
                        <div class="flex-grow min-h-[300px]">
                            <Bar :data="salesChartData" :options="chartOptions" />
                        </div>
                    </div>

                    <!-- Gráfico: Top Productos -->
                    <div class="card p-6 flex flex-col">
                        <div class="mb-6 border-b border-zinc-800/50 pb-4">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-white">Top Productos Más Vendidos</h3>
                            <p class="text-xs text-zinc-500">Concentración de ventas por producto</p>
                        </div>
                        <div class="flex-grow min-h-[300px] flex items-center justify-center relative">
                            <Doughnut :data="topProductsChartData" :options="doughnutOptions" />
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none pb-6">
                                <div class="text-center">
                                    <span class="block text-xl font-black text-white">Top 5</span>
                                    <span class="block text-[10px] text-zinc-500 uppercase tracking-widest">Favoritos</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tablas y Alertas: Productos Menos Vendidos y Movimientos -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-fade-in-up" style="animation-delay: 0.5s">
                    
                    <!-- Productos Menos Vendidos (Alerta Inteligente) -->
                    <div class="card p-6">
                        <div class="mb-4 border-b border-zinc-800/50 pb-4 flex justify-between items-center">
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-wider text-white">Atención Requerida</h3>
                                <p class="text-xs text-zinc-500">Productos con menos ventas</p>
                            </div>
                            <span class="flex h-3 w-3 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500"></span>
                            </span>
                        </div>
                        
                        <div v-if="metricas.productosMenosVendidos && metricas.productosMenosVendidos.length > 0" class="space-y-3">
                            <div v-for="(prod, index) in metricas.productosMenosVendidos" :key="prod.id" class="flex justify-between items-center bg-rose-500/5 border border-rose-500/10 p-3 rounded-xl transition-all hover:bg-rose-500/10">
                                <div class="flex items-center gap-3">
                                    <span class="text-zinc-600 font-mono text-xs">#{{ index + 1 }}</span>
                                    <span class="text-sm font-medium text-zinc-300">{{ prod.nombre }}</span>
                                </div>
                                <span class="text-xs font-bold text-rose-400">{{ prod.total_vendido }} ud.</span>
                            </div>
                        </div>
                        <div v-else class="text-center text-zinc-600 py-6">
                            <p class="text-sm">Todos los productos tienen buenas ventas.</p>
                        </div>
                    </div>

                    <!-- Últimos Movimientos -->
                    <div class="card p-6 lg:col-span-2">
                        <div class="flex justify-between items-center mb-4 pb-4 border-b border-zinc-800/50">
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-wider text-white">Últimos Movimientos de Inventario</h3>
                                <p class="text-xs text-zinc-500">Registro en tiempo real</p>
                            </div>
                            <Link :href="route('movimientos.index')" class="px-3 py-1.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-xs font-bold text-zinc-300 transition-colors">Ver todos</Link>
                        </div>
                        
                        <div v-if="metricas.ultimosMovimientos.length > 0" class="space-y-3">
                            <div v-for="mov in metricas.ultimosMovimientos" :key="mov.id" class="flex items-center justify-between p-3.5 bg-zinc-900/50 border border-zinc-800/80 rounded-xl transition-colors hover:border-zinc-700">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" :class="mov.tipo === 'entrada' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'">
                                        <svg v-if="mov.tipo === 'entrada'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path></svg>
                                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path></svg>
                                    </div>
                                    <div>
                                        <p class="font-bold text-sm text-zinc-200">{{ mov.producto?.nombre || 'Producto Eliminado' }}</p>
                                        <p class="text-[11px] text-zinc-500 uppercase tracking-wider font-medium mt-0.5">
                                            <span v-if="mov.venta_id" class="text-zinc-400">Venta Registrada</span>
                                            <span v-else>{{ mov.tipo === 'entrada' ? 'Ingreso Manual' : 'Salida Manual' }} · {{ mov.user?.name || 'Sistema' }}</span>
                                            · {{ formatDate(mov.fecha) }}
                                        </p>
                                    </div>
                                </div>
                                <span :class="mov.tipo === 'entrada' ? 'text-emerald-400' : 'text-rose-400'" class="font-mono font-bold text-sm">
                                    {{ mov.tipo === 'entrada' ? '+' : '−' }}{{ mov.cantidad }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="text-center text-zinc-600 py-10 border border-dashed border-zinc-800 rounded-xl">
                            <svg class="w-8 h-8 mx-auto mb-2 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-sm">No hay movimientos recientes registrados.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
