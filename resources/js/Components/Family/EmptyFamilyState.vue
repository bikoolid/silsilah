<script setup>
import EmptyState from '../UI/EmptyState.vue';

defineProps({
    title: {
        type: String,
        default: 'Belum ada data keluarga',
    },
    description: {
        type: String,
        default: 'Informasi keluarga belum tersedia.',
    },
});
</script>

<template>
    <EmptyState :title="title" :description="description" />
</template>
