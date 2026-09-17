<script setup>
import { computed } from 'vue';
import Badge from '../UI/Badge.vue';
import PersonAvatar from './PersonAvatar.vue';

const props = defineProps({
    person: {
        type: Object,
        required: true,
    },
    relationshipLabel: {
        type: String,
        default: '',
    },
    secondaryLabel: {
        type: String,
        default: '',
    },
    selected: Boolean,
});

defineEmits(['select']);

const years = computed(() => {
    const birth = props.person.birthYear || props.person.birth || '';
    const death = props.person.deathYear || props.person.death || '';

    if (!birth && !death) return '';
    if (!birth) return `Wafat ${death}`;
    if (!death) return `Lahir ${birth}`;

    return `${birth}–${death}`;
});

const accessibleLabel = computed(() => {
    const role = props.relationshipLabel ? `${props.relationshipLabel}: ` : '';
    return `${role}${props.person.name || 'Nama belum tersedia'}`;
});
</script>

<template>
    <button
        type="button"
        class="flex min-h-16 w-full items-center gap-3 rounded-control border border-ink-200 bg-white px-3 py-3 text-left transition hover:border-brand-200 hover:bg-brand-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
        :class="{ 'border-brand-500 bg-brand-50 ring-2 ring-brand-200': selected }"
        :aria-label="accessibleLabel"
        :aria-pressed="selected"
        @click="$emit('select', person)"
    >
        <PersonAvatar :person="person" size="md" />
        <span class="min-w-0 flex-1">
            <span class="flex flex-wrap items-center gap-2">
                <span class="truncate font-semibold text-ink-950">{{ person.name || 'Nama belum tersedia' }}</span>
                <Badge v-if="relationshipLabel" tone="info">{{ relationshipLabel }}</Badge>
            </span>
            <span class="mt-1 flex flex-wrap gap-x-2 gap-y-1 text-xs text-ink-500">
                <span v-if="person.gender">{{ person.gender }}</span>
                <span v-if="person.gender && years">·</span>
                <span v-if="years">{{ years }}</span>
                <span v-if="secondaryLabel">{{ secondaryLabel }}</span>
            </span>
        </span>
        <span aria-hidden="true" class="text-lg text-ink-400">›</span>
    </button>
</template>
