<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import ApplicationLayout from '../../Layouts/ApplicationLayout.vue';
import Button from '../../Components/UI/Button.vue';
import Input from '../../Components/UI/Input.vue';
import Select from '../../Components/UI/Select.vue';
import CemeteryLocationPicker from '../../Components/Family/CemeteryLocationPicker.vue';
import PersonPhotoUploader from '../../Components/Family/PersonPhotoUploader.vue';

defineOptions({ layout: ApplicationLayout });

const props = defineProps({
    person: { type: Object, default: null },
    mode: { type: String, default: 'create' },
});

const form = useForm({
    full_name: props.person?.name || '',
    gender: props.person?.gender || '',
    dob: props.person?.birthDate || '',
    yob: props.person?.birthYear || '',
    dod: props.person?.deathDate || '',
    yod: props.person?.deathYear || '',
    address: props.person?.address || '',
    phone: props.person?.phone || '',
    city: props.person?.city || '',
    photo: null,
    remove_photo: false,
    cemetery_location: props.person?.cemetery || {},
});

const genderOptions = [
    { value: '', label: 'Pilih gender' },
    { value: 'male', label: 'Laki-laki' },
    { value: 'female', label: 'Perempuan' },
];

function submit() {
    const options = {
        preserveScroll: true,
        forceFormData: true,
        onError: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
    };

    if (props.mode === 'edit') {
        form.transform((data) => ({ ...data, _method: 'put' })).post(`/anggota-keluarga/${props.person.id}`, options);
    } else {
        form.post('/anggota-keluarga', options);
    }
}
</script>

<template>
    <div class="mx-auto max-w-3xl space-y-6">
        <header>
            <Link href="/anggota-keluarga" class="text-sm font-medium text-brand-600 hover:text-brand-700">← Kembali ke Anggota Keluarga</Link>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-ink-950">{{ mode === 'edit' ? 'Edit Profil Person' : 'Tambah Person' }}</h1>
            <p class="mt-2 text-sm text-ink-500">Simpan hanya informasi yang tersedia dan sesuai data keluarga.</p>
        </header>

        <form class="space-y-6" @submit.prevent="submit">
            <div v-if="Object.keys(form.errors).length" class="rounded-control border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                Periksa kembali field yang ditandai sebelum menyimpan.
            </div>
            <section class="grid gap-4 rounded-card border border-ink-200 bg-white p-5 shadow-card sm:grid-cols-2">
                <Input id="full_name" v-model="form.full_name" class="sm:col-span-2" label="Nama lengkap" :error="form.errors.full_name" />
                <Select id="gender" v-model="form.gender" label="Gender" :options="genderOptions" />
                <PersonPhotoUploader v-model="form.photo" :existing-url="props.person?.photo || ''" :error="form.errors.photo" class="sm:col-span-2" @remove="form.remove_photo = true" />
                <Input id="dob" v-model="form.dob" type="date" label="Tanggal lahir" :error="form.errors.dob" />
                <Input id="yob" v-model="form.yob" type="number" label="Tahun lahir" :error="form.errors.yob" />
                <Input id="dod" v-model="form.dod" type="date" label="Tanggal wafat" :error="form.errors.dod" />
                <Input id="yod" v-model="form.yod" type="number" label="Tahun wafat" :error="form.errors.yod" />
                <Input id="address" v-model="form.address" class="sm:col-span-2" label="Alamat" :error="form.errors.address" />
                <Input id="city" v-model="form.city" label="Kota" :error="form.errors.city" />
                <Input id="phone" v-model="form.phone" label="Nomor telepon" :error="form.errors.phone" />
            </section>

            <section class="space-y-4 rounded-card border border-ink-200 bg-white p-5 shadow-card">
                <h2 class="font-semibold text-ink-950">Lokasi Makam</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <Input id="cemetery_name" v-model="form.cemetery_location.name" label="Nama lokasi" :error="form.errors['cemetery_location.name']" />
                    <Input id="cemetery_address" v-model="form.cemetery_location.address" label="Alamat makam" :error="form.errors['cemetery_location.address']" />
                    <Input id="cemetery_latitude" v-model="form.cemetery_location.latitude" label="Latitude" :error="form.errors['cemetery_location.latitude']" />
                    <Input id="cemetery_longitude" v-model="form.cemetery_location.longitude" label="Longitude" :error="form.errors['cemetery_location.longitude']" />
                    <CemeteryLocationPicker v-model="form.cemetery_location" />
                </div>
            </section>

            <div class="flex flex-wrap justify-end gap-3">
                <Link :href="person ? `/anggota-keluarga/${person.id}` : '/anggota-keluarga'"><Button type="button" variant="ghost">Batal</Button></Link>
                <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Menyimpan...' : 'Simpan Person' }}</Button>
            </div>
        </form>
    </div>
</template>
