<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import InterruptController from "@/actions/App/Http/Controllers/InterruptController";

const prompt = ref({ id: null, body: 'Loading...' });
const entry = ref('');
const isRecording = ref(false);

onMounted(async () => {
    // Fetch initial random prompt
    const response = await fetch(InterruptController.index().url);
    prompt.value = await response.json();
});

const toggleRecording = () => {
    // TODO: Implement real-time transcription via Whisper/Groq or Web Speech API in the next iteration.
    alert('Voice-to-text transcription will be implemented in the next iteration.');
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
                        class="p-2 rounded-md transition-colors text-zinc-400 hover:text-zinc-200"
                        title="Voice Input (Coming Soon)"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
