<script setup>
import { useForm } from '@inertiajs/vue3';
import Input from '../../Components/UI/Input.vue';
import Button from '../../Components/UI/Button.vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login');
}
</script>

<template>
    <main class="indonesian-heritage flex min-h-screen items-center justify-center px-4 py-8">
        <section class="relative w-full max-w-md rounded-card border border-ink-200/50 glass-effect p-6 shadow-xl sm:p-8 animate-slide-up">
            <div class="relative z-10 mb-8">
                <div class="mb-4 flex items-center justify-center">
                    <div class="relative">
                        <img :src="'/images/logo.png'" alt="Karuhun" class="h-16 w-16 rounded-control object-contain shadow-glow">
                        <div class="absolute -inset-2 rounded-control bg-gradient-to-r from-brand-400 to-brand-600 opacity-20 blur-xl" />
                    </div>
                </div>
                <p class="text-center text-sm font-semibold uppercase tracking-[0.16em] text-gradient">Karuhun</p>
                <h1 class="mt-2 text-center text-2xl font-semibold tracking-tight text-ink-900">Masuk ke workspace</h1>
                <p class="mt-2 text-center text-sm text-ink-600">Kelola data keluarga dengan akun yang terhubung ke Person.</p>
            </div>
            <form class="relative z-10 space-y-5" @submit.prevent="submit">
                <Input id="email" v-model="form.email" type="email" label="E-mail" autocomplete="email" :error="form.errors.email" />
                <Input id="password" v-model="form.password" type="password" label="Kata sandi" autocomplete="current-password" :error="form.errors.password" />
                <label class="flex items-center gap-2 text-sm text-ink-600 cursor-pointer hover:text-ink-800 transition-colors">
                    <input v-model="form.remember" type="checkbox" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500 focus:ring-offset-2">
                    Ingat saya
                </label>
                <Button type="submit" class="w-full" :disabled="form.processing">
                    <span v-if="form.processing">Memproses...</span>
                    <span v-else>Masuk</span>
                </Button>
            </form>
            
            <!-- Decorative batik-inspired elements -->
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-brand-500 via-brand-400 to-brand-500" />
            <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-brand-500 via-brand-400 to-brand-500" />
            <div class="absolute -top-6 -left-6 w-12 h-12 rounded-full bg-brand-200/30 blur-xl" />
            <div class="absolute -bottom-6 -right-6 w-12 h-12 rounded-full bg-brand-200/30 blur-xl" />
        </section>
    </main>
</template>
