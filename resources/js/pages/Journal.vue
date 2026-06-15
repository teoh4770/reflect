<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import Navigation from '@/components/Navigation.vue'

defineProps<{
  entriesByWeek: Record<string, any[]>
}>()

const formatDate = (dateString: string) => {
  const options: Intl.DateTimeFormatOptions = { weekday: 'long', month: 'short', day: 'numeric' };

  return new Date(dateString).toLocaleDateString('en-US', options);
}

const formatTime = (dateString: string) => {
  const options: Intl.DateTimeFormatOptions = { hour: 'numeric', minute: 'numeric', hour12: true };

  return new Date(dateString).toLocaleTimeString('en-US', options);
}
</script>

<template>
  <Head title="Journal" />
  <Navigation />

  <div class="min-h-screen bg-black text-white font-mono pt-8 md:pt-24 pb-28 md:pb-12 px-4 md:px-6">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <header class="mb-8 md:mb-12 flex justify-between items-end">
        <div>
          <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-zinc-100">Your Journal</h1>
          <p class="text-zinc-500 mt-2 text-xs md:text-sm uppercase tracking-widest">A timeline of your reflections</p>
        </div>
      </header>

      <!-- Timeline / Entries -->
      <div class="space-y-12 md:space-y-16">
        <div v-for="(entries, week) in entriesByWeek" :key="week" class="relative">

          <!-- Week Header -->
          <div class="sticky top-4 md:top-20 z-10 bg-black/90 md:bg-black/80 backdrop-blur-md py-4 mb-4 md:mb-6 border-b border-zinc-800">
            <h2 class="text-[10px] font-bold text-zinc-500 uppercase tracking-[0.3em]">
              Week of {{ new Date(week).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}
            </h2>
          </div>

          <!-- Entries Grid -->
          <div class="grid gap-6">
            <div
              v-for="entry in entries"
              :key="entry.id"
              class="group bg-zinc-900 rounded-lg p-6 md:p-8 transition-all duration-300 border border-zinc-800 hover:border-zinc-600"
            >
              <div class="flex flex-col md:flex-row md:items-start justify-between mb-4 gap-4">
                <div>
                  <div class="flex items-center gap-3 mb-4">
                    <span class="text-[10px] text-zinc-500 tracking-[0.2em] uppercase">
                      {{ formatDate(entry.created_at) }}
                    </span>
                    <span class="w-1 h-1 rounded-full bg-zinc-700"></span>
                    <span class="text-[10px] text-zinc-500 tracking-[0.2em] uppercase">
                      {{ formatTime(entry.created_at) }}
                    </span>
                  </div>
                  <h3 class="text-xl font-bold text-zinc-100 leading-snug">
                    {{ entry.prompt?.body || 'Reflection' }}
                  </h3>
                </div>
              </div>

              <div class="prose prose-invert max-w-none text-zinc-400 leading-relaxed text-sm font-sans">
                <p>{{ entry.body }}</p>
              </div>

              <!-- Tags / Meta (Placeholder for future) -->
              <div class="mt-8 flex items-center gap-2 pt-6 border-t border-zinc-800/50">
                <span class="px-3 py-1 bg-zinc-800 text-zinc-400 rounded text-[10px] uppercase tracking-widest">
                  #reflection
                </span>
                <span v-if="entry.metadata && Object.keys(entry.metadata).length > 0" class="px-3 py-1 bg-zinc-800 text-zinc-400 rounded text-[10px] uppercase tracking-widest">
                  #meta
                </span>
              </div>
            </div>
          </div>

        </div>

        <div v-if="Object.keys(entriesByWeek).length === 0" class="text-center py-24 bg-zinc-900/50 rounded-lg border border-zinc-800">
          <p class="text-zinc-500 text-sm uppercase tracking-widest">Your journal is empty. No entries yet.</p>
        </div>
      </div>
    </div>
  </div>
</template>
