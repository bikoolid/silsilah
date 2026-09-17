<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import ApplicationLayout from '../../Layouts/ApplicationLayout.vue';
import Button from '../../Components/UI/Button.vue';
import EmptyFamilyState from '../../Components/Family/EmptyFamilyState.vue';
import ErrorState from '../../Components/UI/ErrorState.vue';
import Input from '../../Components/UI/Input.vue';
import LoadingState from '../../Components/UI/LoadingState.vue';
import PersonCard from '../../Components/Family/PersonCard.vue';
import Select from '../../Components/UI/Select.vue';

defineOptions({
    layout: ApplicationLayout,
});

const props = defineProps({
    people: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    error: {
        type: String,
        default: null,
    },
});

const search = ref(props.filters.search || '');
const gender = ref(props.filters.gender || '');
const lifeStatus = ref(props.filters.life_status || '');
const sort = ref(props.filters.sort || 'name');
const direction = ref(props.filters.direction || 'asc');
const loading = ref(false);

watch(() => props.filters, (filters) => {
    search.value = filters.search || '';
    gender.value = filters.gender || '';
    lifeStatus.value = filters.life_status || '';
    sort.value = filters.sort || 'name';
    direction.value = filters.direction || 'asc';
}, { deep: true });

const genderOptions = [
    { value: '', label: 'Semua gender' },
    { value: 'male', label: 'Laki-laki' },
    { value: 'female', label: 'Perempuan' },
];

const lifeStatusOptions = [
    { value: '', label: 'Semua status' },
    { value: 'living', label: 'Masih hidup' },
    { value: 'deceased', label: 'Telah wafat' },
];

const sortOptions = [
    { value: 'name', label: 'Nama' },
    { value: 'birth_year', label: 'Tahun lahir' },
];

function visit(page = 1, formEvent = null) {
    if (formEvent?.currentTarget) {
        const formData = new FormData(formEvent.currentTarget);
        search.value = String(formData.get('search') || '');
        gender.value = String(formData.get('gender') || '');
        lifeStatus.value = String(formData.get('life_status') || '');
        sort.value = String(formData.get('sort') || 'name');
    }

    loading.value = true;
    const query = new URLSearchParams();
    const filters = {
        search: search.value.trim(),
        gender: gender.value,
        life_status: lifeStatus.value,
        sort: sort.value,
        direction: direction.value,
        page: String(page),
    };
    Object.entries(filters).forEach(([key, value]) => {
        if (value) query.set(key, value);
    });

    router.visit(`/anggota-keluarga?${query.toString()}`, {
        method: 'get',
        data: {},
        preserveState: false,
        preserveScroll: true,
        onFinish: () => {
            loading.value = false;
        },
    });
}

function clearFilters() {
    search.value = '';
    gender.value = '';
    lifeStatus.value = '';
    sort.value = 'name';
    direction.value = 'asc';
    visit();
}
</script>

<template>
    <div class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-600">Person</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink-950">Anggota Keluarga</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">Cari dan telusuri data anggota keluarga berdasarkan informasi identitas.</p>
            </div>
            <Link href="/anggota-keluarga/create">
                <Button>Tambah Person</Button>
            </Link>
        </header>

        <section class="rounded-card border border-ink-200 bg-white p-5 shadow-card">
            <form class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_12rem_12rem_12rem_auto]" @submit.prevent="visit(1, $event)">
                <Input id="person-search" name="search" v-model="search" label="Cari nama" placeholder="Nama lengkap" />
                <Select id="person-gender" name="gender" v-model="gender" label="Gender" :options="genderOptions" />
                <Select id="person-life-status" name="life_status" v-model="lifeStatus" label="Status" :options="lifeStatusOptions" />
                <Select id="person-sort" name="sort" v-model="sort" label="Urutkan" :options="sortOptions" />
                <div class="flex items-end gap-2">
                    <Button type="submit" :disabled="loading">Terapkan</Button>
                    <Button type="button" variant="ghost" :disabled="loading" @click="clearFilters">Reset</Button>
                </div>
            </form>
        </section>

        <LoadingState v-if="loading" label="Memuat anggota keluarga..." />
        <ErrorState v-else-if="error" title="Data anggota belum tersedia" :description="error">
            <template #action>
                <Button variant="secondary" size="sm" @click="visit()">Coba lagi</Button>
            </template>
        </ErrorState>
        <template v-else>
            <div v-if="people.total" class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <p class="text-sm text-ink-500">
                    Menampilkan {{ people.from }}–{{ people.to }} dari {{ people.total }} anggota
                </p>
                <Button variant="ghost" size="sm" @click="direction = direction === 'asc' ? 'desc' : 'asc'; visit()">
                    {{ direction === 'asc' ? 'A-Z' : 'Z-A' }}
                </Button>
            </div>

            <div v-if="people.data?.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <PersonCard v-for="person in people.data" :key="person.id" :person="person" clickable @select="router.visit(`/anggota-keluarga/${person.id}`)" />
            </div>
            <EmptyFamilyState v-else title="Anggota keluarga tidak ditemukan" description="Coba ubah kata kunci atau filter pencarian." />

            <nav v-if="people.last_page > 1" class="flex items-center justify-center gap-2" aria-label="Pagination">
                <Button v-if="people.current_page > 1" variant="secondary" size="sm" @click="visit(people.current_page - 1)">Sebelumnya</Button>
                <span class="text-sm text-ink-500">Halaman {{ people.current_page }} dari {{ people.last_page }}</span>
                <Button v-if="people.current_page < people.last_page" variant="secondary" size="sm" @click="visit(people.current_page + 1)">Berikutnya</Button>
            </nav>
        </template>
    </div>
</template>
