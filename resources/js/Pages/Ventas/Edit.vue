<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref, onMounted, nextTick } from 'vue';

const props = defineProps({ 
    venta: Object 
});

const form = useForm({
    cliente: {
        nombre: props.venta.cliente?.nombre || '',
        correo: props.venta.cliente?.correo || '',
        telefono: props.venta.cliente?.telefono || '',
    },
    direccion: props.venta.direccion || '',
    latitud: parseFloat(props.venta.latitud) || 13.6929,
    longitud: parseFloat(props.venta.longitud) || -89.2182,
    horario_entrega: props.venta.horario_entrega || 'morning'
});

// Integración de Mapa Leaflet
let leafletMap = null;
let leafletMarker = null;

const loadLeaflet = () => {
    if (window.L) {
        initMap();
        return;
    }
    
    // Inyectar CSS
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(link);
    
    // Inyectar JS
    const script = document.createElement('script');
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    script.onload = () => {
        initMap();
    };
    document.head.appendChild(script);
};

const initMap = () => {
    nextTick(() => {
        const container = document.getElementById('edit-delivery-map');
        if (!container) return;

        if (leafletMap) {
            leafletMap.remove();
            leafletMap = null;
        }

        leafletMap = window.L.map('edit-delivery-map').setView([form.latitud, form.longitud], 14);

        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(leafletMap);

        leafletMarker = window.L.marker([form.latitud, form.longitud], {
            draggable: true
        }).addTo(leafletMap);

        leafletMarker.on('dragend', () => {
            const pos = leafletMarker.getLatLng();
            form.latitud = pos.lat;
            form.longitud = pos.lng;
        });

        leafletMap.on('click', (e) => {
            leafletMarker.setLatLng(e.latlng);
            form.latitud = e.latlng.lat;
            form.longitud = e.latlng.lng;
        });

        setTimeout(() => {
            leafletMap.invalidateSize();
        }, 200);
    });
};

onMounted(() => {
    loadLeaflet();
});

const submit = () => {
    form.put(route('ventas.update', props.venta.id));
};
</script>

<template>
    <Head :title="`Editar Venta #${venta.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-white leading-tight">Editar Venta {{ venta.id }}</h2>
                <Link :href="route('movimientos.index')" class="text-sm link-action">← Volver a Movimientos</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="card p-8">
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <!-- Sección 1: Cliente -->
                        <div class="space-y-4">
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider border-b border-zinc-800 pb-2">Información del Cliente</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <div>
                                    <InputLabel for="nombre" value="Nombre Completo" />
                                    <TextInput id="nombre" type="text" class="mt-1 block w-full" v-model="form.cliente.nombre" required />
                                    <InputError class="mt-2" :message="form.errors['cliente.nombre']" />
                                </div>
                                <div>
                                    <InputLabel for="correo" value="Correo Electrónico" />
                                    <TextInput id="correo" type="email" class="mt-1 block w-full" v-model="form.cliente.correo" required />
                                    <InputError class="mt-2" :message="form.errors['cliente.correo']" />
                                </div>
                                <div>
                                    <InputLabel for="telefono" value="Teléfono" />
                                    <TextInput id="telefono" type="text" class="mt-1 block w-full" v-model="form.cliente.telefono" />
                                    <InputError class="mt-2" :message="form.errors['cliente.telefono']" />
                                </div>
                            </div>
                        </div>

                        <!-- Sección 2: Dirección y Mapa -->
                        <div class="space-y-4 pt-4 border-t border-zinc-800/50">
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider border-b border-zinc-800 pb-2">Detalles de Entrega</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div>
                                        <InputLabel for="direccion" value="Dirección Detallada" />
                                        <input 
                                            id="direccion"
                                            type="text" 
                                            class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-3 text-sm text-zinc-200 focus:outline-none focus:border-zinc-700 transition-all mt-1"
                                            v-model="form.direccion"
                                            required
                                        />
                                        <InputError class="mt-2" :message="form.errors.direccion" />
                                    </div>

                                    <div>
                                        <InputLabel for="horario_entrega" value="Horario Factible para Recibir" />
                                        <select 
                                            id="horario_entrega"
                                            v-model="form.horario_entrega"
                                            class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-3 text-sm text-zinc-200 focus:outline-none focus:border-zinc-700 transition-all mt-1"
                                        >
                                            <option value="morning">Por la Mañana (8:00 AM - 12:00 MD)</option>
                                            <option value="afternoon">Por la Tarde (1:00 PM - 5:00 PM)</option>
                                            <option value="evening">Por la Noche (6:00 PM - 9:00 PM)</option>
                                        </select>
                                        <InputError class="mt-2" :message="form.errors.horario_entrega" />
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4 text-xs font-mono text-zinc-500 bg-zinc-950/40 p-3 rounded-lg border border-zinc-900">
                                        <div>Latitud: <span class="text-zinc-300 font-bold">{{ form.latitud.toFixed(6) }}</span></div>
                                        <div>Longitud: <span class="text-zinc-300 font-bold">{{ form.longitud.toFixed(6) }}</span></div>
                                    </div>
                                </div>

                                <div>
                                    <InputLabel value="Ajustar punto de entrega en el mapa" class="mb-2" />
                                    <div 
                                        id="edit-delivery-map" 
                                        class="w-full h-56 bg-zinc-950 border border-zinc-800 rounded-xl overflow-hidden relative z-10"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección de Botones -->
                        <div class="flex items-center gap-4 pt-6 border-t border-zinc-800/50">
                            <PrimaryButton :disabled="form.processing">Actualizar Venta</PrimaryButton>
                            <Link :href="route('movimientos.index')" class="text-zinc-500 hover:text-white text-sm transition-colors">Cancelar</Link>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
