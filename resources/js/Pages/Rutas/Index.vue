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

    map = window.L.map(mapContainer.value).setView([13.840204, -88.854427], 12);
    
    window.L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    markersLayer = window.L.layerGroup().addTo(map);

    // Marcador Almacén Base
    const baseIcon = window.L.divIcon({
        className: 'custom-icon',
        html: `<div style="background-color:#fff; width:16px; height:16px; border-radius:50%; border:4px solid #000; box-shadow: 0 0 10px rgba(255,255,255,0.5);"></div>`
    });
    window.L.marker([13.840204, -88.854427], { icon: baseIcon }).addTo(map).bindPopup('<b>Almacén Base</b>');

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
        
        const waypoints = [window.L.latLng(13.840204, -88.854427)]; // Inicia en Base
        selectedObjs.forEach(v => waypoints.push(window.L.latLng(v.latitud, v.longitud)));
        
        routingControl = window.L.Routing.control({
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
    hora_programada: '08:00',
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
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-white leading-tight">Control Maestro de Rutas</h2>
            </div>
        </template>

        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col lg:flex-row gap-6">
            
            <!-- Columna Izquierda: Pedidos Libres -->
            <div class="w-full lg:w-1/2 flex flex-col gap-6">
                <div class="bg-zinc-900 border border-zinc-800 shadow-sm sm:rounded-lg overflow-hidden flex flex-col h-[700px]">
                    <div class="p-6 border-b border-zinc-800 flex justify-between items-center bg-zinc-950/50">
                        <div>
                            <h3 class="text-sm font-bold text-white uppercase tracking-widest">Pedidos sin Asignar</h3>
                            <p class="text-xs text-zinc-400 mt-1">Selecciona los pedidos que deseas agrupar en una ruta.</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex bg-zinc-800 rounded-lg p-1">
                                <button @click="viewMode = 'list'" :class="{'bg-zinc-700 text-white': viewMode === 'list', 'text-zinc-400': viewMode !== 'list'}" class="px-3 py-1 text-xs font-bold rounded-md transition">Lista</button>
                                <button @click="viewMode = 'map'; loadLeaflet()" :class="{'bg-zinc-700 text-white': viewMode === 'map', 'text-zinc-400': viewMode !== 'map'}" class="px-3 py-1 text-xs font-bold rounded-md transition">Mapa</button>
                            </div>
                            <button 
                                @click="selectAll" 
                                class="text-xs text-blue-400 hover:text-blue-300 font-bold"
                            >
                                Sel. Todos
                            </button>
                        </div>
                    </div>

                    <!-- Vista de Lista -->
                    <div v-if="viewMode === 'list'" class="overflow-y-auto flex-grow p-4 space-y-3 bg-zinc-950">
                        <div v-if="ventas_pendientes.length === 0" class="text-center py-10 text-zinc-500">
                            No hay pedidos pendientes de envío.
                        </div>

                        <label v-for="venta in ventas_pendientes" :key="venta.id" 
                            class="flex items-start gap-4 p-4 border rounded-xl cursor-pointer transition-all hover:bg-zinc-900"
                            :class="selectedVentas.includes(venta.id) ? 'bg-blue-500/10 border-blue-500/50' : 'bg-zinc-900 border-zinc-800'"
                        >
                            <input 
                                type="checkbox" 
                                :value="venta.id" 
                                :checked="selectedVentas.includes(venta.id)"
                                @change="toggleSelection(venta.id)"
                                class="mt-1 rounded bg-zinc-800 border-zinc-700 text-blue-500 focus:ring-blue-500 focus:ring-offset-zinc-900"
                            >
                            <div class="flex-grow">
                                <div class="flex justify-between">
                                    <span class="text-xs font-bold text-white">{{ venta.id }}</span>
                                    <span class="text-[10px] text-zinc-400 bg-zinc-800 px-2 py-0.5 rounded">{{ venta.horario_entrega }}</span>
                                </div>
                                <div v-if="venta.intentos_entrega > 0" class="mt-1 mb-1">
                                    <span class="text-[10px] font-bold text-rose-500 bg-rose-500/10 px-2 py-0.5 rounded-full border border-rose-500/20">
                                        ⚠️ INTENTO FALLIDO ({{ venta.intentos_entrega }}/3)
                                    </span>
                                </div>
                                <p class="text-sm text-zinc-300 mt-1">{{ venta.cliente.nombre }}</p>
                                <p class="text-xs text-zinc-500 mt-0.5">{{ venta.direccion }}</p>
                            </div>
                        </label>
                    </div>

                    <!-- Vista de Mapa -->
                    <div v-show="viewMode === 'map'" class="flex-grow relative bg-zinc-950">
                        <div v-if="ventas_pendientes.length === 0" class="absolute inset-0 z-10 flex items-center justify-center bg-zinc-950/80 backdrop-blur-sm text-zinc-500">
                            No hay pedidos pendientes de envío para mapear.
                        </div>
                        <div ref="mapContainer" class="w-full h-full"></div>
                        <div class="absolute bottom-4 left-4 z-[400] bg-zinc-900/90 backdrop-blur border border-zinc-800 p-3 rounded-xl pointer-events-none">
                            <p class="text-xs text-zinc-300 font-bold mb-2">Leyenda</p>
                            <div class="flex items-center gap-2 mb-1"><div class="w-3 h-3 rounded-full bg-blue-500 border border-black"></div><span class="text-[10px] text-zinc-400">Sin Seleccionar</span></div>
                            <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-emerald-500 border border-black"></div><span class="text-[10px] text-zinc-400">Seleccionado</span></div>
                        </div>

                        <!-- Estadísticas Satelitales (Flotante) -->
                        <div v-if="routeStats && viewMode === 'map'" class="absolute top-4 right-4 z-[400] bg-zinc-900/95 backdrop-blur border border-zinc-800 p-4 rounded-xl pointer-events-none shadow-2xl min-w-[200px]">
                            <p class="text-[10px] text-emerald-400 font-black tracking-widest uppercase mb-3 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" /></svg>
                                Info Satelital OSRM
                            </p>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-zinc-400 font-bold">Distancia Vial</span>
                                    <span class="text-sm text-white font-mono font-bold">{{ routeStats.distance }} km</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-zinc-400 font-bold">Tiempo Total</span>
                                    <span class="text-sm text-white font-mono font-bold">{{ routeStats.timeStr }}</span>
                                </div>
                            </div>
                            <p class="text-[9px] text-zinc-500 mt-3 italic text-center">*Incluye tráfico vial y 15 min de maniobra por entrega</p>
                        </div>
                    </div>

                    <div class="p-4 border-t border-zinc-800 bg-zinc-900">
                        <button 
                            @click="openCreateModal"
                            :disabled="selectedVentas.length === 0"
                            class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl disabled:opacity-50 transition-all flex justify-center items-center gap-2"
                        >
                            <span>Agrupar {{ selectedVentas.length }} pedidos en Nueva Ruta</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Rutas Creadas -->
            <div class="w-full lg:w-1/2 flex flex-col gap-6">
                <div class="bg-zinc-900 border border-zinc-800 shadow-sm sm:rounded-lg overflow-hidden flex flex-col h-[700px]">
                    <div class="p-6 border-b border-zinc-800 bg-zinc-950/50">
                        <h3 class="text-sm font-bold text-white uppercase tracking-widest">Rutas Logísticas</h3>
                        <p class="text-xs text-zinc-400 mt-1">Gestión de rutas asignadas a conductores.</p>
                    </div>

                    <div class="overflow-y-auto flex-grow p-4 space-y-4 bg-zinc-950">
                        <div v-if="rutas.length === 0" class="text-center py-10 text-zinc-500">
                            No se han creado rutas aún.
                        </div>

                        <div v-for="ruta in rutas" :key="ruta.id" class="p-5 border border-zinc-800 rounded-2xl bg-zinc-900 relative overflow-hidden group">
                            
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h4 class="text-white font-bold text-lg">{{ ruta.nombre }}</h4>
                                    <p class="text-xs text-zinc-400">{{ ruta.ventas.length }} pedidos en la ruta</p>
                                </div>
                                <span class="text-[10px] font-bold px-2.5 py-1 rounded-md uppercase"
                                    :class="{
                                        'bg-zinc-800 text-zinc-400': ruta.estado === 'creada',
                                        'bg-blue-500/20 text-blue-400 border border-blue-500/30': ruta.estado === 'en_curso',
                                        'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30': ruta.estado === 'finalizada'
                                    }">
                                    {{ ruta.estado }}
                                </span>
                            </div>

                            <div class="flex items-center gap-3 mb-4 bg-zinc-950/50 p-3 rounded-xl border border-zinc-800/50">
                                <img v-if="ruta.conductor?.foto_url" :src="ruta.conductor.foto_url" class="w-8 h-8 rounded-full border border-zinc-700">
                                <div class="w-8 h-8 rounded-full border border-zinc-700 bg-zinc-800 flex items-center justify-center" v-else>
                                    <svg class="w-4 h-4 text-zinc-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white">{{ ruta.conductor?.nombre || 'Sin asignar' }}</p>
                                    <p class="text-[10px] text-zinc-500">{{ ruta.fecha_programada }} {{ ruta.hora_programada }}</p>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <button 
                                    v-if="ruta.estado === 'creada' || ruta.estado === 'finalizada'"
                                    @click="deleteRuta(ruta.id)"
                                    class="text-xs bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 border border-rose-500/20 px-3 py-1.5 rounded-lg transition"
                                >
                                    {{ ruta.estado === 'creada' ? 'Deshacer Ruta' : 'Borrar del Historial' }}
                                </button>
                                <div v-if="ruta.estado === 'en_curso'" class="text-xs text-blue-400">
                                    El conductor está navegando.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN ENTREGADOS -->
                <div class="bg-zinc-900 border border-zinc-800 shadow-sm sm:rounded-lg overflow-hidden flex flex-col h-[300px]">
                    <div class="p-4 border-b border-zinc-800 bg-zinc-950/50 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                        <h3 class="text-sm font-bold text-white uppercase tracking-widest">Historial de Entregados</h3>
                    </div>
                    <div class="overflow-y-auto flex-grow p-4 space-y-2 bg-zinc-950">
                        <div v-if="ventas_entregadas && ventas_entregadas.length === 0" class="text-center py-6 text-zinc-500 text-xs">
                            No hay entregas registradas aún.
                        </div>
                        <div v-for="venta in ventas_entregadas" :key="venta.id" class="p-3 bg-zinc-900 border border-emerald-500/20 rounded-xl flex justify-between items-center">
                            <div>
                                <p class="text-xs font-bold text-emerald-400">{{ venta.id }}</p>
                                <p class="text-[10px] text-zinc-400">{{ venta.cliente.nombre }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-zinc-500">Entregado por: {{ venta.conductor?.nombre }}</p>
                                <p class="text-[9px] text-zinc-600">{{ new Date(venta.updated_at).toLocaleString() }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN NUNCA ENTREGADOS (FALLIDOS 3 VECES) -->
                <div class="bg-zinc-900 border border-zinc-800 shadow-sm sm:rounded-lg overflow-hidden flex flex-col h-[300px]">
                    <div class="p-4 border-b border-zinc-800 bg-zinc-950/50 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <h3 class="text-sm font-bold text-rose-500 uppercase tracking-widest">Productos Nunca Retirados</h3>
                    </div>
                    <div class="overflow-y-auto flex-grow p-4 space-y-2 bg-zinc-950">
                        <div v-if="ventas_nunca_entregadas && ventas_nunca_entregadas.length === 0" class="text-center py-6 text-zinc-500 text-xs">
                            No hay productos rebotados.
                        </div>
                        <div v-for="venta in ventas_nunca_entregadas" :key="venta.id" class="p-3 bg-zinc-900 border border-rose-500/50 rounded-xl flex justify-between items-center relative overflow-hidden">
                            <div class="absolute top-0 right-0 left-0 h-0.5 bg-rose-500"></div>
                            <div>
                                <p class="text-xs font-bold text-rose-400">{{ venta.id }}</p>
                                <p class="text-[10px] text-zinc-400">{{ venta.cliente.nombre }}</p>
                                <p class="text-[10px] text-zinc-500 mt-1">{{ venta.direccion }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-[9px] font-bold text-rose-500 bg-rose-500/10 px-2 py-0.5 rounded-full border border-rose-500/20">3 INTENTOS</span>
                                <p class="text-[9px] text-zinc-600 mt-1">Rechazado: {{ new Date(venta.updated_at).toLocaleDateString() }}</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- Modal Crear Ruta Manual -->
        <div v-if="isCreateModalOpen" class="fixed inset-0 z-[1000] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" @click="isCreateModalOpen = false"></div>
            <div class="relative w-full max-w-md bg-zinc-900 border border-zinc-800 rounded-2xl p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-white mb-4">Crear Nueva Ruta Logística</h3>
                
                <form @submit.prevent="submitForm" class="space-y-4">

                    <div>
                        <label class="block text-xs font-bold text-zinc-400 mb-1">Conductor Asignado (Opcional)</label>
                        <select v-model="form.conductor_id" class="w-full bg-zinc-950 border border-zinc-800 rounded-xl text-white text-sm p-3 focus:ring-zinc-700">
                            <option value="">Selecciona un conductor...</option>
                            <option v-for="c in availableConductoresList" :key="c.id" :value="c.id" :disabled="c.isBusy">
                                {{ c.nombre }} ({{ c.vehiculo_tipo }}) {{ c.isBusy ? '- Ocupado en esta fecha' : '' }}
                            </option>
                        </select>
                        <span v-if="form.errors.conductor_id" class="text-rose-500 text-xs mt-1 block">{{ form.errors.conductor_id }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-zinc-400 mb-1">Fecha</label>
                            <input type="date" v-model="form.fecha_programada" required class="w-full bg-zinc-950 border border-zinc-800 rounded-xl text-white text-sm p-3 focus:ring-zinc-700">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-zinc-400 mb-1">Hora Salida</label>
                            <input type="time" v-model="form.hora_programada" required class="w-full bg-zinc-950 border border-zinc-800 rounded-xl text-white text-sm p-3 focus:ring-zinc-700">
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-zinc-800">
                        <button type="button" @click="isCreateModalOpen = false" class="text-sm text-zinc-400 hover:text-white px-4">Cancelar</button>
                        <button type="submit" :disabled="form.processing" class="bg-white text-zinc-900 px-6 py-2.5 rounded-xl text-sm font-bold hover:bg-zinc-200">
                            {{ form.processing ? 'Guardando Ruta...' : 'Guardar y Asignar Ruta' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
