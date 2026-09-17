<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: {
        type: String,
        required: true,
    },
    src: {
        type: String,
        default: '',
    },
    size: {
        type: String,
        default: 'md',
    },
    status: {
        type: String,
        default: '',
        validator: (value) => ['', 'living', 'deceased'].includes(value),
    },
});

const initials = computed(() => props.name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase());

const sizeClass = computed(() => ({
    sm: 'h-8 w-8 text-xs',
    md: 'h-11 w-11 text-sm',
    lg: 'h-16 w-16 text-lg',
}[props.size] ?? 'h-11 w-11 text-sm'));

const statusClass = computed(() => props.status === 'deceased' ? 'avatar-status-deceased' : 'avatar-status-living');
</script>

<template>
    <div class="avatar-shell relative inline-flex shrink-0 items-center justify-center" :class="sizeClass">
        <div class="inline-flex h-full w-full items-center justify-center overflow-hidden rounded-full bg-brand-100 font-semibold text-brand-700">
        <img v-if="src" :src="src" :alt="name" class="h-full w-full object-cover">
        <span v-else aria-hidden="true">{{ initials }}</span>
        </div>
        <span
            v-if="status"
            class="avatar-status absolute bottom-0 right-0 rounded-full border-2 border-white"
            :class="statusClass"
            :aria-label="status === 'deceased' ? 'Telah wafat' : 'Masih hidup'"
            :title="status === 'deceased' ? 'Telah wafat' : 'Masih hidup'"
        />
    </div>
</template>

<style scoped>
.avatar-status {
    height: 0.7rem;
    width: 0.7rem;
    animation: avatar-status-pulse 1.7s ease-in-out infinite;
}

.avatar-status-living {
    background-color: #22a06b;
    box-shadow: 0 0 0 0 rgba(34, 160, 107, 0.6);
}

.avatar-status-deceased {
    background-color: #8793a3;
    box-shadow: 0 0 0 0 rgba(135, 147, 163, 0.6);
    animation-name: avatar-status-pulse-deceased;
}

@keyframes avatar-status-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(34, 160, 107, 0.55); opacity: 0.72; }
    50% { box-shadow: 0 0 0 0.28rem rgba(34, 160, 107, 0); opacity: 1; }
}

@keyframes avatar-status-pulse-deceased {
    0%, 100% { box-shadow: 0 0 0 0 rgba(135, 147, 163, 0.55); opacity: 0.72; }
    50% { box-shadow: 0 0 0 0.28rem rgba(135, 147, 163, 0); opacity: 1; }
}
</style>
