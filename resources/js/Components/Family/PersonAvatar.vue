<script setup>
import { computed } from 'vue';
import Avatar from '../UI/Avatar.vue';

const props = defineProps({
    person: {
        type: Object,
        required: true,
    },
    size: {
        type: String,
        default: 'md',
    },
});

const status = computed(() => {
    if (props.person.deceased || props.person.death || props.person.deathYear || props.person.deathDate) {
        return 'deceased';
    }

    return props.person.deceased === false ? 'living' : '';
});
</script>

<template>
    <Avatar :name="person.name || 'Person'" :src="person.photo || ''" :size="size" :status="status" />
</template>
