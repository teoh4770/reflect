# Reflect App

Reflect App is an installable **Progressive Web App (PWA)** designed to provide a native mobile app-like experience for daily reflections, brutal self-awareness, and intentional living. 

## Philosophy & Theme

The core philosophy of Reflect is heavily inspired by **Dan Koe's writings on self-mastery and changing your life starting from Day 1**. The app is built to serve as a digital accountability mirror.

Instead of passively journaling at the end of the day, Reflect uses **Scheduled Interrupts** to break you out of autopilot. It asks you to establish an *Identity Statement* (who you are striving to be) and randomly interrupts your day to force you to answer: *"Are you acting in alignment with your identity right now?"* 

It's a tool for pattern interruption, forcing radical awareness to stop bad habits and mindless scrolling before they derail your day.

## Features

- **Progressive Web App (PWA):** Fully installable on mobile devices (iOS/Android) and desktop directly from the browser.
- **Audio Journaling:** Capture your thoughts quickly using your device's native microphone.
- **AI Transcription:** Your audio is processed in the background and transcribed to text using OpenAI (via the `laravel/ai` package).
- **Scheduled Interrupts:** The app uses Firebase Cloud Messaging (FCM) to send you push notifications when a scheduled reflection window opens. Clicking the notification instantly brings the app to the foreground.
- **Queue-based Processing:** Audio chunk transcriptions are safely offloaded to Laravel queues, meaning your frontend never hangs waiting for an API response.
- **Weekly AI Summary:** Every Sunday, the app runs an automated AI pipeline that analyzes all your entries from the week, grades your alignment with your Identity Statement, and provides brutal, actionable feedback for the week ahead.

## Tech Stack

- **Backend:** Laravel 11, PHP 8.3+
- **Frontend:** Vue 3, Inertia.js, Tailwind CSS
- **AI Processing:** `laravel/ai` (OpenAI driver by default)
- **Notifications:** Firebase Cloud Messaging (FCM Web)

## Prerequisites

Before setting up the project locally, ensure you have:
- PHP 8.3+ and Composer
- Node.js and NPM
- An OpenAI API Key (for transcription)
- A Firebase Project (for FCM push notifications)

## Installation & Setup

1. **Clone the repository and install dependencies:**
   ```bash
   composer install
   npm install
   ```

2. **Set up the environment file:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configure your Database & API Keys in `.env`:**
   ```env
   # Database Configuration
   DB_CONNECTION=sqlite

   # AI Transcription
   OPENAI_API_KEY="your-openai-api-key"

   # Firebase Push Notifications
   FIREBASE_CREDENTIALS="/path/to/firebase_credentials.json"
   ```

4. **Run migrations:**
   ```bash
   php artisan migrate
   ```

## Running the Application Locally

Because this application relies on queues and background processes, you'll need to run a few services simultaneously. 

The easiest way is to use the built-in dev script which utilizes `concurrently`:
```bash
npm run dev
```

Alternatively, you can run them manually in separate terminal tabs:
```bash
# 1. Run the local PHP server
php artisan serve

# 2. Compile frontend assets (Vite)
npm run dev

# 3. Start the Queue Worker (Crucial for Audio Transcription)
php artisan queue:work
```

## Testing

The project uses PHPUnit for backend testing. To run the test suite:

```bash
php artisan test
```

## How It Works

- **Transcription Flow:** When you record an audio chunk, the frontend posts the data to `/api/transcribe-chunk`. The controller immediately dispatches the `ProcessTranscriptionChunk` job to the queue and responds to the frontend. The worker transcribes the audio and broadcasts a real-time `TranscriptionChunkProcessed` event back to your browser using Laravel Reverb/Echo.
- **Push Notifications:** The `app:trigger-interrupts` Artisan command runs periodically. If the current time matches a `ScheduleSlot`, it pushes a Web FCM Notification to your device. Clicking the notification launches the PWA natively.
- **Weekly Synthesis:** A scheduled command runs every Sunday to compile all your transcribed reflections for the week. It feeds them into an AI model (OpenAI) along with your stated Identity, evaluates your discipline, and generates a structured summary that you review on Monday morning.
