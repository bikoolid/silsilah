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
    id: {
        type: String,
        required: true,
    },
    name: {
        type: String,
        default: '',
    },
    options: {
        type: Array,
        default: () => [],
    },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="space-y-2">
        <label v-if="label" :for="id" class="block text-sm font-medium text-ink-700">{{ label }}</label>
        <select
            :id="id"
            :name="name"
            :value="modelValue"
            class="h-11 w-full rounded-control border border-ink-200 bg-white px-3 text-sm text-ink-950 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
            @change="$emit('update:modelValue', $event.target.value)"
        >
            <option v-for="option in options" :key="option.value" :value="option.value">
                {{ option.label }}
            </option>
        </select>
    </div>
</template>
