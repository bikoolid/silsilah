<script setup>
defineProps({
    title: {
        type: String,
        default: 'Terjadi kesalahan',
    },
    description: {
        type: String,
        default: 'Silakan coba lagi.',
    },
});
</script>

<template>
    <div class="rounded-card border border-red-200 bg-red-50 px-6 py-8 text-center" role="alert">
        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-red-100 font-semibold text-red-700">!</div>
        <h3 class="mt-3 text-sm font-semibold text-red-900">{{ title }}</h3>
        <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-red-700">{{ description }}</p>
        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>
