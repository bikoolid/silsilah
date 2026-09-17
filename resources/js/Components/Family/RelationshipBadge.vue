<script setup>
import { computed } from 'vue';
import Badge from '../UI/Badge.vue';

const props = defineProps({
    relationship: {
        type: String,
        required: true,
    },
});

const tone = computed(() => ({
    parent: 'info',
    child: 'brand',
    spouse: 'warning',
    sibling: 'neutral',
}[props.relationship] || 'neutral'));
</script>

<template>
    <Badge :tone="tone">{{ relationship }}</Badge>
</template>
