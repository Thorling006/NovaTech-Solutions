<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, nextTick, computed, watch } from 'vue';

const props = defineProps({
    conductor: Object,
    rutas: Array,
    errors: Object
});

const mapContainer = ref(null);
let map = null;
let markersLayer = null;
let polylineLayer = null;
let driverMarker = null;

// Base coordinates default (San Miguel)
const currentLat = ref(13.840204);
const currentLng = ref(-88.854427);
let geoWatchId = null;

// Rastro gris (historial de ubicaciones)
const pastPositions = ref([]);
let routingControl = null;
let rastroPolyline = null;

const activeRuta = computed(() => {
    return props.rutas.find(r => r.estado === 'en_curso') || null;
});

const pendingRutas = computed(() => {
    return props.rutas.filter(r => r.estado === 'creada');
});

const activeVenta = computed(() => {
    if (!activeRuta.value) return null;
    
    // Sort logically to ensure we grab the correct active point (orden_ruta)
    const sortedVentas = [...activeRuta.value.ventas].sort((a, b) => a.orden_ruta - b.orden_ruta);
    
    return sortedVentas.find(v => v.estado_entrega_geocerca === 'en_camino' || v.estado_entrega_geocerca === 'en_el_punto') 
        || sortedVentas.find(v => v.estado_entrega_geocerca === 'pendiente');
});

// Helper for UI: Next stops and completed stops
const remainingVentas = computed(() => {
    if (!activeRuta.value || !activeVenta.value) return [];
    const sortedVentas = [...activeRuta.value.ventas].sort((a, b) => a.orden_ruta - b.orden_ruta);
    return sortedVentas.filter(v => v.orden_ruta > activeVenta.value.orden_ruta && v.estado_entrega_geocerca !== 'entregado' && v.estado_entrega_geocerca !== 'fallido');
});

const completedVentas = computed(() => {
    if (!activeRuta.value) return [];
    return [...activeRuta.value.ventas].sort((a, b) => a.orden_ruta - b.orden_ruta).filter(v => v.estado_entrega_geocerca === 'entregado' || v.estado_entrega_geocerca === 'fallido');
});

const initMap = () => {
    if (!window.L || !mapContainer.value) return;

    map = window.L.map(mapContainer.value, { zoomControl: false }).setView([currentLat.value, currentLng.value], 14);

    window.L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; CARTO'
    }).addTo(map);

    markersLayer = window.L.featureGroup().addTo(map);
    polylineLayer = window.L.featureGroup().addTo(map);
    
    const driverIcon = window.L.divIcon({
        className: 'custom-icon',
        html: `<div style="background-color:#3b82f6; width:24px; height:24px; border-radius:50%; border:4px solid #fff; box-shadow: 0 0 15px #3b82f6;"></div>`
    });
    driverMarker = window.L.marker([currentLat.value, currentLng.value], { icon: driverIcon, zIndexOffset: 1000 }).addTo(map);

    drawRuta();
};

// Lógica para Cancelar Ruta
const isCancelModalOpen = ref(false);
const formCancelar = useForm({
    motivo: '',
    foto: null
});

const openCancelModal = () => {
    isCancelModalOpen.value = true;
    formCancelar.reset();
    formCancelar.clearErrors();
};

const submitCancel = () => {
    if (!activeRuta.value) return;
    formCancelar.post(route('conductor.ruta.cancelar', activeRuta.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isCancelModalOpen.value = false;
        }
    });
};

const drawRuta = () => {
    if (!map || !activeRuta.value) return;
    
    markersLayer.clearLayers();
    polylineLayer.clearLayers();

    // Rastro Gris
    if (rastroPolyline) {
        map.removeLayer(rastroPolyline);
    }
    if (pastPositions.value.length > 0) {
        rastroPolyline = window.L.polyline(pastPositions.value, { color: '#71717a', weight: 4, opacity: 0.5 }).addTo(polylineLayer);
    }

    const latlngs = [];
    
    // Almacen Base
    latlngs.push([13.840204, -88.854427]);
    const baseIcon = window.L.divIcon({
        className: 'custom-icon',
        html: `<div style="background-color:#fff; width:14px; height:14px; border-radius:50%; border:3px solid #000;"></div>`
    });
    window.L.marker([13.840204, -88.854427], { icon: baseIcon }).addTo(markersLayer).bindPopup('Almacén');

    activeRuta.value.ventas.forEach((venta) => {
        latlngs.push([venta.latitud, venta.longitud]);
        
        let color = '#71717a'; // zinc-500 future
        if (venta.estado_entrega_geocerca === 'en_camino' || venta.estado_entrega_geocerca === 'en_el_punto') color = '#f59e0b'; // amber active
        if (venta.estado_entrega_geocerca === 'entregado') color = '#10b981'; // emerald done
        if (venta.estado_entrega_geocerca === 'fallido') color = '#ef4444'; // red failed

        const icon = window.L.divIcon({
            className: 'custom-icon',
            html: `<div style="background-color:${color}; width:20px; height:20px; border-radius:50%; border:2px solid #000; color:#fff; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:bold;">${venta.orden_ruta}</div>`
        });

        window.L.marker([venta.latitud, venta.longitud], { icon })
            .addTo(markersLayer)
            .bindPopup(`<b>Punto ${venta.orden_ruta}</b><br>${venta.cliente.nombre}`);
    });

    // Waze turn-by-turn logic
    if (routingControl) {
        map.removeControl(routingControl);
        routingControl = null;
    }

    if (activeRuta.value) {
        // Obtenemos los destinos pendientes (que no están entregados ni fallidos) ordenados lógicamente
        const waypoints = [window.L.latLng(currentLat.value, currentLng.value)];
        
        const sortedVentas = [...activeRuta.value.ventas].sort((a, b) => a.orden_ruta - b.orden_ruta);
        
        sortedVentas.forEach((v) => {
            if (v.estado_entrega_geocerca !== 'entregado' && v.estado_entrega_geocerca !== 'fallido') {
                waypoints.push(window.L.latLng(v.latitud, v.longitud));
            }
        });

        // Rutear desde GPS actual hasta todos los destinos pendientes
        if (waypoints.length > 1) {
            routingControl = window.L.Routing.control({
                waypoints: waypoints,
                routeWhileDragging: false,
                addWaypoints: false,
                fitSelectedRoutes: true,
                showAlternatives: false,
                lineOptions: {
                    styles: [{color: '#3b82f6', opacity: 0.8, weight: 6}]
                },
                createMarker: function() { return null; } // Ocultar marcadores por defecto del routing
            }).addTo(map);
        }
    } else {
        // Si no hay ruta activa, usar polilínea recta para mostrar todo
        window.L.polyline(latlngs, { color: '#3b82f6', weight: 4, opacity: 0.6, dashArray: '8, 8' }).addTo(polylineLayer);
        if (latlngs.length > 0) {
            map.fitBounds(window.L.polyline(latlngs).getBounds(), { padding: [50, 50] });
        }
    }
};

const updateDriverPosition = (pos) => {
    currentLat.value = pos.coords.latitude;
    currentLng.value = pos.coords.longitude;
    
    // Guardar en el rastro gris
    pastPositions.value.push([currentLat.value, currentLng.value]);

    if (driverMarker) {
        driverMarker.setLatLng([currentLat.value, currentLng.value]);
    }

    // Actualizar Routing (recalcular en vivo)
    if (routingControl) {
        routingControl.spliceWaypoints(0, 1, window.L.latLng(currentLat.value, currentLng.value));
    } else {
        drawRuta();
    }
};

const loadLeaflet = () => {
    if (window.L && window.L.Routing) {
        initMap();
        return;
    }
    
    // CSS Base
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(link);
    
    // CSS Routing Machine
    const linkRouting = document.createElement('link');
    linkRouting.rel = 'stylesheet';
    linkRouting.href = 'https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css';
    document.head.appendChild(linkRouting);
    
    // JS Base
    const script = document.createElement('script');
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    script.onload = () => {
        // JS Routing Machine
        const scriptRouting = document.createElement('script');
        scriptRouting.src = 'https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js';
        scriptRouting.onload = () => nextTick(() => initMap());
        document.head.appendChild(scriptRouting);
    };
    document.head.appendChild(script);
};

onMounted(() => {
    loadLeaflet();
    if (navigator.geolocation) {
        geoWatchId = navigator.geolocation.watchPosition(updateDriverPosition, (err) => {
            console.warn("GPS no disponible o denegado, usando base por defecto.");
        }, { enableHighAccuracy: true });
    }
});

onUnmounted(() => {
    if (geoWatchId) navigator.geolocation.clearWatch(geoWatchId);
});

watch(() => props.rutas, () => {
    if (activeRuta.value) {
        nextTick(() => {
            if (!mapContainer.value) return;
            if (!map) initMap();
            else drawRuta();
        });
    }
}, { deep: true });

// Helper function to calculate distance in km
const getDistanceFromLatLonInKm = (lat1, lon1, lat2, lon2) => {
    var R = 6371; 
    var dLat = (lat2-lat1) * (Math.PI/180);
    var dLon = (lon2-lon1) * (Math.PI/180); 
    var a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(lat1 * (Math.PI/180)) * Math.cos(lat2 * (Math.PI/180)) * 
        Math.sin(dLon/2) * Math.sin(dLon/2); 
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
    return R * c;
};

const iniciarRuta = (rutaId) => {
    const distToBase = getDistanceFromLatLonInKm(currentLat.value, currentLng.value, 13.840204, -88.854427);
    
    // Si está a más de 500 metros (0.5 km) de la base
    if (distToBase > 0.5) {
        const warning = "⚠️ ADVERTENCIA ESTRICTA ⚠️\n\nEstás a " + Math.round(distToBase * 1000) + " metros de la sucursal base.\n\nPuedes iniciar la ruta desde tu ubicación actual, pero es tu ABSOLUTA RESPONSABILIDAD cumplir con la entrega de todos los paquetes asignados en los tiempos previstos. \n\n¿Aceptas la responsabilidad e iniciar la ruta?";
        if (!confirm(warning)) {
            return;
        }
    }
    
    useForm({}).post(route('conductor.ruta.iniciar', rutaId));
};

const formLlegar = useForm({ lat: 0, lng: 0 });
const llegarAlPunto = (ventaId) => {
    formLlegar.lat = currentLat.value;
    formLlegar.lng = currentLng.value;
    formLlegar.post(route('conductor.venta.llegar', ventaId), {
        preserveScroll: true
    });
};

const formFinalizar = useForm({ resultado: '' });
const finalizarPunto = (ventaId, res) => {
    if(!confirm(`¿Marcar como ${res.toUpperCase()}?`)) return;
    formFinalizar.resultado = res;
    formFinalizar.post(route('conductor.venta.finalizar', ventaId), {
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Navegación Logística" />

    <AuthenticatedLayout>
        <div class="h-[calc(100vh-65px)] flex flex-col md:flex-row w-full bg-zinc-950 overflow-hidden">
            
            <!-- Estado sin ruta activa -->
            <div v-if="!activeRuta" class="w-full h-full flex flex-col items-center justify-center p-6 text-center">
                <div class="w-24 h-24 rounded-full bg-zinc-900 border border-zinc-800 flex items-center justify-center mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-white mb-2">No estás en ruta</h2>
                <p class="text-zinc-400 mb-8 max-w-md">Selecciona una de las rutas programadas que el administrador te ha asignado para comenzar la navegación GPS.</p>
                
                <div class="w-full max-w-md space-y-4">
                    <div v-if="pendingRutas.length === 0" class="p-6 border border-zinc-800 rounded-2xl bg-zinc-900/50">
                        <p class="text-zinc-500 text-sm">No tienes rutas pendientes asignadas.</p>
                    </div>
                    
                    <div v-for="ruta in pendingRutas" :key="ruta.id" class="p-5 border border-zinc-700 bg-zinc-800 rounded-2xl text-left flex justify-between items-center group hover:border-zinc-600 transition">
                        <div>
                            <h3 class="font-bold text-white text-lg">{{ ruta.nombre }}</h3>
                            <p class="text-xs text-zinc-400 mt-1">{{ ruta.fecha_programada }} • {{ ruta.ventas.length }} puntos de entrega</p>
                        </div>
                        <button 
                            @click="iniciarRuta(ruta.id)"
                            class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-2.5 px-6 rounded-xl transition"
                        >
                            Iniciar Ruta
                        </button>
                    </div>
                </div>
            </div>

            <!-- Interfaz Waze (En Ruta) -->
            <div v-if="activeRuta" class="w-full h-full relative flex flex-col md:flex-row">
                
                <!-- MAPA -->
                <div ref="mapContainer" class="w-full h-1/2 md:h-full md:flex-grow bg-zinc-900 relative z-0"></div>

                <!-- PANEL LATERAL / INFERIOR (Punto Actual) -->
                <div class="w-full h-1/2 md:h-full md:w-[400px] bg-zinc-950 border-t md:border-t-0 md:border-l border-zinc-800 flex flex-col z-10 shadow-2xl">
                    
                    <div class="p-5 border-b border-zinc-800 bg-zinc-900">
                        <div class="flex justify-between items-center mb-1">
                            <h2 class="font-black text-white text-lg uppercase tracking-tight">{{ activeRuta.nombre }}</h2>
                            <span class="bg-blue-500/20 text-blue-400 text-[10px] font-bold px-2 py-1 rounded-md uppercase border border-blue-500/30 animate-pulse">En Curso</span>
                        </div>
                        <p class="text-xs text-zinc-400">Progreso: {{ activeRuta.ventas.filter(v => v.estado_entrega_geocerca === 'entregado' || v.estado_entrega_geocerca === 'fallido').length }} / {{ activeRuta.ventas.length }} puntos</p>
                    </div>

                    <!-- Punto Activo (Destino) -->
                    <div v-if="activeVenta" class="flex-grow overflow-y-auto p-5 flex flex-col justify-start">
                        <div class="mb-6 p-4 bg-zinc-950 border border-zinc-800 rounded-2xl relative overflow-hidden">
                            <div class="absolute top-0 right-0 p-2 opacity-10">
                                <svg class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                            </div>
                            <h3 class="text-xs font-bold text-blue-500 uppercase tracking-widest mb-2">Destino Actual (Punto {{ activeVenta.orden_ruta }})</h3>
                            <p class="text-xl font-black text-white leading-tight">{{ activeVenta.cliente.nombre }}</p>
                            <p class="text-sm text-zinc-400 mt-2">{{ activeVenta.direccion }}</p>
                            
                            <!-- Información de Contacto y Pago -->
                            <div class="grid grid-cols-2 gap-3 mt-4">
                                <a :href="'tel:' + (activeVenta.cliente.telefono || '00000000')" class="flex items-center gap-2 bg-zinc-900 border border-zinc-700 hover:border-blue-500 p-2.5 rounded-xl transition cursor-pointer">
                                    <div class="w-8 h-8 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" /></svg>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-zinc-500 uppercase font-bold">Llamar</p>
                                        <p class="text-xs text-white font-mono">{{ activeVenta.cliente.telefono || 'Sin Número' }}</p>
                                    </div>
                                </a>
                                <div class="flex items-center gap-2 bg-zinc-900 border border-zinc-700 p-2.5 rounded-xl">
                                    <div class="w-8 h-8 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" /><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" /></svg>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-zinc-500 uppercase font-bold">Pago</p>
                                        <p class="text-xs text-white capitalize">{{ activeVenta.metodo_pago || 'Efectivo' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-zinc-500 font-mono text-center mb-2">Ticket: {{ activeVenta.id }}</p>

                        <!-- Errores de Geocerca -->
                        <div v-if="props.errors.geocerca" class="mb-4 p-4 rounded-xl bg-rose-500/10 border border-rose-500/50 text-rose-400 text-sm text-center font-medium animate-shake">
                            {{ props.errors.geocerca }}
                        </div>

                        <!-- Acciones -->
                        <div class="space-y-3 mt-4">
                            <!-- En Camino -> Llegar -->
                            <button 
                                v-if="activeVenta.estado_entrega_geocerca === 'en_camino'"
                                @click="llegarAlPunto(activeVenta.id)"
                                :disabled="formLlegar.processing"
                                class="w-full bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg transition flex justify-center items-center gap-2"
                            >
                                <span v-if="formLlegar.processing" class="w-5 h-5 border-2 border-white/20 border-t-white rounded-full animate-spin"></span>
                                Registrar Llegada al Punto
                            </button>

                            <!-- En Punto -> Finalizar -->
                            <div v-if="activeVenta.estado_entrega_geocerca === 'en_el_punto'" class="flex gap-3">
                                <button 
                                    @click="finalizarPunto(activeVenta.id, 'entregado')"
                                    :disabled="formFinalizar.processing"
                                    class="flex-1 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg shadow-emerald-500/20 transition flex justify-center items-center gap-2"
                                >
                                    <span v-if="formFinalizar.processing && formFinalizar.resultado === 'entregado'" class="w-5 h-5 border-2 border-white/20 border-t-white rounded-full animate-spin"></span>
                                    Entrega Exitosa
                                </button>
                                <button 
                                    @click="finalizarPunto(activeVenta.id, 'fallido')"
                                    :disabled="formFinalizar.processing"
                                    class="flex-1 bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg shadow-rose-500/20 transition flex justify-center items-center gap-2"
                                >
                                    <span v-if="formFinalizar.processing && formFinalizar.resultado === 'fallido'" class="w-5 h-5 border-2 border-white/20 border-t-white rounded-full animate-spin"></span>
                                    Fallido
                                </button>
                            </div>
                        </div>

                        <!-- Resto de Paradas (Itinerario Completo) -->
                        <div v-if="remainingVentas.length > 0 || completedVentas.length > 0" class="mt-8 border-t border-zinc-800 pt-6">
                            <h3 class="text-xs font-bold text-zinc-500 uppercase tracking-widest mb-4">Itinerario Completo</h3>
                            
                            <!-- Próximos -->
                            <div class="space-y-3 mb-6">
                                <div v-for="venta in remainingVentas" :key="venta.id" class="flex items-center gap-3 p-3 bg-zinc-900/50 border border-zinc-800/50 rounded-xl opacity-75">
                                    <div class="w-8 h-8 rounded-full bg-zinc-800 flex flex-shrink-0 items-center justify-center text-zinc-400 font-bold text-xs border border-zinc-700">
                                        {{ venta.orden_ruta }}
                                    </div>
                                    <div class="flex-grow overflow-hidden">
                                        <p class="text-sm font-bold text-zinc-300 truncate">{{ venta.cliente.nombre }}</p>
                                        <p class="text-xs text-zinc-500 truncate">{{ venta.direccion }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Completados -->
                            <div v-if="completedVentas.length > 0" class="space-y-3 opacity-50">
                                <h3 class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest mb-2">Completados</h3>
                                <div v-for="venta in completedVentas" :key="'c_'+venta.id" class="flex items-center gap-3 p-2 bg-emerald-900/10 border border-emerald-900/30 rounded-xl">
                                    <div class="w-6 h-6 rounded-full bg-emerald-500/20 flex flex-shrink-0 items-center justify-center text-emerald-500 font-bold text-[10px]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                    </div>
                                    <div class="flex-grow overflow-hidden">
                                        <p class="text-xs text-emerald-500 truncate line-through">{{ venta.cliente.nombre }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Todos Completados / Sin Activos -->
                    <div v-else class="flex-grow flex flex-col items-center justify-center p-6 text-center text-zinc-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-4 text-emerald-500/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <p class="font-medium text-white mb-1">Punto finalizado</p>
                        <p class="text-xs">Cargando siguiente destino o finalizando ruta...</p>
                    </div>

                    <!-- Botón Cancelar Ruta Maestra -->
                    <div class="mt-auto border-t border-zinc-800 pt-4 pb-2">
                        <button 
                            @click="openCancelModal"
                            class="w-full bg-transparent hover:bg-rose-500/10 border border-rose-500/30 text-rose-500 font-bold py-3 rounded-xl transition flex items-center justify-center gap-2"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                            Cancelar Toda la Ruta (Emergencia)
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Cancelar Ruta -->
        <div v-if="isCancelModalOpen" class="fixed inset-0 z-[999] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
            <div class="bg-zinc-900 border border-rose-500/30 rounded-2xl p-6 w-full max-w-md shadow-2xl relative overflow-hidden">
                <!-- Header -->
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-full bg-rose-500/20 flex items-center justify-center text-rose-500 flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-white leading-tight">Cancelar Ruta</h3>
                        <p class="text-xs text-rose-400 font-bold">Acción irreversible</p>
                    </div>
                </div>
                
                <p class="text-sm text-zinc-400 mb-6">Al cancelar la ruta, todos los paquetes pendientes serán devueltos a la central para ser reasignados. Esta acción requiere justificación y evidencia.</p>
                
                <form @submit.prevent="submitCancel" class="space-y-5 relative z-10">
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-2">Motivo de la Cancelación</label>
                        <textarea 
                            v-model="formCancelar.motivo"
                            rows="3" 
                            required
                            placeholder="Ej. Llanta ponchada, accidente, lluvia extrema..."
                            class="w-full bg-zinc-950 border border-zinc-800 rounded-xl text-white text-sm p-3 focus:ring-rose-500 focus:border-rose-500"
                        ></textarea>
                        <div v-if="formCancelar.errors.motivo" class="text-rose-500 text-xs mt-1">{{ formCancelar.errors.motivo }}</div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-2">Fotografía de Evidencia (Obligatorio)</label>
                        <div class="relative w-full border-2 border-dashed border-zinc-700 rounded-xl bg-zinc-950 hover:bg-zinc-900 transition flex flex-col items-center justify-center p-4 cursor-pointer" :class="{'border-rose-500': formCancelar.foto}">
                            <input 
                                type="file" 
                                required 
                                accept="image/*"
                                capture="environment"
                                @change="(e) => formCancelar.foto = e.target.files[0]"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                            >
                            <svg v-if="!formCancelar.foto" xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-zinc-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-rose-500 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            <span class="text-sm font-bold" :class="formCancelar.foto ? 'text-rose-500' : 'text-zinc-500'">{{ formCancelar.foto ? 'Foto Seleccionada' : 'Tocar para abrir cámara/galería' }}</span>
                        </div>
                        <div v-if="formCancelar.errors.foto" class="text-rose-500 text-xs mt-1">{{ formCancelar.errors.foto }}</div>
                    </div>

                    <div v-if="formCancelar.errors.ruta" class="p-3 bg-rose-500/10 border border-rose-500/30 rounded-lg text-rose-500 text-xs">
                        {{ formCancelar.errors.ruta }}
                    </div>

                    <div class="flex gap-3 pt-2 border-t border-zinc-800">
                        <button type="button" @click="isCancelModalOpen = false" class="flex-1 bg-zinc-800 hover:bg-zinc-700 text-white font-bold py-3 rounded-xl transition text-sm">
                            Volver
                        </button>
                        <button type="submit" :disabled="formCancelar.processing" class="flex-1 bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white font-bold py-3 rounded-xl transition text-sm flex justify-center items-center gap-2">
                            <span v-if="formCancelar.processing" class="w-4 h-4 border-2 border-white/20 border-t-white rounded-full animate-spin"></span>
                            Confirmar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}
.animate-shake {
    animation: shake 0.3s ease-in-out;
}

/* Modo Oscuro para el panel de instrucciones de Waze (Leaflet Routing Machine) */
:deep(.leaflet-routing-container) {
    background-color: #18181b !important; /* zinc-900 */
    color: #a1a1aa !important; /* zinc-400 */
    border: 1px solid #27272a !important; /* zinc-800 */
    border-radius: 0.75rem !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.8) !important;
    padding-bottom: 5px !important;
    margin-right: 15px !important;
}

:deep(.leaflet-routing-alt) {
    background-color: transparent !important;
}

:deep(.leaflet-routing-alt h2) {
    color: #f4f4f5 !important; /* zinc-100 */
    font-size: 14px !important;
    font-weight: bold !important;
    font-family: inherit !important;
}

:deep(.leaflet-routing-alt h3) {
    color: #d4d4d8 !important;
    font-family: inherit !important;
}

:deep(.leaflet-routing-container table) {
    color: #a1a1aa !important;
}

:deep(.leaflet-routing-icon) {
    /* Invierte el color de los iconos de navegación para que se vean en fondo oscuro */
    filter: invert(0.8) brightness(2);
}

:deep(.leaflet-routing-alt tr:hover) {
    background-color: #27272a !important; /* zinc-800 */
    cursor: pointer;
}
</style>
