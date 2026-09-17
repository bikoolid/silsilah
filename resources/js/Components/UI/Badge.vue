<script setup>
import { computed } from 'vue';

const props = defineProps({
    tone: {
        type: String,
        default: 'neutral',
    },
});

const classes = computed(() => [
    'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold transition-all duration-200',
    {
        'bg-ink-100 text-ink-700 ring-1 ring-ink-200': props.tone === 'neutral',
        'bg-brand-100 text-brand-700 ring-1 ring-brand-200': props.tone === 'brand',
        'bg-amber-100 text-amber-800 ring-1 ring-amber-200': props.tone === 'warning',
        'bg-red-100 text-red-700 ring-1 ring-red-200': props.tone === 'danger',
        'bg-sky-100 text-sky-700 ring-1 ring-sky-200': props.tone === 'info',
        'bg-purple-100 text-purple-700 ring-1 ring-purple-200': props.tone === 'purple',
    },
]);
</script>

<template>
    <span :class="classes"><slot /></span>
</template>
