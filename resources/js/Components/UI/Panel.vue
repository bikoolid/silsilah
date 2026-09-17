<template>
    <section class="rounded-card border border-ink-200 bg-surface shadow-card">
        <div v-if="$slots.header" class="border-b border-ink-100 px-5 py-4">
            <slot name="header" />
        </div>
        <div class="p-5">
            <slot />
        </div>
    </section>
</template>
