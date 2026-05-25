<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-2 border-white text-start text-base font-medium text-white bg-zinc-800/50 focus:outline-none transition-all duration-200'
        : 'block w-full ps-3 pe-4 py-2 border-l-2 border-transparent text-start text-base font-medium text-zinc-500 hover:text-zinc-200 hover:bg-zinc-800/30 hover:border-zinc-600 focus:outline-none transition-all duration-200',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
