<script setup lang="ts">
import { ref, watch, onUnmounted } from 'vue';
import axios from 'axios';

const props = withDefaults(defineProps<{
    modelValue: string;
    placeholder?: string;
    variant?: 'inline' | 'interrupt';
    disabled?: boolean;
}>(), {
    variant: 'inline',
    disabled: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'submit'): void;
    (e: 'recording-started'): void;
    (e: 'recording-stopped'): void;
    (e: 'transcribing-started'): void;
    (e: 'transcribing-finished'): void;
    (e: 'duration-update', value: number): void;
    (e: 'error', value: string): void;
}>();

const isRecording = ref(false);
const isTranscribing = ref(false);
const sessionId = ref(typeof window !== 'undefined' ? crypto.randomUUID() : '');
const stream = ref<MediaStream | null>(null);
const mediaRecorder = ref<MediaRecorder | null>(null);
const audioChunks = ref<Blob[]>([]);
const recordingDuration = ref(0);
const errorMessage = ref<string | null>(null);
let timerInterval: any = null;

const localValue = ref(props.modelValue);

watch(() => props.modelValue, (newVal) => {
    localValue.value = newVal;
});

watch(localValue, (newVal) => {
    emit('update:modelValue', newVal);
});

onUnmounted(() => {
    stopRecording();
    if (typeof window !== 'undefined' && window.Echo) {
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
        errorMessage.value = null;
        stream.value = await navigator.mediaDevices.getUserMedia({ audio: true });
        audioChunks.value = [];

        const mimeType = [
            'audio/webm;codecs=opus',
            'audio/webm',
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
        emit('recording-started');

        recordingDuration.value = 0;
        emit('duration-update', recordingDuration.value);
        
        timerInterval = setInterval(() => {
            recordingDuration.value++;
            emit('duration-update', recordingDuration.value);
        }, 1000);

    } catch (error) {
        console.error('Recording error:', error);
        showError('Could not access microphone.');
        emit('error', 'Could not access microphone.');
    }
};

const stopRecording = () => {
    if (mediaRecorder.value && isRecording.value) {
        mediaRecorder.value.stop();
        stream.value?.getTracks().forEach(track => track.stop());
        stream.value = null;
        isRecording.value = false;
        clearInterval(timerInterval);
        emit('recording-stopped');
    }
};

const processFullAudio = async (blob: Blob) => {
    isTranscribing.value = true;
    emit('transcribing-started');

    const formData = new FormData();
    const extension = blob.type.includes('mp4') ? 'm4a' : 'webm';
    formData.append('audio', blob, `full-recording.${extension}`);
    formData.append('session_id', sessionId.value);

    try {
        if (typeof window !== 'undefined' && window.Echo) {
            window.Echo.channel(`transcription.${sessionId.value}`)
                .listen('.TranscriptionProcessed', (e: { text: string }) => {
                    if (e.text) {
                        const newText = e.text.trim();
                        localValue.value = localValue.value ? `${localValue.value.trim()} ${newText}` : newText;
                        isTranscribing.value = false;
                        emit('transcribing-finished');
                        window.Echo.leaveChannel(`transcription.${sessionId.value}`);
                    }
                });
        }

        await axios.post('/api/transcribe-chunk', formData);
    } catch (error: any) {
        console.error('Transcription failed:', error);
        const msg = error.response?.data?.reason || error.message || 'Transcription failed';
        showError(msg);
        emit('error', msg);
        isTranscribing.value = false;
        emit('transcribing-finished');
    }
};

const showError = (msg: string) => {
    errorMessage.value = msg;
    setTimeout(() => {
        errorMessage.value = null;
    }, 5000);
};

const formatTime = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

defineExpose({
    startRecording,
    stopRecording,
    toggleRecording,
    isRecording,
    isTranscribing
});
</script>

<template>
    <!-- INTERRUPT VARIANT: Fixed positioned input at the bottom, floating recording indicators in the center of the page -->
    <template v-if="variant === 'interrupt'">
        <Teleport to="body">
            <!-- Recording State Display -->
            <div v-if="isRecording"
                 class="fixed inset-0 z-50 flex flex-col items-center justify-center space-y-4 pointer-events-none animate-in fade-in duration-500 bg-black/60 backdrop-blur-sm">
                <div @click="stopRecording"
                     class="w-20 h-20 rounded-full bg-red-500/10 border border-red-500/50 flex items-center justify-center relative cursor-pointer group pointer-events-auto">
                    <div
                        class="absolute inset-0 rounded-full bg-red-500 animate-ping opacity-20 group-hover:opacity-40"></div>
                    <div class="w-6 h-6 rounded-sm bg-red-500 group-hover:scale-110 transition-transform"></div>
                </div>
                <span class="text-3xl font-bold text-red-500 tabular-nums tracking-tighter drop-shadow-md">
                    {{ formatTime(recordingDuration) }}
                </span>
                <span
                    class="text-[10px] uppercase tracking-[0.4em] text-zinc-500 animate-pulse drop-shadow-md bg-black/50 px-2 py-1 rounded">Capturing Thought</span>
            </div>

            <!-- Transcription Loading -->
            <div v-if="isTranscribing" class="fixed inset-0 z-50 flex flex-col items-center justify-center space-y-4 pointer-events-none bg-black/60 backdrop-blur-sm animate-in fade-in duration-500">
                <div class="flex space-x-2 bg-black/50 p-4 rounded-full shadow-lg">
                    <div class="w-2 h-2 bg-zinc-500 rounded-full animate-bounce"></div>
                    <div class="w-2 h-2 bg-zinc-500 rounded-full animate-bounce [animation-delay:0.2s]"></div>
                    <div class="w-2 h-2 bg-zinc-500 rounded-full animate-bounce [animation-delay:0.4s]"></div>
                </div>
                <span class="text-[10px] uppercase tracking-[0.4em] text-zinc-500 bg-black/50 px-2 py-1 rounded">Processing Audio</span>
            </div>

            <!-- Error Message -->
            <div v-if="errorMessage" class="fixed inset-0 z-50 flex flex-col items-center justify-center pointer-events-none">
                <div class="bg-red-500/10 border border-red-500/30 px-6 py-3 rounded-lg text-center max-w-sm pointer-events-auto shadow-xl">
                    <span class="text-sm text-red-400 font-sans leading-snug">{{ errorMessage }}</span>
                </div>
            </div>
        </Teleport>

        <!-- Input Area (inline but handles its own recording state teleportation) -->
        <div
            class="relative flex flex-col bg-zinc-900 rounded-lg border border-zinc-800 focus-within:border-zinc-600 transition-all duration-500 overflow-hidden"
            :class="{ 'ring-1 ring-zinc-700': isTranscribing }"
        >
            <textarea
                v-model="localValue"
                :disabled="isTranscribing || disabled"
                class="w-full bg-transparent border-none focus:ring-0 p-4 pr-24 resize-none min-h-[140px] max-h-64 text-zinc-200 placeholder-zinc-600 transition-opacity font-mono"
                :class="{ 'opacity-50': isTranscribing }"
                :placeholder="isTranscribing ? 'Awaiting transcription...' : (placeholder || 'Speak your truth...')"
            ></textarea>

            <div class="absolute right-2 bottom-2 flex items-center space-x-1">
                <button
                    @click="toggleRecording"
                    class="p-2 rounded-md transition-all duration-300"
                    :class="isRecording ? 'text-red-500 hover:text-red-400' : 'text-zinc-500 hover:text-zinc-200'"
                    :title="isRecording ? 'Stop Recording' : 'Start Recording'"
                >
                    <svg v-if="!isRecording" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <rect x="6" y="6" width="12" height="12" rx="2" ry="2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <button
                    @click="emit('submit')"
                    class="p-2 text-zinc-500 hover:text-white transition-colors"
                    :disabled="!localValue.trim() || isTranscribing || disabled"
                    title="Submit"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>
    </template>

    <!-- INLINE VARIANT: Everything contained within the relative flow -->
    <template v-else>
        <div class="relative w-full flex flex-col transition-all duration-500 font-sans">
            <!-- Recording overlay -->
            <div v-if="isRecording" class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-zinc-900/95 backdrop-blur-sm rounded-lg border border-red-500/30 animate-in fade-in duration-300">
                <div @click="stopRecording" class="w-12 h-12 rounded-full bg-red-500/10 border border-red-500/50 flex items-center justify-center relative cursor-pointer group mb-2">
                    <div class="absolute inset-0 rounded-full bg-red-500 animate-ping opacity-20 group-hover:opacity-40"></div>
                    <div class="w-4 h-4 rounded-sm bg-red-500 group-hover:scale-110 transition-transform"></div>
                </div>
                <span class="text-xl font-bold text-red-500 tabular-nums tracking-tighter">
                    {{ formatTime(recordingDuration) }}
                </span>
            </div>

            <!-- Error message inline -->
            <div v-if="errorMessage" class="absolute inset-x-0 -top-12 z-20 flex justify-center pointer-events-none animate-in fade-in slide-in-from-bottom-2">
                <div class="bg-red-500/10 border border-red-500/30 px-4 py-2 rounded-lg text-center shadow-lg bg-zinc-900 pointer-events-auto">
                    <span class="text-xs text-red-400">{{ errorMessage }}</span>
                </div>
            </div>

            <div
                class="relative flex flex-col bg-zinc-900 rounded-lg border border-zinc-800 focus-within:border-zinc-600 transition-all duration-500 overflow-hidden"
                :class="{ 'ring-1 ring-zinc-700': isTranscribing }"
            >
                <textarea
                    v-model="localValue"
                    :disabled="isTranscribing || disabled"
                    class="w-full bg-transparent border-none focus:ring-0 p-4 pr-24 resize-none min-h-[140px] max-h-64 text-zinc-200 placeholder-zinc-600 transition-opacity font-mono"
                    :class="{ 'opacity-50': isTranscribing }"
                    :placeholder="isTranscribing ? 'Awaiting transcription...' : (placeholder || 'Speak your truth...')"
                ></textarea>

                <div class="absolute right-2 bottom-2 flex items-center space-x-1">
                    <div v-if="isTranscribing" class="flex items-center space-x-1 mr-2 bg-zinc-800 px-2 py-1 rounded-full">
                        <div class="w-1.5 h-1.5 bg-zinc-400 rounded-full animate-bounce"></div>
                        <div class="w-1.5 h-1.5 bg-zinc-400 rounded-full animate-bounce [animation-delay:0.2s]"></div>
                        <div class="w-1.5 h-1.5 bg-zinc-400 rounded-full animate-bounce [animation-delay:0.4s]"></div>
                    </div>
                    
                    <button
                        @click="toggleRecording"
                        class="p-2 rounded-md transition-all duration-300"
                        :class="isRecording ? 'text-red-500 hover:text-red-400' : 'text-zinc-500 hover:text-zinc-200'"
                        :title="isRecording ? 'Stop Recording' : 'Start Recording'"
                    >
                        <svg v-if="!isRecording" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                        </svg>
                    </button>
                    <button
                        @click="emit('submit')"
                        class="p-2 text-zinc-500 hover:text-white transition-colors"
                        :disabled="!localValue.trim() || isTranscribing || disabled"
                        title="Submit"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </template>
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
    animation: animate-in 0.3s ease-out forwards;
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
