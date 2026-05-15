<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
    productos: Array,
});

const carrito = ref([]);
const isCartOpen = ref(false);

const form = useForm({
    cliente: {
        nombre: '',
        correo: '',
        telefono: '',
    },
    carrito: [],
});

const addToCart = (producto) => {
    const item = carrito.value.find(i => i.producto.id === producto.id);
    if (item) {
        if (item.cantidad < producto.stock_actual) {
            item.cantidad++;
        } else {
            alert('No hay más stock disponible para este producto.');
        }
    } else {
        carrito.value.push({ producto, cantidad: 1 });
    }
};

const removeFromCart = (index) => {
    carrito.value.splice(index, 1);
};

const cartTotal = computed(() => {
    return carrito.value.reduce((total, item) => total + (item.producto.precio * item.cantidad), 0);
});

const cartCount = computed(() => {
    return carrito.value.reduce((total, item) => total + item.cantidad, 0);
});

const checkout = () => {
    form.carrito = carrito.value.map(item => ({
        producto_id: item.producto.id,
        cantidad: item.cantidad
    }));

    form.post(route('checkout'), {
        onSuccess: () => {
            carrito.value = [];
            isCartOpen.value = false;
            form.reset('cliente');
        }
    });
};
</script>

<template>
    <Head title="Catálogo NovaStock" />

    <div class="min-h-screen bg-gray-900 text-white font-sans selection:bg-blue-500 selection:text-white">
        <!-- Navigation -->
        <nav class="fixed w-full z-50 bg-gray-900/80 backdrop-blur-md border-b border-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    <div class="flex items-center gap-3">
                        <ApplicationLogo class="block h-8 w-auto fill-current text-blue-500" />
                        <span class="text-xl font-bold tracking-tight text-white">Nova<span class="text-blue-500">Stock</span></span>
                    </div>
                    <div class="flex items-center gap-6">
                        <button @click="isCartOpen = true" class="relative text-gray-300 hover:text-white transition group">
                            <svg class="w-6 h-6 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            <span v-if="cartCount > 0" class="absolute -top-2 -right-2 bg-blue-500 text-xs text-white font-bold px-1.5 py-0.5 rounded-full">{{ cartCount }}</span>
                        </button>
                        <template v-if="canLogin">
                            <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="text-sm font-semibold text-gray-300 hover:text-white border border-gray-700 hover:border-gray-500 px-4 py-2 rounded-lg transition">Dashboard</Link>
                            <template v-else>
                                <Link :href="route('login')" class="text-sm font-semibold text-gray-300 hover:text-white transition">Acceder</Link>
                            </template>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="relative pt-32 pb-20 lg:pt-40 lg:pb-28 overflow-hidden">
            <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&q=80')] bg-cover bg-center opacity-10"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/80 to-transparent"></div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight mb-6 bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-purple-500">
                    Tecnología del Futuro, Hoy
                </h1>
                <p class="mt-4 text-xl text-gray-400 max-w-2xl mx-auto mb-10">
                    Explora nuestro catálogo de dispositivos premium. Simula tus compras y experimenta nuestro sistema de inventario en tiempo real.
                </p>
            </div>
        </div>

        <!-- Catalog Grid -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-24">
            
            <div v-if="$page.props.flash && $page.props.flash.success" class="mb-8 bg-green-500/20 border border-green-500/50 text-green-400 p-4 rounded-xl flex items-center gap-3 backdrop-blur-sm">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.errors && $page.props.errors.checkout" class="mb-8 bg-red-500/20 border border-red-500/50 text-red-400 p-4 rounded-xl flex items-center gap-3 backdrop-blur-sm">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ $page.props.errors.checkout }}
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                <div v-for="producto in productos" :key="producto.id" class="group bg-gray-800/50 border border-gray-700/50 rounded-2xl overflow-hidden hover:bg-gray-800 hover:border-blue-500/50 transition-all duration-300 backdrop-blur-sm shadow-xl flex flex-col">
                    <div class="relative h-48 overflow-hidden bg-gray-900">
                        <img v-if="producto.imagen" :src="`/storage/${producto.imagen}`" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-600">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <div class="absolute top-3 right-3">
                            <span v-if="producto.estado === 'stock_bajo'" class="bg-yellow-500/90 text-yellow-900 text-xs font-bold px-2.5 py-1 rounded-full backdrop-blur-md shadow-lg">Pocas Unidades</span>
                        </div>
                    </div>
                    <div class="p-6 flex flex-col flex-grow">
                        <p class="text-blue-400 text-xs font-semibold mb-1 uppercase tracking-wider">{{ producto.categoria?.nombre }}</p>
                        <h3 class="text-xl font-bold text-white mb-2">{{ producto.nombre }}</h3>
                        <p class="text-gray-400 text-sm mb-4 line-clamp-2 flex-grow">{{ producto.descripcion }}</p>
                        
                        <div class="flex items-end justify-between mt-auto pt-4 border-t border-gray-700/50">
                            <div>
                                <p class="text-2xl font-black text-white">${{ producto.precio }}</p>
                                <p class="text-xs text-gray-500 mt-1">Stock: {{ producto.stock_actual }}</p>
                            </div>
                            <button @click="addToCart(producto)" class="bg-blue-600 hover:bg-blue-500 text-white p-3 rounded-xl shadow-lg shadow-blue-500/30 transition-all hover:-translate-y-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="productos.length === 0" class="col-span-full py-20 text-center">
                    <p class="text-gray-500 text-lg">No hay productos disponibles en este momento.</p>
                </div>
            </div>
        </div>

        <!-- Shopping Cart Modal -->
        <div v-if="isCartOpen" class="fixed inset-0 z-50 flex items-center justify-center sm:justify-end p-4 sm:p-0">
            <!-- Overlay -->
            <div @click="isCartOpen = false" class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm transition-opacity"></div>
            
            <!-- Modal/Drawer -->
            <div class="relative w-full max-w-md bg-gray-800 sm:h-screen sm:max-h-screen rounded-2xl sm:rounded-none overflow-hidden flex flex-col shadow-2xl border-l border-gray-700/50 transform transition-all duration-300">
                <div class="p-6 bg-gray-800/90 border-b border-gray-700 backdrop-blur-md flex justify-between items-center z-10">
                    <h2 class="text-xl font-bold flex items-center gap-2">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        Tu Carrito Simulador
                    </h2>
                    <button @click="isCartOpen = false" class="text-gray-400 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="flex-grow overflow-y-auto p-6 space-y-6">
                    <div v-if="carrito.length === 0" class="text-center py-10 text-gray-500">
                        Tu carrito está vacío.
                    </div>
                    <div v-else class="space-y-4">
                        <div v-for="(item, index) in carrito" :key="index" class="flex gap-4 p-4 bg-gray-900/50 rounded-xl border border-gray-700">
                            <img v-if="item.producto.imagen" :src="`/storage/${item.producto.imagen}`" class="w-16 h-16 object-cover rounded-lg" />
                            <div v-else class="w-16 h-16 bg-gray-800 rounded-lg flex items-center justify-center"><span class="text-xs text-gray-500">N/A</span></div>
                            
                            <div class="flex-grow">
                                <h4 class="font-bold text-sm line-clamp-1">{{ item.producto.nombre }}</h4>
                                <p class="text-blue-400 font-semibold mt-1">${{ item.producto.precio }} <span class="text-gray-500 text-xs font-normal">x {{ item.cantidad }}</span></p>
                            </div>
                            <button @click="removeFromCart(index)" class="text-red-400 hover:text-red-300 self-start p-1 bg-red-400/10 rounded-md">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>

                        <div class="pt-4 border-t border-gray-700 flex justify-between items-center font-bold text-lg">
                            <span>Total Estimado:</span>
                            <span class="text-blue-400">${{ cartTotal.toFixed(2) }}</span>
                        </div>
                    </div>

                    <div v-if="carrito.length > 0" class="pt-6 border-t border-gray-700">
                        <h3 class="font-bold text-gray-300 mb-4">Datos para simulación</h3>
                        <form @submit.prevent="checkout" class="space-y-4">
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Nombre</label>
                                <input v-model="form.cliente.nombre" type="text" required class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-600" placeholder="Ej. Juan Pérez">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Correo Electrónico</label>
                                <input v-model="form.cliente.correo" type="email" required class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-600" placeholder="juan@ejemplo.com">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Teléfono (Opcional)</label>
                                <input v-model="form.cliente.telefono" type="text" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-600">
                            </div>
                            <div v-if="form.errors.checkout" class="text-red-400 text-sm p-3 bg-red-400/10 rounded-lg">
                                {{ form.errors.checkout }}
                            </div>
                            <button type="submit" :disabled="form.processing" class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-blue-500/30 transition-all disabled:opacity-50 mt-6">
                                {{ form.processing ? 'Procesando...' : 'Simular Compra' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>
