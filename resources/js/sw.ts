/// <reference lib="webworker" />
import { initializeApp } from 'firebase/app';
import { getMessaging, onBackgroundMessage, MessagePayload } from 'firebase/messaging/sw';
import { precacheAndRoute } from 'workbox-precaching';

declare let self: ServiceWorkerGlobalScope & typeof globalThis;

// Inject Workbox precache manifest (Vite replaces this at build time)
precacheAndRoute(self.__WB_MANIFEST || []);

// Initialize Firebase securely with Vite's env vars
const firebaseApp = initializeApp({
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID,
});

const messaging = getMessaging(firebaseApp);

onBackgroundMessage(messaging, (payload: MessagePayload) => {
    console.log('[sw.ts] Received background message ', payload);
});
