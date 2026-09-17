<script setup>
import { ref } from 'vue';

defineProps({
    query: {
        type: String,
        default: '',
    },
    generations: { type: Number, default: 1 },
});

defineEmits(['update:query', 'update:generations', 'zoom-in', 'zoom-out', 'fit', 'expand', 'center', 'toggle-fullscreen', 'toggle-detail', 'export-png', 'export-pdf']);
const collapsed = ref(false);
</script>

<template>
    <button v-if="collapsed" type="button" class="tree-toolbar absolute left-5 top-5 z-10 rounded-control border border-ink-200/50 glass-effect px-3 py-2 text-sm shadow-lg hover-lift transition-all duration-200" aria-label="Tampilkan kontrol Family Tree" @click="collapsed = false">☰ Kontrol</button>
    <div v-else class="tree-toolbar absolute left-5 top-5 z-10 w-48 rounded-card border border-ink-200/50 glass-effect p-2 shadow-lg animate-slide-up">
        <div class="mb-2 flex items-center justify-between px-1">
            <span class="text-xs font-semibold text-gradient">Kontrol pohon</span>
            <button type="button" class="rounded px-1.5 text-ink-600 hover:bg-brand-50 transition-all duration-200" aria-label="Sembunyikan kontrol Family Tree" title="Sembunyikan kontrol" @click="collapsed = true">−</button>
        </div>
        <input
            :value="query"
            type="search"
            placeholder="Cari anggota keluarga..."
            aria-label="Cari anggota di pohon keluarga"
            class="mb-2 w-full rounded-control border border-ink-200/50 bg-white/80 backdrop-blur px-2 py-2 text-xs text-ink-900 outline-none transition-all duration-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-200"
            @input="$emit('update:query', $event.target.value)"
        >
        <button type="button" class="tree-control" aria-label="Perbesar pohon" @click="$emit('zoom-in')"><span>＋</span> Zoom In</button>
        <button type="button" class="tree-control" aria-label="Perkecil pohon" @click="$emit('zoom-out')"><span>−</span> Zoom Out</button>
        <button type="button" class="tree-control" @click="$emit('fit')"><span>⛶</span> Fit View</button>
        <div class="mb-1 px-1 text-[0.68rem] font-semibold uppercase tracking-wide text-ink-500">Tampilkan keturunan</div>
        <div class="mb-2 grid grid-cols-3 gap-1">
            <button v-for="option in [{ value: 1, label: 'Anak' }, { value: 2, label: 'Cucu' }, { value: 3, label: 'Cicit' }]" :key="option.value" type="button" class="rounded-control border px-1 py-1.5 text-[0.68rem] transition-all duration-200" :class="generations === option.value ? 'border-brand-500 bg-brand-50 text-brand-700 shadow-sm' : 'border-ink-200 text-ink-600 hover:bg-brand-50 hover:border-brand-300'" @click="$emit('update:generations', option.value)">{{ option.label }}</button>
        </div>
        <button type="button" class="tree-control" @click="$emit('center')"><span>◎</span> Center</button>
        <button type="button" class="tree-control" @click="$emit('toggle-fullscreen')"><span>⛶</span> Fullscreen</button>
        <button type="button" class="tree-control" @click="$emit('toggle-detail')"><span>▣</span> Detail</button>
        <div class="my-1 border-t border-ink-200/50" />
        <button type="button" class="tree-control" @click="$emit('export-png')"><span>▣</span> Ekspor PNG</button>
        <button type="button" class="tree-control" @click="$emit('export-pdf')"><span>▤</span> Ekspor PDF</button>
    </div>
</template>

<style scoped>
.tree-control {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 0.5rem;
    border-radius: 0.65rem;
    padding: 0.65rem 0.5rem;
    color: #475569;
    font-size: 0.72rem;
    text-align: left;
    transition: all 0.2s ease;
}
.tree-control:hover {
    background: #f0fdf9;
    color: #105f50;
    transform: translateX(2px);
}
.tree-control span {
    width: 1rem;
    font-size: 1rem;
    text-align: center;
}
</style>
