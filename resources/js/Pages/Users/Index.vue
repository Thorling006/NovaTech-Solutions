<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    users: Array,
});

const form = useForm({});

const deleteUser = (user) => {
    if (confirm('¿Estás seguro de que deseas eliminar este usuario?')) {
        form.delete(route('users.destroy', user.id));
    }
};
</script>

<template>
    <Head title="Usuarios" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Usuarios</h2>
                <Link
                    :href="route('users.create')"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                    Nuevo Usuario
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
                                    <th class="p-4 border-b border-gray-100 bg-gray-50">Correo</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50">Rol</th>
                                    <th class="p-4 border-b border-gray-100 bg-gray-50 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
                                    <td class="p-4 border-b border-gray-50">{{ user.name }}</td>
                                    <td class="p-4 border-b border-gray-50">{{ user.email }}</td>
                                    <td class="p-4 border-b border-gray-50">
                                        <span class="px-2 py-1 bg-gray-200 rounded text-sm">{{ user.role?.nombre }}</span>
                                    </td>
                                    <td class="p-4 border-b border-gray-50 text-right">
                                        <Link :href="route('users.edit', user.id)" class="text-blue-500 hover:underline mr-4">Editar</Link>
                                        <button @click="deleteUser(user)" class="text-red-500 hover:underline">Eliminar</button>
                                    </td>
                                </tr>
                                <tr v-if="users.length === 0">
                                    <td colspan="4" class="p-4 text-center text-gray-500">No hay usuarios registrados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
