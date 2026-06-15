# Use PWA instead of native wrappers

We are transitioning the application from a Capacitor-based native wrapper to a Progressive Web App (PWA) using `vite-plugin-pwa`.

This decision simplifies development and deployment by avoiding mobile-specific frameworks, build tools, and app store review processes. We will instead rely on standard web APIs (such as Web Push and `getUserMedia`) to provide device integration like notifications and microphone access, which are sufficient for our current requirements.
