<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted } from 'vue';

import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging";

const firebaseConfig = {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID,
};

let messaging: any;
if (typeof window !== 'undefined') {
    const app = initializeApp(firebaseConfig);
    messaging = getMessaging(app);
}

onMounted(() => {
    // --- WEB PUSH NOTIFICATIONS ---
    if (typeof window !== 'undefined' && 'Notification' in window) {
        const setupWebPush = async () => {
            try {
                const permission = await Notification.requestPermission();
                if (permission === 'granted') {
                    const currentToken = await getToken(messaging, { vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY });
                    if (currentToken) {
                        axios.post('/api/fcm-token', { token: currentToken }).catch(() => {});
                    }
                }
            } catch (err) {
                console.error('An error occurred while retrieving token: ', err);
            }
        };

        setupWebPush();

        onMessage(messaging, (payload) => {
            if (payload.notification) {
                new Notification(payload.notification.title || 'Notification', {
                    body: payload.notification.body,
                });
            }
        });
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
    <nav class="fixed bottom-0 md:top-0 md:bottom-auto left-0 w-full z-40 bg-black/90 md:bg-black/50 backdrop-blur-md border-t md:border-t-0 md:border-b border-white/10 md:border-white/5 px-2 md:px-8 py-4 flex justify-between items-center font-mono text-[10px] md:text-xs uppercase tracking-[0.15em] md:tracking-[0.3em]">
        <div class="flex w-full md:w-auto justify-around md:justify-start gap-2 md:gap-8">
            <Link
                href="/"
                class="transition-colors hover:text-white flex flex-col items-center gap-1"
                :class="[$page.component === 'Interrupt' ? 'text-white font-bold' : 'text-zinc-500']"
            >
                <!-- SVG Icon for Interrupt -->
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="md:hidden mb-1"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                <span class="text-[9px] md:text-[10px]">Interrupt</span>
            </Link>
            <Link
                href="/journal"
                class="transition-colors hover:text-white flex flex-col items-center gap-1"
                :class="[$page.component === 'Journal' ? 'text-white font-bold' : 'text-zinc-500']"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="md:hidden mb-1"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                <span class="text-[9px] md:text-[10px]">Journal</span>
            </Link>
            <Link
                href="/armory"
                class="transition-colors hover:text-white flex flex-col items-center gap-1"
                :class="[$page.component === 'Armory' ? 'text-white font-bold' : 'text-zinc-500']"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="md:hidden mb-1"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                <span class="text-[9px] md:text-[10px]">Armory</span>
            </Link>
            <Link
                href="/settings"
                class="transition-colors hover:text-white flex flex-col items-center gap-1"
                :class="[$page.component === 'Settings' ? 'text-white font-bold' : 'text-zinc-500']"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="md:hidden mb-1"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                <span class="text-[9px] md:text-[10px]">Settings</span>
            </Link>
        </div>
        <div class="text-zinc-700 hidden md:block">
            Reflect &bull; v1.0
        </div>
    </nav>
</template>
