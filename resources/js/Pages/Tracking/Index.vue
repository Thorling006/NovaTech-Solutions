<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    tracking_id: ''
});

const submit = () => {
    form.post(route('tracking.search'));
};
</script>

<template>
    <Head title="Seguimiento de Pedido - NovaStock" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 flex flex-col font-sans relative overflow-x-hidden">
        <!-- Fondo Decorativo -->
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-zinc-800/10 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="absolute bottom-10 right-1/4 w-96 h-96 bg-zinc-700/10 rounded-full blur-[120px] pointer-events-none"></div>

        <!-- Barra de Navegación Simple -->
        <header class="border-b border-zinc-900 bg-zinc-950/80 backdrop-blur-md sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
                <Link :href="route('home')" class="flex items-center gap-3 cursor-pointer">
                    <span class="text-2xl font-black tracking-tight bg-gradient-to-r from-white via-zinc-300 to-zinc-500 bg-clip-text text-transparent">
                        NovaTech Solutions
                    </span>
                    <span class="text-[10px] uppercase font-bold tracking-widest text-zinc-500 border border-zinc-800/80 rounded px-1.5 py-0.5 bg-zinc-900/50 hidden sm:inline">
                        Seguimiento
                    </span>
                </Link>
                <Link :href="route('home')" class="text-zinc-400 font-medium hover:text-white transition text-sm">
                    Volver a la tienda
                </Link>
            </div>
        </header>

        <main class="flex-grow flex items-center justify-center p-6 relative z-10">
            <div class="w-full max-w-md">
                <div class="text-center mb-10">
                    <h1 class="text-4xl font-black text-white tracking-tighter mb-4">Rastrea tu pedido</h1>
                    <p class="text-zinc-400 text-sm font-light">Ingresa tu código numérico de seguimiento de 10 dígitos para conocer el estado actual de tu entrega.</p>
                </div>

                <div class="bg-zinc-900/80 backdrop-blur-xl border border-zinc-800 p-8 rounded-3xl shadow-2xl">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div v-if="$page.props.flash && $page.props.flash.error" class="bg-rose-500/10 border border-rose-500/20 text-rose-500 px-4 py-3 rounded-xl text-sm font-medium">
                            {{ $page.props.flash.error }}
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-400 uppercase tracking-wider mb-2">Código de Seguimiento</label>
                            <input 
                                type="text" 
                                v-model="form.tracking_id" 
                                placeholder="Ej. 1054329087" 
                                class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-4 text-center text-xl font-mono text-white placeholder-zinc-700 focus:outline-none focus:border-white focus:ring-1 focus:ring-white transition-all"
                                required
                            />
                            <span v-if="form.errors.tracking_id" class="text-rose-500 text-xs mt-2 block text-center">
                                {{ form.errors.tracking_id }}
                            </span>
                        </div>

                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="w-full bg-white text-zinc-950 py-4 rounded-xl font-bold text-sm hover:bg-zinc-200 transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="form.processing">Buscando...</span>
                            <span v-else>Rastrear Pedido</span>
                            <svg v-if="!form.processing" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</template>
