<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import InterruptController from "@/actions/App/Http/Controllers/InterruptController";
import axios from 'axios';

const prompt = ref({ id: null, body: 'Loading...' });
const entry = ref('');
const isRecording = ref(false);
const sessionId = ref(typeof window !== 'undefined' ? crypto.randomUUID() : '');
const stream = ref<MediaStream | null>(null);

onMounted(async () => {
    if (!sessionId.value) {
        sessionId.value = crypto.randomUUID();
    }

    // Fetch initial random prompt
    const response = await fetch(InterruptController.index().url);
    prompt.value = await response.json();

    // Listen for transcription chunks
    if (window.Echo) {
        console.log(`Subscribing to channel: transcription.${sessionId.value}`);
        window.Echo.channel(`transcription.${sessionId.value}`)
            .listen('.TranscriptionChunkProcessed', (e: { text: string }) => {
                console.log('Transcription event received:', e);
                if (e.text) {
                    const newText = e.text.trim();
                    if (newText) {
                        entry.value = entry.value ? `${entry.value.trim()} ${newText}` : newText;
                    }
                }
            });
    } else {
        console.error('Laravel Echo is not initialized on window');
    }
});

onUnmounted(() => {
    stopRecording();
    if (window.Echo) {
        window.Echo.leaveChannel(`transcription.${sessionId.value}`);
    }
});

const toggleRecording = async () => {
    if (isRecording.value) {
        stopRecording();
    } else {
        await startRecording();
    }
};

const startRecording = async () => {
    try {
        stream.value = await navigator.mediaDevices.getUserMedia({ audio: true });
        isRecording.value = true;
        recordNextChunk();
    } catch (error) {
        console.error('Failed to start recording:', error);
        alert('Could not access microphone. Please check permissions.');
    }
};

const stopRecording = () => {
    isRecording.value = false;
    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop());
        stream.value = null;
    }
};

const getSupportedMimeType = () => {
    const types = [
        'audio/webm;codecs=opus',
        'audio/webm',
        'audio/ogg;codecs=opus',
        'audio/mp4',
    ];
    return types.find(type => MediaRecorder.isTypeSupported(type)) || '';
};

const recordNextChunk = () => {
    if (!isRecording.value || !stream.value) return;

    const mimeType = getSupportedMimeType();
    const recorder = new MediaRecorder(stream.value, mimeType ? { mimeType } : {});
    const chunks: Blob[] = [];

    recorder.ondataavailable = (e) => {
        if (e.data.size > 0) chunks.push(e.data);
    };

    recorder.onstop = async () => {
        if (chunks.length > 0) {
            const blob = new Blob(chunks, { type: recorder.mimeType || 'audio/webm' });
            sendChunk(blob); // Don't await here, send it in the background
        }
    };

    recorder.start();
    
    // Start the next recorder slightly BEFORE this one stops to ensure no gaps
    setTimeout(() => {
        if (isRecording.value) {
            recordNextChunk();
        }
    }, 2000); // 2-second chunks for low latency

    // Stop this recorder after a tiny overlap
    setTimeout(() => {
        if (recorder.state === 'recording') {
            recorder.stop();
        }
    }, 2100); 
};

const sendChunk = async (blob: Blob) => {
    const extension = blob.type.includes('mp4') ? 'm4a' : 'webm';
    const formData = new FormData();
    formData.append('audio', blob, `chunk.${extension}`);
    formData.append('session_id', sessionId.value);

    try {
        await axios.post('/api/transcribe-chunk', formData);
    } catch (error) {
        console.error('Failed to send audio chunk:', error);
    }
};

const sendEntry = async () => {
    if (!entry.value.trim()) return;

    try {
        const response = await fetch(InterruptController.store().url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                prompt_id: prompt.value.id,
                body: entry.value,
            }),
        });

        if (response.ok) {
            entry.value = '';
            // Fetch the next random prompt to keep the ritual going
            const nextResponse = await fetch(InterruptController.index().url);
            prompt.value = await nextResponse.json();
        }
    } catch (error) {
        console.error('Failed to save entry:', error);
    }
};
</script>

<template>
    <Head title="Interrupt" />

    <div class="min-h-screen bg-black text-white flex flex-col items-center justify-center p-6 font-mono">
        <!-- Challenger Prompt -->
        <div class="max-w-2xl text-center space-y-8">
            <h1 class="text-2xl md:text-4xl font-bold leading-tight animate-pulse">
                {{ prompt.body }}
            </h1>
        </div>

        <!-- Input Area -->
        <div class="fixed bottom-12 w-full max-w-xl px-4">
            <div class="relative flex items-center bg-zinc-900 rounded-lg border border-zinc-800 focus-within:border-zinc-600 transition-colors">
                <textarea
                    v-model="entry"
                    class="w-full bg-transparent border-none focus:ring-0 p-4 pr-24 resize-none min-h-[56px] max-h-32 text-zinc-200 placeholder-zinc-500"
                    placeholder="Speak your truth..."
                    @keydown.enter.prevent="sendEntry"
                ></textarea>

                <div class="absolute right-2 flex items-center space-x-1">
                    <button
                        @click="toggleRecording"
                        class="p-2 rounded-md transition-all duration-300"
                        :class="isRecording ? 'text-red-500 bg-red-500/10' : 'text-zinc-400 hover:text-zinc-200'"
                        :title="isRecording ? 'Stop Recording' : 'Start Voice Input'"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" :class="{ 'animate-pulse': isRecording }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                    </button>

                    <button
                        @click="sendEntry"
                        class="p-2 text-zinc-400 hover:text-white transition-colors"
                        :disabled="!entry.trim()"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Minimal custom scrollbar for textarea */
textarea::-webkit-scrollbar {
    width: 4px;
}
textarea::-webkit-scrollbar-thumb {
    background: #27272a;
    border-radius: 2px;
}
</style>
