<script setup>
import EmptyFamilyState from './EmptyFamilyState.vue';
import RelationshipItem from './RelationshipItem.vue';

defineProps({
    members: {
        type: Array,
        default: () => [],
    },
    emptyTitle: {
        type: String,
        default: 'Belum ada anggota keluarga',
    },
    emptyDescription: {
        type: String,
        default: 'Informasi keluarga belum tersedia.',
    },
    relationshipLabel: {
        type: String,
        default: '',
    },
    selectedId: {
        type: [Number, String],
        default: null,
    },
});

defineEmits(['select']);
</script>

<template>
    <div v-if="members.length" class="grid gap-3">
        <RelationshipItem
            v-for="member in members"
            :key="member.id || member.name"
            :person="member"
            :relationship-label="relationshipLabel"
            :selected="selectedId !== null && String(selectedId) === String(member.id)"
            @select="$emit('select', $event)"
        />
    </div>
    <EmptyFamilyState v-else :title="emptyTitle" :description="emptyDescription" />
</template>
