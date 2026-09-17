<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    modelValue: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:modelValue']);
const container = ref(null);
let map;
let marker;

const fallback = [-6.9175, 107.6191];

function coordinates(value) {
    const latitude = Number(value?.latitude);
    const longitude = Number(value?.longitude);
    return Number.isFinite(latitude) && Number.isFinite(longitude)
        && latitude >= -90 && latitude <= 90 && longitude >= -180 && longitude <= 180
        ? [latitude, longitude]
        : null;
}

function updateLocation(latitude, longitude) {
    const next = { ...props.modelValue, latitude, longitude };
    emit('update:modelValue', next);
    marker?.setLatLng([latitude, longitude]);
}

function placeMarker(event) {
    updateLocation(event.latlng.lat.toFixed(6), event.latlng.lng.toFixed(6));
}

function markerIcon() {
    return L.divIcon({
        className: 'cemetery-picker-marker',
        html: '<span aria-hidden="true">✦</span>',
        iconSize: [36, 36],
        iconAnchor: [18, 18],
    });
}

onMounted(() => {
    const initial = coordinates(props.modelValue) || fallback;
    map = L.map(container.value).setView(initial, coordinates(props.modelValue) ? 15 : 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);
    marker = L.marker(initial, { draggable: true, icon: markerIcon(), title: 'Lokasi makam terpilih' }).addTo(map);
    marker.on('dragend', () => {
        const point = marker.getLatLng();
        updateLocation(point.lat.toFixed(6), point.lng.toFixed(6));
    });
    map.on('click', placeMarker);
});

watch(() => [props.modelValue.latitude, props.modelValue.longitude], (value) => {
    const next = coordinates({ latitude: value[0], longitude: value[1] });
    if (next && marker) {
        marker.setLatLng(next);
        map.panTo(next);
    }
});

onUnmounted(() => {
    map?.remove();
    map = null;
    marker = null;
});
</script>

<template>
    <div class="space-y-2 sm:col-span-2">
        <div ref="container" class="h-64 overflow-hidden rounded-control border border-ink-200" aria-label="Pilih titik lokasi makam" />
        <p class="text-xs text-ink-500">Klik peta atau geser marker untuk menentukan koordinat. Latitude dan longitude akan terisi otomatis.</p>
    </div>
</template>
