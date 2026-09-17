<script setup>
import Badge from '../UI/Badge.vue';
import Card from '../UI/Card.vue';
import PersonSummary from './PersonSummary.vue';

defineProps({
    husband: {
        type: Object,
        default: null,
    },
    wife: {
        type: Object,
        default: null,
    },
    marriageDate: {
        type: [String, Object],
        default: '',
    },
    divorceDate: {
        type: [String, Object],
        default: '',
    },
    status: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <Card>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <PersonSummary v-if="husband" :person="husband" class="flex-1" />
            <span v-if="husband && wife" class="self-center text-sm font-semibold text-ink-500">dan</span>
            <PersonSummary v-if="wife" :person="wife" class="flex-1" />
        </div>
        <div v-if="marriageDate || divorceDate || status" class="mt-4 flex flex-wrap items-center gap-2 border-t border-ink-100 pt-4 text-sm text-ink-500">
            <span v-if="marriageDate">Menikah {{ typeof marriageDate === 'object' ? marriageDate.year || marriageDate.value : marriageDate }}</span>
            <span v-if="divorceDate">· Cerai {{ typeof divorceDate === 'object' ? divorceDate.year || divorceDate.value : divorceDate }}</span>
            <Badge v-if="status" tone="neutral">{{ status }}</Badge>
        </div>
    </Card>
</template>
