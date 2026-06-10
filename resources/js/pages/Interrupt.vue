<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import InterruptController from "@/actions/App/Http/Controllers/InterruptController";
import axios from 'axios';

const prompt = ref({ id: null, body: 'Loading...' });
const entry = ref('');
const pendingText = ref('');
const isRecording = ref(false);
const isFinalizing = ref(false);
const sessionId = ref(typeof window !== 'undefined' ? crypto.randomUUID() : '');
const stream = ref<MediaStream | null>(null);
const audioContext = ref<AudioContext | null>(null);
const analyser = ref<AnalyserNode | null>(null);
const SILENCE_THRESHOLD = 0.01;

onMounted(async () => {
    if (!sessionId.value) {
        sessionId.value = crypto.randomUUID();
    }

    // Fetch initial random prompt
    const response = await fetch(InterruptController.index().url);
    prompt.value = await response.json();

    // Listen for transcription chunks
    if (window.Echo) {
        window.Echo.channel(`transcription.${sessionId.value}`)
            .listen('.TranscriptionChunkProcessed', (e: { text: string }) => {
                if (e.text) {
                    const newText = e.text.trim();
                    if (newText) {
                        // Immediate append - no delay
                        entry.value = entry.value ? `${entry.value.trim()} ${newText}` : newText;
                    }
                }
            });
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

        // Setup Web Audio API for silence detection
        audioContext.value = new (window.AudioContext || (window as any).webkitAudioContext)();
        const source = audioContext.value.createMediaStreamSource(stream.value);
        analyser.value = audioContext.value.createAnalyser();
        analyser.value.fftSize = 256;
        source.connect(analyser.value);

        isRecording.value = true;
        isFinalizing.value = false;
        recordNextChunk();
    } catch (error) {
        console.error('Failed to start recording:', error);
        alert('Could not access microphone. Please check permissions.');
    }
};

const stopRecording = () => {
    if (!isRecording.value) return;

    isRecording.value = false;
    isFinalizing.value = true;

    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop());
        stream.value = null;
    }

    if (audioContext.value) {
        audioContext.value.close();
        audioContext.value = null;
    }

    // Keep it locked for 3 seconds to catch the last few "late" events from the AI
    setTimeout(() => {
        isFinalizing.value = false;
    }, 3000);
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
    if (!isRecording.value || !stream.value || !analyser.value) return;

    const mimeType = getSupportedMimeType();
    const recorder = new MediaRecorder(stream.value, mimeType ? { mimeType } : {});
    const chunks: Blob[] = [];

    // Check for volume during the chunk
    let maxVolume = 0;
    const dataArray = new Uint8Array(analyser.value.frequencyBinCount);
    const volumeCheckInterval = setInterval(() => {
        if (!analyser.value) return;
        analyser.value.getByteTimeDomainData(dataArray);

        for (let i = 0; i < dataArray.length; i++) {
            const val = (dataArray[i] - 128) / 128;
            const volume = Math.abs(val);
            if (volume > maxVolume) maxVolume = volume;
        }
    }, 100);

    recorder.ondataavailable = (e) => {
        if (e.data.size > 0) chunks.push(e.data);
    };

    recorder.onstop = async () => {
        clearInterval(volumeCheckInterval);

        if (chunks.length > 0) {
            // Only send if max volume detected during this chunk was above threshold
            if (maxVolume > SILENCE_THRESHOLD) {
                const blob = new Blob(chunks, { type: recorder.mimeType || 'audio/webm' });
                sendChunk(blob);
            } else {
                console.log('Skipping silent chunk (max volume: ' + maxVolume.toFixed(4) + ')');
            }
        }
    };

    recorder.start();

    // 3-second chunks strike a good balance
    setTimeout(() => {
        if (isRecording.value) {
            recordNextChunk();
        }
    }, 3000);

    setTimeout(() => {
        if (recorder.state === 'recording') {
            recorder.stop();
        }
    }, 3100);
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
    if (!entry.value.trim() || isRecording.value) return;

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
        <div class="max-w-2xl text-center space-y-8 mb-12">
            <h1 class="text-2xl md:text-4xl font-bold leading-tight animate-pulse text-zinc-100">
                {{ prompt.body }}
            </h1>
        </div>

        <!-- Input Area -->
        <div class="fixed bottom-12 w-full max-w-xl px-4">
            <div class="relative flex flex-col bg-zinc-900 rounded-lg border border-zinc-800 focus-within:border-zinc-600 transition-all duration-500 overflow-hidden"
                 :class="{
                    'ring-1 ring-red-500/30 border-red-500/50': isRecording,
                    'ring-1 ring-amber-500/30 border-amber-500/50': isFinalizing && !isRecording
                 }">

                <textarea
                    v-model="entry"
                    :readonly="isRecording || isFinalizing"
                    class="w-full bg-transparent border-none focus:ring-0 p-4 pr-24 resize-none min-h-[120px] max-h-64 text-zinc-200 placeholder-zinc-500 transition-opacity"
                    :class="{ 'opacity-80 pointer-events-none': isRecording || isFinalizing }"
                    :placeholder="isRecording ? 'Listening...' : (isFinalizing ? 'Finishing up...' : 'Speak your truth...')"
                    @keydown.enter.prevent="sendEntry"
                ></textarea>

                <div class="absolute right-2 bottom-2 flex items-center space-x-1">
                    <button
                        @click="toggleRecording"
                        :disabled="isFinalizing && !isRecording"
                        class="p-2 rounded-md transition-all duration-300 disabled:opacity-30"
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
                        :disabled="!entry.trim() || isRecording || isFinalizing"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="mt-2 text-center h-4">
                <p v-if="isRecording" class="text-[10px] uppercase tracking-[0.2em] text-red-500/60 animate-pulse">
                    Recording Active &bull; Text Area Locked
                </p>
                <p v-else-if="isFinalizing" class="text-[10px] uppercase tracking-[0.2em] text-amber-500/60 animate-pulse">
                    Capturing remaining audio...
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
    animation: animate-in 0.7s ease-out forwards;
}

@keyframes animate-in {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
