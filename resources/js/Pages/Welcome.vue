<script setup>
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    productos: Array
});

// Navegación
const activeTab = ref('inicio');

// Carrito de compras
const cart = ref([]);
const isCartOpen = ref(false);
const shippingCost = ref(0);
const confirmedTrackingId = ref(null);

// Cargar carrito del localStorage
onMounted(() => {
    const savedCart = localStorage.getItem('novastock_cart');
    if (savedCart) {
        try {
            cart.value = JSON.parse(savedCart);
        } catch (e) {
            cart.value = [];
        }
    }

    // Silent Refresh Polling
    startSilentRefresh();
});

let refreshInterval = null;
const startSilentRefresh = () => {
    refreshInterval = setInterval(() => {
        router.reload({ preserveState: true, preserveScroll: true, only: ['productos'] });
    }, 10000);
};

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});

// Guardar carrito al cambiar
watch(cart, (newCart) => {
    localStorage.setItem('novastock_cart', JSON.stringify(newCart));
}, { deep: true });

// Búsqueda y filtrado de productos
const searchQuery = ref('');
const filteredProductos = computed(() => {
    if (!searchQuery.value) return props.productos;
    const query = searchQuery.value.toLowerCase();
    return props.productos.filter(p => 
        p.nombre.toLowerCase().includes(query) || 
        (p.categoria && p.categoria.nombre.toLowerCase().includes(query))
    );
});

// Agregar producto al carrito
const addToCart = (producto) => {
    if (producto.stock_actual <= 0) return;
    
    const existing = cart.value.find(item => item.id === producto.id);
    if (existing) {
        if (existing.cantidad < producto.stock_actual) {
            existing.cantidad++;
        }
    } else {
        cart.value.push({
            id: producto.id,
            nombre: producto.nombre,
            precio: parseFloat(producto.precio),
            cantidad: 1,
            stock_actual: producto.stock_actual,
            categoria: producto.categoria?.nombre || 'General'
        });
    }
    isCartOpen.value = true;
};

// Remover producto
const removeFromCart = (id) => {
    cart.value = cart.value.filter(item => item.id !== id);
};

// Cambiar cantidad
const updateQuantity = (id, change) => {
    const item = cart.value.find(i => i.id === id);
    if (!item) return;
    
    const newQty = item.cantidad + change;
    if (newQty <= 0) {
        removeFromCart(id);
    } else if (newQty <= item.stock_actual) {
        item.cantidad = newQty;
    }
};

// Totales
const cartCount = computed(() => cart.value.reduce((acc, item) => acc + item.cantidad, 0));
const cartTotal = computed(() => cart.value.reduce((acc, item) => acc + (item.precio * item.cantidad), 0));

// Formulario de Pago y Checkout por Etapas
const isCheckoutModalOpen = ref(false);
const checkoutStep = ref(1); // 1: Registro, 2: Dirección y Horario, 3: Pago, 4: Validación, 5: Éxito

const checkoutForm = useForm({
    cliente: {
        nombre: '',
        correo: '',
        telefono: '',
    },
    direccion: '',
    latitud: 13.6929, // Centro neutro por defecto
    longitud: -89.2182,
    horario_entrega: 'morning', // morning, afternoon, evening
    metodo_pago: 'cash',
    tarjeta: {
        numero: '',
        titular: '',
        expiracion: '',
        cvv: '',
    },
    carrito: []
});

// Integración de Mapa Leaflet
const isMapLoaded = ref(false);
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
        setTimeout(() => {
            const container = document.getElementById('delivery-map');
            if (!container) return;
            
            // Destruir mapa previo si existe
            if (leafletMap) {
                leafletMap.remove();
                leafletMap = null;
            }

            const defaultLat = checkoutForm.latitud || 13.6929;
            const defaultLng = checkoutForm.longitud || -89.2182;

            leafletMap = window.L.map('delivery-map').setView([defaultLat, defaultLng], 14);

            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(leafletMap);

            leafletMarker = window.L.marker([defaultLat, defaultLng], {
                draggable: true
            }).addTo(leafletMap);

            // Escuchar drag del marcador
            leafletMarker.on('dragend', () => {
                const pos = leafletMarker.getLatLng();
                checkoutForm.latitud = pos.lat;
                checkoutForm.longitud = pos.lng;
            });

            // Escuchar click en mapa
            leafletMap.on('click', (e) => {
                leafletMarker.setLatLng(e.latlng);
                checkoutForm.latitud = e.latlng.lat;
                checkoutForm.longitud = e.latlng.lng;
            });

            // Asegurar render correcto de Leaflet
            setTimeout(() => {
                leafletMap.invalidateSize();
            }, 200);

            isMapLoaded.value = true;
        }, 100);
    });
};

// Mensajes de validación dinámicos y contextuales
const saveCustomerDetails = ref(false);

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

const validationMessages = computed(() => {
    const methodStr = checkoutForm.metodo_pago === 'card' ? 'Tarjeta de Crédito/Débito' : 'Efectivo contra entrega';
    const cardLast4 = checkoutForm.tarjeta.numero ? checkoutForm.tarjeta.numero.replace(/\s/g, '').slice(-4) : '****';
    const scheduleStr = formatSchedule(checkoutForm.horario_entrega);
    
    const list = [
        `Iniciando procesamiento de pedido vía ${methodStr}...`,
        `Verificando stock de los ${cartCount.value} productos seleccionados...`,
        `Comprobando dirección de entrega: "${checkoutForm.direccion}"...`,
        `Validando geolocalización de entrega en (${checkoutForm.latitud.toFixed(4)}, ${checkoutForm.longitud.toFixed(4)})...`,
    ];

    if (checkoutForm.metodo_pago === 'card') {
        list.push(`Estableciendo conexión encriptada con el procesador bancario...`);
        list.push(`Validando tarjeta terminada en **${cardLast4} a nombre de "${checkoutForm.tarjeta.titular}"...`);
        list.push(`Validando código de seguridad CVV...`);
    } else {
        list.push(`Confirmando disponibilidad de pago en efectivo al recibir...`);
        list.push(`Bloqueando inventario físico en almacén...`);
    }

    list.push(`Programando logística de entrega en horario: "${scheduleStr}"...`);
    list.push(`Registrando compra y emitiendo ticket...`);
    list.push(`¡Pago validado y transacción completada con éxito!`);

    return list;
});

const currentValMessageIndex = ref(0);
let valMessageInterval = null;

const startValidationMessages = () => {
    currentValMessageIndex.value = 0;
    valMessageInterval = setInterval(() => {
        if (currentValMessageIndex.value < validationMessages.value.length - 1) {
            currentValMessageIndex.value++;
        }
    }, 500);
};

const stopValidationMessages = () => {
    if (valMessageInterval) {
        clearInterval(valMessageInterval);
    }
};

// Control de flujo de checkout
const openCheckout = () => {
    if (cart.value.length === 0) return;
    isCartOpen.value = false;
    checkoutStep.value = 1;
    checkoutForm.clearErrors();

    // Cargar datos guardados si el usuario aceptó guardarlos anteriormente
    const shouldSave = localStorage.getItem('novastock_save_details') === 'true';
    saveCustomerDetails.value = shouldSave;
    if (shouldSave) {
        const savedDetails = localStorage.getItem('novastock_customer_details');
        if (savedDetails) {
            try {
                checkoutForm.cliente = JSON.parse(savedDetails);
            } catch (e) {
                checkoutForm.cliente = { nombre: '', correo: '', telefono: '' };
            }
        }
    } else {
        checkoutForm.cliente = { nombre: '', correo: '', telefono: '' };
    }

    isCheckoutModalOpen.value = true;
};

const closeCheckout = () => {
    if (checkoutStep.value === 4) return; // Bloquear cierre durante validación
    isCheckoutModalOpen.value = false;
};

const validateStep1 = () => {
    checkoutForm.clearErrors();
    let hasErrors = false;
    
    if (!checkoutForm.cliente.nombre) {
        checkoutForm.setError('cliente.nombre', 'El nombre es obligatorio.');
        hasErrors = true;
    }
    if (!checkoutForm.cliente.correo) {
        checkoutForm.setError('cliente.correo', 'El correo es obligatorio.');
        hasErrors = true;
    } else if (!/\S+@\S+\.\S+/.test(checkoutForm.cliente.correo)) {
        checkoutForm.setError('cliente.correo', 'El correo debe ser un email válido.');
        hasErrors = true;
    }
    
    if (!hasErrors) {
        checkoutStep.value = 2;
        loadLeaflet();
    }
};

const validateStep2 = () => {
    checkoutForm.clearErrors();
    let hasErrors = false;

    if (!checkoutForm.direccion) {
        checkoutForm.setError('direccion', 'La dirección detallada es obligatoria.');
        hasErrors = true;
    }

    if (!hasErrors) {
        // Calcular costo envío
        const latBase = 13.840204;
        const lngBase = -88.854427;
        const latTarget = checkoutForm.latitud;
        const lngTarget = checkoutForm.longitud;
        const earthRadius = 6371;
        
        const dLat = (latTarget - latBase) * Math.PI / 180;
        const dLng = (lngTarget - lngBase) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(latBase * Math.PI / 180) * Math.cos(latTarget * Math.PI / 180) *
                  Math.sin(dLng/2) * Math.sin(dLng/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        const dist = earthRadius * c;
        
        if (dist <= 5) shippingCost.value = 2.00;
        else if (dist <= 20) shippingCost.value = 3.50;
        else if (dist <= 50) shippingCost.value = 5.00;
        else shippingCost.value = 5.00 + ((dist - 50) * 0.05);

        checkoutStep.value = 3;
    }
};

const submitCheckout = () => {
    checkoutForm.clearErrors();

    // Guardar o borrar los datos del cliente según el checkbox
    if (saveCustomerDetails.value) {
        localStorage.setItem('novastock_customer_details', JSON.stringify(checkoutForm.cliente));
        localStorage.setItem('novastock_save_details', 'true');
    } else {
        localStorage.removeItem('novastock_customer_details');
        localStorage.removeItem('novastock_save_details');
    }
    
    // Asignar el carrito actual al formulario
    checkoutForm.carrito = cart.value.map(item => ({
        producto_id: item.id,
        cantidad: item.cantidad
    }));
    
    // Iniciar simulación de validación
    checkoutStep.value = 4;
    startValidationMessages();
    
    checkoutForm.post(route('checkout'), {
        onSuccess: (page) => {
            stopValidationMessages();
            checkoutStep.value = 5;
            cart.value = [];
            localStorage.removeItem('novastock_cart');
            
            if (page.props.flash && page.props.flash.tracking_id) {
                confirmedTrackingId.value = page.props.flash.tracking_id;
            }
        },
        onError: () => {
            stopValidationMessages();
            checkoutStep.value = 3; // Regresar a pago para corregir errores
        }
    });
};
</script>

<template>
    <Head title="Catálogo - NovaStock" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 flex flex-col font-sans relative overflow-x-hidden">
        <!-- Fondo Decorativo Gradient Minimalista -->
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-zinc-800/10 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="absolute bottom-10 right-1/4 w-96 h-96 bg-zinc-700/10 rounded-full blur-[120px] pointer-events-none"></div>

        <!-- Barra de Navegación -->
        <header class="border-b border-zinc-900 bg-zinc-950/80 backdrop-blur-md sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center gap-4">
                <div class="flex items-center gap-3 cursor-pointer" @click="activeTab = 'inicio'">
                    <span class="text-2xl font-black tracking-tight bg-gradient-to-r from-white via-zinc-300 to-zinc-500 bg-clip-text text-transparent hidden sm:inline">
                        NovaTech Solutions
                    </span>
                    <span class="text-2xl font-black tracking-tight text-white sm:hidden">
                        NTS
                    </span>
                    <span class="hidden sm:inline text-[10px] uppercase font-bold tracking-widest text-zinc-500 border border-zinc-800/80 rounded px-1.5 py-0.5 bg-zinc-900/50">
                        E-commerce
                    </span>
                </div>
                
                <!-- Navbar Links -->
                <nav class="hidden md:flex items-center gap-6">
                    <button @click="activeTab = 'inicio'" :class="activeTab === 'inicio' ? 'text-white font-bold border-b-2 border-white' : 'text-zinc-400 font-medium hover:text-white hover:scale-105'" class="pb-1 text-sm transition-all duration-300">Inicio</button>
                    <button @click="activeTab = 'nosotros'" :class="activeTab === 'nosotros' ? 'text-white font-bold border-b-2 border-white' : 'text-zinc-400 font-medium hover:text-white hover:scale-105'" class="pb-1 text-sm transition-all duration-300">Quiénes Somos</button>
                    <button @click="activeTab = 'servicios'" :class="activeTab === 'servicios' ? 'text-white font-bold border-b-2 border-white' : 'text-zinc-400 font-medium hover:text-white hover:scale-105'" class="pb-1 text-sm transition-all duration-300">Servicios</button>
                    <button @click="activeTab = 'equipo'" :class="activeTab === 'equipo' ? 'text-white font-bold border-b-2 border-white' : 'text-zinc-400 font-medium hover:text-white hover:scale-105'" class="pb-1 text-sm transition-all duration-300">Nuestro Equipo</button>
                    <button @click="activeTab = 'catalogo'" :class="activeTab === 'catalogo' ? 'text-white font-bold border-b-2 border-white' : 'text-zinc-400 font-medium hover:text-white hover:scale-105'" class="pb-1 text-sm transition-all duration-300">Tienda</button>
                    <Link :href="route('tracking.index')" class="text-zinc-400 font-medium hover:text-white hover:scale-105 pb-1 text-sm transition-all duration-300">Seguimiento</Link>
                </nav>

                <div class="flex items-center gap-4">
                    <!-- Login / Admin removido a petición del cliente -->

                    <!-- Carrito Trigger -->
                    <button @click="isCartOpen = true" class="relative p-2.5 rounded-xl bg-zinc-900 border border-zinc-800 hover:border-zinc-700 hover:text-white transition-all text-zinc-300 flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <span v-if="cartCount > 0" class="absolute -top-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-white text-[10px] font-black text-zinc-950 animate-pulse">
                            {{ cartCount }}
                        </span>
                        <span class="text-sm font-medium pr-1 hidden sm:inline" v-if="cartCount > 0">${{ cartTotal.toFixed(2) }}</span>
                    </button>
                </div>
            </div>
            
            <!-- Mobile Tabs -->
            <div class="md:hidden flex px-6 pb-3 gap-4 overflow-x-auto border-t border-zinc-900 pt-3 hide-scrollbar">
                <button @click="activeTab = 'inicio'" :class="activeTab === 'inicio' ? 'text-white font-bold bg-zinc-800' : 'text-zinc-400 hover:text-white bg-zinc-900/50'" class="px-4 py-2 rounded-xl text-xs whitespace-nowrap transition-all shadow-sm">Inicio</button>
                <button @click="activeTab = 'nosotros'" :class="activeTab === 'nosotros' ? 'text-white font-bold bg-zinc-800' : 'text-zinc-400 hover:text-white bg-zinc-900/50'" class="px-4 py-2 rounded-xl text-xs whitespace-nowrap transition-all shadow-sm">Quiénes Somos</button>
                <button @click="activeTab = 'servicios'" :class="activeTab === 'servicios' ? 'text-white font-bold bg-zinc-800' : 'text-zinc-400 hover:text-white bg-zinc-900/50'" class="px-4 py-2 rounded-xl text-xs whitespace-nowrap transition-all shadow-sm">Servicios</button>
                <button @click="activeTab = 'equipo'" :class="activeTab === 'equipo' ? 'text-white font-bold bg-zinc-800' : 'text-zinc-400 hover:text-white bg-zinc-900/50'" class="px-4 py-2 rounded-xl text-xs whitespace-nowrap transition-all shadow-sm">Equipo</button>
                <button @click="activeTab = 'catalogo'" :class="activeTab === 'catalogo' ? 'text-white font-bold bg-zinc-800' : 'text-zinc-400 hover:text-white bg-zinc-900/50'" class="px-4 py-2 rounded-xl text-xs whitespace-nowrap transition-all shadow-sm">Tienda</button>
            </div>
        </header>

        <!-- Contenido Principal con Animaciones -->
        <main class="flex-grow max-w-7xl w-full mx-auto px-6 py-10 z-10 relative">
            <Transition name="fade-slide" mode="out-in">
                <div :key="activeTab" class="w-full">
            <!-- Premium Hero -->
            <section v-if="activeTab === 'inicio'" class="relative py-20 lg:py-32 flex flex-col items-center justify-center text-center px-4 z-10">
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="w-[800px] h-[400px] bg-gradient-to-r from-blue-600/20 to-purple-600/20 blur-[100px] rounded-full"></div>
                </div>
                <h1 class="text-5xl sm:text-7xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white via-zinc-200 to-zinc-500 tracking-tighter mb-6 leading-tight relative z-10">
                    Soluciones Tecnológicas <br/> Avanzadas
                </h1>
                <p class="text-zinc-400 text-lg sm:text-xl max-w-2xl font-light mb-10 relative z-10">
                    Tu tienda de confianza para encontrar los mejores componentes, laptops, smartphones y accesorios tecnológicos de última generación.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 relative z-10">
                    <button @click="activeTab = 'catalogo'" class="px-8 py-3.5 bg-white text-zinc-950 font-bold rounded-full hover:scale-105 transition-transform duration-300">
                        Ir a la Tienda
                    </button>
                    <button @click="activeTab = 'nosotros'" class="px-8 py-3.5 bg-zinc-900 border border-zinc-800 text-white font-bold rounded-full hover:bg-zinc-800 hover:border-zinc-700 transition-colors duration-300">
                        Conócenos
                    </button>
                </div>
            </section>

            <!-- Quiénes Somos -->
            <section v-if="activeTab === 'nosotros'" id="nosotros" class="py-10 lg:py-24 relative z-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                    <div class="space-y-6">
                        <h2 class="text-3xl font-black text-white">Quiénes Somos</h2>
                        <p class="text-zinc-400 leading-relaxed font-light">
                            En <strong class="text-white">NovaTech Solutions</strong> nos apasiona acercarte la mejor tecnología del mercado. Somos una tienda especializada en la venta de equipos informáticos, dispositivos móviles, componentes de alto rendimiento y gadgets inteligentes. Con más de 10 años de experiencia, nuestro objetivo es ofrecerte productos de calidad premium y un excelente servicio de atención al cliente.
                        </p>
                        <p class="text-zinc-400 leading-relaxed font-light">
                            Seleccionamos minuciosamente cada producto de nuestro catálogo para garantizar que obtengas el mejor rendimiento, durabilidad y la última innovación en tus manos.
                        </p>
                        <div class="pt-4 flex gap-6">
                            <div class="text-center">
                                <span class="block text-3xl font-black text-white">10+</span>
                                <span class="text-xs text-zinc-500 uppercase tracking-widest">Años de Exp.</span>
                            </div>
                            <div class="text-center">
                                <span class="block text-3xl font-black text-white">5k+</span>
                                <span class="text-xs text-zinc-500 uppercase tracking-widest">Clientes</span>
                            </div>
                            <div class="text-center">
                                <span class="block text-3xl font-black text-white">99%</span>
                                <span class="text-xs text-zinc-500 uppercase tracking-widest">Satisfacción</span>
                            </div>
                        </div>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-0 bg-gradient-to-tr from-purple-500/20 to-blue-500/20 blur-[60px] rounded-full"></div>
                        <div class="relative bg-zinc-900/80 backdrop-blur-xl border border-zinc-800 rounded-3xl p-2 aspect-square flex items-center justify-center overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=800&auto=format&fit=crop" alt="Equipo NovaTech" class="rounded-2xl object-cover w-full h-full opacity-80 mix-blend-luminosity hover:mix-blend-normal transition-all duration-700" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Servicios -->
            <section v-if="activeTab === 'servicios'" id="servicios" class="py-10 lg:py-24 relative z-10">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-black text-white mb-4">Nuestros Servicios</h2>
                    <p class="text-zinc-400 max-w-2xl mx-auto font-light">
                        Más allá de vender los mejores productos, te ofrecemos el respaldo y la asesoría que mereces.
                    </p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Card 1 -->
                    <div class="bg-zinc-900/40 border border-zinc-800 p-8 rounded-3xl hover:bg-zinc-900/80 transition-all hover:-translate-y-2 hover:shadow-2xl hover:shadow-blue-500/10 group">
                        <div class="w-14 h-14 bg-blue-500/10 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Asesoría Especializada</h3>
                        <p class="text-zinc-400 text-sm leading-relaxed">
                            Te ayudamos a elegir el equipo perfecto según tus necesidades y presupuesto, ya sea para gaming, diseño o trabajo de oficina.
                        </p>
                    </div>
                    <!-- Card 2 -->
                    <div class="bg-zinc-900/40 border border-zinc-800 p-8 rounded-3xl hover:bg-zinc-900/80 transition-all hover:-translate-y-2 hover:shadow-2xl hover:shadow-purple-500/10 group">
                        <div class="w-14 h-14 bg-purple-500/10 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Envíos Rápidos y Seguros</h3>
                        <p class="text-zinc-400 text-sm leading-relaxed">
                            Garantizamos que tus productos tecnológicos lleguen a la puerta de tu casa en tiempo récord y en perfectas condiciones.
                        </p>
                    </div>
                    <!-- Card 3 -->
                    <div class="bg-zinc-900/40 border border-zinc-800 p-8 rounded-3xl hover:bg-zinc-900/80 transition-all hover:-translate-y-2 hover:shadow-2xl hover:shadow-emerald-500/10 group">
                        <div class="w-14 h-14 bg-emerald-500/10 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Soporte y Garantía</h3>
                        <p class="text-zinc-400 text-sm leading-relaxed">
                            Todos nuestros artículos cuentan con garantía oficial y soporte técnico dedicado para resolver cualquier inconveniente.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Equipo de Trabajo -->
            <section v-if="activeTab === 'equipo'" id="equipo" class="py-10 lg:py-24 relative z-10">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-black text-white mb-4">Nuestro Equipo</h2>
                    <p class="text-zinc-400 max-w-2xl mx-auto font-light">
                        El talento detrás de NovaTech Solutions. Profesionales apasionados por la tecnología, el diseño y la innovación comercial.
                    </p>
                </div>
                
                <div class="flex flex-wrap justify-center gap-8 lg:gap-12 max-w-6xl mx-auto">
                    <!-- Brayan Adaly -->
                    <div class="flex flex-col items-center group w-48">
                        <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-zinc-800 bg-zinc-900 group-hover:border-blue-500 transition-all duration-300 mb-4 shadow-xl">
                            <img src="/images/equipo/Adaly.JPG" alt="Brayan Adaly Campos Martinez" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <h3 class="text-white font-bold text-center leading-tight">Brayan Adaly <br> Campos Martinez</h3>
                        <span class="text-xs text-zinc-500 uppercase tracking-widest mt-2 font-semibold">CEO & Fundador</span>
                    </div>

                    <!-- Kevin Antonio -->
                    <div class="flex flex-col items-center group w-48">
                        <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-zinc-800 bg-zinc-900 group-hover:border-purple-500 transition-all duration-300 mb-4 shadow-xl">
                            <img src="/images/equipo/Kevin.JPG" alt="Kevin Antonio Castro Araujo" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <h3 class="text-white font-bold text-center leading-tight">Kevin Antonio <br> Castro Araujo</h3>
                        <span class="text-xs text-zinc-500 uppercase tracking-widest mt-2 font-semibold">CEO & Fundador</span>
                    </div>

                    <!-- Omar David -->
                    <div class="flex flex-col items-center group w-48">
                        <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-zinc-800 bg-zinc-900 group-hover:border-emerald-500 transition-all duration-300 mb-4 shadow-xl">
                            <img src="/images/equipo/Omar.JPG" alt="Omar David Ventura Cruz" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <h3 class="text-white font-bold text-center leading-tight">Omar David <br> Ventura Cruz</h3>
                        <span class="text-xs text-zinc-500 uppercase tracking-widest mt-2 font-semibold">CEO & Fundador</span>
                    </div>

                    <!-- Jeremias Neftaly -->
                    <div class="flex flex-col items-center group w-48">
                        <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-zinc-800 bg-zinc-900 group-hover:border-amber-500 transition-all duration-300 mb-4 shadow-xl">
                            <img src="/images/equipo/Nefta.JPG" alt="Jeremias Neftaly Fuentes Mendez" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <h3 class="text-white font-bold text-center leading-tight">Jeremias Neftaly <br> Fuentes Mendez</h3>
                        <span class="text-xs text-zinc-500 uppercase tracking-widest mt-2 font-semibold">CEO & Fundador</span>
                    </div>

                    <!-- Yensi Elizabeth -->
                    <div class="flex flex-col items-center group w-48">
                        <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-zinc-800 bg-zinc-900 group-hover:border-rose-500 transition-all duration-300 mb-4 shadow-xl">
                            <img src="/images/equipo/Yensi.JPG" alt="Yensi Elizabeth Valladares Ventura" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <h3 class="text-white font-bold text-center leading-tight">Yensi Elizabeth <br> Valladares Ventura</h3>
                        <span class="text-xs text-zinc-500 uppercase tracking-widest mt-2 font-semibold">CEO & Fundador</span>
                    </div>
                </div>
            </section>

            <!-- Tienda (Antes Catálogo) -->
            <section v-if="activeTab === 'catalogo'" id="catalogo" class="py-10 pb-10 z-10 relative">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-10 gap-4">
                    <div>
                        <h2 class="text-3xl font-black text-white mb-2">Nuestra Tienda</h2>
                        <p class="text-zinc-400 text-sm">Explora nuestra colección clasificada de tecnología.</p>
                    </div>
                    <!-- Buscador Minimalista -->
                    <div class="relative w-full sm:w-72">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-zinc-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            v-model="searchQuery" 
                            placeholder="Buscar productos..." 
                            class="w-full bg-zinc-900/80 backdrop-blur-md border border-zinc-800 rounded-full py-3 pl-12 pr-4 text-sm text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-white focus:ring-1 focus:ring-white transition-all"
                        />
                    </div>
                </div>

            <!-- Grilla de Productos -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 animate-fade-in-up stagger">
                <div 
                    v-for="producto in filteredProductos" 
                    :key="producto.id" 
                    class="card bg-zinc-900/40 backdrop-blur-sm border border-zinc-800/80 rounded-2xl p-6 flex flex-col justify-between group hover:border-zinc-700 transition-all duration-300"
                >
                    <div>
                        <!-- Header de Tarjeta -->
                        <div class="flex justify-between items-start mb-4">
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider bg-zinc-800 text-zinc-400">
                                {{ producto.categoria?.nombre || 'General' }}
                            </span>
                            
                            <span 
                                :class="[
                                    producto.stock_actual === 0 ? 'text-rose-500 bg-rose-500/5 border border-rose-500/10' :
                                    producto.stock_actual <= producto.stock_minimo ? 'text-amber-500 bg-amber-500/5 border border-amber-500/10' :
                                    'text-zinc-500 bg-zinc-800/20'
                                ]" 
                                class="text-[11px] font-medium px-2 py-0.5 rounded"
                            >
                                {{ producto.stock_actual === 0 ? 'Agotado' : `${producto.stock_actual} disp.` }}
                            </span>
                        </div>

                        <!-- Nombre e Info -->
                        <h3 class="text-lg font-bold text-white mb-2 group-hover:text-white transition-colors">
                            {{ producto.nombre }}
                        </h3>
                        <p class="text-zinc-400 text-xs font-light leading-relaxed mb-6">
                            {{ producto.descripcion || 'Sin descripción detallada.' }}
                        </p>
                    </div>

                    <!-- Precio y Comprar -->
                    <div class="flex items-center justify-between mt-auto">
                        <div>
                            <span class="text-[10px] text-zinc-500 uppercase tracking-widest block font-medium">Precio</span>
                            <span class="text-xl font-extrabold text-white">${{ parseFloat(producto.precio).toFixed(2) }}</span>
                        </div>

                        <button 
                            @click="addToCart(producto)" 
                            :disabled="producto.stock_actual <= 0"
                            class="px-4 py-2 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all"
                            :class="[
                                producto.stock_actual <= 0 
                                ? 'bg-zinc-800/50 text-zinc-600 cursor-not-allowed' 
                                : 'bg-white text-zinc-950 hover:bg-zinc-200 hover:scale-105 active:scale-95'
                            ]"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Añadir
                        </button>
                    </div>
                </div>

                <!-- Estado Vacío -->
                <div v-if="filteredProductos.length === 0" class="col-span-full text-center py-20 bg-zinc-900/10 border border-dashed border-zinc-800 rounded-2xl">
                    <p class="text-zinc-500 text-sm">No se encontraron productos en la tienda.</p>
                </div>
            </div>
            </section>
                </div>
            </Transition>
        </main>

        <!-- Footer -->
        <footer class="border-t border-zinc-900 bg-zinc-950 py-8 text-center text-xs text-zinc-650 z-10">
            <div class="max-w-7xl mx-auto px-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                <span>&copy; 2026 NovaStock. Todos los derechos reservados.</span>
                <span class="font-mono text-zinc-700">Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})</span>
            </div>
        </footer>

        <!-- Carrito Lateral (Drawer) -->
        <div v-if="isCartOpen" class="fixed inset-0 z-50 overflow-hidden flex justify-end">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="isCartOpen = false"></div>

            <!-- Panel -->
            <div class="relative w-full max-w-md bg-zinc-900 border-l border-zinc-800 shadow-2xl flex flex-col justify-between animate-slide-in-left">
                <!-- Header Cart -->
                <div class="p-6 border-b border-zinc-800/80 flex justify-between items-center">
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <span>Carrito</span>
                        <span class="text-xs px-2 py-0.5 bg-zinc-800 text-zinc-400 rounded-full font-medium">{{ cartCount }}</span>
                    </h2>
                    <button @click="isCartOpen = false" class="p-1 rounded-lg hover:bg-zinc-800 text-zinc-400 hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Contenido Items -->
                <div class="flex-grow overflow-y-auto p-6 space-y-4">
                    <div v-for="item in cart" :key="item.id" class="flex gap-4 p-3 bg-zinc-950/50 border border-zinc-850 rounded-xl hover:border-zinc-800 transition-all">
                        <div class="flex-grow">
                            <div class="flex justify-between items-start">
                                <h4 class="text-sm font-bold text-white line-clamp-1 pr-2">{{ item.nombre }}</h4>
                                <button @click="removeFromCart(item.id)" class="text-zinc-600 hover:text-rose-450 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                            <span class="text-[10px] text-zinc-550 block mb-3">{{ item.categoria }}</span>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-black text-white">${{ (item.precio * item.cantidad).toFixed(2) }}</span>
                                
                                <div class="flex items-center bg-zinc-900 border border-zinc-800 rounded-lg overflow-hidden">
                                    <button @click="updateQuantity(item.id, -1)" class="px-2 py-1 hover:bg-zinc-800 text-zinc-400 hover:text-white transition-colors">-</button>
                                    <span class="px-3 text-xs font-mono text-white">{{ item.cantidad }}</span>
                                    <button @click="updateQuantity(item.id, 1)" class="px-2 py-1 hover:bg-zinc-800 text-zinc-400 hover:text-white transition-colors">+</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="cart.length === 0" class="text-center py-20">
                        <p class="text-zinc-500 text-sm">Tu carrito está vacío.</p>
                        <button @click="isCartOpen = false" class="mt-4 text-xs font-bold text-white border-b border-white pb-0.5 hover:text-zinc-300 hover:border-zinc-350 transition-all">
                            Seguir navegando
                        </button>
                    </div>
                </div>

                <!-- Footer Cart -->
                <div v-if="cart.length > 0" class="p-6 border-t border-zinc-800/80 bg-zinc-950/50">
                    <div class="flex justify-between items-center mb-6">
                        <span class="text-sm text-zinc-455 font-medium">Subtotal del pedido</span>
                        <span class="text-2xl font-black text-white">${{ cartTotal.toFixed(2) }}</span>
                    </div>

                    <button @click="openCheckout" class="w-full bg-white text-zinc-950 py-3.5 rounded-xl font-bold hover:bg-zinc-200 transition-all flex items-center justify-center gap-2 shadow-lg shadow-white/5 active:scale-98">
                        Proceder a la compra
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de Checkout en Etapas -->
        <div v-if="isCheckoutModalOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/80 backdrop-blur-md" @click="closeCheckout"></div>
            
            <div class="relative w-full max-w-lg bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden shadow-2xl animate-scale-in">
                <!-- Barra de pasos superior -->
                <div class="border-b border-zinc-800/80 bg-zinc-950/40 px-6 py-4 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-bold text-white">Completar Compra</span>
                        <div class="flex items-center gap-1.5" v-if="checkoutStep <= 4">
                            <span class="w-1.5 h-1.5 rounded-full" :class="checkoutStep >= 1 ? 'bg-white' : 'bg-zinc-700'"></span>
                            <span class="w-1.5 h-1.5 rounded-full" :class="checkoutStep >= 2 ? 'bg-white' : 'bg-zinc-700'"></span>
                            <span class="w-1.5 h-1.5 rounded-full" :class="checkoutStep >= 3 ? 'bg-white' : 'bg-zinc-700'"></span>
                            <span class="w-1.5 h-1.5 rounded-full" :class="checkoutStep >= 4 ? 'bg-white' : 'bg-zinc-700'"></span>
                        </div>
                    </div>
                    <button @click="closeCheckout" v-if="checkoutStep !== 4" class="text-zinc-500 hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- CONTENIDO MULTI-ETAPA -->
                <div class="p-6">
                    <!-- ETAPA 1: REGISTRO (DATOS PERSONALES) -->
                    <div v-if="checkoutStep === 1" class="space-y-4">
                        <div class="mb-4">
                            <h3 class="text-base font-bold text-white">1. Datos de Contacto</h3>
                            <p class="text-zinc-400 text-xs mt-0.5">Ingresa tus datos personales para registrar el pedido.</p>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-zinc-400 uppercase tracking-wider mb-1.5">Nombre Completo</label>
                                <input 
                                    type="text" 
                                    v-model="checkoutForm.cliente.nombre" 
                                    placeholder="Ej. Juan Pérez"
                                    class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-3 text-sm text-zinc-200 placeholder-zinc-650 focus:outline-none focus:border-zinc-700 focus:ring-1 focus:ring-zinc-700 transition-all"
                                />
                                <span v-if="checkoutForm.errors['cliente.nombre']" class="text-rose-500 text-xs mt-1 block">
                                    {{ checkoutForm.errors['cliente.nombre'] }}
                                </span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-zinc-400 uppercase tracking-wider mb-1.5">Correo Electrónico</label>
                                <input 
                                    type="email" 
                                    v-model="checkoutForm.cliente.correo" 
                                    placeholder="Ej. juan@correo.com"
                                    class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-3 text-sm text-zinc-200 placeholder-zinc-650 focus:outline-none focus:border-zinc-700 focus:ring-1 focus:ring-zinc-700 transition-all"
                                />
                                <span v-if="checkoutForm.errors['cliente.correo']" class="text-rose-500 text-xs mt-1 block">
                                    {{ checkoutForm.errors['cliente.correo'] }}
                                </span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-zinc-400 uppercase tracking-wider mb-1.5">Teléfono (Opcional)</label>
                                <input 
                                    type="text" 
                                    v-model="checkoutForm.cliente.telefono" 
                                    placeholder="Ej. +503 7000-0000"
                                    class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-3 text-sm text-zinc-200 placeholder-zinc-650 focus:outline-none focus:border-zinc-700 focus:ring-1 focus:ring-zinc-700 transition-all"
                                />
                                <span v-if="checkoutForm.errors['cliente.telefono']" class="text-rose-500 text-xs mt-1 block">
                                    {{ checkoutForm.errors['cliente.telefono'] }}
                                </span>
                            </div>
                            <div class="mt-4 flex items-center gap-2.5">
                                <input 
                                    type="checkbox" 
                                    id="save_customer_details" 
                                    v-model="saveCustomerDetails"
                                    class="rounded bg-zinc-950 border-zinc-800 text-white focus:ring-zinc-700 w-4 h-4"
                                />
                                <label for="save_customer_details" class="text-xs text-zinc-400 cursor-pointer select-none">
                                    Deseo guardar mis datos de contacto para futuras compras.
                                </label>
                            </div>
                        </div>

                        <div class="pt-6 flex justify-end">
                            <button 
                                @click="validateStep1" 
                                class="bg-white text-zinc-950 px-6 py-2.5 rounded-xl text-sm font-bold hover:bg-zinc-200 transition-all flex items-center gap-1.5"
                            >
                                Siguiente paso
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- ETAPA 2: DIRECCIÓN EN MAPA Y HORARIOS FACTIBLES -->
                    <div v-if="checkoutStep === 2" class="space-y-4">
                        <div class="mb-2">
                            <h3 class="text-base font-bold text-white">2. Ubicación de Entrega</h3>
                            <p class="text-zinc-400 text-xs mt-0.5">Selecciona el punto en el mapa y detalla los horarios de entrega.</p>
                        </div>

                        <!-- Mapa Interactivo Leaflet -->
                        <div>
                            <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-2">Marca el punto de entrega en el mapa</label>
                            <div 
                                id="delivery-map" 
                                class="w-full h-48 bg-zinc-950 border border-zinc-800 rounded-xl overflow-hidden relative z-10"
                            ></div>
                            <span class="text-[10px] text-zinc-500 mt-1 block">Puedes arrastrar el pin rojo o hacer clic directamente en el mapa.</span>
                        </div>

                        <!-- Inputs de Dirección y Horario -->
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-zinc-400 uppercase tracking-wider mb-1.5">Dirección Detallada (Calle, Casa, Ref.)</label>
                                <input 
                                    type="text" 
                                    v-model="checkoutForm.direccion" 
                                    placeholder="Ej. Colonia Escalón, Pasaje L-3, Casa 12-A"
                                    class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-3 text-sm text-zinc-200 placeholder-zinc-650 focus:outline-none focus:border-zinc-700 focus:ring-1 focus:ring-zinc-700 transition-all"
                                />
                                <span v-if="checkoutForm.errors.direccion" class="text-rose-500 text-xs mt-1 block">
                                    {{ checkoutForm.errors.direccion }}
                                </span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-zinc-400 uppercase tracking-wider mb-1.5">Horario Factible para Recibir</label>
                                <select 
                                    v-model="checkoutForm.horario_entrega"
                                    class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-3 text-sm text-zinc-200 focus:outline-none focus:border-zinc-700 focus:ring-1 focus:ring-zinc-700 transition-all"
                                >
                                    <option value="any">Cualquier horario (Flexible)</option>
                                    <option value="morning">Por la Mañana (8:00 AM - 12:00 MD)</option>
                                    <option value="afternoon">Por la Tarde (1:00 PM - 5:00 PM)</option>
                                    <option value="evening">Por la Noche / Nocturno (6:00 PM - 9:00 PM)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Botones de Control -->
                        <div class="pt-4 flex justify-between">
                            <button 
                                @click="checkoutStep = 1" 
                                class="bg-zinc-800 text-zinc-300 px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-zinc-700 transition-all"
                            >
                                Regresar
                            </button>
                            <button 
                                @click="validateStep2" 
                                class="bg-white text-zinc-950 px-6 py-2.5 rounded-xl text-sm font-bold hover:bg-zinc-200 transition-all flex items-center gap-1.5"
                            >
                                Siguiente paso
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- ETAPA 3: DETALLES DE PAGO (PLANTILLA DE PAGO INTEGRADA) -->
                    <div v-if="checkoutStep === 3" class="space-y-5">
                        <div class="mb-2">
                            <h3 class="text-base font-bold text-white">3. Método de Pago</h3>
                            <p class="text-zinc-400 text-xs mt-0.5">Selecciona cómo deseas realizar el pago e ingresa la información requerida.</p>
                        </div>

                        <!-- Selector de Método de Pago -->
                        <div class="grid grid-cols-2 gap-4">
                            <label 
                                class="relative border rounded-xl p-4 flex flex-col items-center gap-2 cursor-pointer transition-all hover:bg-zinc-950/50"
                                :class="checkoutForm.metodo_pago === 'cash' ? 'border-white bg-zinc-950/80 text-white' : 'border-zinc-800 text-zinc-400'"
                            >
                                <input 
                                    type="radio" 
                                    name="paymentMethod" 
                                    value="cash" 
                                    v-model="checkoutForm.metodo_pago"
                                    class="sr-only"
                                />
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span class="text-xs font-bold">Pagar en Efectivo</span>
                            </label>

                            <label 
                                class="relative border rounded-xl p-4 flex flex-col items-center gap-2 cursor-pointer transition-all hover:bg-zinc-950/50"
                                :class="checkoutForm.metodo_pago === 'card' ? 'border-white bg-zinc-950/80 text-white' : 'border-zinc-800 text-zinc-400'"
                            >
                                <input 
                                    type="radio" 
                                    name="paymentMethod" 
                                    value="card" 
                                    v-model="checkoutForm.metodo_pago"
                                    class="sr-only"
                                />
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                <span class="text-xs font-bold">Pagar con Tarjeta</span>
                            </label>
                        </div>

                        <!-- Formulario de Tarjeta (PlantilladePago.html) -->
                        <div v-if="checkoutForm.metodo_pago === 'card'" class="p-5 bg-zinc-950 border border-zinc-800 rounded-xl space-y-3 animate-fade-in-up">
                            <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-2 border-b border-zinc-900 pb-2">Detalles de la tarjeta</h4>
                            
                            <div>
                                <label class="block text-[10px] font-bold text-zinc-450 uppercase tracking-widest mb-1.5">Número de tarjeta</label>
                                <input 
                                    type="text" 
                                    v-model="checkoutForm.tarjeta.numero" 
                                    placeholder="1234 5678 9012 3456" 
                                    maxlength="19"
                                    class="w-full bg-zinc-900 border border-zinc-800 rounded-lg px-3.5 py-2.5 text-xs text-zinc-200 placeholder-zinc-600 focus:outline-none focus:border-zinc-700 transition-all font-mono"
                                />
                                <span v-if="checkoutForm.errors['tarjeta.numero']" class="text-rose-500 text-[11px] mt-1 block">
                                    {{ checkoutForm.errors['tarjeta.numero'] }}
                                </span>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-zinc-450 uppercase tracking-widest mb-1.5">Nombre del titular</label>
                                <input 
                                    type="text" 
                                    v-model="checkoutForm.tarjeta.titular" 
                                    placeholder="Juan Pérez" 
                                    class="w-full bg-zinc-900 border border-zinc-800 rounded-lg px-3.5 py-2.5 text-xs text-zinc-200 placeholder-zinc-600 focus:outline-none focus:border-zinc-700 transition-all"
                                />
                                <span v-if="checkoutForm.errors['tarjeta.titular']" class="text-rose-500 text-[11px] mt-1 block">
                                    {{ checkoutForm.errors['tarjeta.titular'] }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-zinc-450 uppercase tracking-widest mb-1.5">Expiración (MM/YY)</label>
                                    <input 
                                        type="text" 
                                        v-model="checkoutForm.tarjeta.expiracion" 
                                        placeholder="12/26" 
                                        maxlength="5"
                                        class="w-full bg-zinc-900 border border-zinc-800 rounded-lg px-3.5 py-2.5 text-xs text-zinc-200 placeholder-zinc-600 focus:outline-none focus:border-zinc-700 transition-all font-mono"
                                    />
                                    <span v-if="checkoutForm.errors['tarjeta.expiracion']" class="text-rose-500 text-[11px] mt-1 block">
                                        {{ checkoutForm.errors['tarjeta.expiracion'] }}
                                    </span>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-zinc-450 uppercase tracking-widest mb-1.5">CVV</label>
                                    <input 
                                        type="password" 
                                        v-model="checkoutForm.tarjeta.cvv" 
                                        placeholder="123" 
                                        maxlength="4"
                                        class="w-full bg-zinc-900 border border-zinc-800 rounded-lg px-3.5 py-2.5 text-xs text-zinc-200 placeholder-zinc-600 focus:outline-none focus:border-zinc-700 transition-all font-mono"
                                    />
                                    <span v-if="checkoutForm.errors['tarjeta.cvv']" class="text-rose-500 text-[11px] mt-1 block">
                                        {{ checkoutForm.errors['tarjeta.cvv'] }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Info Efectivo -->
                        <div v-else class="p-5 bg-zinc-950 border border-zinc-800 rounded-xl text-center text-zinc-400 text-xs font-light py-8 animate-fade-in-up">
                            El pago se registrará para ser completado contra entrega en efectivo. Recibirás confirmación al finalizar.
                        </div>

                        <!-- Resumen Breve -->
                        <div class="space-y-2 p-4 bg-zinc-950/30 rounded-xl border border-zinc-800 text-sm">
                            <div class="flex justify-between items-center text-zinc-400">
                                <span>Subtotal</span>
                                <span>${{ cartTotal.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-zinc-400">
                                <span>Tarifa Inteligente de Envío</span>
                                <span>${{ shippingCost.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between items-center border-t border-zinc-800 pt-2 mt-2">
                                <span class="text-zinc-300 font-bold">Total a Pagar</span>
                                <span class="font-extrabold text-white text-lg">${{ (cartTotal + shippingCost).toFixed(2) }}</span>
                            </div>
                        </div>

                        <!-- Botones de Control -->
                        <div class="pt-4 flex justify-between">
                            <button 
                                @click="checkoutStep = 2; loadLeaflet();" 
                                class="bg-zinc-800 text-zinc-300 px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-zinc-700 transition-all"
                            >
                                Regresar
                            </button>
                            <button 
                                @click="submitCheckout" 
                                class="bg-white text-zinc-950 px-6 py-2.5 rounded-xl text-sm font-bold hover:bg-zinc-200 transition-all shadow-lg active:scale-95"
                            >
                                Confirmar Pago
                            </button>
                        </div>
                    </div>

                    <!-- ETAPA 4: VALIDACIÓN DEL PAGO (MICRO-ANIMACIONES Y CARGA) -->
                    <div v-if="checkoutStep === 4" class="py-12 flex flex-col items-center justify-center text-center space-y-6">
                        <!-- Anillo de carga rotativo minimalista -->
                        <div class="relative w-16 h-16">
                            <div class="absolute inset-0 border-[3px] border-zinc-850 rounded-full"></div>
                            <div class="absolute inset-0 border-[3px] border-white border-t-transparent rounded-full animate-spin"></div>
                        </div>

                        <div class="space-y-2 max-w-xs mx-auto">
                            <h3 class="text-base font-bold text-white">Validando transacción</h3>
                            <!-- Mensaje cambiante dinámico -->
                            <p class="text-zinc-400 text-xs font-mono min-h-[32px] flex items-center justify-center">
                                {{ validationMessages[currentValMessageIndex] }}
                            </p>
                        </div>

                        <p class="text-[11px] text-zinc-600 font-light max-w-xs">
                            Por favor no cierres la ventana ni refresques el navegador mientras el sistema valida los datos con el procesador.
                        </p>
                    </div>

                    <!-- ETAPA 5: ÉXITO (CONFIRMACIÓN DE COMPRA) -->
                    <div v-if="checkoutStep === 5" class="py-10 flex flex-col items-center justify-center text-center space-y-6">
                        <!-- Checkmark Animado Premium -->
                        <div class="h-16 w-16 bg-white/10 rounded-full border border-white/20 flex items-center justify-center text-white scale-in">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        <div class="space-y-2">
                            <h3 class="text-xl font-bold text-white">¡Compra Validada y Completada!</h3>
                            <p class="text-zinc-400 text-xs max-w-xs mx-auto font-light leading-relaxed">
                                Tu pedido ha sido procesado de manera exitosa. La transacción fue aprobada, la dirección de entrega fue registrada y los registros de inventario se actualizaron correctamente.
                            </p>
                            
                            <div v-if="confirmedTrackingId" class="mt-6 p-5 border-2 border-rose-500/50 bg-rose-500/10 rounded-2xl max-w-sm mx-auto text-center animate-fade-in-up relative overflow-hidden shadow-lg shadow-rose-500/20">
                                <div class="absolute top-0 right-0 left-0 h-1 bg-gradient-to-r from-rose-500 to-purple-500"></div>
                                <h4 class="text-rose-400 font-bold mb-3 flex items-center justify-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    ¡TOMA CAPTURA A ESTE CÓDIGO!
                                </h4>
                                <p class="text-[12px] text-zinc-300 mb-5 leading-relaxed">Este es el <strong>único momento</strong> en que verás este código. Es indispensable para rastrear el progreso de tu entrega.</p>
                                
                                <p class="text-[10px] text-zinc-500 uppercase tracking-widest font-bold mb-2">Tu ID de Seguimiento</p>
                                <p class="text-3xl font-mono font-black text-white tracking-widest py-4 bg-zinc-950 rounded-xl border border-zinc-800 shadow-inner select-all">{{ confirmedTrackingId }}</p>
                            </div>
                        </div>

                        <div class="pt-4">
                            <button 
                                @click="closeCheckout" 
                                class="bg-white text-zinc-950 px-8 py-3 rounded-xl text-xs font-bold hover:bg-zinc-200 transition-all"
                            >
                                Volver al catálogo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Transiciones fluidas extra */
@keyframes slideInLeft {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}

@keyframes scaleIn {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.animate-slide-in-left {
    animation: slideInLeft 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.animate-scale-in {
    animation: scaleIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Transiciones entre Tabs */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(15px);
}
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-15px);
}

/* Ocultar barra de desplazamiento en Mobile Tabs */
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>