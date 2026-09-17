<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Avatar from '../Components/UI/Avatar.vue';
import Button from '../Components/UI/Button.vue';

defineProps({
    title: {
        type: String,
        default: 'Dashboard',
    },
    description: {
        type: String,
        default: '',
    },
});

const mobileNavigationOpen = ref(false);
const navigationCollapsed = ref(false);
const page = usePage();
const authUser = computed(() => page.props.auth?.user || null);

const navigationItems = [
    { label: 'Dashboard', detail: 'Ringkasan aplikasi', href: '/dashboard', icon: '▦' },
    { label: 'Anggota Keluarga', detail: 'Daftar Person', href: '/anggota-keluarga', icon: '♙' },
    { label: 'Silsilah Keluarga', detail: 'Family Tree', href: '/silsilah', icon: '⌘' },
    { label: 'Pasangan', detail: 'Daftar Couple', href: '/pasangan', icon: '♡' },
    { label: 'Peta Makam', detail: 'Lokasi makam', href: '/peta-makam', icon: '◉' },
    { label: 'Pengaturan', detail: 'Preferensi aplikasi', href: '/pengaturan', icon: '⚙' },
];

function closeMobileNavigation() {
    mobileNavigationOpen.value = false;
}

function toggleNavigation() {
    navigationCollapsed.value = !navigationCollapsed.value;
}

function logout() {
    router.post('/logout');
}

const resolvedTitle = computed(() => {
    if (page.url.startsWith('/pengaturan')) {
        return 'Pengaturan';
    }

    const title = page.props.title || 'Dashboard';
    return title === 'Dashboard' ? '' : title;
});
</script>

<template>
    <div class="min-h-screen bg-canvas text-ink-950">
        <div
            v-if="mobileNavigationOpen"
            class="fixed inset-0 z-30 bg-ink-950/30 lg:hidden"
            aria-hidden="true"
            @click="closeMobileNavigation"
        />

        <aside
            class="fixed inset-y-0 left-0 z-40 flex -translate-x-full flex-col border-r border-ink-200 bg-white/90 backdrop-blur-lg transition-all duration-300 lg:translate-x-0"
            :class="[
                mobileNavigationOpen ? 'translate-x-0' : '',
                navigationCollapsed ? 'lg:w-20' : 'lg:w-72',
            ]"
            aria-label="Navigasi utama"
        >
            <div class="flex h-20 items-center gap-3 border-b border-ink-100/50 px-4 bg-gradient-to-r from-brand-50/50 to-transparent" :class="{ 'lg:justify-center': navigationCollapsed }">
                <img :src="'/images/logo.png'" alt="Karuhun" class="h-10 w-10 rounded-control object-contain shadow-glow">
                <div v-if="!navigationCollapsed">
                    <p class="font-semibold tracking-tight text-gradient">Karuhun</p>
                    <p class="text-xs text-ink-500">Family workspace</p>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-6">
                <p v-if="!navigationCollapsed" class="mb-3 px-3 text-xs font-semibold uppercase tracking-[0.16em] text-ink-500">Workspace</p>
                <Link
                    v-for="item in navigationItems"
                    :key="item.label"
                    :href="item.href"
                    class="group flex items-center gap-3 rounded-control px-3 py-3 transition-all duration-200 hover:bg-brand-50 hover:shadow-sm"
                    :class="{ 'lg:justify-center': navigationCollapsed }"
                    :title="navigationCollapsed ? item.label : undefined"
                    @click="closeMobileNavigation"
                >
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-ink-100 text-base font-semibold text-ink-500 transition-all duration-200 group-hover:bg-brand-100 group-hover:text-brand-700 group-hover:scale-110" aria-hidden="true">
                        {{ item.icon }}
                    </span>
                    <span v-if="!navigationCollapsed" class="min-w-0">
                        <span class="block text-sm font-medium text-ink-700 group-hover:text-brand-700">{{ item.label }}</span>
                        <span class="mt-0.5 block truncate text-xs text-ink-500">{{ item.detail }}</span>
                    </span>
                </Link>
            </nav>

            <div class="border-t border-ink-100/50 p-3 bg-gradient-to-r from-brand-50/30 to-transparent">
                <div class="flex items-center gap-3 rounded-control bg-ink-100/60 p-3 hover:bg-ink-100 transition-all duration-200" :class="{ 'lg:justify-center': navigationCollapsed }" :title="navigationCollapsed ? 'Admin Karuhun' : undefined">
                    <Avatar :name="authUser?.name || 'Karuhun'" :src="authUser?.person?.photo" :status="authUser?.person ? (authUser.person.deceased ? 'deceased' : 'living') : ''" size="sm" />
                    <div v-if="!navigationCollapsed" class="min-w-0">
                        <p class="truncate text-sm font-medium text-ink-700">{{ authUser?.name || 'Pengguna' }}</p>
                        <p class="truncate text-xs text-ink-500">{{ authUser?.role || 'Manager' }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <div class="transition-[padding] duration-300" :class="navigationCollapsed ? 'lg:pl-20' : 'lg:pl-72'">
            <header class="sticky top-0 z-20 border-b border-ink-200/50 glass-effect">
                <div class="flex h-20 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <Button
                            variant="ghost"
                            size="sm"
                            class="lg:hidden"
                            aria-label="Buka navigasi"
                            :aria-expanded="mobileNavigationOpen"
                            @click="mobileNavigationOpen = true"
                        >
                            <span aria-hidden="true" class="text-lg">☰</span>
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="hidden lg:inline-flex"
                            :aria-label="navigationCollapsed ? 'Tampilkan menu utama' : 'Perkecil menu utama'"
                            :aria-expanded="!navigationCollapsed"
                            :title="navigationCollapsed ? 'Tampilkan menu utama' : 'Perkecil menu utama'"
                            @click="toggleNavigation"
                        >
                            <span aria-hidden="true">{{ navigationCollapsed ? '»' : '«' }}</span>
                        </Button>
                        <div v-if="resolvedTitle" class="min-w-0">
                            <p class="truncate text-lg font-semibold tracking-tight text-gradient">{{ resolvedTitle }}</p>
                            <p v-if="description" class="hidden truncate text-sm text-ink-500 sm:block">{{ description }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <slot name="header-actions" />
                        <div class="hidden h-8 w-px bg-ink-200 sm:block" aria-hidden="true" />
                        <Avatar :name="authUser?.name || 'Karuhun'" :src="authUser?.person?.photo" :status="authUser?.person ? (authUser.person.deceased ? 'deceased' : 'living') : ''" size="sm" />
                        <button type="button" class="rounded-control px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100 transition-all duration-200" title="Keluar" @click="logout">Keluar</button>
                    </div>
                </div>
            </header>

            <main class="min-h-[calc(100vh-5rem)] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <div class="mx-auto w-full max-w-7xl">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
