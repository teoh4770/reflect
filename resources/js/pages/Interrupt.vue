<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import InterruptController from "@/actions/App/Http/Controllers/InterruptController";
import axios from 'axios';

const prompt = ref({ id: null, body: 'Loading...' });
const entry = ref('');
const isRecording = ref(false);
const isTranscribing = ref(false);
const sessionId = ref(typeof window !== 'undefined' ? crypto.randomUUID() : '');
const stream = ref<MediaStream | null>(null);
const mediaRecorder = ref<MediaRecorder | null>(null);
const audioChunks = ref<Blob[]>([]);
const recordingDuration = ref(0);
let timerInterval: any = null;

onMounted(async () => {
    if (!sessionId.value) {
        sessionId.value = crypto.randomUUID();
    }

    const response = await fetch(InterruptController.index().url);
    prompt.value = await response.json();
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
        audioChunks.value = [];

        const mimeType = [
            'audio/webm;codecs=opus',
            'audio/webm',
            'audio/ogg;codecs=opus',
            'audio/mp4',
        ].find(type => MediaRecorder.isTypeSupported(type));

        mediaRecorder.value = new MediaRecorder(stream.value, mimeType ? { mimeType } : {});

        mediaRecorder.value.ondataavailable = (event) => {
            if (event.data.size > 0) {
                audioChunks.value.push(event.data);
            }
        };

        mediaRecorder.value.onstop = async () => {
            const audioBlob = new Blob(audioChunks.value, { type: mediaRecorder.value?.mimeType || 'audio/webm' });
            await processFullAudio(audioBlob);
        };

        mediaRecorder.value.start();
        isRecording.value = true;

        recordingDuration.value = 0;
        timerInterval = setInterval(() => {
            recordingDuration.value++;
        }, 1000);

    } catch (error) {
        console.error('Recording error:', error);
        alert('Could not access microphone.');
    }
};

const stopRecording = () => {
    if (mediaRecorder.value && isRecording.value) {
        mediaRecorder.value.stop();
        stream.value?.getTracks().forEach(track => track.stop());
        stream.value = null;
        isRecording.value = false;
        clearInterval(timerInterval);
    }
};

const processFullAudio = async (blob: Blob) => {
    isTranscribing.value = true;

    const formData = new FormData();
    const extension = blob.type.includes('mp4') ? 'm4a' : 'webm';
    formData.append('audio', blob, `full-recording.${extension}`);
    formData.append('session_id', sessionId.value);

    try {
        if (window.Echo) {
            window.Echo.channel(`transcription.${sessionId.value}`)
                .listen('.TranscriptionChunkProcessed', (e: { text: string }) => {
                    if (e.text) {
                        const newText = e.text.trim();
                        entry.value = entry.value ? `${entry.value.trim()} ${newText}` : newText;
                        isTranscribing.value = false;
                        window.Echo.leaveChannel(`transcription.${sessionId.value}`);
                    }
                });
        }

        await axios.post('/api/transcribe-chunk', formData);
    } catch (error) {
        console.error('Transcription failed:', error);
        isTranscribing.value = false;
    }
};

const formatTime = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const sendEntry = async () => {
    if (!entry.value.trim() || isRecording.value || isTranscribing.value) return;

    try {
        const response = await fetch(InterruptController.store().url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ prompt_id: prompt.value.id, body: entry.value }),
        });

        if (response.ok) {
            entry.value = '';
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
        <div class="max-w-2xl text-center space-y-8 mb-12">
            <h1 class="text-2xl md:text-4xl font-bold leading-tight text-zinc-100 transition-opacity duration-1000"
                :class="{ 'opacity-20': isRecording }">
                {{ prompt.body }}
            </h1>
        </div>

        <!-- Recording State Display -->
        <div v-if="isRecording" class="flex flex-col items-center space-y-4 mb-32 animate-in fade-in zoom-in duration-500">
            <div @click="stopRecording" class="w-20 h-20 rounded-full bg-red-500/10 border border-red-500/50 flex items-center justify-center relative">
                <div class="absolute inset-0 rounded-full bg-red-500 animate-ping opacity-20"></div>
                <div class="w-6 h-6 rounded-sm bg-red-500"></div>
            </div>
            <span class="text-3xl font-bold text-red-500 tabular-nums tracking-tighter">{{ formatTime(recordingDuration) }}</span>
            <span class="text-[10px] uppercase tracking-[0.4em] text-zinc-500 animate-pulse">Capturing Thought</span>
        </div>

        <!-- Transcription Loading -->
        <div v-if="isTranscribing" class="flex flex-col items-center space-y-4 mb-32">
            <div class="flex space-x-2">
                <div class="w-2 h-2 bg-zinc-500 rounded-full animate-bounce"></div>
                <div class="w-2 h-2 bg-zinc-500 rounded-full animate-bounce [animation-delay:0.2s]"></div>
                <div class="w-2 h-2 bg-zinc-500 rounded-full animate-bounce [animation-delay:0.4s]"></div>
            </div>
            <span class="text-[10px] uppercase tracking-[0.4em] text-zinc-500">Processing Audio</span>
        </div>

        <!-- Input Area -->
        <div class="fixed bottom-12 w-full max-w-xl px-4 transition-all duration-700"
             :class="{ 'opacity-0 translate-y-8 pointer-events-none': isRecording }">

            <div class="relative flex flex-col bg-zinc-900 rounded-lg border border-zinc-800 focus-within:border-zinc-600 transition-all duration-500 overflow-hidden"
                 :class="{ 'ring-1 ring-zinc-700': isTranscribing }">

                <textarea
                    v-model="entry"
                    :disabled="isTranscribing"
                    class="w-full bg-transparent border-none focus:ring-0 p-4 pr-24 resize-none min-h-[140px] max-h-64 text-zinc-200 placeholder-zinc-600 transition-opacity"
                    :class="{ 'opacity-50': isTranscribing }"
                    :placeholder="isTranscribing ? 'Awaiting transcription...' : 'Speak your truth...'"
                ></textarea>

                <div class="absolute right-2 bottom-2 flex items-center space-x-1">
                    <button
                        @click="toggleRecording"
                        class="p-2 rounded-md transition-all duration-300 text-zinc-500 hover:text-zinc-200"
                        title="Start Recording"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                    </button>

                    <button
                        @click="sendEntry"
                        class="p-2 text-zinc-500 hover:text-white transition-colors"
                        :disabled="!entry.trim() || isTranscribing"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="mt-4 text-center">
                <p class="text-[9px] text-zinc-700 uppercase tracking-[0.3em]">
                    Record &bull; Review &bull; Commit
                </p>
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

.animate-in {
    animation: animate-in 0.5s ease-out forwards;
}

@keyframes animate-in {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>
