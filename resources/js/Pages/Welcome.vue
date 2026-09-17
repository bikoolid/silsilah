<script setup>
import { ref } from 'vue';
import ApplicationLayout from '../Layouts/ApplicationLayout.vue';
import Avatar from '../Components/UI/Avatar.vue';
import Badge from '../Components/UI/Badge.vue';
import Button from '../Components/UI/Button.vue';
import Card from '../Components/UI/Card.vue';
import Dropdown from '../Components/UI/Dropdown.vue';
import EmptyState from '../Components/UI/EmptyState.vue';
import Input from '../Components/UI/Input.vue';
import LoadingState from '../Components/UI/LoadingState.vue';
import Modal from '../Components/UI/Modal.vue';
import Panel from '../Components/UI/Panel.vue';
import Select from '../Components/UI/Select.vue';
import CoupleCard from '../Components/Family/CoupleCard.vue';
import FamilyDetailPanel from '../Components/Family/FamilyDetailPanel.vue';
import FamilyMemberList from '../Components/Family/FamilyMemberList.vue';
import PersonCard from '../Components/Family/PersonCard.vue';
import RelationshipBadge from '../Components/Family/RelationshipBadge.vue';

defineOptions({
    layout: ApplicationLayout,
});

const search = ref('');
const status = ref('active');
const modalOpen = ref(false);

const statusOptions = [
    { value: 'active', label: 'Aktif' },
    { value: 'archived', label: 'Diarsipkan' },
];

const samplePerson = {
    id: 1,
    name: 'Siti Aminah',
    gender: 'Perempuan',
    birthYear: 1928,
    deathYear: 2010,
};

const sampleFather = {
    id: 2,
    name: 'Hasan Basri',
    gender: 'Laki-laki',
    birthYear: 1901,
};

const sampleMother = {
    id: 3,
    name: 'Maryam',
    gender: 'Perempuan',
    birthYear: 1905,
};

const sampleChild = {
    id: 4,
    name: 'Budi Santoso',
    gender: 'Laki-laki',
    birthYear: 1955,
};

const sampleCouple = {
    id: 10,
    husband: sampleFather,
    wife: sampleMother,
    marriageDate: '1925',
    status: 'Menikah',
};
</script>

<template>
    <div class="space-y-8">
            <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <div class="mb-3 flex items-center gap-3">
                        <img :src="'/images/logo.png'" alt="Karuhun" class="h-10 w-10 rounded-control object-contain">
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-600">Karuhun design system</p>
                    </div>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Fondasi visual yang tenang dan terbaca</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-ink-500">
                        Komponen reusable untuk Person Card, Family Tree node, toolbar, dan Family Detail Panel pada tahap berikutnya.
                    </p>
                </div>
                <Button size="sm" @click="modalOpen = true">Buka modal</Button>
            </header>

            <Panel>
                <template #header>
                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                        <div>
                            <h2 class="font-semibold">Kontrol dan status</h2>
                            <p class="mt-1 text-sm text-ink-500">Contoh penggunaan input, select, badge, dropdown, dan button.</p>
                        </div>
                        <Dropdown label="Aksi lainnya">
                            <button type="button" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-ink-700 hover:bg-ink-100">Pengaturan tampilan</button>
                            <button type="button" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-ink-700 hover:bg-ink-100">Bantuan</button>
                        </Dropdown>
                    </div>
                </template>
                <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_12rem_auto] md:items-end">
                    <Input id="search" v-model="search" label="Cari" hint="Gunakan untuk pencarian anggota keluarga." placeholder="Nama anggota keluarga" />
                    <Select id="status" v-model="status" label="Status" :options="statusOptions" />
                    <div class="flex gap-2">
                        <Button variant="secondary">Sekunder</Button>
                        <Button variant="ghost">Batal</Button>
                    </div>
                </div>
                <div class="mt-5 flex flex-wrap gap-2">
                    <Badge tone="brand">Aktif</Badge>
                    <Badge>Data belum lengkap</Badge>
                    <Badge tone="info">Generasi 2</Badge>
                    <Badge tone="warning">Perlu ditinjau</Badge>
                </div>
            </Panel>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <div class="flex items-start gap-4">
                        <Avatar name="Siti Aminah" size="lg" />
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-semibold">Siti Aminah</h2>
                                <Badge tone="brand">Person</Badge>
                            </div>
                            <p class="mt-1 text-sm text-ink-500">1928 — 2010 · Bandung</p>
                            <p class="mt-4 text-sm leading-6 text-ink-700">Contoh visual language untuk Person Card dan node keluarga.</p>
                        </div>
                    </div>
                </Card>
                <LoadingState label="Memuat konteks keluarga..." />
            </div>

            <EmptyState
                title="Belum ada anggota yang dipilih"
                description="Pilih Person dari Family Tree untuk melihat informasi keluarga secara lebih lengkap."
            >
                <template #action>
                    <Button variant="secondary" size="sm">Lihat panduan</Button>
                </template>
            </EmptyState>

            <section class="space-y-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-600">Family UI components</p>
                    <h2 class="mt-2 text-2xl font-semibold tracking-tight">Reusable family presentation</h2>
                    <p class="mt-2 text-sm text-ink-500">Showcase props-only untuk komponen Person, Couple, dan Family Detail.</p>
                </div>
                <div class="grid gap-6 xl:grid-cols-2">
                    <div class="space-y-4">
                        <PersonCard :person="samplePerson" selected />
                        <CoupleCard v-bind="sampleCouple" />
                        <div class="flex flex-wrap gap-2">
                            <RelationshipBadge relationship="parent" />
                            <RelationshipBadge relationship="spouse" />
                            <RelationshipBadge relationship="child" />
                        </div>
                        <Panel>
                            <template #header><h3 class="font-semibold">Family members</h3></template>
                            <FamilyMemberList :members="[sampleFather, sampleMother, sampleChild]" />
                        </Panel>
                    </div>
                    <FamilyDetailPanel
                        :person="samplePerson"
                        :parents="[sampleFather, sampleMother]"
                        :spouses="[sampleCouple]"
                        :children="[sampleChild]"
                        cemetery="TPU Keluarga, Bandung"
                    />
                </div>
            </section>
    </div>

    <Modal v-model="modalOpen" title="Contoh modal">
            <p class="text-sm leading-6 text-ink-700">Modal ini menjadi pola dasar untuk konfirmasi dan detail ringan tanpa membawa logic domain.</p>
            <template #footer>
                <Button variant="ghost" size="sm" @click="modalOpen = false">Tutup</Button>
                <Button size="sm" @click="modalOpen = false">Selesai</Button>
            </template>
    </Modal>
</template>
