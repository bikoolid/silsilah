<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import ApplicationLayout from '../../Layouts/ApplicationLayout.vue';
import Button from '../../Components/UI/Button.vue';
import Card from '../../Components/UI/Card.vue';
import EmptyState from '../../Components/UI/EmptyState.vue';
import Input from '../../Components/UI/Input.vue';
import Select from '../../Components/UI/Select.vue';
import CoupleCard from '../../Components/Family/CoupleCard.vue';

defineOptions({ layout: ApplicationLayout });
const props = defineProps({
    couples: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const loading = ref(false);
const statusOptions = [
    { value: '', label: 'Semua status' },
    ...props.statuses.map((value) => ({ value, label: value })),
];

function visit(page = 1) {
    loading.value = true;
    router.get('/pasangan', {
        search: search.value || undefined,
        status: status.value || undefined,
        page,
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { loading.value = false; },
    });
}

function formatDate(value) {
    if (!value) return '';
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(`${value}T00:00:00`));
}
</script>

<template>
    <div class="space-y-6 couple-page-background">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-600">Couple</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink-950">Daftar Pasangan</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">Kelola sejarah hubungan pasangan dan metadata pernikahan keluarga.</p>
            </div>
            <Link href="/anggota-keluarga"><Button variant="secondary">Lihat Anggota</Button></Link>
        </header>

        <Card class="glass-effect">
            <form class="grid gap-4 md:grid-cols-[minmax(0,1fr)_14rem_auto]" @submit.prevent="visit()">
                <Input id="couple-search" v-model="search" label="Cari pasangan" placeholder="Nama suami atau istri" />
                <Select id="couple-status" v-model="status" label="Status hubungan" :options="statusOptions" />
                <div class="flex items-end gap-2"><Button type="submit" :disabled="loading">Terapkan</Button><Button type="button" variant="ghost" :disabled="loading" @click="search = ''; status = ''; visit()">Reset</Button></div>
            </form>
        </Card>

        <div v-if="couples.total" class="text-sm text-ink-500">Menampilkan {{ couples.from }}–{{ couples.to }} dari {{ couples.total }} pasangan</div>
        <div v-if="couples.data?.length" class="grid gap-4 xl:grid-cols-2">
            <Card v-for="couple in couples.data" :key="couple.id">
                <CoupleCard :husband="couple.husband" :wife="couple.wife" :marriage-date="formatDate(couple.marriageDate)" :divorce-date="formatDate(couple.divorceDate)" :status="couple.status" />
                <div class="mt-4 flex items-center justify-between border-t border-ink-100 pt-3">
                    <span class="text-xs text-ink-500">{{ couple.childrenCount }} anak terhubung</span>
                    <div class="flex gap-2">
                        <Link v-if="couple.husband" :href="`/anggota-keluarga/${couple.husband.id}`"><Button variant="ghost" size="sm">Profil</Button></Link>
                        <Link v-if="couple.wife" :href="`/anggota-keluarga/${couple.wife.id}`"><Button variant="ghost" size="sm">Profil pasangan</Button></Link>
                    </div>
                </div>
            </Card>
        </div>
        <EmptyState v-else title="Belum ada pasangan" description="Tambahkan relasi pasangan dari halaman detail Person." />

        <nav v-if="couples.last_page > 1" class="flex items-center justify-center gap-2" aria-label="Pagination pasangan">
            <Button v-if="couples.current_page > 1" variant="secondary" size="sm" @click="visit(couples.current_page - 1)">Sebelumnya</Button>
            <span class="text-sm text-ink-500">Halaman {{ couples.current_page }} dari {{ couples.last_page }}</span>
            <Button v-if="couples.current_page < couples.last_page" variant="secondary" size="sm" @click="visit(couples.current_page + 1)">Berikutnya</Button>
        </nav>
    </div>
</template>
