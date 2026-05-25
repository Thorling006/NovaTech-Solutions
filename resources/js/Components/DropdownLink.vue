<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-2 text-start text-sm leading-5 text-zinc-400 transition-all duration-200 hover:bg-zinc-800 hover:text-white focus:bg-zinc-800 focus:outline-none"
    >
        <slot />
    </Link>
</template>
