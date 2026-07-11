<script setup lang="ts">
import {router, Head} from '@inertiajs/vue3';
import {ref, computed, onMounted} from 'vue';
import Navigation from "@/components/Navigation.vue";

interface Prompt {
    id: number;
    body: string;
    ritual: string;
    tooltip?: string;
}

interface Entry {
    id: number;
    prompt_id: number;
    body: string;
}

const props = defineProps<{
    prompts: Prompt[];
    entries: Record<number, Entry>;
}>();

// Setup form data
const forms = ref<Record<number, string>>({});
props.prompts.forEach(p => {
    forms.value[p.id] = props.entries[p.id] ? props.entries[p.id].body : '';
});

// Save function
const saving = ref<Record<number, boolean>>({});
const saved = ref<Record<number, boolean>>({});
const warnings = ref<Record<number, string>>({});

const storeEntry = (prompt: Prompt) => {
    const body = forms.value[prompt.id];

    if (!body.trim()) {
        warnings.value[prompt.id] = 'Answer cannot be empty.';
        setTimeout(() => {
            warnings.value[prompt.id] = '';
        }, 3000);

        return;
    }

    saving.value[prompt.id] = true;
    saved.value[prompt.id] = false;
    warnings.value[prompt.id] = '';

    router.post('/visions', {prompt_id: prompt.id, body}, {
        preserveScroll: true,
        onSuccess: () => {
            saved.value[prompt.id] = true;
            setTimeout(() => {
                saved.value[prompt.id] = false;
            }, 3000);
        },
        onFinish: () => {
            saving.value[prompt.id] = false;
        }
    });
};

const updateEntry = (prompt: Prompt) => {
    const body = forms.value[prompt.id];

    if (!body.trim()) {
        forms.value[prompt.id] = props.entries[prompt.id].body;
        warnings.value[prompt.id] = 'Answer cannot be empty. Reverted to original.';
        setTimeout(() => {
            warnings.value[prompt.id] = '';
        }, 3000);

        return;
    }

    saving.value[prompt.id] = true;
    saved.value[prompt.id] = false;
    warnings.value[prompt.id] = '';

    router.post(`/visions/${props.entries[prompt.id].id}`, {body}, {
        preserveScroll: true,
        onSuccess: () => {
            saved.value[prompt.id] = true;
            setTimeout(() => {
                saved.value[prompt.id] = false;
            }, 3000);
        },
        onFinish: () => {
            saving.value[prompt.id] = false;
        }
    });
};

const painPrompts = computed(() => props.prompts.filter(p => p.ritual === 'pain'));
const antiVisionPrompts = computed(() => props.prompts.filter(p => p.ritual === 'anti-vision'));
const visionPrompts = computed(() => props.prompts.filter(p => p.ritual === 'vision'));

const isPainCompleted = computed(() => painPrompts.value.length > 0 && painPrompts.value.every(p => props.entries[p.id] && props.entries[p.id].body.trim() !== ''));
const isAntiVisionCompleted = computed(() => antiVisionPrompts.value.length > 0 && antiVisionPrompts.value.every(p => props.entries[p.id] && props.entries[p.id].body.trim() !== ''));
const isVisionCompleted = computed(() => visionPrompts.value.length > 0 && visionPrompts.value.every(p => props.entries[p.id] && props.entries[p.id].body.trim() !== ''));

const totalCompleted = computed(() => Object.values(props.entries).filter(e => e.body.trim() !== '').length);
const totalPrompts = computed(() => props.prompts.length);
const progressPercent = computed(() => totalPrompts.value > 0 ? (totalCompleted.value / totalPrompts.value) * 100 : 0);

const activeTab = ref<'pain' | 'anti-vision' | 'vision'>('pain');

const activeTooltip = ref<number | null>(null);
const toggleTooltip = (id: number) => {
    activeTooltip.value = activeTooltip.value === id ? null : id;
};

onMounted(() => {
    if (isPainCompleted.value && !isAntiVisionCompleted.value) {
        activeTab.value = 'anti-vision';
    } else if (isPainCompleted.value && isAntiVisionCompleted.value) {
        activeTab.value = 'vision';
    } else {
        activeTab.value = 'pain';
    }
});

const activePrompts = computed(() => {
    if (activeTab.value === 'pain') {
        return painPrompts.value;
    }

    if (activeTab.value === 'anti-vision') {
        return antiVisionPrompts.value;
    }

    return visionPrompts.value;
});
</script>

<template>
    <Head title="Vision"/>
    <Navigation/>

    <div class="min-h-screen bg-[#0a0a0a] text-[#EDEDEC] pt-8 md:pt-24 pb-28 md:pb-6 px-4 md:px-6 font-sans overflow-x-hidden">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-xl md:text-2xl font-bold mb-6 md:mb-8 uppercase tracking-widest text-zinc-300 font-mono">
                Design Your Future
            </h1>

            <div class="mb-8">
                <div class="flex justify-between mb-2">
                    <small class="text-zinc-500 font-mono uppercase">PROGRESS</small>
                    <small class="text-zinc-500 font-mono uppercase">{{ totalCompleted }} / {{ totalPrompts }}
                        COMPLETED</small>
                </div>
                <div class="h-2 bg-zinc-800 rounded-full overflow-hidden">
                    <div class="h-full bg-zinc-200 transition-all duration-300"
                         :style="{ width: progressPercent + '%' }"></div>
                </div>
            </div>

            <div class="flex flex-wrap gap-4 md:gap-6 border-b border-zinc-800 pb-2 mb-6">
                <button
                    @click="activeTab = 'pain'"
                    :class="['flex items-center gap-2 pb-2 -mb-2 border-b-2 transition-colors', activeTab === 'pain' ? 'border-zinc-200 text-zinc-200 font-bold' : 'border-transparent text-zinc-500 hover:text-zinc-300']"
                >
                    <span v-if="isPainCompleted">✅</span>
                    Pain
                </button>

                <button
                    @click="isPainCompleted ? activeTab = 'anti-vision' : null"
                    :class="['flex items-center gap-2 pb-2 -mb-2 border-b-2 transition-colors', activeTab === 'anti-vision' ? 'border-zinc-200 text-zinc-200 font-bold' : 'border-transparent text-zinc-500', !isPainCompleted ? 'opacity-50 cursor-not-allowed' : 'hover:text-zinc-300']"
                    :disabled="!isPainCompleted"
                >
                    <span v-if="isAntiVisionCompleted">✅</span>
                    <span v-else-if="!isPainCompleted">🔒</span>
                    Anti-Vision
                </button>

                <button
                    @click="isAntiVisionCompleted ? activeTab = 'vision' : null"
                    :class="['flex items-center gap-2 pb-2 -mb-2 border-b-2 transition-colors', activeTab === 'vision' ? 'border-zinc-200 text-zinc-200 font-bold' : 'border-transparent text-zinc-500', !isAntiVisionCompleted ? 'opacity-50 cursor-not-allowed' : 'hover:text-zinc-300']"
                    :disabled="!isAntiVisionCompleted"
                >
                    <span v-if="isVisionCompleted">✅</span>
                    <span v-else-if="!isAntiVisionCompleted">🔒</span>
                    Vision
                </button>
            </div>

            <div class="flex flex-col gap-6">
                <template v-for="prompt in activePrompts" :key="prompt.id">
                    <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-6 flex flex-col gap-4 relative">
                        <div class="flex gap-2">
                            <h3 class="text-lg text-zinc-200 font-medium">{{ prompt.body }}</h3>

                            <div v-if="prompt.tooltip" class="relative group inline-flex items-center self-start mt-1">
                                <div
                                    @click="toggleTooltip(prompt.id)"
                                    class="w-4 h-4 rounded-full border border-zinc-500 flex items-center justify-center text-[10px] text-zinc-500 cursor-pointer md:cursor-help font-bold">
                                    ?
                                </div>
                                <div
                                    :class="[
                                        'absolute bottom-full right-0 md:left-1/2 md:-translate-x-1/2 mb-2 w-[calc(100vw-3rem)] max-w-xs md:w-64 p-3 bg-zinc-800 text-sm text-zinc-300 rounded shadow-lg transition-all z-10 pointer-events-none',
                                        activeTooltip === prompt.id ? 'opacity-100 visible' : 'opacity-0 invisible md:group-hover:opacity-100 md:group-hover:visible'
                                    ]">
                                    {{ prompt.tooltip }}
                                </div>
                            </div>
                        </div>

                        <textarea
                            v-model="forms[prompt.id]"
                            class="w-full h-24 bg-transparent border border-zinc-700 rounded-md p-3 text-zinc-200 focus:outline-none focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500 transition-all resize-none"
                            placeholder="Your answer..."
                        ></textarea>

                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <small v-if="saved[prompt.id]" class="text-green-500 font-mono">SAVED</small>
                                <small v-if="warnings[prompt.id]" class="text-red-400 font-mono">{{
                                        warnings[prompt.id]
                                    }}</small>
                            </div>
                            <button
                                v-if="!props.entries[prompt.id]"
                                @click="storeEntry(prompt)"
                                :disabled="saving[prompt.id]"
                                class="bg-zinc-200 hover:bg-zinc-100 text-zinc-900 px-4 py-2 rounded text-sm font-medium transition-colors disabled:opacity-50"
                            >
                                {{ saving[prompt.id] ? 'Saving...' : 'Save' }}
                            </button>
                            <button
                                v-else
                                @click="updateEntry(prompt)"
                                :disabled="saving[prompt.id]"
                                class="bg-zinc-200 hover:bg-zinc-100 text-zinc-900 px-4 py-2 rounded text-sm font-medium transition-colors disabled:opacity-50"
                            >
                                {{ saving[prompt.id] ? 'Updating...' : 'Update' }}
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
