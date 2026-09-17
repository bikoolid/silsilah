<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet.markercluster';
import { Link } from '@inertiajs/vue3';
import Input from '../UI/Input.vue';
import EmptyFamilyState from './EmptyFamilyState.vue';

const props = defineProps({ locations: { type: Array, default: () => [] } });
const container = ref(null);
const search = ref('');
let map;
let cluster;
let markers = [];

const filteredLocations = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) return props.locations;
    return props.locations.filter((item) => `${item.name} ${item.cemetery?.name || ''} ${item.cemetery?.address || ''}`.toLowerCase().includes(term));
});

function markerHtml() {
    return '<span aria-hidden="true">✦</span>';
}

function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    }[character]));
}

function popup(item) {
    const cemetery = item.cemetery || {};
    const years = [item.birthYear, item.deathYear].filter(Boolean).join(' – ');
    return `<div class="min-w-[180px]"><strong>${escapeHtml(item.name)}</strong>${years ? `<br><span>${escapeHtml(years)}</span>` : ''}${cemetery.name ? `<br><span>${escapeHtml(cemetery.name)}</span>` : ''}<br><a href="/anggota-keluarga/${item.id}">Buka profil Person →</a></div>`;
}

function renderMarkers() {
    if (!map) return;
    cluster?.clearLayers();
    markers = filteredLocations.value.map((item) => {
        const point = [item.cemetery.latitude, item.cemetery.longitude];
        return L.marker(point, {
            title: item.name,
            icon: L.divIcon({ className: 'cemetery-marker', html: markerHtml(), iconSize: [36, 36], iconAnchor: [18, 18] }),
        }).bindTooltip(item.name).bindPopup(popup(item));
    });
    cluster.addLayers(markers);
    if (markers.length === 1) map.setView(markers[0].getLatLng(), 14);
    else if (markers.length > 1) map.fitBounds(L.featureGroup(markers).getBounds().pad(0.15));
}

function recenter() {
    renderMarkers();
    if (!markers.length) map.setView([-2.5, 118], 4);
}

onMounted(() => {
    map = L.map(container.value).setView([-2.5, 118], 4);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors', maxZoom: 19 }).addTo(map);
    cluster = L.markerClusterGroup();
    map.addLayer(cluster);
    renderMarkers();
});

watch(filteredLocations, renderMarkers);

onUnmounted(() => {
    map?.remove();
    map = null;
    cluster = null;
    markers = [];
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap items-end gap-3">
            <Input id="cemetery-search" v-model="search" label="Cari makam atau Person" placeholder="Nama Person atau area makam" class="min-w-64 flex-1" />
            <button type="button" class="rounded-control border border-ink-200 bg-white px-4 py-2.5 text-sm font-medium text-ink-700 shadow-card hover:border-brand-500" @click="recenter">Tampilkan semua</button>
            <span class="pb-3 text-sm text-ink-500">{{ filteredLocations.length }} lokasi</span>
        </div>
        <div ref="container" class="h-[min(70vh,700px)] overflow-hidden rounded-card border border-ink-200 shadow-card" aria-label="Peta lokasi makam keluarga" />
        <EmptyFamilyState v-if="!locations.length" title="Belum ada lokasi makam" description="Person dengan koordinat makam yang valid akan tampil di peta ini." />
    </div>
</template>
