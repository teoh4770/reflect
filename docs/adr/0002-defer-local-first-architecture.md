# Defer Local-first Architecture

We are explicitly deferring the implementation of a Local-first Architecture (running SQLite in the browser and syncing with the server). 

Instead, we are maintaining a server-driven application approach and using a simple offline fallback page when the user loses network connectivity. This prevents adding significant data-synchronization complexity during the initial PWA migration. We will re-evaluate local data syncing in a future iteration only if offline editing becomes a strict requirement.
