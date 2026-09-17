<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
    },
    size: {
        type: String,
        default: 'md',
    },
    type: {
        type: String,
        default: 'button',
    },
    disabled: Boolean,
});

const classes = computed(() => [
    'inline-flex items-center justify-center gap-2 rounded-control font-medium transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 hover-lift',
    {
        'gradient-primary text-white hover:shadow-glow': props.variant === 'primary',
        'border border-ink-200 bg-white text-ink-700 hover:border-brand-300 hover:bg-brand-50': props.variant === 'secondary',
        'text-ink-700 hover:bg-ink-100': props.variant === 'ghost',
        'gradient-accent text-white hover:shadow-glow': props.variant === 'danger',
        'gradient-cool text-white hover:shadow-glow': props.variant === 'info',
        'h-9 px-3 text-sm': props.size === 'sm',
        'h-11 px-4 text-sm': props.size === 'md',
        'h-12 px-5': props.size === 'lg',
    },
]);
</script>

<template>
    <button :class="classes" :disabled="disabled" :type="type">
        <slot />
    </button>
</template>
