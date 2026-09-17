<script setup>
defineProps({
    modelValue: Boolean,
    title: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue', 'close']);

function close() {
    emit('update:modelValue', false);
    emit('close');
}
</script>

<template>
    <Teleport to="body">
        <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/40 px-4 py-6" role="presentation" @click.self="close">
            <section class="w-full max-w-lg rounded-card bg-white shadow-popover" role="dialog" aria-modal="true" :aria-label="title || 'Dialog'">
                <header class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-ink-950">{{ title }}</h2>
                    <button type="button" class="rounded-control p-2 text-ink-500 hover:bg-ink-100 hover:text-ink-700" aria-label="Tutup" @click="close">
                        <span aria-hidden="true">×</span>
                    </button>
                </header>
                <div class="p-5">
                    <slot />
                </div>
                <footer v-if="$slots.footer" class="flex justify-end gap-3 border-t border-ink-100 px-5 py-4">
                    <slot name="footer" />
                </footer>
            </section>
        </div>
    </Teleport>
</template>
