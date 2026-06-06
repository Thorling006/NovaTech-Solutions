<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';

const props = defineProps({
    ventas_pendientes: Array,
    ventas_entregadas: Array,
    ventas_nunca_entregadas: Array,
    rutas: Array,
    conductores: Array
});

// Intercept console.warn to suppress OSRM demo warnings
if (typeof window !== 'undefined') {
    const originalWarn = console.warn;
    console.warn = function (...args) {
        if (args[0] && typeof args[0] === 'string' && args[0].includes("OSRM's demo server")) return;
        originalWarn.apply(console, args);
    };
}

const selectedVentas = ref([]);
const isCreateModalOpen = ref(false);
const viewMode = ref('map'); // 'list' o 'map'

// Map logic
const mapContainer = ref(null);
let map = null;
let markersLayer = null;
let routingControl = null;
const routeStats = ref(null);

const initMap = () => {
    if (!mapContainer.value || map) return;

    map = window.L.map(mapContainer.value).setView([13.348428, -88.440182], 12);
    
    window.L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    markersLayer = window.L.layerGroup().addTo(map);

    // Marcador Almacén Base
    const baseIcon = window.L.divIcon({
        className: 'custom-icon',
        html: `<div style="background-color:#fff; width:16px; height:16px; border-radius:50%; border:4px solid #000; box-shadow: 0 0 10px rgba(255,255,255,0.5);"></div>`
    });
    window.L.marker([13.348428, -88.440182], { icon: baseIcon }).addTo(map).bindPopup('<b>Almacén Base</b>');

    renderMarkers();
};

const renderMarkers = () => {
    if (!markersLayer) return;
    markersLayer.clearLayers();

    props.ventas_pendientes.forEach(venta => {
        const isSelected = selectedVentas.value.includes(venta.id);
        const bgColor = isSelected ? '#10b981' : '#3b82f6'; // Verde si seleccionado, azul si no

        const icon = window.L.divIcon({
            className: 'custom-icon',
            html: `<div style="background-color:${bgColor}; width:20px; height:20px; border-radius:50%; border:2px solid #000; box-shadow: 0 0 5px rgba(0,0,0,0.5); cursor:pointer;"></div>`
        });

        const marker = window.L.marker([venta.latitud, venta.longitud], { icon })
            .addTo(markersLayer)
            .bindPopup(`
                <div style="text-align:center;">
                    <b>${venta.cliente.nombre}</b><br>
                    Horario: ${venta.horario_entrega}<br>
                    <button onclick="window.toggleVentaFromMap('${venta.id}')" style="margin-top:5px; background:#3b82f6; color:white; border:none; padding:3px 8px; border-radius:3px; cursor:pointer;">
                        ${isSelected ? 'Deseleccionar' : 'Seleccionar'}
                    </button>
                </div>
            `);
    });

    if (routingControl) {
        map.removeControl(routingControl);
        routingControl = null;
    }
    routeStats.value = null;

    if (selectedVentas.value.length > 0 && window.L.Routing) {
        const selectedObjs = selectedVentas.value
            .map(id => props.ventas_pendientes.find(v => v.id === id))
            .filter(Boolean);
        
        const waypoints = [window.L.latLng(13.348428, -88.440182)]; // Inicia en Base
        selectedObjs.forEach(v => waypoints.push(window.L.latLng(v.latitud, v.longitud)));
        
        routingControl = window.L.Routing.control({
            router: window.L.Routing.osrmv1({
                serviceUrl: 'https://router.project-osrm.org/route/v1'
            }),
            waypoints: waypoints,
            routeWhileDragging: false,
            addWaypoints: false,
            fitSelectedRoutes: false,
            showAlternatives: false,
            lineOptions: {
                styles: [{color: '#10b981', opacity: 0.8, weight: 6}]
            },
            createMarker: function() { return null; }, // Evitar que routing cree pines dobles
            show: false // Ocultar panel de direcciones
        }).addTo(map);

        routingControl.on('routesfound', function(e) {
            const routes = e.routes;
            const summary = routes[0].summary;
            
            const distanceKm = (summary.totalDistance / 1000).toFixed(1);
            
            // Tiempo de conducción satelital en minutos
            let timeMinutes = Math.round(summary.totalTime / 60);
            
            // +15 minutos por cada paquete
            const stopsCount = selectedObjs.length;
            timeMinutes += (stopsCount * 15);

            const hours = Math.floor(timeMinutes / 60);
            const minutes = timeMinutes % 60;
            
            routeStats.value = {
                distance: distanceKm,
                timeStr: hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`,
                stops: stopsCount
            };
        });
    }
};

// Exponer la función para que el popup de Leaflet pueda llamarla
window.toggleVentaFromMap = (id) => {
    toggleSelection(id);
    renderMarkers(); // Re-renderizar para cambiar el color del pin
};

const loadLeaflet = () => {
    if (window.L && window.L.Routing) {
        if (viewMode.value === 'map') nextTick(() => initMap());
        return;
    }
    
    // CSS Base
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(link);
    
    // CSS Routing
    const linkRouting = document.createElement('link');
    linkRouting.rel = 'stylesheet';
    linkRouting.href = 'https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css';
    document.head.appendChild(linkRouting);

    const script = document.createElement('script');
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    script.onload = () => {
        const scriptRouting = document.createElement('script');
        scriptRouting.src = 'https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js';
        scriptRouting.onload = () => {
            if (viewMode.value === 'map') nextTick(() => initMap());
        };
        document.head.appendChild(scriptRouting);
    };
    document.head.appendChild(script);
};

onMounted(() => {
    loadLeaflet();
});

const form = useForm({
    nombre: '',
    conductor_id: '',
    fecha_programada: new Date().toISOString().split('T')[0],
    hora_programada: '07:00',
    ventas_ids: []
});

const formDateSelected = computed(() => form.fecha_programada);

const availableConductoresList = computed(() => {
    return props.conductores.map(c => {
        const busy = props.rutas.some(r => 
            r.conductor_id === c.id && 
            r.fecha_programada === formDateSelected.value && 
            r.estado !== 'finalizada'
        );
        return { ...c, isBusy: busy };
    });
});

const selectedDriverFilter = ref('');

const activeAndPendingRutas = computed(() => {
    return props.rutas.filter(r => r.estado === 'creada' || r.estado === 'en_curso');
});

const finishedAndCanceledRutas = computed(() => {
    let filtered = props.rutas.filter(r => r.estado === 'finalizada' || r.estado === 'cancelada');
    if (selectedDriverFilter.value) {
        filtered = filtered.filter(r => r.conductor_id === parseInt(selectedDriverFilter.value));
    }
    return filtered;
});

const toggleSelection = (id) => {
    if (selectedVentas.value.includes(id)) {
        selectedVentas.value = selectedVentas.value.filter(v => v !== id);
    } else {
        selectedVentas.value.push(id);
    }
};

const selectAll = () => {
    if (selectedVentas.value.length === props.ventas_pendientes.length) {
        selectedVentas.value = [];
    } else {
        selectedVentas.value = props.ventas_pendientes.map(v => v.id);
    }
};

const openCreateModal = () => {
    if (selectedVentas.value.length === 0) {
        alert("Selecciona al menos 1 pedido para crear una ruta.");
        return;
    }
    form.ventas_ids = selectedVentas.value;
    isCreateModalOpen.value = true;
};

const submitForm = () => {
    form.post(route('rutas.store'), {
        onSuccess: () => {
            isCreateModalOpen.value = false;
            selectedVentas.value = [];
            form.reset();
        }
    });
};

const deleteRuta = (id) => {
    if(confirm("¿Seguro que deseas eliminar esta ruta? Los pedidos volverán a estar pendientes.")) {
        router.delete(route('rutas.destroy', id));
    }
};
</script>
<template>
    <Head title="Gestor de Rutas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="font-black text-2xl text-white leading-tight uppercase tracking-tight">Control Maestro de Rutas</h2>
                    <p class="text-xs text-zinc-400 mt-1">Planificación GPS, monitoreo en vivo y despacho logístico.</p>
                </div>
            </div>
        </template>

        <div class="py-8 bg-zinc-950 min-h-screen text-zinc-300">
            <!-- Panel de Estadísticas Generales (Glow KPI Cards) -->
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-8 grid grid-cols-2 md:grid-cols-4 gap-5">
                <!-- Card 1: Pedidos Libres -->
                <div class="bg-zinc-900/60 border border-zinc-800/80 rounded-2xl p-5 relative overflow-hidden transition-all duration-300 hover:border-zinc-700/50 hover:shadow-lg hover:shadow-black/30 shadow-xl group">
                    <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:scale-110 transition-transform">
                        <svg class="w-12 h-12 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                    </div>
                    <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest">Pedidos sin Asignar</p>
                    <p class="text-3xl font-black text-white mt-2 font-mono">{{ ventas_pendientes.length }}</p>
                </div>
                
                <!-- Card 2: Rutas Activas -->
                <div class="bg-zinc-900/60 border border-zinc-800/80 rounded-2xl p-5 relative overflow-hidden transition-all duration-300 hover:border-blue-900/30 hover:shadow-lg hover:shadow-blue-500/5 shadow-xl group">
                    <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:scale-110 transition-transform">
                        <svg class="w-12 h-12 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                    </div>
                    <p class="text-[10px] font-bold text-blue-500 uppercase tracking-widest">Rutas Activas</p>
                    <p class="text-3xl font-black text-blue-400 mt-2 font-mono">{{ activeAndPendingRutas.length }}</p>
                </div>

                <!-- Card 3: Entregados -->
                <div class="bg-zinc-900/60 border border-zinc-800/80 rounded-2xl p-5 relative overflow-hidden transition-all duration-300 hover:border-emerald-900/30 hover:shadow-lg hover:shadow-emerald-500/5 shadow-xl group">
                    <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:scale-110 transition-transform">
                        <svg class="w-12 h-12 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest">Entregas Completadas</p>
                    <p class="text-3xl font-black text-emerald-400 mt-2 font-mono">{{ ventas_entregadas.length }}</p>
                </div>

                <!-- Card 4: Devueltos -->
                <div class="bg-zinc-900/60 border border-zinc-800/80 rounded-2xl p-5 relative overflow-hidden transition-all duration-300 hover:border-rose-900/30 hover:shadow-lg hover:shadow-rose-500/5 shadow-xl group">
                    <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:scale-110 transition-transform">
                        <svg class="w-12 h-12 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <p class="text-[10px] font-bold text-rose-500 uppercase tracking-widest">Productos Rebotados</p>
                    <p class="text-3xl font-black text-rose-400 mt-2 font-mono">{{ ventas_nunca_entregadas.length }}</p>
                </div>
            </div>

            <!-- SECCIÓN 1: PEDIDOS SIN ASIGNAR Y MAPA (Ancho Completo) -->
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-8">
                <div class="bg-zinc-900 border border-zinc-800/80 shadow-2xl rounded-2xl overflow-hidden flex flex-col min-h-[600px] transition-all duration-500 hover:border-zinc-700/30">
                    <div class="p-6 border-b border-zinc-800 bg-zinc-950/40 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <h3 class="text-base font-black text-white uppercase tracking-widest flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                Pedidos sin Asignar
                            </h3>
                            <p class="text-xs text-zinc-400 mt-1">Agrupa los pedidos en un itinerario optimizado y asígnalos a un repartidor.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-4 w-full sm:w-auto">
                            <!-- Toggle view mode -->
                            <div class="flex bg-zinc-950 rounded-xl p-1 border border-zinc-850 w-full sm:w-auto">
                                <button 
                                    @click="viewMode = 'list'" 
                                    :class="{'bg-zinc-800 text-white font-bold': viewMode === 'list', 'text-zinc-500': viewMode !== 'list'}" 
                                    class="flex-1 sm:flex-none px-4 py-2 text-xs rounded-lg transition"
                                >
                                    Lista
                                </button>
                                <button 
                                    @click="viewMode = 'map'; loadLeaflet()" 
                                    :class="{'bg-zinc-800 text-white font-bold': viewMode === 'map', 'text-zinc-500': viewMode !== 'map'}" 
                                    class="flex-1 sm:flex-none px-4 py-2 text-xs rounded-lg transition"
                                >
                                    Mapa Interactivo
                                </button>
                            </div>
                            <button 
                                @click="selectAll" 
                                class="text-xs bg-zinc-800 hover:bg-zinc-750 text-zinc-300 font-bold px-4 py-2 rounded-xl transition border border-zinc-800"
                            >
                                {{ selectedVentas.length === ventas_pendientes.length ? 'Deseleccionar Todos' : 'Seleccionar Todos' }}
                            </button>
                        </div>
                    </div>

                    <!-- Vista de Lista en Grid de 3 Columnas -->
                    <div v-if="viewMode === 'list'" class="overflow-y-auto flex-grow p-6 bg-zinc-950">
                        <div v-if="ventas_pendientes.length === 0" class="text-center py-20 text-zinc-500 flex flex-col items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-zinc-700 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                            <p class="text-sm font-bold text-zinc-400">No hay pedidos pendientes de envío.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5" v-else>
                            <label v-for="venta in ventas_pendientes" :key="venta.id" 
                                class="flex items-start gap-4 p-5 border rounded-2xl cursor-pointer transition-all duration-300 hover:scale-[1.02] hover:-translate-y-0.5 hover:bg-zinc-900/60 shadow-md relative overflow-hidden group"
                                :class="selectedVentas.includes(venta.id) ? 'bg-emerald-500/5 border-emerald-500/50 shadow-emerald-500/5' : 'bg-zinc-900/60 border-zinc-800/80 shadow-black/30'"
                            >
                                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-emerald-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>

                                <input 
                                    type="checkbox" 
                                    :value="venta.id" 
                                    :checked="selectedVentas.includes(venta.id)"
                                    @change="toggleSelection(venta.id)"
                                    class="mt-1 rounded-lg bg-zinc-950 border-zinc-800 text-emerald-500 focus:ring-emerald-500 focus:ring-offset-zinc-950 w-5 h-5 cursor-pointer transition"
                                >
                                <div class="flex-grow min-w-0">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-black text-zinc-500 font-mono tracking-wider">TICKET #{{ venta.id }}</span>
                                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-zinc-300 bg-zinc-800/80 px-2 py-0.5 rounded border border-zinc-700/50">{{ venta.horario_entrega }}</span>
                                    </div>
                                    <div v-if="venta.intentos_entrega > 0" class="mt-2">
                                        <span class="text-[9px] font-bold tracking-widest uppercase text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded border border-rose-500/20">
                                            ⚠️ {{ venta.intentos_entrega }} Intento Fallido
                                        </span>
                                    </div>
                                    <p class="text-base font-extrabold text-white mt-3 leading-snug truncate">{{ venta.cliente.nombre }}</p>
                                    <p class="text-xs text-zinc-500 mt-1 leading-relaxed line-clamp-2">{{ venta.direccion }}</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Vista de Mapa (Ancho Completo) -->
                    <div v-show="viewMode === 'map'" class="h-[550px] w-full relative bg-zinc-950 flex-grow">
                        <div v-if="ventas_pendientes.length === 0" class="absolute inset-0 z-10 flex items-center justify-center bg-zinc-950/80 backdrop-blur-sm text-zinc-500">
                            No hay pedidos pendientes de envío para mapear.
                        </div>
                        <div ref="mapContainer" class="w-full h-full"></div>
                        <div class="absolute bottom-4 left-4 z-[400] bg-zinc-900/95 backdrop-blur border border-zinc-800/80 p-3.5 rounded-xl pointer-events-none shadow-2xl">
                            <p class="text-[10px] text-zinc-400 font-bold uppercase tracking-wider mb-2.5">Código de Colores</p>
                            <div class="flex items-center gap-2 mb-2"><div class="w-3.5 h-3.5 rounded-full bg-blue-500 border-2 border-zinc-950 shadow"></div><span class="text-[10px] text-zinc-400 font-medium">Sin Seleccionar</span></div>
                            <div class="flex items-center gap-2"><div class="w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-zinc-950 shadow"></div><span class="text-[10px] text-zinc-400 font-medium">Seleccionado para Ruta</span></div>
                        </div>

                        <!-- Estadísticas Satelitales (Flotante Premium) -->
                        <div v-if="routeStats && viewMode === 'map'" class="absolute top-4 right-4 z-[400] bg-zinc-900/95 backdrop-blur-md border border-zinc-800 p-5 rounded-2xl pointer-events-none shadow-2xl min-w-[240px] animate-fade-in">
                            <p class="text-[10px] text-emerald-400 font-black tracking-widest uppercase mb-3.5 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                Cómputo Vial OSRM
                            </p>
                            <div class="space-y-2.5">
                                <div class="flex justify-between items-center border-b border-zinc-800 pb-2">
                                    <span class="text-xs text-zinc-400 font-bold">Recorrido Vial</span>
                                    <span class="text-sm text-white font-mono font-black">{{ routeStats.distance }} km</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-zinc-400 font-bold">Tiempo Estimado</span>
                                    <span class="text-sm text-white font-mono font-black">{{ routeStats.timeStr }}</span>
                                </div>
                            </div>
                            <p class="text-[9px] text-zinc-500 mt-4 italic text-center">*Basado en velocidad promedio + 15 min de descarga por parada.</p>
                        </div>
                    </div>

                    <!-- Acción de Agrupado -->
                    <div class="p-5 border-t border-zinc-800/80 bg-zinc-900/80 backdrop-blur-md flex justify-between items-center">
                        <p class="text-xs text-zinc-400"><b class="text-white font-black">{{ selectedVentas.length }}</b> pedidos seleccionados</p>
                        <button 
                            @click="openCreateModal"
                            :disabled="selectedVentas.length === 0"
                            class="bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-bold py-3.5 px-6 rounded-xl transition-all duration-300 flex justify-center items-center gap-2 shadow-lg shadow-emerald-600/10 hover:shadow-emerald-500/20"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Planificar Ruta de Despacho
                        </button>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: GRID DE RUTAS ACTIVAS Y HISTORIAL -->
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- PANEL 1: RUTAS ACTIVAS -->
                <div class="bg-zinc-900 border border-zinc-800 shadow-xl rounded-2xl overflow-hidden flex flex-col h-[520px] transition hover:border-zinc-700/30">
                    <div class="p-6 border-b border-zinc-800 bg-zinc-950/40">
                        <h3 class="text-sm font-bold text-white uppercase tracking-widest flex items-center gap-2">
                            <span class="relative flex h-2.5 w-2.5">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-500"></span>
                            </span>
                            Rutas Activas
                        </h3>
                        <p class="text-xs text-zinc-400 mt-1">Rutas en curso o preparadas pendientes de inicio.</p>
                    </div>

                    <div class="overflow-y-auto flex-grow p-4 space-y-4 bg-zinc-950">
                        <div v-if="activeAndPendingRutas.length === 0" class="text-center py-20 text-zinc-500 flex flex-col items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-zinc-700 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                            <p class="text-xs font-bold text-zinc-400">No hay rutas activas en este momento.</p>
                        </div>

                        <div v-for="ruta in activeAndPendingRutas" :key="ruta.id" class="p-5 border border-zinc-800 rounded-2xl bg-zinc-900 relative overflow-hidden group hover:border-zinc-700 transition duration-300">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h4 class="text-white font-bold text-lg">{{ ruta.nombre }}</h4>
                                    <p class="text-xs text-zinc-400 mt-0.5">{{ ruta.ventas.length }} pedidos en la ruta</p>
                                </div>
                                <span class="text-[9px] font-black tracking-widest px-2.5 py-1 rounded-md uppercase border"
                                    :class="{
                                        'bg-zinc-950 text-zinc-400 border-zinc-800': ruta.estado === 'creada',
                                        'bg-blue-500/10 text-blue-400 border-blue-500/20': ruta.estado === 'en_curso',
                                        'bg-amber-500/10 text-amber-400 border-amber-500/20': ruta.estado === 'retorno'
                                    }">
                                    {{ ruta.estado === 'creada' ? 'PREPARADA' : (ruta.estado === 'en_curso' ? 'EN CAMINO' : 'RETORNANDO') }}
                                </span>
                            </div>

                            <div class="flex items-center gap-3 mb-4 bg-zinc-950/60 p-3 rounded-xl border border-zinc-800/50">
                                <img v-if="ruta.conductor?.foto_url" :src="ruta.conductor.foto_url" class="w-8 h-8 rounded-full border border-zinc-800">
                                <div class="w-8 h-8 rounded-full border border-zinc-850 bg-zinc-950 flex items-center justify-center" v-else>
                                    <svg class="w-4 h-4 text-zinc-650" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white leading-tight">{{ ruta.conductor?.nombre || 'Sin asignar' }}</p>
                                    <p class="text-[10px] text-zinc-550 mt-0.5">{{ ruta.fecha_programada }} a las {{ ruta.hora_programada }}</p>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <button 
                                    v-if="ruta.estado === 'creada'"
                                    @click="deleteRuta(ruta.id)"
                                    class="text-xs bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 border border-rose-500/20 px-3.5 py-2 rounded-xl transition-all duration-300 font-bold"
                                >
                                    Deshacer Ruta
                                </button>
                                <div v-else class="text-xs text-blue-400 flex items-center gap-2 font-bold p-1">
                                    <span class="w-2 h-2 rounded-full bg-blue-500 animate-ping"></span>
                                    En navegación satelital...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PANEL 2: HISTORIAL Y REGISTRO DE JORNADAS -->
                <div class="bg-zinc-900 border border-zinc-800 shadow-xl rounded-2xl overflow-hidden flex flex-col h-[520px] transition hover:border-zinc-700/30">
                    <div class="p-6 border-b border-zinc-800 bg-zinc-950/40 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <h3 class="text-sm font-bold text-white uppercase tracking-widest flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-zinc-650"></span>
                                Historial de Jornadas
                            </h3>
                            <p class="text-xs text-zinc-400 mt-1">Registro de rutas completadas o canceladas.</p>
                        </div>
                        <div class="w-full sm:w-auto">
                            <select v-model="selectedDriverFilter" class="w-full bg-zinc-950 border border-zinc-850 rounded-xl text-white text-xs px-3.5 py-2.5 focus:ring-zinc-800 focus:border-zinc-800 font-medium transition cursor-pointer">
                                <option value="">Todos los Conductores</option>
                                <option v-for="c in conductores" :key="c.id" :value="c.id">{{ c.nombre }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-y-auto flex-grow p-4 space-y-4 bg-zinc-950">
                        <div v-if="finishedAndCanceledRutas.length === 0" class="text-center py-20 text-zinc-500 flex flex-col items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-zinc-700 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <p class="text-xs font-bold text-zinc-400">No hay registros históricos para mostrar.</p>
                        </div>

                        <div v-for="ruta in finishedAndCanceledRutas" :key="ruta.id" class="p-5 border border-zinc-850 rounded-2xl bg-zinc-900 relative overflow-hidden group hover:border-zinc-700 transition duration-300">
                            
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h4 class="text-white font-bold text-lg leading-tight">{{ ruta.nombre }}</h4>
                                    <p class="text-xs text-zinc-500 mt-0.5">Jornada con {{ ruta.ventas.length }} entregas</p>
                                </div>
                                <span class="text-[9px] font-black tracking-widest px-2.5 py-1 rounded-md uppercase border"
                                    :class="{
                                        'bg-emerald-500/10 text-emerald-400 border-emerald-500/20': ruta.estado === 'finalizada',
                                        'bg-rose-500/10 text-rose-400 border-rose-500/20': ruta.estado === 'cancelada'
                                    }">
                                    {{ ruta.estado === 'finalizada' ? 'FINALIZADA' : 'CANCELADA' }}
                                </span>
                            </div>

                            <div class="flex items-center gap-3 mb-4 bg-zinc-950/60 p-3 rounded-xl border border-zinc-800/50">
                                <img v-if="ruta.conductor?.foto_url" :src="ruta.conductor.foto_url" class="w-8 h-8 rounded-full border border-zinc-800">
                                <div class="w-8 h-8 rounded-full border border-zinc-850 bg-zinc-950 flex items-center justify-center" v-else>
                                    <svg class="w-4 h-4 text-zinc-650" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white leading-tight">{{ ruta.conductor?.nombre || 'Sin asignar' }}</p>
                                    <p class="text-[10px] text-zinc-550 mt-0.5">{{ ruta.fecha_programada }} {{ ruta.hora_programada }}</p>
                                </div>
                            </div>

                            <!-- Justificación de cancelación -->
                            <div v-if="ruta.estado === 'cancelada'" class="mb-4 p-4 bg-rose-950/10 border border-rose-500/10 rounded-xl space-y-3">
                                <p class="text-xs font-bold text-rose-400 uppercase tracking-wider">Justificación de Cancelación:</p>
                                <p class="text-sm text-zinc-300 italic">"{{ ruta.motivo_cancelacion || 'Sin motivo especificado' }}"</p>
                                <div v-if="ruta.foto_cancelacion" class="mt-2">
                                    <p class="text-[10px] text-zinc-500 uppercase font-black mb-1.5">Evidencia Fotográfica:</p>
                                    <a :href="'/storage/' + ruta.foto_cancelacion" target="_blank" class="inline-block relative rounded-lg overflow-hidden border border-zinc-850 hover:border-zinc-700 transition group">
                                        <img :src="'/storage/' + ruta.foto_cancelacion" class="w-full max-h-32 object-cover group-hover:scale-105 transition duration-300">
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                            <span class="text-xs text-white font-bold bg-zinc-900/80 px-2 py-1 rounded">Ver Evidencia</span>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <!-- Desglose de Entregas -->
                            <div class="mb-4 p-4 bg-zinc-950/40 border border-zinc-850/80 rounded-xl space-y-3">
                                <div class="flex justify-between items-center border-b border-zinc-800/80 pb-2">
                                    <p class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Desglose de Entregas</p>
                                    <span class="text-xs font-bold text-zinc-300">
                                        {{ ruta.ventas.filter(v => v.estado_envio === 'entregado').length }} exitosas /
                                        {{ ruta.ventas.filter(v => v.estado_envio === 'nunca_entregado' || v.estado_entrega_geocerca === 'fallido').length }} fallidas
                                    </span>
                                </div>
                                <div class="space-y-1.5 max-h-40 overflow-y-auto">
                                    <div v-if="ruta.ventas.length === 0" class="text-xs text-zinc-500 italic">
                                        Ningún paquete fue entregado o fallido en esta ruta (cancelación total o sin entregas).
                                    </div>
                                    <div v-for="venta in ruta.ventas" :key="venta.id" class="flex justify-between items-center bg-zinc-900/40 p-2.5 rounded-lg text-xs border border-zinc-800/30">
                                        <div class="truncate pr-2 max-w-[70%]">
                                            <span class="font-mono text-zinc-500">#{{ venta.id }}</span>
                                            <span class="text-zinc-300 ml-2 font-bold">{{ venta.cliente?.nombre || 'Cliente' }}</span>
                                        </div>
                                        <div>
                                            <span v-if="venta.estado_envio === 'entregado'" class="text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded text-[9px] font-black border border-emerald-500/20">Entregado</span>
                                            <span v-else-if="venta.estado_envio === 'nunca_entregado' || venta.estado_entrega_geocerca === 'fallido'" class="text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded text-[9px] font-black border border-rose-500/20">Fallido</span>
                                            <span v-else class="text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded text-[9px] font-black border border-amber-500/20">{{ venta.estado_envio }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <button 
                                    @click="deleteRuta(ruta.id)"
                                    class="text-xs bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 border border-rose-500/20 px-3.5 py-2 rounded-xl transition duration-300 font-bold"
                                >
                                    {{ ruta.estado === 'cancelada' ? 'Eliminar Registro Cancelado' : 'Borrar del Historial' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 3: SUB-GRID DE REGISTROS DE ENTREGAS Y DEVUELTOS -->
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-2 gap-8 pb-16">
                <!-- REGISTRO DE ENTREGADOS -->
                <div class="bg-zinc-900 border border-zinc-800 shadow-xl rounded-2xl overflow-hidden flex flex-col h-[350px] transition hover:border-zinc-700/30">
                    <div class="p-5 border-b border-zinc-800 bg-zinc-950/40 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                        <h3 class="text-sm font-bold text-white uppercase tracking-widest">Entregados Hoy</h3>
                    </div>
                    <div class="overflow-y-auto flex-grow p-4 space-y-2.5 bg-zinc-950">
                        <div v-if="ventas_entregadas && ventas_entregadas.length === 0" class="text-center py-12 text-zinc-500 text-xs">
                            No hay entregas registradas hoy.
                        </div>
                        <div v-for="venta in ventas_entregadas" :key="venta.id" class="p-3 bg-zinc-900/60 border border-emerald-500/10 rounded-2xl flex justify-between items-center transition hover:bg-zinc-900 hover:border-emerald-500/25">
                            <div>
                                <p class="text-xs font-bold text-emerald-400 font-mono">TICKET #{{ venta.id }}</p>
                                <p class="text-sm font-black text-white mt-1 leading-tight">{{ venta.cliente.nombre }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-zinc-400 font-bold">Por: {{ venta.conductor?.nombre }}</p>
                                <p class="text-[9px] text-zinc-500 font-mono mt-0.5">{{ new Date(venta.updated_at).toLocaleString() }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PRODUCTOS NUNCA RETIRADOS -->
                <div class="bg-zinc-900 border border-zinc-800 shadow-xl rounded-2xl overflow-hidden flex flex-col h-[350px] transition hover:border-zinc-700/30">
                    <div class="p-5 border-b border-zinc-800 bg-zinc-950/40 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                        <h3 class="text-sm font-bold text-rose-500 uppercase tracking-widest">Productos Rebotados</h3>
                    </div>
                    <div class="overflow-y-auto flex-grow p-4 space-y-2.5 bg-zinc-950">
                        <div v-if="ventas_nunca_entregadas && ventas_nunca_entregadas.length === 0" class="text-center py-12 text-zinc-500 text-xs">
                            No hay productos rebotados.
                        </div>
                        <div v-for="venta in ventas_nunca_entregadas" :key="venta.id" class="p-3 bg-zinc-900/60 border border-rose-500/10 rounded-2xl flex justify-between items-center relative overflow-hidden transition hover:bg-zinc-900 hover:border-rose-500/25">
                            <div class="absolute top-0 right-0 left-0 h-0.5 bg-rose-500"></div>
                            <div class="min-w-0 pr-2">
                                <p class="text-xs font-bold text-rose-450 font-mono">TICKET #{{ venta.id }}</p>
                                <p class="text-sm font-black text-white mt-1 leading-tight truncate">{{ venta.cliente.nombre }}</p>
                                <p class="text-[10px] text-zinc-500 mt-1 leading-relaxed truncate">{{ venta.direccion }}</p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="text-[9px] font-black text-rose-500 bg-rose-500/10 px-2 py-0.5 rounded border border-rose-500/20">3 INTENTOS</span>
                                <p class="text-[9px] text-zinc-500 mt-1.5 font-mono">Fallo: {{ new Date(venta.updated_at).toLocaleDateString() }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Crear Ruta Manual -->
        <Teleport to="body">
            <div v-if="isCreateModalOpen" class="fixed inset-0 z-[1000] flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" @click="isCreateModalOpen = false"></div>
                <div class="relative w-full max-w-md bg-zinc-900 border border-zinc-800 rounded-3xl p-6 shadow-2xl overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-bl from-blue-500/10 to-transparent pointer-events-none"></div>

                    <h3 class="text-lg font-black text-white mb-4 uppercase tracking-wider flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                        Planificar Ruta Logística
                    </h3>
                    
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <!-- Errores de validación/creación general -->
                        <div v-if="$page.props.errors && Object.keys($page.props.errors).length > 0" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-semibold space-y-1">
                            <p class="font-bold">Error al crear ruta:</p>
                            <ul class="list-disc pl-4">
                                <li v-for="(err, key) in $page.props.errors" :key="key">{{ err }}</li>
                            </ul>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-400 mb-1.5 uppercase tracking-wide">Conductor Asignado (Opcional)</label>
                            <select v-model="form.conductor_id" class="w-full bg-zinc-950 border border-zinc-850 rounded-xl text-white text-sm p-3.5 focus:ring-zinc-700 font-medium cursor-pointer transition">
                                <option value="">Selecciona un conductor...</option>
                                <option v-for="c in availableConductoresList" :key="c.id" :value="c.id" :disabled="c.isBusy">
                                    {{ c.nombre }} ({{ c.vehiculo_tipo }}) {{ c.isBusy ? '- Ocupado en esta fecha' : '' }}
                                </option>
                            </select>
                            <span v-if="form.errors.conductor_id" class="text-rose-500 text-xs mt-1 block">{{ form.errors.conductor_id }}</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-400 mb-1.5 uppercase tracking-wide">Fecha Programada</label>
                            <input type="date" v-model="form.fecha_programada" required class="w-full bg-zinc-950 border border-zinc-850 rounded-xl text-white text-sm p-3.5 focus:ring-zinc-700 transition">
                        </div>

                        <div class="mt-6 flex justify-end gap-3 pt-5 border-t border-zinc-850">
                            <button type="button" @click="isCreateModalOpen = false" class="text-sm text-zinc-450 hover:text-white px-4 transition font-bold">Cancelar</button>
                            <button type="submit" :disabled="form.processing" class="bg-white hover:bg-zinc-200 text-zinc-950 px-6 py-3 rounded-xl text-sm font-black transition-all duration-300 shadow-lg shadow-white/5">
                                {{ form.processing ? 'Guardando Ruta...' : 'Despachar y Guardar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>
