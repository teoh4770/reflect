<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted } from 'vue';
import Navigation from '@/components/Navigation.vue';

interface Summary {
    id: number;
    week_start: string;
    week_end: string;
    content: string;
    identity_snapshot: string;
    artifact_type: string;
    created_at: string;
}

const summaries = ref<Summary[]>([]);
const isSunday = ref(false);
const alreadyFinished = ref(false);
const loading = ref(true);
const selectedSummary = ref<Summary | null>(null);

const fetchSummaries = async () => {
    loading.value = true;

    try {
        const response = await axios.get('/api/summary');
        summaries.value = response.data.summaries;
        isSunday.value = response.data.is_sunday;
        alreadyFinished.value = response.data.already_finished;

        console.log(isSunday.value)
        console.log(summaries.value)
    } catch (error) {
        console.error('Failed to fetch summaries', error);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchSummaries);

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
};
</script>

<template>
    <Head title="The Armory" />
    <Navigation />

    <div class="min-h-screen pt-8 pb-32 md:py-16 bg-neutral-950 text-neutral-200 px-4 md:px-8 font-serif">
        <div class="max-w-4xl mx-auto space-y-12">

            <header class="flex flex-col md:flex-row md:justify-between md:items-end border-b border-white/10 pb-8 gap-6">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold tracking-widest uppercase text-center md:text-left">The Armory</h1>
                    <p class="text-neutral-500 mt-2 italic text-center md:text-left text-sm md:text-base">Your collection of past weekly summaries.</p>
                </div>
            </header>

            <div v-if="loading" class="text-center py-20 opacity-30 animate-pulse">
                Loading summaries...
            </div>

            <div v-else-if="summaries.length === 0" class="text-center py-20 border-2 border-dashed border-white/5 rounded-2xl">
                <p class="text-neutral-500 italic">No summaries yet. Finish your first week on Sunday to start your collection.</p>
            </div>

            <div v-else class="relative border-l border-neutral-800 ml-3 md:ml-4 space-y-10 pb-8">
                <div
                    v-for="summary in summaries"
                    :key="summary.id"
                    @click="selectedSummary = summary"
                    class="relative pl-8 md:pl-10 group cursor-pointer"
                >
                    <!-- Timeline Dot -->
                    <div class="absolute -left-[5px] top-6 h-2.5 w-2.5 rounded-full bg-neutral-700 ring-4 ring-neutral-950 group-hover:bg-blue-500 group-hover:ring-blue-500/30 transition-all"></div>

                    <div class="bg-neutral-900 border border-white/5 p-6 rounded-xl hover:border-blue-500/50 transition-all hover:shadow-[0_0_30px_rgba(59,130,246,0.1)]">
                        <div class="flex items-start mb-4">
                            <span class="text-[10px] font-mono uppercase tracking-tighter text-blue-400 font-bold">{{ formatDate(summary.week_end) }}</span>
                        </div>
                        <h3 class="text-lg font-bold text-neutral-300 group-hover:text-blue-300 transition-colors truncate">
                            {{ summary.identity_snapshot || 'My Identity' }}
                        </h3>
                        <p class="text-sm text-neutral-500 line-clamp-2 mt-2 font-sans italic whitespace-pre-wrap">
                            {{ summary.content }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overlay: The Scroll Reveal (Hybrid Approach) -->
        <div v-if="selectedSummary" class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-8 bg-black/90 backdrop-blur-sm" @click.self="selectedSummary = null">
            <div class="max-w-prose w-full bg-[#f4ead5] text-neutral-900 p-6 md:p-12 shadow-2xl relative overflow-y-auto max-h-[85vh] border-x-4 md:border-x-8 border-[#dcc69c] animate-in fade-in zoom-in duration-300">
                <button @click="selectedSummary = null" class="absolute top-2 right-2 md:top-4 md:right-4 text-black/30 hover:text-black p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>

                <div class="space-y-6 md:space-y-8">
                    <header class="text-center border-b border-black/10 pb-6 md:pb-8">
                        <h1 class="text-2xl md:text-3xl font-bold uppercase tracking-[0.2em] mb-2">Weekly Summary</h1>
                        <p class="text-[10px] md:text-xs italic text-neutral-600">Week of {{ formatDate(selectedSummary.week_start) }} — {{ formatDate(selectedSummary.week_end) }}</p>
                    </header>

                    <section class="space-y-4">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-500">Your Identity</h2>
                        <blockquote class="text-xl font-medium border-l-4 border-black/20 pl-6 py-2">
                            "{{ selectedSummary.identity_snapshot }}"
                        </blockquote>
                    </section>

                    <section class="prose prose-neutral leading-relaxed text-base md:text-lg pt-4 whitespace-pre-wrap">
                        {{ selectedSummary.content }}
                    </section>

                    <footer class="text-center pt-8 md:pt-12">
                        <button @click="selectedSummary = null" class="w-full md:w-auto px-8 py-3 border-2 border-neutral-900 font-bold uppercase tracking-widest hover:bg-neutral-900 hover:text-white transition-all text-sm">
                            Back to Armory
                        </button>
                    </footer>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    line-clamp: 2;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

@keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
@keyframes zoom-in { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.animate-in { animation-duration: 300ms; animation-fill-mode: both; }
.fade-in { animation-name: fade-in; }
.zoom-in { animation-name: zoom-in; }
</style>
