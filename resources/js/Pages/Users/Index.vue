<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ users: Array });
const form = useForm({});
const deleteUser = (user) => { if (confirm('¿Estás seguro de que deseas eliminar este usuario?')) { form.delete(route('users.destroy', user.id)); } };
</script>

<template>
    <Head title="Usuarios" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-white leading-tight">Gestión de Usuarios</h2>
                <Link :href="route('users.create')" class="btn-primary text-sm">+ Nuevo Usuario</Link>
            </div>
        </template>
        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="card overflow-hidden">
                    <div class="p-6">
                        <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 flash-success">{{ $page.props.flash.success }}</div>
                        <div v-if="$page.props.flash && $page.props.flash.error" class="mb-4 flash-error">{{ $page.props.flash.error }}</div>
                        <table class="table-minimal">
                            <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th class="text-right">Acciones</th></tr></thead>
                            <tbody>
                                <tr v-for="user in users" :key="user.id">
                                    <td class="font-medium text-zinc-200">{{ user.name }}</td>
                                    <td>{{ user.email }}</td>
                                    <td><span class="badge badge-info">{{ user.role?.nombre }}</span></td>
                                    <td class="text-right space-x-4">
                                        <Link :href="route('users.edit', user.id)" class="link-action">Editar</Link>
                                        <button @click="deleteUser(user)" class="link-action-danger">Eliminar</button>
                                    </td>
                                </tr>
                                <tr v-if="users.length === 0"><td colspan="4" class="text-center text-zinc-600 py-8">No hay usuarios registrados.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
