/// <reference lib="webworker" />
import { initializeApp } from 'firebase/app';
import type { MessagePayload } from 'firebase/messaging/sw';
import { getMessaging, onBackgroundMessage } from 'firebase/messaging/sw';
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

self.addEventListener('notificationclick', (event: NotificationEvent) => {
    event.notification.close();
    event.stopImmediatePropagation();

    const fcmData = event.notification.data?.FCM_MSG;
    const payloadLink = fcmData?.notification?.click_action || fcmData?.fcmOptions?.link || '/';
    const urlToOpen = new URL(payloadLink, self.location.origin).href;

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (let i = 0; i < windowClients.length; i++) {
                const client = windowClients[i];
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    return client.focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(urlToOpen);
            }
        })
    );
});
