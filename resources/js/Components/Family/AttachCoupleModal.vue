<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Button from '../UI/Button.vue';
import Input from '../UI/Input.vue';
import Modal from '../UI/Modal.vue';
import Select from '../UI/Select.vue';

const props = defineProps({
    modelValue: Boolean,
    person: { type: Object, required: true },
    peopleOptions: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue', 'saved']);
const form = useForm({ person_id: props.person.id, partner_id: '', marriage_date: '', divorce_date: '', status: 'married' });
const partnerOptions = computed(() => props.peopleOptions
    .filter((person) => person.id !== props.person.id)
    .map((person) => ({ value: person.id, label: person.name })));

function submit() {
    form.post('/couples', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('partner_id', 'marriage_date', 'divorce_date');
            emit('update:modelValue', false);
            emit('saved');
        },
    });
}
</script>

<template>
    <Modal :model-value="modelValue" title="Tambah Pasangan" @update:model-value="$emit('update:modelValue', $event)">
        <form class="space-y-4" @submit.prevent="submit">
            <Select id="attach-couple-partner" v-model="form.partner_id" label="Person pasangan" :options="[{ value: '', label: 'Pilih Person' }, ...partnerOptions]" />
            <Input id="attach-couple-marriage" v-model="form.marriage_date" type="date" label="Tanggal pernikahan" :error="form.errors.marriage_date" />
            <Input id="attach-couple-divorce" v-model="form.divorce_date" type="date" label="Tanggal perceraian" :error="form.errors.divorce_date" />
            <Input id="attach-couple-status" v-model="form.status" label="Status hubungan" :error="form.errors.status" />
            <p v-if="form.errors.partner_id" class="text-sm text-red-700">{{ form.errors.partner_id }}</p>
            <div class="flex justify-end gap-2">
                <Button type="button" variant="ghost" @click="$emit('update:modelValue', false)">Batal</Button>
                <Button type="submit" :disabled="form.processing">Simpan</Button>
            </div>
        </form>
    </Modal>
</template>
