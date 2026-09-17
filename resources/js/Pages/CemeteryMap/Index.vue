<script setup>
import ApplicationLayout from '../../Layouts/ApplicationLayout.vue';
import CemeteryMap from '../../Components/Family/CemeteryMap.vue';

defineOptions({ layout: ApplicationLayout });
defineProps({ locations: { type: Array, default: () => [] } });
</script>

<template>
    <div class="space-y-6">
        <header>
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-600">Geografi Keluarga</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink-950">Peta Makam Keluarga</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">Jelajahi lokasi makam Person keluarga yang sudah memiliki koordinat.</p>
        </header>
        <CemeteryMap :locations="locations" />
    </div>
</template>
