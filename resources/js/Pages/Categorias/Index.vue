<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    categorias: Array,
});

const form = useForm({});

const toggleEstado = (categoria) => {
    form.put(route('categorias.update', categoria.id), {
        data: {
            nombre: categoria.nombre,
            estado: !categoria.estado
        }
    });
};
</script>

<template>
    <Head title="Categorías" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Categorías</h2>
                <Link
                    :href="route('categorias.create')"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                    Nueva Categoría
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 text-green-600 bg-green-100 p-3 rounded">
                            {{ $page.props.flash.success }}
                        </div>
                        <div v-if="$page.props.flash && $page.props.flash.error" class="mb-4 text-red-600 bg-red-100 p-3 rounded">
                            {{ $page.props.flash.error }}
                        </div>

                        <table class="w-full text-left table-auto min-w-max">
                            <thead>
                                <tr>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50">Nombre</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50">Estado</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="categoria in categorias" :key="categoria.id" class="hover:bg-gray-50">
                                    <td class="p-4 border-b border-gray-50">{{ categoria.nombre }}</td>
                                    <td class="p-4 border-b border-gray-50">
                                        <span :class="categoria.estado ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 py-1 rounded text-sm">
                                            {{ categoria.estado ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td class="p-4 border-b border-gray-50 text-right">
                                        <Link :href="route('categorias.edit', categoria.id)" class="text-blue-500 hover:underline mr-4">Editar</Link>
                                        <button @click="toggleEstado(categoria)" class="text-orange-500 hover:underline">
                                            {{ categoria.estado ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="categorias.length === 0">
                                    <td colspan="3" class="p-4 text-center text-gray-500">No hay categorías registradas.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
