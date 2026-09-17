<script setup>
defineProps({
    title: {
        type: String,
        default: 'Belum ada data',
    },
    description: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="rounded-card border border-dashed border-ink-200 bg-white px-6 py-10 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-100 text-brand-700">
            <span aria-hidden="true" class="text-lg">—</span>
        </div>
        <h3 class="mt-4 text-sm font-semibold text-ink-950">{{ title }}</h3>
        <p v-if="description" class="mx-auto mt-2 max-w-sm text-sm leading-6 text-ink-500">{{ description }}</p>
        <div v-if="$slots.action" class="mt-5">
            <slot name="action" />
        </div>
    </div>
</template>
