<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Navigation from '@/components/Navigation.vue';
import axios from 'axios';

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
const loading = ref(true);
const generating = ref(false);
const selectedSummary = ref<Summary | null>(null);

const fetchSummaries = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/summary');
        summaries.value = response.data.summaries;
        isSunday.value = response.data.is_sunday;
    } catch (error) {
        console.error('Failed to fetch summaries', error);
    } finally {
        loading.value = false;
    }
};

const finishWeek = async () => {
    generating.value = true;
    try {
        const response = await axios.post('/api/summary');
        summaries.value.unshift(response.data);
        selectedSummary.value = response.data;
    } catch (error: any) {
        alert(error.response?.data?.error || 'Failed to generate summary');
    } finally {
        generating.value = false;
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

    <div class="min-h-screen bg-neutral-950 text-neutral-200 p-8 font-serif">
        <div class="max-w-4xl mx-auto space-y-12">

            <header class="flex justify-between items-end border-b border-white/10 pb-8">
                <div>
                    <h1 class="text-4xl font-bold tracking-widest uppercase">The Armory</h1>
                    <p class="text-neutral-500 mt-2 italic">Your collection of past weekly summaries.</p>
                </div>

                <button
                    @click="finishWeek"
                    :disabled="!isSunday || generating"
                    class="px-8 py-3 border-2 transition-all font-bold uppercase tracking-widest disabled:opacity-20 disabled:cursor-not-allowed"
                    :class="[isSunday ? 'border-blue-500 text-blue-400 hover:bg-blue-500 hover:text-white shadow-[0_0_20px_rgba(59,130,246,0.3)]' : 'border-neutral-800 text-neutral-600']"
                >
                    <span v-if="generating">Analyzing your week...</span>
                    <span v-else-if="isSunday">Finish the Week</span>
                    <span v-else>Locked until Sunday</span>
                </button>
            </header>

            <div v-if="loading" class="text-center py-20 opacity-30 animate-pulse">
                Loading summaries...
            </div>

            <div v-else-if="summaries.length === 0" class="text-center py-20 border-2 border-dashed border-white/5 rounded-2xl">
                <p class="text-neutral-500 italic">No summaries yet. Finish your first week on Sunday to start your collection.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    v-for="summary in summaries"
                    :key="summary.id"
                    @click="selectedSummary = summary"
                    class="group bg-neutral-900 border border-white/5 p-6 rounded-xl cursor-pointer hover:border-blue-500/50 transition-all hover:shadow-[0_0_30px_rgba(59,130,246,0.1)]"
                >
                    <div class="flex justify-between items-start mb-4">
                        <span class="text-[10px] font-mono uppercase tracking-tighter text-blue-400 font-bold">Week {{ summary.id }}</span>
                        <span class="text-xs text-neutral-600">{{ formatDate(summary.week_end) }}</span>
                    </div>
                    <h3 class="text-lg font-bold text-neutral-300 group-hover:text-blue-300 transition-colors truncate">
                        {{ summary.identity_snapshot || 'My Identity' }}
                    </h3>
                    <p class="text-sm text-neutral-500 line-clamp-2 mt-2 font-sans italic">
                        {{ summary.content }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Overlay: The Scroll Reveal (Hybrid Approach) -->
        <div v-if="selectedSummary" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm" @click.self="selectedSummary = null">
            <div class="max-w-prose w-full bg-[#f4ead5] text-neutral-900 p-8 md:p-12 shadow-2xl relative overflow-y-auto max-h-[90vh] border-x-8 border-[#dcc69c] animate-in fade-in zoom-in duration-300">
                <button @click="selectedSummary = null" class="absolute top-4 right-4 text-black/30 hover:text-black">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>

                <div class="space-y-8">
                    <header class="text-center border-b border-black/10 pb-8">
                        <h1 class="text-3xl font-bold uppercase tracking-[0.2em] mb-2">Weekly Summary</h1>
                        <p class="text-xs italic text-neutral-600">Week of {{ formatDate(selectedSummary.week_start) }} — {{ formatDate(selectedSummary.week_end) }}</p>
                    </header>

                    <section class="space-y-4">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-500">Your Identity</h2>
                        <blockquote class="text-xl font-medium border-l-4 border-black/20 pl-6 py-2">
                            "{{ selectedSummary.identity_snapshot }}"
                        </blockquote>
                    </section>

                    <section class="prose prose-neutral leading-relaxed text-lg first-letter:text-4xl first-letter:font-bold first-letter:mr-3 first-letter:float-left pt-4">
                        {{ selectedSummary.content }}
                    </section>

                    <footer class="text-center pt-12">
                        <button @click="selectedSummary = null" class="px-8 py-3 border-2 border-neutral-900 font-bold uppercase tracking-widest hover:bg-neutral-900 hover:text-white transition-all">
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
