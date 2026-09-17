<script setup>
import Panel from '../UI/Panel.vue';
import PersonCard from './PersonCard.vue';
import FamilyMemberList from './FamilyMemberList.vue';
import CoupleCard from './CoupleCard.vue';

defineProps({
    person: {
        type: Object,
        default: null,
    },
    parents: {
        type: Array,
        default: () => [],
    },
    spouses: {
        type: Array,
        default: () => [],
    },
    children: {
        type: Array,
        default: () => [],
    },
    cemetery: {
        type: [String, Object],
        default: null,
    },
});

defineEmits(['select-person']);
</script>

<template>
    <Panel>
        <template #header>
            <h2 class="font-semibold text-ink-950">Family Detail</h2>
        </template>

        <div v-if="person" class="space-y-6">
            <PersonCard :person="person" />

            <section>
                <h3 class="mb-3 text-sm font-semibold text-ink-700">Parents</h3>
                <FamilyMemberList :members="parents" empty-title="Data orang tua belum tersedia" @select="$emit('select-person', $event)" />
            </section>

            <section>
                <h3 class="mb-3 text-sm font-semibold text-ink-700">Spouses</h3>
                <div v-if="spouses.length" class="space-y-3">
                    <CoupleCard v-for="couple in spouses" :key="couple.id || couple.husband?.id || couple.wife?.id" :husband="couple.husband" :wife="couple.wife" :marriage-date="couple.marriageDate" :divorce-date="couple.divorceDate" :status="couple.status" />
                </div>
                <FamilyMemberList v-else :members="[]" empty-title="Data pasangan belum tersedia" />
            </section>

            <section>
                <h3 class="mb-3 text-sm font-semibold text-ink-700">Children</h3>
                <FamilyMemberList :members="children" empty-title="Data anak belum tersedia" @select="$emit('select-person', $event)" />
            </section>

            <section v-if="cemetery">
                <h3 class="mb-2 text-sm font-semibold text-ink-700">Cemetery</h3>
                <p class="rounded-control bg-ink-100/60 px-3 py-2 text-sm leading-6 text-ink-700">
                    {{ typeof cemetery === 'object' ? cemetery.name || cemetery.address || cemetery.value : cemetery }}
                </p>
            </section>
        </div>
        <EmptyFamilyState v-else title="Belum ada Person dipilih" description="Pilih Person untuk melihat detail keluarga." />
    </Panel>
</template>
