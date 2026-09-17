<script setup>
defineProps({
    label: {
        type: String,
        default: 'Memuat data...',
    },
});
</script>

<template>
    <div class="flex items-center gap-3 rounded-card border border-ink-200 bg-white px-5 py-4 text-sm text-ink-500" role="status">
        <span class="h-4 w-4 animate-spin rounded-full border-2 border-ink-200 border-t-brand-600" aria-hidden="true" />
        <span>{{ label }}</span>
    </div>
</template>
