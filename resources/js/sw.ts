import { initializeApp } from 'firebase/app';
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

onBackgroundMessage(messaging, (payload) => {
    console.log('[sw.ts] Received background message ', payload);
    // The Firebase SDK automatically displays the notification when the app is in the background 
    // if the payload contains a 'notification' object. 
    // Do NOT call self.registration.showNotification() here, or the user will get duplicate notifications!
});

// Handle notification clicks (applies to both foreground and background notifications)
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    event.waitUntil(
        self.clients.matchAll({ type: 'window' }).then((windowClients) => {
            // Check if there is already a window/tab open with the target URL
            for (let i = 0; i < windowClients.length; i++) {
                const client = windowClients[i];
                // If so, just focus it and navigate to root
                if (client.url && 'focus' in client) {
                    return client.focus().then(c => c.navigate('/'));
                }
            }
            // If not, open a new window
            if (self.clients.openWindow) {
                return self.clients.openWindow('/');
            }
        })
    );
});
