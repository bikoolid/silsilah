<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import ApplicationLayout from '../../Layouts/ApplicationLayout.vue';
import { ref } from 'vue';
import Button from '../../Components/UI/Button.vue';
import Badge from '../../Components/UI/Badge.vue';
import EmptyFamilyState from '../../Components/Family/EmptyFamilyState.vue';
import ErrorState from '../../Components/UI/ErrorState.vue';
import FamilyMemberList from '../../Components/Family/FamilyMemberList.vue';
import LoadingState from '../../Components/UI/LoadingState.vue';
import PersonAvatar from '../../Components/Family/PersonAvatar.vue';
import Panel from '../../Components/UI/Panel.vue';
import RelationshipItem from '../../Components/Family/RelationshipItem.vue';
import PersonManagementActions from '../../Components/Family/PersonManagementActions.vue';
import Modal from '../../Components/UI/Modal.vue';
import Input from '../../Components/UI/Input.vue';

defineOptions({
    layout: ApplicationLayout,
});

const props = defineProps({
    person: {
        type: Object,
        default: null,
    },
    notFound: {
        type: Boolean,
        default: false,
    },
    peopleOptions: {
        type: Array,
        default: () => [],
    },
    coupleOptions: {
        type: Array,
        default: () => [],
    },
});

const loading = ref(false);
const coupleEditorOpen = ref(false);
const editingCouple = ref(null);
const coupleForm = useForm({ marriage_date: '', divorce_date: '', status: '' });

function openPerson(id) {
    loading.value = true;
    router.visit(`/anggota-keluarga/${id}`, {
        onFinish: () => {
            loading.value = false;
        },
    });
}

function displayDate(value) {
    if (!value) return '';
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(new Date(value));
}

function spouseSummary(spouse) {
    return [
        spouse.marriageDate ? `Menikah ${displayDate(spouse.marriageDate)}` : '',
        spouse.divorceDate ? `Cerai ${displayDate(spouse.divorceDate)}` : '',
        spouse.status || '',
    ].filter(Boolean).join(' · ');
}

function editCouple(spouse) {
    editingCouple.value = spouse;
    coupleForm.marriage_date = spouse.marriageDate || '';
    coupleForm.divorce_date = spouse.divorceDate || '';
    coupleForm.status = spouse.status || '';
    coupleEditorOpen.value = true;
}

function updateCouple() {
    coupleForm.put(`/couples/${editingCouple.value.id}`, {
        preserveScroll: true,
        onSuccess: () => { coupleEditorOpen.value = false; },
    });
}

function removeCouple(spouse) {
    const warning = spouse.childrenCount
        ? `Couple ini memiliki ${spouse.childrenCount} anak. Relasi Couple akan dihapus, tetapi data ayah/ibu anak tetap dipertahankan dan konteks Couple dikosongkan. Lanjutkan?`
        : 'Putus hubungan pasangan ini? Tindakan ini tidak menghapus data Person.';
    if (window.confirm(warning)) {
        useForm({}).delete(`/couples/${spouse.id}`, { preserveScroll: true });
    }
}

function cemeteryValue(cemetery, key) {
    if (!cemetery || typeof cemetery !== 'object') return '';
    return cemetery[key] || '';
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <Link href="/anggota-keluarga" class="text-sm font-medium text-brand-600 hover:text-brand-700">← Kembali ke Anggota Keluarga</Link>
            <div v-if="person" class="flex flex-wrap items-center gap-2">
                <PersonManagementActions :person="person" :people-options="peopleOptions" :couple-options="coupleOptions" :can="person.can" />
                <Link
                    :href="`/silsilah/${person.id}`"
                    class="action-icon group !h-10 !w-10 border border-ink-200 bg-white text-ink-700 hover:bg-ink-100"
                    aria-label="Buka Family Tree"
                    title="Buka Family Tree"
                >
                    <span aria-hidden="true">⌘</span>
                    <span class="action-tooltip">Buka Family Tree</span>
                </Link>
            </div>
        </div>

        <LoadingState v-if="loading" label="Memuat detail Person..." />
        <ErrorState v-else-if="notFound || !person" title="Person tidak ditemukan" description="Data Person yang diminta tidak tersedia atau sudah tidak dapat diakses.">
            <template #action>
                <Link href="/anggota-keluarga"><Button variant="secondary" size="sm">Kembali ke daftar</Button></Link>
            </template>
        </ErrorState>
        <template v-else>
            <section class="rounded-card border border-ink-200 bg-white p-6 shadow-card sm:p-8">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                    <PersonAvatar :person="person" size="lg" />
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="text-3xl font-semibold tracking-tight text-ink-950">{{ person.name }}</h1>
                            <Badge :tone="person.deceased ? 'neutral' : 'brand'">{{ person.deceased ? 'Wafat' : 'Hidup' }}</Badge>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-sm text-ink-500">
                            <span v-if="person.gender">{{ person.gender }}</span>
                            <span v-if="person.birthYear || person.birthDate">
                                Lahir {{ person.birthYear || displayDate(person.birthDate) }}
                            </span>
                            <span v-if="person.deathYear || person.deathDate">
                                Wafat {{ person.deathYear || displayDate(person.deathDate) }}
                            </span>
                            <span v-if="person.city">{{ person.city }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 sm:grid-cols-3" aria-label="Ringkasan relasi keluarga">
                <Panel class="border-brand-100 bg-brand-50/40">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-ink-500">Jumlah anak</p>
                            <p class="mt-2 text-3xl font-semibold tracking-tight text-ink-950">{{ person.familyCounts?.children ?? 0 }}</p>
                            <p class="mt-1 text-xs text-ink-500">Keturunan langsung</p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-control bg-brand-100 text-brand-700" aria-hidden="true">
                            <svg viewBox="0 0 24 24" class="h-6 w-6 fill-none stroke-current" stroke-width="1.8"><circle cx="12" cy="6" r="3" /><path stroke-linecap="round" d="M5 20a7 7 0 0 1 14 0M12 9v4m-3 0h6" /></svg>
                        </span>
                    </div>
                </Panel>
                <Panel class="border-sky-100 bg-sky-50/40">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-ink-500">Jumlah cucu/cicit</p>
                            <p class="mt-2 text-3xl font-semibold tracking-tight text-ink-950">{{ person.familyCounts?.descendants ?? 0 }}</p>
                            <p class="mt-1 text-xs text-ink-500">Mulai generasi kedua</p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-control bg-sky-100 text-sky-700" aria-hidden="true">
                            <svg viewBox="0 0 24 24" class="h-6 w-6 fill-none stroke-current" stroke-width="1.8"><circle cx="12" cy="5" r="2.5" /><circle cx="6" cy="18" r="2.5" /><circle cx="18" cy="18" r="2.5" /><path stroke-linecap="round" d="M12 8v4M12 12H6m6 0h6M6 15v-3m12 3v-3" /></svg>
                        </span>
                    </div>
                </Panel>
                <Panel class="border-violet-100 bg-violet-50/40">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-ink-500">Jumlah saudara</p>
                            <p class="mt-2 text-3xl font-semibold tracking-tight text-ink-950">{{ person.familyCounts?.siblings ?? 0 }}</p>
                            <p class="mt-1 text-xs text-ink-500">Berbagi minimal satu orang tua</p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-control bg-violet-100 text-violet-700" aria-hidden="true">
                            <svg viewBox="0 0 24 24" class="h-6 w-6 fill-none stroke-current" stroke-width="1.8"><circle cx="12" cy="6" r="2.5" /><circle cx="5.5" cy="18" r="2.5" /><circle cx="18.5" cy="18" r="2.5" /><path stroke-linecap="round" d="M12 8.5v3M12 11.5l-6.5 3M12 11.5l6.5 3" /></svg>
                        </span>
                    </div>
                </Panel>
            </section>

            <div class="grid gap-6 xl:grid-cols-2">
                <Panel>
                    <template #header><h2 class="font-semibold">Informasi Dasar</h2></template>
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">Tanggal lahir</dt>
                            <dd class="mt-1 text-sm text-ink-700">{{ person.birthDate ? displayDate(person.birthDate) : (person.birthYear || 'Belum tersedia') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">Tanggal wafat</dt>
                            <dd class="mt-1 text-sm text-ink-700">{{ person.deathDate ? displayDate(person.deathDate) : (person.deathYear || 'Belum tersedia') }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">Status</dt>
                            <dd class="mt-1 text-sm text-ink-700">{{ person.deceased ? 'Telah wafat' : 'Masih hidup' }}</dd>
                        </div>
                    </dl>
                </Panel>

                <Panel>
                    <template #header><h2 class="font-semibold">Kontak dan Alamat</h2></template>
                    <dl v-if="person.address || person.city || person.phone" class="space-y-4">
                        <div v-if="person.address">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">Alamat</dt>
                            <dd class="mt-1 text-sm leading-6 text-ink-700">{{ person.address }}</dd>
                        </div>
                        <div v-if="person.city">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">Kota</dt>
                            <dd class="mt-1 text-sm text-ink-700">{{ person.city }}</dd>
                        </div>
                        <div v-if="person.phone">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">Nomor telepon</dt>
                            <dd class="mt-1 text-sm text-ink-700">{{ person.phone }}</dd>
                        </div>
                    </dl>
                    <EmptyFamilyState v-else title="Informasi kontak belum tersedia" description="Belum ada alamat atau nomor telepon untuk Person ini." />
                </Panel>
            </div>

            <section class="grid gap-6 xl:grid-cols-2">
                <Panel>
                    <template #header><h2 class="font-semibold">Orang Tua</h2></template>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <RelationshipItem
                            v-if="person.father"
                            :person="person.father"
                            relationship-label="Ayah"
                            @select="openPerson($event.id)"
                        />
                        <EmptyFamilyState v-else title="Ayah belum tercatat" description="Data ayah belum tersedia." />
                        <RelationshipItem
                            v-if="person.mother"
                            :person="person.mother"
                            relationship-label="Ibu"
                            @select="openPerson($event.id)"
                        />
                        <EmptyFamilyState v-else title="Ibu belum tercatat" description="Data ibu belum tersedia." />
                    </div>
                </Panel>
                <Panel>
                    <template #header><h2 class="font-semibold">Pasangan</h2></template>
                    <div v-if="person.spouses?.length" class="grid gap-3 sm:grid-cols-2">
                        <div v-for="spouse in person.spouses" :key="spouse.id" class="relative">
                            <RelationshipItem
                                :person="spouse.person"
                                relationship-label="Pasangan"
                                :secondary-label="spouseSummary(spouse)"
                                @select="openPerson($event.id)"
                            />
                            <div class="mt-2 flex justify-end gap-2">
                                <Button v-if="person.can?.edit" variant="ghost" size="sm" @click="editCouple(spouse)">Edit informasi</Button>
                                <Button v-if="person.can?.edit" variant="danger" size="sm" @click="removeCouple(spouse)">Putus relasi</Button>
                            </div>
                        </div>
                    </div>
                    <EmptyFamilyState v-else title="Belum ada pasangan" description="Belum ada informasi pasangan untuk Person ini." />
                </Panel>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <Panel>
                    <template #header><h2 class="font-semibold">Anak</h2></template>
                    <FamilyMemberList :members="person.children" relationship-label="Anak" empty-title="Belum ada data anak" @select="openPerson($event.id)" />
                </Panel>
                <Panel>
                    <template #header><h2 class="font-semibold">Saudara</h2></template>
                    <FamilyMemberList :members="person.siblings" relationship-label="Saudara" empty-title="Belum ada data saudara" @select="openPerson($event.id)" />
                </Panel>
            </section>

            <Modal v-model="coupleEditorOpen" title="Edit Informasi Pasangan">
                <form class="space-y-4" @submit.prevent="updateCouple">
                    <Input id="edit-marriage-date" v-model="coupleForm.marriage_date" type="date" label="Tanggal pernikahan" :error="coupleForm.errors.marriage_date" />
                    <Input id="edit-divorce-date" v-model="coupleForm.divorce_date" type="date" label="Tanggal perceraian" :error="coupleForm.errors.divorce_date" />
                    <Input id="edit-couple-status" v-model="coupleForm.status" label="Status hubungan" :error="coupleForm.errors.status" />
                    <div class="flex justify-end gap-2">
                        <Button type="button" variant="ghost" @click="coupleEditorOpen = false">Batal</Button>
                        <Button type="submit" :disabled="coupleForm.processing">Simpan</Button>
                    </div>
                </form>
            </Modal>

            <Panel>
                <template #header><h2 class="font-semibold">Lokasi Makam</h2></template>
                <div v-if="person.cemetery && (typeof person.cemetery === 'string' || Object.values(person.cemetery).some(Boolean))" class="grid gap-4 sm:grid-cols-2">
                    <div v-if="cemeteryValue(person.cemetery, 'name')"><p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Nama lokasi</p><p class="mt-1 text-sm text-ink-700">{{ cemeteryValue(person.cemetery, 'name') }}</p></div>
                    <div v-if="cemeteryValue(person.cemetery, 'address')"><p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Alamat</p><p class="mt-1 text-sm text-ink-700">{{ cemeteryValue(person.cemetery, 'address') }}</p></div>
                    <div v-if="cemeteryValue(person.cemetery, 'latitude')"><p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Latitude</p><p class="mt-1 text-sm text-ink-700">{{ cemeteryValue(person.cemetery, 'latitude') }}</p></div>
                    <div v-if="cemeteryValue(person.cemetery, 'longitude')"><p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Longitude</p><p class="mt-1 text-sm text-ink-700">{{ cemeteryValue(person.cemetery, 'longitude') }}</p></div>
                    <a v-if="cemeteryValue(person.cemetery, 'latitude') && cemeteryValue(person.cemetery, 'longitude')" :href="`https://www.google.com/maps/search/?api=1&query=${cemeteryValue(person.cemetery, 'latitude')},${cemeteryValue(person.cemetery, 'longitude')}`" target="_blank" rel="noopener" class="text-sm font-medium text-brand-600 hover:text-brand-700 sm:col-span-2">Buka lokasi di peta →</a>
                </div>
                <EmptyFamilyState v-else title="Lokasi makam belum tersedia" description="Belum ada informasi cemetery untuk Person ini." />
            </Panel>

            <div class="flex flex-wrap gap-3">
                <Link href="/anggota-keluarga"><Button variant="secondary">Kembali ke daftar</Button></Link>
                <Link :href="`/silsilah/${person.id}`"><Button variant="secondary">Lihat di Family Tree</Button></Link>
            </div>
        </template>
    </div>
</template>
