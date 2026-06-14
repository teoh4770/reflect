<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <nav class="fixed top-0 left-0 w-full z-40 bg-black/50 backdrop-blur-sm border-b border-white/5 px-8 py-4 flex justify-between items-center font-mono text-[10px] uppercase tracking-[0.3em]">
        <div class="flex gap-8">
            <Link 
                href="/" 
                class="transition-colors hover:text-white"
                :class="[$page.component === 'Interrupt' ? 'text-white font-bold' : 'text-zinc-500']"
            >
                Interrupt
            </Link>
            <Link 
                href="/armory" 
                class="transition-colors hover:text-white"
                :class="[$page.component === 'Armory' ? 'text-white font-bold' : 'text-zinc-500']"
            >
                Armory
            </Link>
            <Link 
                href="/settings" 
                class="transition-colors hover:text-white"
                :class="[$page.component === 'Settings' ? 'text-white font-bold' : 'text-zinc-500']"
            >
                Settings
            </Link>
        </div>
        <div class="text-zinc-700 hidden md:block">
            Reflect &bull; v1.0
        </div>
    </nav>
</template>
