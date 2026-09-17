<script setup>
import { ref } from 'vue';

defineProps({
    label: {
        type: String,
        default: 'Menu',
    },
});

const open = ref(false);
</script>

<template>
    <div class="relative">
        <button
            type="button"
            class="inline-flex h-11 items-center gap-2 rounded-control border border-ink-200 bg-white px-4 text-sm font-medium text-ink-700 hover:bg-ink-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
            :aria-expanded="open"
            @click="open = !open"
        >
            {{ label }}
            <span aria-hidden="true" class="text-ink-500">⌄</span>
        </button>
        <div v-if="open" class="absolute right-0 z-10 mt-2 min-w-44 rounded-control border border-ink-200 bg-white p-1 shadow-popover">
            <slot @click="open = false" />
        </div>
    </div>
</template>
