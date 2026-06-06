<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref, nextTick } from 'vue';

const props = defineProps({
    venta: Object
});

const mapContainer = ref(null);
let map = null;

const statusInfo = {
    'pendiente': { title: 'Pendiente de Envío', color: 'text-amber-500', bg: 'bg-amber-500/10', border: 'border-amber-500/20' },
    'en_ruta': { title: 'En Ruta', color: 'text-blue-500', bg: 'bg-blue-500/10', border: 'border-blue-500/20' },
    'entregado': { title: 'Entregado', color: 'text-emerald-500', bg: 'bg-emerald-500/10', border: 'border-emerald-500/20' }
};

const getStatus = (status) => statusInfo[status] || statusInfo['pendiente'];

const initMap = () => {
    if (!window.L) return;

    const latBase = 13.348428;
    const lngBase = -88.440182;
    const latDest = props.venta.latitud;
    const lngDest = props.venta.longitud;

    map = window.L.map(mapContainer.value).setView([latBase, lngBase], 12);

    window.L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap &copy; CARTO'
    }).addTo(map);

    // Marcador Base
    const baseIcon = window.L.divIcon({
        className: 'custom-icon',
        html: `<div style="background-color:#fff; width:16px; height:16px; border-radius:50%; border:3px solid #18181b;"></div>`
    });
    window.L.marker([latBase, lngBase], { icon: baseIcon }).addTo(map).bindPopup('Sucursal Base');

    // Marcador Destino
    const destIcon = window.L.divIcon({
        className: 'custom-icon',
        html: `<div style="background-color:#3b82f6; width:16px; height:16px; border-radius:50%; border:3px solid #18181b; box-shadow: 0 0 10px #3b82f6;"></div>`
    });
    window.L.marker([latDest, lngDest], { icon: destIcon }).addTo(map).bindPopup('Ubicación de Entrega');

    // Trazar línea (Vuelo de pájaro simple para el frontend del cliente)
    const latlngs = [
        [latBase, lngBase],
        [latDest, lngDest]
    ];
    window.L.polyline(latlngs, { color: '#3b82f6', dashArray: '5, 10', weight: 2, opacity: 0.7 }).addTo(map);

    // Ajustar bounds
    map.fitBounds(latlngs, { padding: [50, 50] });
};

const loadLeaflet = () => {
    if (window.L) {
        initMap();
        return;
    }
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(link);
    
    const script = document.createElement('script');
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    script.onload = () => nextTick(() => initMap());
    document.head.appendChild(script);
};

onMounted(() => {
    loadLeaflet();
});
</script>

<template>
    <Head title="Estado del Pedido - NovaStock" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 flex flex-col font-sans relative">
        <header class="border-b border-zinc-900 bg-zinc-950/80 backdrop-blur-md sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
                <Link :href="route('home')" class="flex items-center gap-3 cursor-pointer">
                    <span class="text-2xl font-black tracking-tight bg-gradient-to-r from-white via-zinc-300 to-zinc-500 bg-clip-text text-transparent">
                        NovaTech Solutions
                    </span>
                    <span class="text-[10px] uppercase font-bold tracking-widest text-zinc-500 border border-zinc-800/80 rounded px-1.5 py-0.5 bg-zinc-900/50 hidden sm:inline">
                        Seguimiento
                    </span>
                </Link>
                <Link :href="route('tracking.index')" class="text-zinc-400 font-medium hover:text-white transition text-sm">
                    Nueva Búsqueda
                </Link>
            </div>
        </header>

        <main class="flex-grow max-w-5xl mx-auto w-full px-6 py-10 relative z-10 grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Columna Izquierda: Información -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Tarjeta de Estado -->
                <div class="bg-zinc-900/50 border border-zinc-800 rounded-3xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs text-zinc-500 uppercase tracking-widest font-bold">Ticket: {{ venta.id }}</span>
                    </div>
                    
                    <div 
                        class="px-4 py-3 rounded-xl border flex items-center justify-center gap-2 text-sm font-bold mb-6"
                        :class="[getStatus(venta.estado_envio).bg, getStatus(venta.estado_envio).border, getStatus(venta.estado_envio).color]"
                    >
                        <div class="w-2 h-2 rounded-full animate-pulse" :class="`bg-current`"></div>
                        {{ getStatus(venta.estado_envio).title }}
                    </div>

                    <div class="space-y-4 text-sm">
                        <div class="flex justify-between pb-4 border-b border-zinc-800/50">
                            <span class="text-zinc-500">Fecha Pedido</span>
                            <span class="text-white font-medium">{{ new Date(venta.created_at).toLocaleDateString() }}</span>
                        </div>
                        <div class="flex justify-between pb-4 border-b border-zinc-800/50">
                            <span class="text-zinc-500">Horario Pref.</span>
                            <span class="text-white font-medium capitalize">{{ venta.horario_entrega }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Total Pagado</span>
                            <span class="text-white font-black">${{ parseFloat(venta.total).toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de Conductor -->
                <div v-if="venta.conductor" class="bg-zinc-900/50 border border-zinc-800 rounded-3xl p-6">
                    <h3 class="text-xs text-zinc-500 uppercase tracking-widest font-bold mb-4">Conductor Asignado</h3>
                    <div class="flex items-center gap-4">
                        <img :src="venta.conductor.foto_url" :alt="venta.conductor.nombre" class="w-14 h-14 rounded-full object-cover border-2 border-zinc-700">
                        <div>
                            <p class="text-white font-bold">{{ venta.conductor.nombre }}</p>
                            <p class="text-xs text-zinc-400">Repartidor Oficial</p>
                        </div>
                    </div>
                </div>
                <div v-else class="bg-zinc-900/20 border border-dashed border-zinc-800 rounded-3xl p-6 text-center text-zinc-500 text-xs">
                    El sistema inteligente de rutas asignará un conductor en breve.
                </div>
            </div>

            <!-- Columna Derecha: Mapa y Detalles -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Mapa -->
                <div class="bg-zinc-900/50 border border-zinc-800 rounded-3xl overflow-hidden shadow-2xl relative">
                    <div class="absolute top-4 left-4 z-[400] bg-zinc-950/80 backdrop-blur border border-zinc-800 rounded-lg px-3 py-1.5 text-xs text-white font-medium flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span> Ruta de Entrega
                    </div>
                    <div ref="mapContainer" class="w-full h-80 bg-zinc-950"></div>
                </div>

                <!-- Detalles de Compra -->
                <div class="bg-zinc-900/50 border border-zinc-800 rounded-3xl p-6">
                    <h3 class="text-xs text-zinc-500 uppercase tracking-widest font-bold mb-4">Detalle de Productos</h3>
                    <div class="space-y-3">
                        <div v-for="detalle in venta.detalles" :key="detalle.id" class="flex justify-between items-center py-2 border-b border-zinc-800/50 last:border-0">
                            <div>
                                <p class="text-white text-sm">{{ detalle.producto.nombre }}</p>
                                <p class="text-xs text-zinc-500">Cantidad: {{ detalle.cantidad }}</p>
                            </div>
                            <span class="text-white text-sm font-medium">${{ parseFloat(detalle.subtotal).toFixed(2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</template>
