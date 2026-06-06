<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

const props = defineProps({ productos: Array });
const form = useForm({});

const deleteProducto = (producto) => {
    if (confirm('¿Estás seguro de que deseas eliminar este producto?')) {
        form.delete(route('productos.destroy', producto.id));
    }
};

const getBadgeClass = (estado) => {
    switch(estado) {
        case 'disponible': return 'badge-success';
        case 'stock_bajo': return 'badge-warning';
        case 'agotado': return 'badge-danger';
        case 'inactivo': return 'badge-neutral';
        default: return 'badge-neutral';
    }
};

let refreshInterval = null;
onMounted(() => {
    refreshInterval = setInterval(() => {
        router.reload({ preserveState: true, preserveScroll: true });
    }, 60000);
});
onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});
</script>

<template>
    <Head title="Productos" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-white leading-tight">Catálogo de Productos</h2>
                <Link :href="route('productos.create')" class="btn-primary text-sm">+ Nuevo Producto</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="card overflow-hidden">
                    <div class="p-6 overflow-x-auto">
                        <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 flash-success">{{ $page.props.flash.success }}</div>
                        <div v-if="$page.props.flash && $page.props.flash.error" class="mb-4 flash-error">{{ $page.props.flash.error }}</div>

                        <table class="table-minimal">
                            <thead>
                                <tr>
                                    <th>Img</th><th>Código</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Estado</th><th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="producto in productos" :key="producto.id">
                                    <td>
                                        <img v-if="producto.imagen" :src="`/storage/${producto.imagen}`" class="w-10 h-10 object-cover rounded-lg border border-zinc-800" />
                                        <div v-else class="w-10 h-10 bg-zinc-800 rounded-lg flex items-center justify-center text-xs text-zinc-600">—</div>
                                    </td>
                                    <td class="font-mono text-xs text-zinc-500">{{ producto.codigo }}</td>
                                    <td class="font-medium text-zinc-200">{{ producto.nombre }}</td>
                                    <td>{{ producto.categoria?.nombre }}</td>
                                    <td class="text-zinc-200 font-medium">${{ producto.precio }}</td>
                                    <td class="font-medium">{{ producto.stock_actual }}</td>
                                    <td>
                                        <span :class="getBadgeClass(producto.estado)" class="badge">{{ producto.estado.replace('_', ' ') }}</span>
                                    </td>
                                    <td class="text-right space-x-4">
                                        <Link :href="route('productos.edit', producto.id)" class="link-action">Editar</Link>
                                        <button @click="deleteProducto(producto)" class="link-action-danger">Eliminar</button>
                                    </td>
                                </tr>
                                <tr v-if="productos.length === 0">
                                    <td colspan="8" class="text-center text-zinc-600 py-8">No hay productos registrados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
