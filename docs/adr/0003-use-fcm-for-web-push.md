# Use FCM for Web Push Notifications

We will use Firebase Cloud Messaging (FCM) for Web via the Firebase JS SDK to implement push notifications for the PWA, instead of standard VAPID-based Web Push.

Transitioning away from Capacitor meant losing the native push notification implementation. While standard Web Push is a viable alternative, it requires storing complex `PushSubscription` objects (endpoints and encryption keys) which necessitates database schema changes. By using FCM for Web, we can reuse the existing `fcm_token` column on the users table and minimize backend migration effort.
