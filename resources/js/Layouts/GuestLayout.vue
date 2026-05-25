<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-zinc-950 pt-6 sm:justify-center sm:pt-0">
        <div class="animate-fade-in-down">
            <Link href="/" class="flex items-center gap-3 group">
                <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center transition-all duration-300 group-hover:scale-110 group-hover:rounded-xl">
                    <span class="text-zinc-950 font-bold text-xl">N</span>
                </div>
                <span class="text-white font-semibold text-lg">NovaStock</span>
            </Link>
        </div>

        <div class="mt-8 w-full overflow-hidden card-glass px-8 py-6 sm:max-w-md animate-fade-in-up">
            <slot />
        </div>
    </div>
</template>
