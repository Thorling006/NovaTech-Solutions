<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ users: Array });
const form = useForm({});

const deleteUser = (user) => { 
    if (confirm('¿Estás seguro de que deseas eliminar este usuario?')) { 
        form.delete(route('users.destroy', user.id)); 
    } 
};

// Group users by role name
const groupedUsers = computed(() => {
    const groups = {};
    props.users.forEach(user => {
        const roleName = user.role?.nombre || 'Sin Cargo';
        if (!groups[roleName]) {
            groups[roleName] = [];
        }
        groups[roleName].push(user);
    });
    return groups;
});
</script>

<template>
    <Head title="Usuarios" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-white leading-tight">Equipo & Cargos</h2>
                    <p class="text-xs text-zinc-400 mt-1">Gestión y visualización de todos los colaboradores del panel administrativo.</p>
                </div>
                <Link :href="route('users.create')" class="btn-primary text-sm px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-lg shadow-lg shadow-indigo-600/10 hover:shadow-indigo-600/20 transition-all duration-200">
                    + Nuevo Usuario
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Flash messages -->
                <div v-if="$page.props.flash && $page.props.flash.success" class="mb-6 p-4 bg-emerald-950/40 border border-emerald-800/80 rounded-xl text-emerald-400 text-sm">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash && $page.props.flash.error" class="mb-6 p-4 bg-rose-950/40 border border-rose-800/80 rounded-xl text-rose-400 text-sm">
                    {{ $page.props.flash.error }}
                </div>

                <!-- Empty state -->
                <div v-if="users.length === 0" class="card p-12 text-center text-zinc-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-zinc-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <p class="font-medium text-zinc-300">No hay usuarios registrados</p>
                    <p class="text-sm text-zinc-500 mt-1">Crea un nuevo usuario para empezar a colaborar.</p>
                </div>

                <!-- Grouped Users Cards -->
                <div v-else class="space-y-10">
                    <div v-for="(usersInRole, roleName) in groupedUsers" :key="roleName" class="space-y-4">
                        <!-- Group Header -->
                        <div class="flex items-center gap-3 border-b border-zinc-800/80 pb-2">
                            <h3 class="text-sm font-semibold text-zinc-400 tracking-wider uppercase">{{ roleName }}</h3>
                            <span class="px-2 py-0.5 text-xs font-semibold bg-zinc-800/60 text-zinc-300 rounded-full border border-zinc-700/60">
                                {{ usersInRole.length }}
                            </span>
                        </div>

                        <!-- Cards Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div 
                                v-for="user in usersInRole" 
                                :key="user.id" 
                                class="group relative bg-zinc-950/40 border border-zinc-800/80 hover:border-zinc-700/80 rounded-xl p-4 flex items-center justify-between transition-all duration-300 hover:shadow-lg hover:shadow-black/25"
                            >
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <!-- User Photo / Placeholder -->
                                    <div class="relative flex-shrink-0 w-11 h-11 rounded-full overflow-hidden border border-zinc-700 bg-zinc-800 flex items-center justify-center shadow-inner">
                                        <img 
                                            v-if="user.foto_url" 
                                            :src="user.foto_url" 
                                            alt="Foto de perfil" 
                                            class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" 
                                        />
                                        <span v-else class="text-base font-bold text-zinc-400 uppercase">
                                            {{ user.name.charAt(0) }}
                                        </span>
                                    </div>
                                    
                                    <!-- User Info -->
                                    <div class="min-w-0">
                                        <h4 class="text-sm font-medium text-zinc-100 truncate group-hover:text-white transition-colors duration-150">
                                            {{ user.name }}
                                        </h4>
                                        <p class="text-xs text-zinc-400 truncate mt-0.5">
                                            {{ user.email }}
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Quick Actions -->
                                <div class="flex items-center gap-1.5 opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity duration-200 ml-4 flex-shrink-0">
                                    <Link 
                                        :href="route('users.edit', user.id)" 
                                        class="p-2 text-zinc-400 hover:text-white hover:bg-zinc-800 rounded-lg transition-all duration-150"
                                        title="Editar Usuario"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>
                                    <button 
                                        @click="deleteUser(user)" 
                                        class="p-2 text-zinc-500 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-all duration-150"
                                        title="Eliminar Usuario"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
