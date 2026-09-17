<script setup>
import { computed } from 'vue';
import Badge from '../UI/Badge.vue';
import Card from '../UI/Card.vue';
import PersonAvatar from './PersonAvatar.vue';

const props = defineProps({
    person: {
        type: Object,
        required: true,
    },
    selected: Boolean,
    clickable: Boolean,
});

const emit = defineEmits(['select']);

const classes = computed(() => ({
    'ring-2 ring-brand-500 ring-offset-2 shadow-glow': props.selected,
    'opacity-75': props.person.deceased || props.person.death || props.person.deathYear,
    'cursor-pointer hover:border-brand-300 hover:shadow-lg hover:scale-[1.02]': props.clickable,
}));

function year(value) {
    if (value === null || value === undefined || value === '') return '';
    return typeof value === 'object' ? value.year || value.value || '' : value;
}
</script>

<template>
    <Card>
        <button
            v-if="clickable"
            type="button"
            class="block w-full text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 transition-all duration-200"
            :aria-pressed="selected"
            @click="emit('select', person)"
        >
            <div class="flex items-start gap-4" :class="classes">
                <PersonAvatar :person="person" size="lg" />
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="truncate font-semibold text-ink-900">{{ person.name || 'Nama belum tersedia' }}</h3>
                        <Badge v-if="person.deceased || person.death || person.deathYear" tone="neutral">Wafat</Badge>
                    </div>
                    <p v-if="person.gender" class="mt-1 text-sm text-ink-600">{{ person.gender }}</p>
                    <p v-if="person.birth || person.birthYear || person.death || person.deathYear" class="mt-2 text-sm text-ink-800">
                        <span v-if="person.birth || person.birthYear">{{ year(person.birth || person.birthYear) }}</span>
                        <span v-if="person.death || person.deathYear"> — {{ year(person.death || person.deathYear) }}</span>
                    </p>
                </div>
            </div>
        </button>
        <div v-else class="flex items-start gap-4" :class="classes">
            <PersonAvatar :person="person" size="lg" />
            <div class="min-w-0 flex-1">
                <h3 class="truncate font-semibold text-ink-900">{{ person.name || 'Nama belum tersedia' }}</h3>
                <p v-if="person.gender" class="mt-1 text-sm text-ink-600">{{ person.gender }}</p>
                <p v-if="person.birth || person.birthYear || person.death || person.deathYear" class="mt-2 text-sm text-ink-800">
                    <span v-if="person.birth || person.birthYear">{{ year(person.birth || person.birthYear) }}</span>
                    <span v-if="person.death || person.deathYear"> — {{ year(person.death || person.deathYear) }}</span>
                </p>
            </div>
        </div>
    </Card>
</template>
