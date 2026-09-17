<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Button from '../UI/Button.vue';
import Modal from '../UI/Modal.vue';
import Select from '../UI/Select.vue';
import Input from '../UI/Input.vue';
import AttachCoupleModal from './AttachCoupleModal.vue';

const props = defineProps({
    person: { type: Object, required: true },
    peopleOptions: { type: Array, default: () => [] },
    coupleOptions: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({ edit: false, delete: false, add_child: false }) },
});

const coupleOpen = ref(false);
const parentsOpen = ref(false);
const childOpen = ref(false);
const parentsForm = useForm({ father_id: props.person.father?.id || '', mother_id: props.person.mother?.id || '', parents_couple_id: '' });
const childForm = useForm({ parents_couple_id: '', child_id: '', birth_order: '' });

const partnerOptions = computed(() => props.peopleOptions.filter((person) => person.id !== props.person.id));
const parentOptions = computed(() => partnerOptions.value);
const childOptions = computed(() => partnerOptions.value.filter((person) => person.id !== props.person.father?.id && person.id !== props.person.mother?.id));
const coupleSelectOptions = computed(() => [
    { value: '', label: 'Pilih Couple' },
    ...props.person.spouses.map((spouse) => ({ value: spouse.id, label: spouseSummary(spouse) })),
]);

function spouseSummary(spouse) {
    return spouse.person?.name || `Couple #${spouse.id}`;
}

function submitParents() {
    parentsForm.put(`/anggota-keluarga/${props.person.id}/parents`, {
        preserveScroll: true,
        onSuccess: () => { parentsOpen.value = false; },
    });
}

function submitChild() {
    if (!childForm.parents_couple_id) return;
    childForm.post(`/couples/${childForm.parents_couple_id}/children`, {
        preserveScroll: true,
        onSuccess: () => { childOpen.value = false; childForm.reset('child_id', 'birth_order'); },
    });
}

function removePerson() {
    if (window.confirm('Hapus Person ini? Tindakan ini tidak dapat dibatalkan.')) {
        useForm({}).delete(`/anggota-keluarga/${props.person.id}`);
    }
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-2" aria-label="Aksi Person">
        <Link
            v-if="can.edit"
            :href="`/anggota-keluarga/${person.id}/edit`"
            class="action-icon group"
            aria-label="Edit Profil"
            title="Edit Profil"
        >
            <span aria-hidden="true">✎</span>
            <span class="action-tooltip">Edit Profil</span>
        </Link>
        <Button v-if="can.edit" variant="secondary" class="action-icon group !h-10 !w-10 !p-0" aria-label="Tambah Pasangan" title="Tambah Pasangan" @click="coupleOpen = true">
            <span aria-hidden="true">♡</span>
            <span class="action-tooltip">Tambah Pasangan</span>
        </Button>
        <Button v-if="can.add_child" variant="secondary" class="action-icon group !h-10 !w-10 !p-0" aria-label="Tambah Anak" title="Tambah Anak" :disabled="!person.spouses?.length" @click="childOpen = true">
            <span aria-hidden="true">♧</span>
            <span class="action-tooltip">Tambah Anak</span>
        </Button>
        <Button v-if="can.edit" variant="ghost" class="action-icon group !h-10 !w-10 !p-0" aria-label="Atur Orang Tua" title="Atur Orang Tua" @click="parentsOpen = true">
            <span aria-hidden="true">⌂</span>
            <span class="action-tooltip">Atur Orang Tua</span>
        </Button>
        <Button v-if="can.delete" variant="ghost" class="action-icon group !h-10 !w-10 !p-0 text-red-700 hover:bg-red-50" aria-label="Hapus Person" title="Hapus Person" @click="removePerson">
            <span aria-hidden="true">×</span>
            <span class="action-tooltip">Hapus Person</span>
        </Button>
    </div>

    <AttachCoupleModal v-model="coupleOpen" :person="person" :people-options="peopleOptions" />

    <Modal v-model="parentsOpen" title="Atur Orang Tua">
        <form class="space-y-4" @submit.prevent="submitParents">
            <Select id="father_id" v-model="parentsForm.father_id" label="Ayah" :options="[{ value: '', label: 'Belum diketahui' }, ...parentOptions.map((item) => ({ value: item.id, label: item.name }))]" />
            <Select id="mother_id" v-model="parentsForm.mother_id" label="Ibu" :options="[{ value: '', label: 'Belum diketahui' }, ...parentOptions.map((item) => ({ value: item.id, label: item.name }))]" />
            <Select id="parents_couple_id" v-model="parentsForm.parents_couple_id" label="Couple orang tua (opsional)" :options="[{ value: '', label: 'Tidak ditentukan' }, ...coupleOptions.map((item) => ({ value: item.id, label: item.label }))]" />
            <p v-if="parentsForm.errors.father_id || parentsForm.errors.parents_couple_id" class="text-sm text-red-700">{{ parentsForm.errors.father_id || parentsForm.errors.parents_couple_id }}</p>
            <div class="flex justify-end gap-2"><Button type="button" variant="ghost" @click="parentsOpen = false">Batal</Button><Button type="submit" :disabled="parentsForm.processing">Simpan</Button></div>
        </form>
    </Modal>

    <Modal v-model="childOpen" title="Tambah Anak">
        <form class="space-y-4" @submit.prevent="submitChild">
            <Select id="child_couple_id" v-model="childForm.parents_couple_id" label="Couple orang tua" :options="coupleSelectOptions" />
            <Select id="child_id" v-model="childForm.child_id" label="Person anak" :options="[{ value: '', label: 'Pilih Person' }, ...childOptions.map((item) => ({ value: item.id, label: item.name }))]" />
            <Input id="birth_order" v-model="childForm.birth_order" type="number" label="Urutan kelahiran" :error="childForm.errors.birth_order" />
            <p v-if="childForm.errors.child_id" class="text-sm text-red-700">{{ childForm.errors.child_id }}</p>
            <div class="flex justify-end gap-2"><Button type="button" variant="ghost" @click="childOpen = false">Batal</Button><Button type="submit" :disabled="childForm.processing">Simpan</Button></div>
        </form>
    </Modal>
</template>
