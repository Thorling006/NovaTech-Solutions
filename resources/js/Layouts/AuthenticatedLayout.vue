<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div>
        <div class="min-h-screen bg-zinc-950">
            <nav class="border-b border-zinc-800/50 bg-zinc-950/80 backdrop-blur-xl sticky top-0 z-30 animate-fade-in-down">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')" class="flex items-center gap-2.5 group">
                                    <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center transition-transform duration-300 group-hover:scale-110">
                                        <span class="text-zinc-950 font-bold text-sm">N</span>
                                    </div>
                                    <span class="text-white font-semibold text-sm hidden sm:block">NovaStock</span>
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-6 sm:-my-px sm:ms-8 sm:flex">
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    Dashboard
                                </NavLink>
                                <NavLink
                                    v-if="$page.props.auth.user.role_id === 1"
                                    :href="route('users.index')"
                                    :active="route().current('users.*')"
                                >
                                    Usuarios
                                </NavLink>
                                <NavLink
                                    v-if="$page.props.auth.user.role_id === 1 || $page.props.auth.user.role_id === 2"
                                    :href="route('categorias.index')"
                                    :active="route().current('categorias.*')"
                                >
                                    Categorías
                                </NavLink>
                                <NavLink
                                    v-if="$page.props.auth.user.role_id === 1 || $page.props.auth.user.role_id === 2"
                                    :href="route('productos.index')"
                                    :active="route().current('productos.*')"
                                >
                                    Productos
                                </NavLink>
                                <NavLink
                                    v-if="$page.props.auth.user.role_id === 1 || $page.props.auth.user.role_id === 2"
                                    :href="route('ventas.index')"
                                    :active="route().current('ventas.*')"
                                >
                                    Ventas Sim.
                                </NavLink>
                                <NavLink
                                    :href="route('movimientos.index')"
                                    :active="route().current('movimientos.*')"
                                >
                                    Movimientos
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <!-- Settings Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-xl">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-xl border border-zinc-800 bg-zinc-900 px-3 py-2 text-sm font-medium text-zinc-400 transition-all duration-200 hover:text-white hover:border-zinc-700 focus:outline-none"
                                            >
                                                <div class="w-6 h-6 rounded-full bg-zinc-700 flex items-center justify-center mr-2 text-xs text-white font-medium">
                                                    {{ $page.props.auth.user.name.charAt(0) }}
                                                </div>
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4 transition-transform duration-200"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            Profile
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-xl p-2 text-zinc-500 transition-all duration-200 hover:bg-zinc-800 hover:text-white focus:outline-none"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 -translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-2"
                >
                    <div
                        v-show="showingNavigationDropdown"
                        class="sm:hidden border-t border-zinc-800/50"
                    >
                        <div class="space-y-1 pb-3 pt-2">
                            <ResponsiveNavLink
                                :href="route('dashboard')"
                                :active="route().current('dashboard')"
                            >
                                Dashboard
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                v-if="$page.props.auth.user.role_id === 1"
                                :href="route('users.index')"
                                :active="route().current('users.*')"
                            >
                                Usuarios
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                v-if="$page.props.auth.user.role_id === 1 || $page.props.auth.user.role_id === 2"
                                :href="route('categorias.index')"
                                :active="route().current('categorias.*')"
                            >
                                Categorías
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                v-if="$page.props.auth.user.role_id === 1 || $page.props.auth.user.role_id === 2"
                                :href="route('productos.index')"
                                :active="route().current('productos.*')"
                            >
                                Productos
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                v-if="$page.props.auth.user.role_id === 1 || $page.props.auth.user.role_id === 2"
                                :href="route('ventas.index')"
                                :active="route().current('ventas.*')"
                            >
                                Ventas Sim.
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('movimientos.index')"
                                :active="route().current('movimientos.*')"
                            >
                                Movimientos
                            </ResponsiveNavLink>
                        </div>

                        <!-- Responsive Settings Options -->
                        <div class="border-t border-zinc-800/50 pb-1 pt-4">
                            <div class="px-4">
                                <div class="text-base font-medium text-zinc-200">
                                    {{ $page.props.auth.user.name }}
                                </div>
                                <div class="text-sm font-medium text-zinc-500">
                                    {{ $page.props.auth.user.email }}
                                </div>
                            </div>

                            <div class="mt-3 space-y-1">
                                <ResponsiveNavLink :href="route('profile.edit')">
                                    Profile
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                >
                                    Log Out
                                </ResponsiveNavLink>
                            </div>
                        </div>
                    </div>
                </Transition>
            </nav>

            <!-- Page Heading -->
            <header
                class="border-b border-zinc-800/30"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8 animate-fade-in">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="animate-fade-in-up">
                <slot />
            </main>
        </div>
    </div>
</template>
