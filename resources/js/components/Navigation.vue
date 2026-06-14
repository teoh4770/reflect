<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import DemoTour from '@/components/DemoTour.vue';

onMounted(() => {
    if (typeof window !== 'undefined' && 'Notification' in window) {
        if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
            Notification.requestPermission();
        }
    }

    if (typeof window !== 'undefined' && window.Echo) {
        window.Echo.channel('interrupts')
            .listen('.InterruptTriggered', () => {
                if (Notification.permission === 'granted') {
                    const notification = new Notification('Time to Reflect', {
                        body: 'Your next interrupt is ready. Take a moment for brutal awareness.',
                    });
                    
                    notification.onclick = () => {
                        window.focus();
                        router.visit('/');
                    };
                } else {
                    alert('Time to Reflect: Your next interrupt is ready.');
                }
            });
    }
});
</script>

<template>
    <DemoTour />
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
