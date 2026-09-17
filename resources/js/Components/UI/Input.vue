<script setup>
defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    label: {
        type: String,
        default: '',
    },
    hint: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
    id: {
        type: String,
        required: true,
    },
    placeholder: {
        type: String,
        default: '',
    },
    name: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'text',
    },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="space-y-2">
        <label v-if="label" :for="id" class="block text-sm font-medium text-ink-700">{{ label }}</label>
        <input
            :id="id"
            :type="type"
            :name="name"
            :value="modelValue"
            :placeholder="placeholder"
            class="h-11 w-full rounded-control border border-ink-200 bg-white px-3 text-sm text-ink-950 outline-none transition placeholder:text-ink-500 focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
            :class="{ 'border-red-400 focus:border-red-500 focus:ring-red-100': error }"
            @input="$emit('update:modelValue', $event.target.value)"
        >
        <p v-if="error" class="text-xs text-red-700">{{ error }}</p>
        <p v-else-if="hint" class="text-xs text-ink-500">{{ hint }}</p>
    </div>
</template>
