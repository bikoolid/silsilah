<script setup>
import { computed, ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import ApplicationLayout from '../../Layouts/ApplicationLayout.vue';
import Badge from '../../Components/UI/Badge.vue';
import Button from '../../Components/UI/Button.vue';
import Card from '../../Components/UI/Card.vue';
import EmptyState from '../../Components/UI/EmptyState.vue';
import PersonAvatar from '../../Components/Family/PersonAvatar.vue';

defineOptions({ 
    layout: ApplicationLayout,
    title: ''
});
const props = defineProps({ dashboard: { type: Object, required: true } });

const animatedMetrics = ref({
    totalPeople: 0,
    generations: 0,
    couples: 0,
    living: 0,
    deceased: 0
});

const metrics = computed(() => [
    { label: 'Total Anggota Keluarga', value: animatedMetrics.value.totalPeople, detail: 'Person terdaftar', icon: '♧', color: 'from-brand-500 to-brand-600' },
    { label: 'Jumlah Generasi', value: animatedMetrics.value.generations, detail: 'Kedalaman hirarki', icon: '⌘', color: 'from-purple-500 to-purple-600' },
    { label: 'Pasangan Terdaftar', value: animatedMetrics.value.couples, detail: 'Relasi Couple', icon: '♡', color: 'from-pink-500 to-pink-600' },
    { label: 'Hidup / Wafat', value: `${animatedMetrics.value.living} / ${animatedMetrics.value.deceased}`, detail: 'Anggota hidup / wafat', icon: '◉', color: 'from-amber-500 to-amber-600' },
]);

const totalGender = computed(() => Object.values(props.dashboard.gender).reduce((sum, value) => sum + value, 0));
const maxDecade = computed(() => Math.max(...props.dashboard.birthDecades.map((item) => item.value), 1));

const genderChartData = computed(() => {
    const total = totalGender.value;
    return [
        { label: 'Laki-laki', value: props.dashboard.gender.male, percentage: total ? (props.dashboard.gender.male / total) * 100 : 0, color: '#0ea5e9' },
        { label: 'Perempuan', value: props.dashboard.gender.female, percentage: total ? (props.dashboard.gender.female / total) * 100 : 0, color: '#f472b6' },
        { label: 'Belum diketahui', value: props.dashboard.gender.unknown, percentage: total ? (props.dashboard.gender.unknown / total) * 100 : 0, color: '#94a3b8' }
    ];
});

const healthScore = computed(() => {
    const total = props.dashboard.metrics.totalPeople;
    if (total === 0) return 0;
    const dataCompleteness = (props.dashboard.gender.male + props.dashboard.gender.female) / total;
    return Math.round(dataCompleteness * 100);
});

function formatEventDate(date, type) {
    if (!date) return '';
    const value = new Date(`${date}T00:00:00`);
    return `${value.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })}${type === 'memorials' ? ' · Haul' : ''}`;
}

function formatUpdated(date) {
    if (!date) return 'Baru diperbarui';
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(date));
}

function animateValue(start, end, duration, callback) {
    const range = end - start;
    const increment = range / (duration / 16);
    let current = start;
    
    const timer = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
            current = end;
            clearInterval(timer);
        }
        callback(Math.round(current));
    }, 16);
}

onMounted(() => {
    // Animate metrics on mount
    animateValue(0, props.dashboard.metrics.totalPeople, 1500, (value) => {
        animatedMetrics.value.totalPeople = value;
    });
    animateValue(0, props.dashboard.metrics.generations, 1200, (value) => {
        animatedMetrics.value.generations = value;
    });
    animateValue(0, props.dashboard.metrics.couples, 1300, (value) => {
        animatedMetrics.value.couples = value;
    });
    animateValue(0, props.dashboard.metrics.living, 1400, (value) => {
        animatedMetrics.value.living = value;
    });
    animateValue(0, props.dashboard.metrics.deceased, 1400, (value) => {
        animatedMetrics.value.deceased = value;
    });
});
</script>

<template>
    <div class="space-y-6">
        <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-brand-600 animate-fade-in">Ringkasan keluarga</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight text-gradient animate-slide-up">Dashboard Karuhun</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-600 animate-fade-in">Pantau perkembangan data silsilah, peristiwa keluarga, dan aktivitas terbaru dalam satu tempat.</p>
            </div>
            <Link href="/anggota-keluarga/create"><Button class="animate-slide-up">+ Tambah Anggota</Button></Link>
        </header>

        <!-- Animated Metrics Cards -->
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card v-for="(metric, index) in metrics" :key="metric.label" class="flex items-start justify-between hover-lift animate-slide-up" :style="{ animationDelay: `${index * 100}ms` }">
                <div>
                    <p class="text-sm text-ink-600">{{ metric.label }}</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight" :class="`bg-gradient-to-r ${metric.color} bg-clip-text text-transparent`">{{ metric.value }}</p>
                    <p class="mt-1 text-xs text-ink-500">{{ metric.detail }}</p>
                </div>
                <span class="flex h-10 w-10 items-center justify-center rounded-control text-lg text-white shadow-glow animate-bounce" :class="`bg-gradient-to-r ${metric.color}`" style="animation-duration: 3s;" aria-hidden="true">{{ metric.icon }}</span>
            </Card>
        </section>

        <!-- Health Score Widget -->
        <Card class="animate-slide-up">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-ink-900">Skor Kesehatan Data</h2>
                    <p class="mt-1 text-sm text-ink-600">Kelengkapan informasi keluarga</p>
                </div>
                <div class="relative h-20 w-20">
                    <svg class="h-full w-full transform -rotate-90">
                        <circle cx="40" cy="40" r="36" stroke="#e2e8f0" stroke-width="8" fill="none" />
                        <circle cx="40" cy="40" r="36" :stroke="healthScore >= 70 ? '#10b981' : healthScore >= 40 ? '#f59e0b' : '#ef4444'" stroke-width="8" fill="none" :stroke-dasharray="`${healthScore * 2.26} 226`" class="transition-all duration-1000 ease-out" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-xl font-bold" :class="healthScore >= 70 ? 'text-green-600' : healthScore >= 40 ? 'text-amber-600' : 'text-red-600'">{{ healthScore }}%</span>
                    </div>
                </div>
            </div>
        </Card>

        <section class="grid gap-6 xl:grid-cols-[1.25fr_1fr]">
            <!-- Quick Actions with Enhanced Animations -->
            <Card class="animate-slide-up">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-ink-900">Aksi cepat</h2>
                        <p class="mt-1 text-sm text-ink-600">Akses fitur utama keluarga.</p>
                    </div>
                </div>
                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <Link href="/silsilah" class="group relative overflow-hidden rounded-control border border-ink-200 p-4 transition-all duration-300 hover:border-brand-300 hover:shadow-lg hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-r from-brand-50 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100" />
                        <span class="relative text-xl gradient-primary bg-clip-text text-transparent group-hover:scale-110 transition-transform duration-300">⌘</span>
                        <strong class="relative mt-3 block text-sm text-ink-900">Pohon Keluarga</strong>
                        <span class="relative mt-1 block text-xs text-ink-500">Lihat relasi</span>
                    </Link>
                    <Link href="/peta-makam" class="group relative overflow-hidden rounded-control border border-ink-200 p-4 transition-all duration-300 hover:border-brand-300 hover:shadow-lg hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-r from-brand-50 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100" />
                        <span class="relative text-xl gradient-primary bg-clip-text text-transparent group-hover:scale-110 transition-transform duration-300">⌖</span>
                        <strong class="relative mt-3 block text-sm text-ink-900">Peta Makam</strong>
                        <span class="relative mt-1 block text-xs text-ink-500">Jelajahi lokasi</span>
                    </Link>
                    <Link href="/anggota-keluarga/create" class="group relative overflow-hidden rounded-control border border-ink-200 p-4 transition-all duration-300 hover:border-brand-300 hover:shadow-lg hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-r from-brand-50 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100" />
                        <span class="relative text-xl gradient-primary bg-clip-text text-transparent group-hover:scale-110 transition-transform duration-300">+</span>
                        <strong class="relative mt-3 block text-sm text-ink-900">Tambah Person</strong>
                        <span class="relative mt-1 block text-xs text-ink-500">Catat anggota</span>
                    </Link>
                </div>
            </Card>

            <!-- Animated Pie Chart for Gender Distribution -->
            <Card class="animate-slide-up">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-ink-900">Distribusi Gender</h2>
                        <p class="mt-1 text-sm text-ink-600">{{ totalGender }} anggota terdata</p>
                    </div>
                    <Badge tone="info">Statistik</Badge>
                </div>
                <div class="mt-5 flex items-center gap-6">
                    <!-- CSS Pie Chart -->
                    <div class="relative h-32 w-32">
                        <svg viewBox="0 0 100 100" class="h-full w-full transform -rotate-90">
                            <circle cx="50" cy="50" r="40" fill="transparent" :stroke="genderChartData[0].color" stroke-width="20" :stroke-dasharray="`${genderChartData[0].percentage * 2.51} 251`" class="transition-all duration-1000 ease-out" />
                            <circle cx="50" cy="50" r="40" fill="transparent" :stroke="genderChartData[1].color" stroke-width="20" :stroke-dasharray="`${genderChartData[1].percentage * 2.51} 251`" :stroke-dashoffset="`-${genderChartData[0].percentage * 2.51}`" class="transition-all duration-1000 ease-out" />
                            <circle cx="50" cy="50" r="40" fill="transparent" :stroke="genderChartData[2].color" stroke-width="20" :stroke-dasharray="`${genderChartData[2].percentage * 2.51} 251`" :stroke-dashoffset="`-${(genderChartData[0].percentage + genderChartData[1].percentage) * 2.51}`" class="transition-all duration-1000 ease-out" />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-lg font-bold text-ink-900">{{ totalGender }}</span>
                        </div>
                    </div>
                    <!-- Legend -->
                    <div class="flex-1 space-y-2">
                        <div v-for="item in genderChartData" :key="item.label" class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="h-3 w-3 rounded-full" :style="{ backgroundColor: item.color }" />
                                <span class="text-sm text-ink-800">{{ item.label }}</span>
                            </div>
                            <span class="text-sm font-semibold text-ink-900">{{ item.value }} ({{ Math.round(item.percentage) }}%)</span>
                        </div>
                    </div>
                </div>
            </Card>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <!-- Animated Birthday Cards -->
            <Card class="animate-slide-up">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-ink-900">Ulang Tahun Mendatang</h2>
                        <p class="mt-1 text-sm text-ink-600">Dua bulan ke depan</p>
                    </div>
                    <Badge tone="brand">Pengingat</Badge>
                </div>
                <div v-if="dashboard.events.birthdays.length" class="mt-5 space-y-3">
                    <Link v-for="(person, index) in dashboard.events.birthdays" :key="person.id" :href="`/anggota-keluarga/${person.id}`" class="group flex items-center gap-3 rounded-control border border-ink-200 p-3 transition-all duration-300 hover:border-brand-300 hover:bg-brand-50 hover:shadow-md hover:scale-[1.02]" :style="{ animationDelay: `${index * 100}ms` }">
                        <div class="relative">
                            <PersonAvatar :person="person" size="sm" />
                            <div class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-brand-500 text-white text-xs">
                                🎂
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-ink-800 group-hover:text-brand-700 transition-colors">{{ person.name }}</span>
                            <span class="text-xs text-ink-500">{{ formatEventDate(person.date, 'birthdays') }}</span>
                        </div>
                        <span class="text-xl text-ink-400 group-hover:text-brand-500 group-hover:translate-x-1 transition-all">›</span>
                    </Link>
                </div>
                <EmptyState v-else title="Tidak ada ulang tahun terdekat" description="Belum ada tanggal lahir pada rentang pengingat." class="mt-4" />
            </Card>

            <!-- Animated Memorial Cards -->
            <Card class="animate-slide-up">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-ink-900">Peringatan Wafat</h2>
                        <p class="mt-1 text-sm text-ink-600">Haul dua bulan ke depan</p>
                    </div>
                    <Badge tone="neutral">Pengingat</Badge>
                </div>
                <div v-if="dashboard.events.memorials.length" class="mt-5 space-y-3">
                    <Link v-for="(person, index) in dashboard.events.memorials" :key="person.id" :href="`/anggota-keluarga/${person.id}`" class="group flex items-center gap-3 rounded-control border border-ink-200 p-3 transition-all duration-300 hover:border-brand-300 hover:bg-brand-50 hover:shadow-md hover:scale-[1.02]" :style="{ animationDelay: `${index * 100}ms` }">
                        <div class="relative">
                            <PersonAvatar :person="person" size="sm" />
                            <div class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-ink-500 text-white text-xs">
                                🕯️
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-ink-800 group-hover:text-brand-700 transition-colors">{{ person.name }}</span>
                            <span class="text-xs text-ink-500">{{ formatEventDate(person.date, 'memorials') }}</span>
                        </div>
                        <span class="text-xl text-ink-400 group-hover:text-brand-500 group-hover:translate-x-1 transition-all">›</span>
                    </Link>
                </div>
                <EmptyState v-else title="Tidak ada haul terdekat" description="Belum ada tanggal wafat pada rentang pengingat." class="mt-4" />
            </Card>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.25fr_1fr]">
            <!-- Enhanced Birth Decade Chart -->
            <Card class="animate-slide-up">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-ink-900">Distribusi Tahun Kelahiran</h2>
                        <p class="mt-1 text-sm text-ink-600">Jumlah anggota per dekade</p>
                    </div>
                </div>
                <div v-if="dashboard.birthDecades.length" class="mt-6 flex h-48 items-end gap-2 sm:gap-4">
                    <div v-for="(item, index) in dashboard.birthDecades" :key="item.label" class="group flex min-w-0 flex-1 flex-col items-center gap-2">
                        <span class="text-xs font-medium text-ink-600 transition-all duration-300 group-hover:scale-110">{{ item.value }}</span>
                        <div class="relative w-full">
                            <div class="w-full rounded-t-lg gradient-primary transition-all duration-500 hover:shadow-glow group-hover:scale-105" :style="{ height: `${Math.max((item.value / maxDecade) * 130, 8)}px` }" :title="`${item.label}: ${item.value}`" />
                            <div class="absolute -top-8 left-1/2 -translate-x-1/2 rounded bg-ink-900 px-2 py-1 text-xs text-white opacity-0 transition-opacity duration-300 group-hover:opacity-100 whitespace-nowrap">
                                {{ item.label }}: {{ item.value }}
                            </div>
                        </div>
                        <span class="truncate text-[10px] text-ink-500 transition-colors duration-300 group-hover:text-brand-600">{{ item.label.slice(0, 4) }}</span>
                    </div>
                </div>
                <EmptyState v-else title="Data kelahiran belum tersedia" description="Tambahkan tahun lahir untuk melihat distribusi." class="mt-4" />
            </Card>

            <!-- Recent Activity Timeline -->
            <Card class="animate-slide-up">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-ink-900">Pembaruan Terakhir</h2>
                        <p class="mt-1 text-sm text-ink-600">Profil yang baru diubah</p>
                    </div>
                    <Link href="/anggota-keluarga" class="text-sm font-medium text-brand-600 hover:text-brand-700 transition-colors">Lihat semua</Link>
                </div>
                <div v-if="dashboard.recentPeople.length" class="mt-5 relative">
                    <!-- Timeline line -->
                    <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-ink-200" />
                    
                    <div class="space-y-4">
                        <Link v-for="(person, index) in dashboard.recentPeople" :key="person.id" :href="`/anggota-keluarga/${person.id}`" class="group relative flex items-center gap-3 transition-all duration-300 hover:translate-x-1">
                            <!-- Timeline dot -->
                            <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white border-2 border-brand-500 transition-all duration-300 group-hover:scale-125 group-hover:shadow-glow">
                                <PersonAvatar :person="person" size="sm" />
                            </div>
                            <div class="flex-1 rounded-control border border-ink-200 p-3 transition-all duration-300 hover:border-brand-300 hover:bg-brand-50">
                                <div class="flex items-center justify-between">
                                    <span class="block truncate text-sm font-medium text-ink-800 group-hover:text-brand-700 transition-colors">{{ person.name }}</span>
                                    <span class="text-xs text-ink-500">{{ formatUpdated(person.updatedAt) }}</span>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
                <EmptyState v-else title="Belum ada aktivitas" description="Pembaruan Person akan muncul di sini." class="mt-4" />
            </Card>
        </section>

        <!-- Cemetery Map Widget -->
        <Card class="animate-slide-up">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-ink-900">Peta Makam</h2>
                    <p class="mt-1 text-sm text-ink-600">Jelajahi lokasi makam keluarga</p>
                </div>
                <Badge tone="brand">Fitur</Badge>
            </div>
            <div class="mt-5">
                <Link href="/peta-makam" class="group relative overflow-hidden rounded-xl border border-ink-200 bg-gradient-to-br from-brand-50 to-canvas p-6 transition-all duration-300 hover:border-brand-300 hover:shadow-lg hover:scale-[1.02]">
                    <div class="absolute inset-0 bg-gradient-to-r from-brand-100/50 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100" />
                    <div class="relative flex items-center gap-6">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full gradient-primary text-3xl text-white shadow-glow group-hover:scale-110 transition-transform duration-300">
                            ⌖
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-ink-900 group-hover:text-brand-700 transition-colors">Peta Makam Interaktif</h3>
                            <p class="mt-1 text-sm text-ink-600">Lihat lokasi makam anggota keluarga dengan peta interaktif yang informatif</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-brand-600 group-hover:bg-brand-200 transition-colors">
                            <span class="text-xl">→</span>
                        </div>
                    </div>
                    <div class="relative mt-4 flex items-center gap-4 text-xs text-ink-500">
                        <span class="flex items-center gap-1">
                            <span class="h-2 w-2 rounded-full bg-brand-500" />
                            Lokasi akurat
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="h-2 w-2 rounded-full bg-brand-500" />
                            Informasi detail
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="h-2 w-2 rounded-full bg-brand-500" />
                            Navigasi mudah
                        </span>
                    </div>
                </Link>
            </div>
        </Card>
    </div>
</template>
