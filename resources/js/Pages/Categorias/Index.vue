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

const deleteCategoria = (categoria) => {
    if (confirm('¡PELIGRO! ¿Estás seguro de eliminar esta categoría? Esta acción borrará permanentemente TODOS los productos que estén dentro de ella.')) {
        form.delete(route('categorias.destroy', categoria.id));
    }
};
</script>

<template>
    <Head title="Categorías" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-white leading-tight">Gestión de Categorías</h2>
                <Link :href="route('categorias.create')" class="btn-primary text-sm">
                    + Nueva Categoría
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="card overflow-hidden">
                    <div class="p-6">
                        <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 flash-success">
                            {{ $page.props.flash.success }}
                        </div>
                        <div v-if="$page.props.flash && $page.props.flash.error" class="mb-4 flash-error">
                            {{ $page.props.flash.error }}
                        </div>

                        <table class="table-minimal">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Estado</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="categoria in categorias" :key="categoria.id">
                                    <td class="font-medium text-zinc-200">{{ categoria.nombre }}</td>
                                    <td>
                                        <span :class="categoria.estado ? 'badge-success' : 'badge-danger'" class="badge">
                                            {{ categoria.estado ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td class="text-right space-x-4">
                                        <Link :href="route('categorias.edit', categoria.id)" class="link-action">Editar</Link>
                                        <button @click="toggleEstado(categoria)" class="link-action-danger">
                                            {{ categoria.estado ? 'Desactivar' : 'Activar' }}
                                        </button>
                                        <button v-if="$page.props.auth.user.role_id === 1" @click="deleteCategoria(categoria)" class="link-action-danger font-bold uppercase text-xs">
                                            Eliminar
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="categorias.length === 0">
                                    <td colspan="3" class="text-center text-zinc-600 py-8">No hay categorías registradas.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
