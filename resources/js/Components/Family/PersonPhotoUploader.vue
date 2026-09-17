<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: [Object, File], default: null },
    existingUrl: { type: String, default: '' },
    error: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue', 'remove']);
const preview = ref(props.existingUrl);
const validationError = ref('');
const input = ref(null);
let objectUrl = null;

function selectFile(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    validationError.value = '';
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        validationError.value = 'Format foto harus JPG, PNG, atau WebP.';
        event.target.value = '';
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        validationError.value = 'Ukuran foto maksimal 2 MB.';
        event.target.value = '';
        return;
    }
    if (objectUrl) URL.revokeObjectURL(objectUrl);
    objectUrl = URL.createObjectURL(file);
    preview.value = objectUrl;
    emit('update:modelValue', file);
}

function clearPhoto() {
    if (objectUrl) URL.revokeObjectURL(objectUrl);
    objectUrl = null;
    preview.value = '';
    if (input.value) input.value.value = '';
    emit('update:modelValue', null);
    emit('remove');
}

watch(() => props.existingUrl, (value) => {
    if (!objectUrl) preview.value = value;
});

onBeforeUnmount(() => {
    if (objectUrl) URL.revokeObjectURL(objectUrl);
});
</script>

<template>
    <div class="space-y-3">
        <label class="block text-sm font-medium text-ink-700" for="photo">Foto Person</label>
        <div class="flex items-center gap-4">
            <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand-100 text-2xl font-semibold text-brand-700">
                <img v-if="preview" :src="preview" alt="Preview foto Person" class="h-full w-full object-cover">
                <span v-else aria-hidden="true">Foto</span>
            </div>
            <div class="min-w-0 space-y-2">
                <input ref="input" id="photo" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-ink-700 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:font-medium file:text-brand-700" @change="selectFile">
                <p class="text-xs text-ink-500">JPG, PNG, atau WebP. Maksimal 2 MB.</p>
                <button v-if="preview" type="button" class="text-xs font-medium text-red-700 hover:underline" @click="clearPhoto">Hapus atau ganti foto</button>
            </div>
        </div>
        <p v-if="validationError || error" class="text-sm text-red-700">{{ validationError || error }}</p>
    </div>
</template>
