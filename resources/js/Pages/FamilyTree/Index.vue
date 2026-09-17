<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { jsPDF } from 'jspdf';
import Badge from '../../Components/UI/Badge.vue';
import Button from '../../Components/UI/Button.vue';
import EmptyFamilyState from '../../Components/Family/EmptyFamilyState.vue';
import ErrorState from '../../Components/UI/ErrorState.vue';
import PersonAvatar from '../../Components/Family/PersonAvatar.vue';
import TreeCanvas from '../../Components/Family/TreeCanvas.vue';
import TreeToolbar from '../../Components/Family/TreeToolbar.vue';

defineOptions({ layout: null });
const props = defineProps({ 
    tree: { type: Object, default: () => ({ nodes: [], links: [], root: null, descendantDepth: 1 }) }, 
    notFound: Boolean,
    persons: { type: Array, default: () => [] }
});
const treeShell = ref(null);
const selectedId = ref(null);
const detailVisible = ref(true);
const fullscreen = ref(false);
const query = ref('');
const selectedPerson = ref(null);
const canvas = ref(null);
const toolbarGenerations = ref(props.tree.descendantDepth || 1);
const selected = computed(() => props.tree.nodes.find((node) => String(node.id) === String(selectedId.value)) || null);
const related = computed(() => {
    if (!selected.value) return { parents: [], spouses: [], children: [] };
    const ids = (type, direction) => props.tree.links
        .filter((link) => link.type === type && String(direction === 'in' ? link.target : link.source) === String(selected.value.id))
        .map((link) => props.tree.nodes.find((node) => String(node.id) === String(direction === 'in' ? link.source : link.target)))
        .filter(Boolean);
    return {
        parents: ids('parent-child', 'in'),
        children: ids('parent-child', 'out'),
        spouses: ids('spouse', 'out').concat(ids('spouse', 'in')),
    };
});

function selectNode(node) { selectedId.value = node.id; }
function toggleDetail() { detailVisible.value = !detailVisible.value; }

function selectPerson(person) {
    selectedPerson.value = person;
    router.get(`/silsilah/${person.id}`, {}, { preserveState: false });
}

function changeGenerations(value) {
    toolbarGenerations.value = value;
    if (props.tree.root) {
        router.get(`/silsilah/${props.tree.root}`, { generations: value }, { preserveState: true, preserveScroll: true, replace: true });
    }
}
async function toggleFullscreen() {
    if (!document.fullscreenElement) {
        await treeShell.value?.requestFullscreen?.();
    } else {
        await document.exitFullscreen?.();
    }
}
function syncFullscreen() {
    fullscreen.value = Boolean(document.fullscreenElement);
}
function genderLabel(person) { return person.gender === 'female' ? 'Perempuan' : person.gender === 'male' ? 'Laki-laki' : 'Gender belum tersedia'; }
function years(person) { return `${person.birthYear || '—'} – ${person.deathYear || ''}`; }
function exportName(extension) {
    const root = props.tree.nodes.find((node) => node.isRoot) || props.tree.nodes[0];
    return `karuhun-silsilah-${(root?.name || 'keluarga').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')}.${extension}`;
}
function svgDataUrl() {
    const markup = canvas.value?.svgMarkup();
    if (!markup) return null;
    const viewBox = markup.match(/viewBox="0 0 ([\d.]+) ([\d.]+)"/);
    const sizedMarkup = viewBox
        ? markup.replace('width="100%"', `width="${viewBox[1]}"`).replace('height="100%"', `height="${viewBox[2]}"`)
        : markup;
    const svg = sizedMarkup.includes('xmlns="http://www.w3.org/2000/svg"')
        ? sizedMarkup
        : sizedMarkup.replace('<svg ', '<svg xmlns="http://www.w3.org/2000/svg" ');
    return `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`;
}
function downloadDataUrl(dataUrl, filename) {
    const link = document.createElement('a');
    link.download = filename;
    link.href = dataUrl;
    document.body.appendChild(link);
    link.click();
    link.remove();
}
function exportReady() {
    canvas.value?.reset();
    return new Promise((resolve) => window.setTimeout(resolve, 350));
}
function rasterizeSvg(dataUrl) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = () => {
            const output = document.createElement('canvas');
            output.width = image.width * 2;
            output.height = image.height * 2;
            const context = output.getContext('2d');
            context.fillStyle = '#f5f9fd';
            context.fillRect(0, 0, output.width, output.height);
            context.drawImage(image, 0, 0, output.width, output.height);
            resolve({
                dataUrl: output.toDataURL('image/png'),
                width: output.width,
                height: output.height,
            });
        };
        image.onerror = () => reject(new Error('Gagal memuat SVG Family Tree untuk ekspor.'));
        image.src = dataUrl;
    });
}
async function exportPng() {
    await exportReady();
    const dataUrl = svgDataUrl();
    if (!dataUrl) return;
    const raster = await rasterizeSvg(dataUrl);
    downloadDataUrl(raster.dataUrl, exportName('png'));
}
async function exportPdf() {
    await exportReady();
    const dataUrl = svgDataUrl();
    if (!dataUrl) return;
    const raster = await rasterizeSvg(dataUrl);
    {
        const pdf = new jsPDF({ orientation: raster.width >= raster.height ? 'landscape' : 'portrait', unit: 'pt', format: 'a4' });
        const margin = 24;
        const maxWidth = pdf.internal.pageSize.getWidth() - margin * 2;
        const maxHeight = pdf.internal.pageSize.getHeight() - margin * 2 - 20;
        const scale = Math.min(maxWidth / raster.width, maxHeight / raster.height);
        const width = raster.width * scale;
        const height = raster.height * scale;
        const root = props.tree.nodes.find((node) => node.isRoot) || props.tree.nodes[0];
        const exportDate = new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(new Date());
        pdf.setFontSize(16);
        pdf.setTextColor('#1c3858');
        pdf.text(`Silsilah Keluarga - ${root?.name || 'Keluarga'}`, margin, margin);
        pdf.setFontSize(9);
        pdf.setTextColor('#527396');
        pdf.text(`Diekspor ${exportDate}`, margin, margin + 13);
        pdf.addImage(raster.dataUrl, 'PNG', margin, margin + 26, width, height);
        const legendY = Math.min(margin + 26 + height + 12, pdf.internal.pageSize.getHeight() - margin);
        pdf.text('Legenda: garis penuh = orang tua-anak · garis putus-putus = pasangan · hijau = hidup · abu-abu = wafat', margin, legendY);
        pdf.save(exportName('pdf'));
    }
}
</script>

<template>
    <div ref="treeShell" class="fixed inset-0 flex flex-col bg-gradient-to-br from-canvas to-brand-50/30 text-ink-950" @fullscreenchange="syncFullscreen">
        <header class="flex h-[76px] shrink-0 items-center gap-6 glass-effect px-6">
            <Link href="/dashboard" class="flex items-center gap-2 rounded-control border border-ink-200 px-3 py-2 text-sm font-medium text-ink-700 hover:bg-brand-50 hover:border-brand-300 transition-all duration-200 hover-lift">
                <span>←</span>
                <span>Kembali</span>
            </Link>
            <div class="flex items-center gap-3">
                <img :src="'/images/logo.png'" alt="Karuhun" class="h-8 w-8 rounded-control object-contain shadow-glow">
                <div>
                    <p class="text-lg font-semibold text-gradient">Silsilah Keluarga</p>
                    <p class="text-xs text-ink-600">Visualisasi pohon keluarga interaktif</p>
                </div>
            </div>
            <div class="flex-1" />
            <div class="flex items-center gap-3">
                <div class="relative">
                    <select 
                        v-model="selectedPerson" 
                        @change="selectPerson(selectedPerson)"
                        class="w-64 appearance-none rounded-control border border-ink-200/50 bg-white/80 backdrop-blur px-4 py-2 pr-10 text-sm outline-none transition-all duration-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-200 cursor-pointer"
                        aria-label="Pilih anggota keluarga"
                    >
                        <option :value="null">Pilih anggota keluarga...</option>
                        <option v-for="person in props.persons" :key="person.id" :value="person">
                            {{ person.name }} {{ person.birthYear ? `(${person.birthYear})` : '' }}
                        </option>
                    </select>
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-ink-500">▼</span>
                </div>
            </div>
        </header>

        <ErrorState v-if="notFound" title="Person root tidak ditemukan" description="Pohon keluarga tidak dapat dibuat dari Person yang diminta." class="m-6" />
        <div v-else-if="!tree.nodes?.length" class="m-6 text-center">
            <div class="rounded-card border border-ink-200/50 bg-white/80 backdrop-blur p-8">
                <div class="mb-4 flex justify-center">
                    <span class="text-6xl">🌳</span>
                </div>
                <h3 class="text-xl font-semibold text-ink-900 mb-2">Pilih Anggota Keluarga</h3>
                <p class="text-ink-600 mb-4">Pilih anggota keluarga dari dropdown di atas untuk melihat silsilah keluarga mereka.</p>
                <div class="inline-flex items-center gap-2 text-sm text-ink-500">
                    <span class="animate-bounce">👆</span>
                    <span>Gunakan dropdown di pojok kanan atas</span>
                </div>
            </div>
        </div>
        <main v-else class="relative min-h-0 flex-1">
            <TreeToolbar :generations="toolbarGenerations" @update:generations="changeGenerations" @zoom-in="canvas?.zoomIn()" @zoom-out="canvas?.zoomOut()" @fit="canvas?.fitView()" @expand="canvas?.fitView()" @center="canvas?.center()" @toggle-fullscreen="toggleFullscreen" @toggle-detail="toggleDetail" @export-png="exportPng" @export-pdf="exportPdf" />
            <TreeCanvas ref="canvas" :nodes="tree.nodes" :links="tree.links" :selected-id="selectedId" :query="query" @select="selectNode" />
            <section class="absolute bottom-4 left-5 flex flex-wrap items-center gap-x-7 gap-y-2 rounded-card border border-ink-200/50 glass-effect px-6 py-4 text-xs text-ink-600 shadow-lg animate-slide-up" :class="detailVisible ? 'right-[24rem]' : 'right-5'">
                <strong class="text-sm text-gradient">Keterangan:</strong>
                <span><i class="mr-2 inline-block h-0.5 w-8 bg-brand-500" />Hubungan orang tua - anak</span>
                <span><i class="mr-2 inline-block h-0.5 w-8 border-t-2 border-dashed border-brand-400" />Hubungan pasangan</span>
                <span><i class="mr-2 inline-block h-3 w-3 rounded-full bg-brand-500" />Masih hidup</span>
                <span><i class="mr-2 inline-block h-3 w-3 rounded-full bg-ink-400" />Meninggal</span>
                <span class="text-sky-600">♂ &nbsp; Laki-laki</span>
                <span class="text-pink-600">♀ &nbsp; Perempuan</span>
            </section>

            <aside v-if="detailVisible" class="absolute inset-y-0 right-0 w-[23rem] overflow-y-auto border-l border-ink-200/50 glass-effect px-5 py-5 shadow-xl animate-slide-up">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gradient">Detail Anggota Keluarga</h2>
                    <button type="button" class="rounded-control px-2 py-1 text-sm text-ink-600 hover:bg-brand-50 transition-all duration-200" aria-label="Sembunyikan detail anggota keluarga" title="Sembunyikan detail" @click="toggleDetail">−</button>
                </div>
                <div v-if="selected" class="rounded-card border border-ink-200/50 card-gradient p-4 shadow-md hover-lift transition-all duration-200">
                    <div class="flex items-center gap-4 border-b border-ink-200/50 pb-5">
                        <PersonAvatar :person="selected" size="lg" />
                        <div class="min-w-0">
                            <h3 class="truncate text-xl font-bold">{{ selected.name }} <span :class="selected.gender === 'female' ? 'text-pink-600' : 'text-sky-600'">{{ selected.gender === 'female' ? '♀' : '♂' }}</span></h3>
                            <p class="text-sm text-ink-600">{{ years(selected) }}</p>
                            <Badge :tone="selected.deceased ? 'neutral' : 'brand'" class="mt-2">{{ selected.deceased ? '● Sudah Meninggal' : '● Masih Hidup' }}</Badge>
                        </div>
                    </div>
                    <section class="border-b border-ink-200/50 py-5">
                        <h4 class="mb-3 font-semibold text-ink-800">♧ &nbsp; Orang Tua</h4>
                        <div v-if="related.parents.length" class="space-y-3">
                            <button v-for="parent in related.parents" :key="parent.id" class="flex w-full items-center gap-3 text-left rounded-control p-2 hover:bg-brand-50 transition-all duration-200" @click="selectNode(parent)">
                                <PersonAvatar :person="parent" size="sm" /><span><strong class="block text-sm text-ink-800">{{ parent.name }}</strong><small class="text-ink-500">{{ genderLabel(parent) }} · {{ years(parent) }}</small></span>
                            </button>
                        </div>
                        <p v-else class="text-sm text-ink-500">Data orang tua belum tersedia.</p>
                    </section>
                    <section class="border-b border-ink-200/50 py-5">
                        <h4 class="mb-3 font-semibold text-ink-800">🔗 &nbsp; Pasangan</h4>
                        <div v-if="related.spouses.length" class="space-y-3">
                            <button v-for="spouse in related.spouses" :key="spouse.id" class="flex w-full items-center gap-3 text-left rounded-control p-2 hover:bg-brand-50 transition-all duration-200" @click="selectNode(spouse)">
                                <PersonAvatar :person="spouse" size="sm" /><span><strong class="block text-sm text-ink-800">{{ spouse.name }}</strong><small class="text-ink-500">{{ years(spouse) }}</small></span>
                            </button>
                        </div>
                        <p v-else class="text-sm text-ink-500">Belum ada pasangan.</p>
                    </section>
                    <section class="py-5">
                        <h4 class="mb-3 font-semibold text-ink-800">♧ &nbsp; Anak-anak ({{ related.children.length }})</h4>
                        <div v-if="related.children.length" class="space-y-3">
                            <button v-for="child in related.children" :key="child.id" class="flex w-full items-center gap-3 text-left rounded-control p-2 hover:bg-brand-50 transition-all duration-200" @click="selectNode(child)">
                                <PersonAvatar :person="child" size="sm" /><span class="min-w-0 flex-1"><strong class="block truncate text-sm text-ink-800">{{ child.name }}</strong><small class="text-ink-500">{{ years(child) }}</small></span><span class="text-xl text-brand-500">›</span>
                            </button>
                        </div>
                        <p v-else class="text-sm text-ink-500">Belum ada data anak.</p>
                    </section>
                    <div class="space-y-3 border-t border-ink-200/50 pt-5">
                        <Link :href="`/anggota-keluarga/${selected.id}`"><Button class="w-full">♧ &nbsp; Lihat Profil</Button></Link>
                        <Link v-if="selected.can?.edit" :href="`/anggota-keluarga/${selected.id}/edit`"><Button variant="secondary" class="w-full">✎ &nbsp; Edit Profil</Button></Link>
                    </div>
                </div>
                <EmptyFamilyState v-else title="Pilih Person" description="Klik node untuk melihat ringkasan." />
            </aside>
            <button
                v-else
                type="button"
                class="absolute right-5 top-5 z-10 rounded-control border border-ink-200/50 glass-effect px-4 py-3 text-sm font-semibold text-ink-700 shadow-lg hover:bg-brand-50 transition-all duration-200 hover-lift"
                aria-label="Tampilkan detail anggota keluarga"
                title="Tampilkan detail anggota keluarga"
                @click="toggleDetail"
            >
                ‹ Detail Anggota Keluarga
            </button>
        </main>
    </div>
</template>
