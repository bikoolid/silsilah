<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import ApplicationLayout from '../../Layouts/ApplicationLayout.vue';
import Badge from '../../Components/UI/Badge.vue';
import Button from '../../Components/UI/Button.vue';
import Card from '../../Components/UI/Card.vue';
import Input from '../../Components/UI/Input.vue';
import EmptyState from '../../Components/UI/EmptyState.vue';

defineOptions({ layout: ApplicationLayout });
const props = defineProps({
    user: { type: Object, default: null },
    users: { type: Array, default: () => [] },
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user || props.user);
const canAdmin = computed(() => authUser.value?.role === 'administrator');
const tabs = [
    { key: 'profile', label: 'Profil & Keamanan' },
    { key: 'preferences', label: 'Preferensi Aplikasi' },
    ...(canAdmin.value ? [{ key: 'users', label: 'Pengelola Silsilah' }, { key: 'backup', label: 'Cadangan Data' }] : []),
];
const activeTab = ref('profile');
const profile = useForm({
    name: props.user?.name || '',
    email: props.user?.email || '',
    password: '',
    password_confirmation: '',
});
const newUser = useForm({ name: '', email: '', password: '', password_confirmation: '' });
const backup = useForm({ backup: null, confirmation: false });
const selectedFile = ref(null);
const preview = computed(() => page.props.flash?.backup_preview || null);
const canMutate = computed(() => Boolean(props.user));
const canBackup = computed(() => canAdmin.value);

function saveProfile() {
    profile.put('/pengaturan/profil', { preserveScroll: true });
}

function addUser() {
    newUser.post('/pengaturan/pengelola', { preserveScroll: true, onSuccess: () => newUser.reset() });
}

function removeUser(id) {
    if (window.confirm('Hapus akses pengelola ini?')) {
        router.delete(`/pengaturan/pengelola/${id}`, { preserveScroll: true });
    }
}

function chooseFile(event) {
    selectedFile.value = event.target.files?.[0] || null;
    backup.backup = selectedFile.value;
}

function previewBackup() {
    backup.post('/pengaturan/backup/preview', { preserveScroll: true });
}

function restoreBackup() {
    backup.post('/pengaturan/backup/restore', { preserveScroll: true });
}
</script>

<template>
    <div class="space-y-6">
        <header>
            <p class="text-sm font-medium text-brand-600">Kontrol aplikasi</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-ink-950">Pengaturan</h1>
            <p class="mt-2 text-sm leading-6 text-ink-500">Kelola akun, akses pengelola, preferensi, dan keamanan data silsilah.</p>
        </header>

        <div class="overflow-x-auto border-b border-ink-200">
            <nav class="flex min-w-max gap-6" aria-label="Tab pengaturan">
                <button v-for="tab in tabs" :key="tab.key" type="button" class="border-b-2 px-1 pb-3 text-sm font-medium transition" :class="activeTab === tab.key ? 'border-brand-600 text-brand-700' : 'border-transparent text-ink-500 hover:text-ink-800'" @click="activeTab = tab.key">
                    {{ tab.label }}
                </button>
            </nav>
        </div>

        <Card v-if="activeTab === 'profile'">
            <div class="max-w-2xl">
                <div class="flex items-center justify-between"><div><h2 class="font-semibold text-ink-950">Profil & Keamanan</h2><p class="mt-1 text-sm text-ink-500">Perbarui identitas akun dan kata sandi.</p></div><Badge :tone="canMutate ? 'brand' : 'warning'">{{ canMutate ? 'Akun aktif' : 'Login diperlukan' }}</Badge></div>
                <form class="mt-6 space-y-4" @submit.prevent="saveProfile">
                    <Input id="settings-name" v-model="profile.name" label="Nama" :error="profile.errors.name" />
                    <Input id="settings-email" v-model="profile.email" type="email" label="E-mail" :error="profile.errors.email" />
                    <div class="grid gap-4 sm:grid-cols-2"><Input id="settings-password" v-model="profile.password" type="password" label="Kata sandi baru" hint="Kosongkan jika tidak diubah." :error="profile.errors.password" /><Input id="settings-password-confirmation" v-model="profile.password_confirmation" type="password" label="Konfirmasi kata sandi" /></div>
                    <Button type="submit" :disabled="profile.processing || !canMutate">Simpan perubahan</Button>
                </form>
            </div>
        </Card>

        <Card v-else-if="activeTab === 'preferences'">
            <h2 class="font-semibold text-ink-950">Preferensi Aplikasi</h2>
            <p class="mt-1 text-sm text-ink-500">Preferensi tampilan siap dikembangkan tanpa mengubah data domain.</p>
            <div class="mt-6 grid gap-4 sm:grid-cols-2"><div class="rounded-control border border-ink-200 p-4"><p class="text-sm font-medium text-ink-800">Format nama</p><p class="mt-1 text-xs text-ink-500">Nama lengkap digunakan sebagai format utama.</p></div><div class="rounded-control border border-ink-200 p-4"><p class="text-sm font-medium text-ink-800">Format tanggal</p><p class="mt-1 text-xs text-ink-500">Tanggal ditampilkan mengikuti lokal Indonesia.</p></div></div>
        </Card>

        <div v-else-if="activeTab === 'users'" class="grid gap-6 xl:grid-cols-[1.3fr_1fr]">
            <Card><h2 class="font-semibold text-ink-950">Pengelola saat ini</h2><div v-if="users.length" class="mt-5 divide-y divide-ink-100"><div v-for="item in users" :key="item.id" class="flex items-center gap-3 py-3 first:pt-0 last:pb-0"><div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">{{ item.name.slice(0, 1).toUpperCase() }}</div><div class="min-w-0 flex-1"><p class="truncate text-sm font-medium text-ink-800">{{ item.name }}</p><p class="truncate text-xs text-ink-500">{{ item.email }}</p><Badge class="mt-1" :tone="item.role === 'administrator' ? 'brand' : 'neutral'">{{ item.role }}</Badge></div><Badge v-if="item.id === user?.id" tone="brand">Akun Anda</Badge><Button v-else variant="danger" size="sm" :disabled="!canAdmin" @click="removeUser(item.id)">Hapus</Button></div></div><EmptyState v-else title="Belum ada pengelola" description="Tambahkan akun pengelola pertama." class="mt-4" /></Card>
            <Card><h2 class="font-semibold text-ink-950">Undang pengelola</h2><p class="mt-1 text-sm text-ink-500">Buat akses baru untuk pengelola silsilah.</p><form class="mt-5 space-y-4" @submit.prevent="addUser"><Input id="new-user-name" v-model="newUser.name" label="Nama" :error="newUser.errors.name" /><Input id="new-user-email" v-model="newUser.email" type="email" label="E-mail" :error="newUser.errors.email" /><Input id="new-user-password" v-model="newUser.password" type="password" label="Kata sandi sementara" :error="newUser.errors.password" /><Input id="new-user-password-confirmation" v-model="newUser.password_confirmation" type="password" label="Konfirmasi kata sandi" /><Button type="submit" :disabled="newUser.processing || !canAdmin">Tambah pengelola</Button></form></Card>
        </div>

        <Card v-else>
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start"><div><h2 class="font-semibold text-ink-950">Cadangan & Restore</h2><p class="mt-1 max-w-2xl text-sm text-ink-500">Ekspor Person, Couple, relasi, dan metadata foto ke JSON. Restore mengganti data silsilah dalam satu transaksi database.</p></div><a href="/pengaturan/backup" class="inline-flex h-11 items-center justify-center rounded-control bg-brand-600 px-4 text-sm font-medium text-white hover:bg-brand-700" :class="{ 'pointer-events-none opacity-50': !canBackup }">Unduh Cadangan Data</a></div>
            <div class="mt-6 rounded-control border border-dashed border-ink-300 p-5"><label for="backup-file" class="block text-sm font-medium text-ink-700">File backup JSON</label><input id="backup-file" type="file" accept=".json,application/json" class="mt-3 block w-full text-sm text-ink-500" :disabled="!canBackup" @change="chooseFile"><p v-if="backup.errors.backup" class="mt-2 text-xs text-red-700">{{ backup.errors.backup }}</p><div class="mt-4 flex flex-wrap gap-3"><Button variant="secondary" :disabled="!selectedFile || backup.processing || !canBackup" @click="previewBackup">Preview isi backup</Button><Button variant="danger" :disabled="!selectedFile || !preview || !backup.confirmation || backup.processing || !canBackup" @click="restoreBackup">Restore data</Button></div><label v-if="preview" class="mt-4 flex items-center gap-2 text-sm text-ink-700"><input v-model="backup.confirmation" type="checkbox"> Saya memahami restore akan mengganti data saat ini.</label><p v-if="preview" class="mt-3 text-sm text-ink-600">Preview: <strong>{{ preview.people }}</strong> Person dan <strong>{{ preview.couples }}</strong> Couple (versi {{ preview.version }}).</p></div>
        </Card>
    </div>
</template>
