<script setup lang="ts">
import {Head} from '@inertiajs/vue3';
import axios from 'axios';
import {ref, onMounted, onUnmounted} from 'vue';
import EntryController from "@/actions/App/Http/Controllers/EntryController";
import InterruptController from "@/actions/App/Http/Controllers/InterruptController";
import Navigation from '@/components/Navigation.vue';
import TranscribeTextarea from '@/components/TranscribeTextarea.vue';

const prompt = ref({id: null, body: 'Loading...'});
const activeSlot = ref({id: null, time: ''});
const nextSlotAt = ref<string | null>(null);
const status = ref<'loading' | 'active' | 'locked'>('loading');
const errorMessage = ref<string | null>(null);
const visionCompleted = ref(true);
const visionAnsweredCount = ref(0);
const visionTotalCount = ref(0);
const showVisionPopup = ref(false);

const entry = ref('');
const isConfirming = ref(false);
const countdown = ref('');
let countdownInterval: any = null;
const transcribeTextareaRef = ref<InstanceType<typeof TranscribeTextarea> | null>(null);

const fetchState = async () => {
    status.value = 'loading';

    try {
        const response = await fetch(InterruptController.index().url);
        const data = await response.json();

        if (data.status === 'active') {
            prompt.value = data.prompt;
            activeSlot.value = data.slot;
            status.value = 'active';
            showVisionPopup.value = false;
        } else {
            nextSlotAt.value = data.next_slot_at;
            status.value = 'locked';

            if (data.vision_completed === false) {
                visionCompleted.value = false;
                visionAnsweredCount.value = data.vision_answered_count;
                visionTotalCount.value = data.vision_total_count;
                showVisionPopup.value = true;
            } else {
                showVisionPopup.value = false;
            }

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
    await fetchState();
});

onUnmounted(() => {
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
});



const formatTime = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;

    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const confirmEntry = () => {
    if (!entry.value.trim() || transcribeTextareaRef.value?.isRecording || transcribeTextareaRef.value?.isTranscribing) {
        return;
    }

    isConfirming.value = true;
};

const cancelConfirmation = () => {
    isConfirming.value = false;
};

const submitEntry = async () => {
    if (!entry.value.trim() || transcribeTextareaRef.value?.isRecording || transcribeTextareaRef.value?.isTranscribing) {
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
             class="flex flex-col items-center space-y-8 md:space-y-12 animate-in fade-in duration-1000 relative w-full h-full justify-center">

            <div class="text-center space-y-2 transition-opacity duration-500" :class="{ 'opacity-30': showVisionPopup }">
                <div class="text-zinc-600 text-[10px] uppercase tracking-[0.4em]">Ritual Closed</div>
                <h1 class="text-5xl md:text-6xl font-bold tabular-nums tracking-tighter text-zinc-300">
                    {{ countdown }}
                </h1>
            </div>

            <div class="max-w-xs text-center transition-opacity duration-500" :class="{ 'opacity-30': showVisionPopup }">
                <p class="text-zinc-500 text-xs leading-relaxed italic">
                    Take a breath. Return when the next reflection window opens.
                </p>
            </div>

            <div class="h-px w-12 bg-zinc-800 transition-opacity duration-500" :class="{ 'opacity-30': showVisionPopup }"></div>

            <!-- Vision Nudge Popup -->
            <div v-if="showVisionPopup" class="absolute inset-0 flex items-center justify-center p-4 z-50">
                <div class="bg-zinc-900 border border-zinc-700 w-full max-w-sm flex flex-col gap-4 text-center p-8 shadow-2xl animate-in fade-in zoom-in duration-300">
                    <h2 class="text-xl font-bold text-zinc-100">Design Your Future</h2>
                    <p class="text-zinc-400 text-sm leading-relaxed">
                        You've completed {{ visionAnsweredCount }} out of {{ visionTotalCount }} Vision questions. Taking time to clarify your anti-vision helps fuel your daily reflections.
                    </p>
                    <p class="text-zinc-300 text-sm">Can you answer more today?</p>
                    <div class="flex flex-col gap-2 mt-4">
                        <button
                            @click="$inertia.visit('/visions')"
                            class="bg-zinc-200 text-black w-full py-3 font-bold text-sm tracking-widest uppercase hover:bg-white transition-colors"
                        >
                            YES, LET'S DO IT
                        </button>
                        <button
                            @click="showVisionPopup = false"
                            class="bg-transparent text-zinc-500 w-full py-3 text-xs uppercase tracking-widest hover:text-zinc-300 transition-colors"
                        >
                            NOT RIGHT NOW
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACTIVE STATE -->
        <template v-else-if="status === 'active'">
            <!-- Challenger Prompt -->
            <div class="max-w-2xl text-center space-y-8 mb-8 md:mb-12">
                <h1 class="text-2xl md:text-4xl font-bold leading-tight text-zinc-100 transition-opacity duration-1000 px-2"
                    :class="{ 'opacity-20': transcribeTextareaRef?.isRecording }">
                    {{ prompt.body }}
                </h1>
            </div>

            <!-- Input Area -->
            <div class="fixed bottom-24 md:bottom-12 w-full max-w-xl px-4 transition-all duration-700 z-10"
                 :class="{ 'opacity-0 translate-y-8 pointer-events-none': transcribeTextareaRef?.isRecording }">

                <TranscribeTextarea
                    v-if="!isConfirming"
                    ref="transcribeTextareaRef"
                    v-model="entry"
                    variant="interrupt"
                    placeholder="Speak your truth..."
                    @submit="confirmEntry"
                />

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
