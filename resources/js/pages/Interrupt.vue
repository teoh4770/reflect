<script setup lang="ts">
import {Head} from '@inertiajs/vue3';
import axios from 'axios';
import {ref, onMounted, onUnmounted} from 'vue';
import InterruptController from "@/actions/App/Http/Controllers/InterruptController";
import EntryController from "@/actions/App/Http/Controllers/EntryController";
import Navigation from '@/components/Navigation.vue';

const prompt = ref({id: null, body: 'Loading...'});
const activeSlot = ref({id: null, time: ''});
const nextSlotAt = ref<string | null>(null);
const status = ref<'loading' | 'active' | 'locked'>('loading');

const entry = ref('');
const isRecording = ref(false);
const isTranscribing = ref(false);
const isConfirming = ref(false);
const sessionId = ref(typeof window !== 'undefined' ? crypto.randomUUID() : '');
const stream = ref<MediaStream | null>(null);
const mediaRecorder = ref<MediaRecorder | null>(null);
const audioChunks = ref<Blob[]>([]);
const recordingDuration = ref(0);
const countdown = ref('');
let timerInterval: any = null;
let countdownInterval: any = null;

const fetchState = async () => {
    status.value = 'loading';

    try {
        const response = await fetch(InterruptController.index().url);
        const data = await response.json();

        if (data.status === 'active') {
            prompt.value = data.prompt;
            activeSlot.value = data.slot;
            status.value = 'active';
        } else {
            nextSlotAt.value = data.next_slot_at;
            status.value = 'locked';
            startCountdown();
        }
    } catch (error) {
        console.error('Failed to fetch state:', error);
    }
};

const startCountdown = () => {
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }

    const update = () => {
        console.log(nextSlotAt.value)
        if (!nextSlotAt.value) {
            return;
        }

        const target = new Date(nextSlotAt.value.replace(/-/g, '/')).getTime();
        const now = new Date().getTime();
        const diff = target - now;

        if (diff <= 0) {
            clearInterval(countdownInterval);
            fetchState();

            return;
        }

        const h = Math.floor(diff / (1000 * 60 * 60));
        const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const s = Math.floor((diff % (1000 * 60)) / 1000);
        countdown.value = `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    };

    update();
    countdownInterval = setInterval(update, 1000);
};

onMounted(async () => {
    if (!sessionId.value) {
        sessionId.value = crypto.randomUUID();
    }

    await fetchState();
});

onUnmounted(() => {
    stopRecording();

    if (countdownInterval) {
        clearInterval(countdownInterval);
    }

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
        stream.value = await navigator.mediaDevices.getUserMedia({audio: true});
        audioChunks.value = [];

        const mimeType = [
            'audio/webm;codecs=opus',
            'audio/webm',
            'audio/mp4',
        ].find(type => MediaRecorder.isTypeSupported(type));

        mediaRecorder.value = new MediaRecorder(stream.value, mimeType ? {mimeType} : {});

        mediaRecorder.value.ondataavailable = (event) => {
            if (event.data.size > 0) {
                audioChunks.value.push(event.data);
            }
        };

        mediaRecorder.value.onstop = async () => {
            const audioBlob = new Blob(audioChunks.value, {type: mediaRecorder.value?.mimeType || 'audio/webm'});
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

const confirmEntry = () => {
    if (!entry.value.trim() || isRecording.value || isTranscribing.value) {
        return;
    }

    isConfirming.value = true;
};

const cancelConfirmation = () => {
    isConfirming.value = false;
};

const submitEntry = async () => {
    if (!entry.value.trim() || isRecording.value || isTranscribing.value) {
        return;
    }

    try {
        const response = await fetch(EntryController.store().url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify({
                prompt_id: prompt.value.id,
                body: entry.value,
                slot_id: activeSlot.value.id
            }),
        });

        if (response.ok) {
            entry.value = '';
            isConfirming.value = false;
            await fetchState();
        }
    } catch (error) {
        console.error('Failed to save entry:', error);
    }
};
</script>

<template>
    <Head title="Interrupt"/>
    <Navigation/>

    <div
        class="min-h-[100dvh] bg-black text-white flex flex-col items-center justify-center px-4 pb-28 md:pb-6 pt-8 md:pt-20 font-mono relative">

        <!-- LOADING STATE -->
        <div v-if="status === 'loading'" class="flex items-center space-x-2 text-zinc-500">
            <div class="w-1 h-1 bg-zinc-500 rounded-full animate-ping"></div>
            <span class="text-[10px] uppercase tracking-widest">Initialising...</span>
        </div>

        <!-- LOCKED STATE -->
        <div v-else-if="status === 'locked'"
             class="flex flex-col items-center space-y-8 md:space-y-12 animate-in fade-in duration-1000">
            <div class="text-center space-y-2">
                <div class="text-zinc-600 text-[10px] uppercase tracking-[0.4em]">Ritual Closed</div>
                <h1 class="text-5xl md:text-6xl font-bold tabular-nums tracking-tighter text-zinc-300">
                    {{ countdown }}
                </h1>
            </div>

            <div class="max-w-xs text-center">
                <p class="text-zinc-500 text-xs leading-relaxed italic">
                    Take a breath. Return when the next reflection window opens.
                </p>
            </div>

            <div class="h-px w-12 bg-zinc-800"></div>
        </div>

        <!-- ACTIVE STATE -->
        <template v-else-if="status === 'active'">
            <!-- Challenger Prompt -->
            <div class="max-w-2xl text-center space-y-8 mb-8 md:mb-12">
                <h1 class="text-2xl md:text-4xl font-bold leading-tight text-zinc-100 transition-opacity duration-1000 px-2"
                    :class="{ 'opacity-20': isRecording }">
                    {{ prompt.body }}
                </h1>
            </div>

            <!-- Recording State Display -->
            <div v-if="isRecording"
                 class="flex flex-col items-center space-y-4 mb-32 animate-in fade-in zoom-in duration-500">
                <div @click="stopRecording"
                     class="w-20 h-20 rounded-full bg-red-500/10 border border-red-500/50 flex items-center justify-center relative cursor-pointer group">
                    <div
                        class="absolute inset-0 rounded-full bg-red-500 animate-ping opacity-20 group-hover:opacity-40"></div>
                    <div class="w-6 h-6 rounded-sm bg-red-500 group-hover:scale-110 transition-transform"></div>
                </div>
                <span class="text-3xl font-bold text-red-500 tabular-nums tracking-tighter">{{
                        formatTime(recordingDuration)
                    }}</span>
                <span
                    class="text-[10px] uppercase tracking-[0.4em] text-zinc-500 animate-pulse">Capturing Thought</span>
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
            <div class="fixed bottom-24 md:bottom-12 w-full max-w-xl px-4 transition-all duration-700 z-10"
                 :class="{ 'opacity-0 translate-y-8 pointer-events-none': isRecording }">

                <div v-if="!isConfirming"
                     class="relative flex flex-col bg-zinc-900 rounded-lg border border-zinc-800 focus-within:border-zinc-600 transition-all duration-500 overflow-hidden"
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
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                            </svg>
                        </button>

                        <button
                            @click="confirmEntry"
                            class="p-2 text-zinc-500 hover:text-white transition-colors"
                            :disabled="!entry.trim() || isTranscribing"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div v-else
                     class="bg-zinc-900 rounded-lg border border-zinc-800 p-6 space-y-6 animate-in fade-in zoom-in duration-300">
                    <div>
                        <h3 class="text-zinc-100 font-bold mb-2">Review Your Entry</h3>
                        <p class="text-zinc-400 text-sm leading-relaxed whitespace-pre-wrap">{{ entry }}</p>
                    </div>

                    <div class="bg-red-500/10 border border-red-500/20 rounded p-4">
                        <p class="text-red-400 text-xs uppercase tracking-widest text-center">
                            Warning: You can only submit this once. Ensure your entry is correct.
                        </p>
                    </div>

                    <div class="flex items-center justify-end space-x-4 pt-2">
                        <button
                            @click="cancelConfirmation"
                            class="px-4 py-2 text-xs uppercase tracking-widest text-zinc-500 hover:text-zinc-300 transition-colors"
                        >
                            Edit
                        </button>
                        <button
                            @click="submitEntry"
                            class="px-6 py-2 bg-white text-black text-xs font-bold uppercase tracking-widest rounded hover:bg-zinc-200 transition-colors"
                        >
                            Commit
                        </button>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <p class="text-[9px] text-zinc-700 uppercase tracking-[0.3em]">
                        Record &bull; Review &bull; Commit
                    </p>
                </div>
            </div>
        </template>
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
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>
