<script setup>
import PersonAvatar from './PersonAvatar.vue';

defineProps({
    person: {
        type: Object,
        required: true,
    },
    avatarSize: {
        type: String,
        default: 'md',
    },
    compact: Boolean,
});

function year(value) {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    if (typeof value === 'object') {
        return value.year || value.value || '';
    }

    return value;
}
</script>

<template>
    <div class="flex min-w-0 items-center gap-3">
        <PersonAvatar :person="person" :size="avatarSize" />
        <div class="min-w-0">
            <p class="truncate font-medium text-ink-950">{{ person.name || 'Nama belum tersedia' }}</p>
            <p v-if="!compact" class="mt-0.5 truncate text-xs text-ink-500">
                <span v-if="person.gender">{{ person.gender }}</span>
                <span v-if="person.gender && (person.birth || person.birthYear || person.death || person.deathYear)"> · </span>
                <span v-if="person.birth || person.birthYear">{{ year(person.birth || person.birthYear) }}</span>
                <span v-if="person.death || person.deathYear"> — {{ year(person.death || person.deathYear) }}</span>
            </p>
        </div>
    </div>
</template>
