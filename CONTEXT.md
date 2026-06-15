# Reflect App Context

Glossary and domain language for the Reflect application.

## Architecture

**PWA (Progressive Web App)**:
A web application that uses modern web capabilities to provide an installable, app-like experience using standard web APIs (like Web Push and getUserMedia) without requiring native wrappers.
_Avoid_: Native app, Capacitor app, hybrid app.

**Service Worker**:
A script that the browser runs in the background to handle caching, push notifications, and offline capabilities for the PWA.
_Avoid_: Background process.

**FCM for Web**:
The mechanism used for sending push notifications to the PWA. We use the Firebase JS SDK to generate tokens, allowing us to reuse the existing `fcm_token` database column.
_Avoid_: Standard VAPID Web Push, Capacitor Push Notifications.

**Local-first Architecture**:
An architectural pattern where the browser's local storage (e.g. SQLite via WebAssembly) is the primary database, synchronizing with the backend server in the background. We are explicitly avoiding this for now in favor of a server-driven PWA.
_Avoid_: Offline-first, browser database.
