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
        ? 'inline-flex items-center px-1 pt-1 border-b-2 border-white text-sm font-medium leading-5 text-white transition-all duration-200'
        : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-zinc-500 hover:text-zinc-200 hover:border-zinc-600 transition-all duration-200',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
