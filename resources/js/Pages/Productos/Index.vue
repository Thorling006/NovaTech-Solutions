<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    productos: Array,
});

const form = useForm({});

const deleteProducto = (producto) => {
    if (confirm('¿Estás seguro de que deseas eliminar este producto?')) {
        form.delete(route('productos.destroy', producto.id));
    }
};

const getBadgeClass = (estado) => {
    switch(estado) {
        case 'disponible': return 'bg-green-100 text-green-800';
        case 'stock_bajo': return 'bg-yellow-100 text-yellow-800';
        case 'agotado': return 'bg-red-100 text-red-800';
        case 'inactivo': return 'bg-gray-100 text-gray-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <Head title="Productos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Catálogo de Productos</h2>
                <Link
                    :href="route('productos.create')"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                    Nuevo Producto
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 overflow-x-auto">
                        <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 text-green-600 bg-green-100 p-3 rounded">
                            {{ $page.props.flash.success }}
                        </div>
                        <div v-if="$page.props.flash && $page.props.flash.error" class="mb-4 text-red-600 bg-red-100 p-3 rounded">
                            {{ $page.props.flash.error }}
                        </div>

                        <table class="w-full text-left table-auto min-w-max">
                            <thead>
                                <tr>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50">Img</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50">Código</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50">Nombre</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50">Categoría</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50">Precio</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50">Stock</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50">Estado</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="producto in productos" :key="producto.id" class="hover:bg-gray-50">
                                    <td class="p-4 border-b border-gray-50">
                                        <img v-if="producto.imagen" :src="`/storage/${producto.imagen}`" class="w-10 h-10 object-cover rounded" />
                                        <div v-else class="w-10 h-10 bg-gray-200 rounded flex items-center justify-center text-xs text-gray-500">N/A</div>
                                    </td>
                                    <td class="p-4 border-b border-gray-50 font-mono text-sm">{{ producto.codigo }}</td>
                                    <td class="p-4 border-b border-gray-50">{{ producto.nombre }}</td>
                                    <td class="p-4 border-b border-gray-50">{{ producto.categoria?.nombre }}</td>
                                    <td class="p-4 border-b border-gray-50">${{ producto.precio }}</td>
                                    <td class="p-4 border-b border-gray-50">{{ producto.stock_actual }}</td>
                                    <td class="p-4 border-b border-gray-50">
                                        <span :class="getBadgeClass(producto.estado)" class="px-2 py-1 rounded text-xs uppercase font-bold">
                                            {{ producto.estado.replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="p-4 border-b border-gray-50 text-right">
                                        <Link :href="route('productos.edit', producto.id)" class="text-blue-500 hover:underline mr-4">Editar</Link>
                                        <button @click="deleteProducto(producto)" class="text-red-500 hover:underline">Eliminar</button>
                                    </td>
                                </tr>
                                <tr v-if="productos.length === 0">
                                    <td colspan="8" class="p-4 text-center text-gray-500">No hay productos registrados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
