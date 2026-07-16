<script setup lang="ts">
import { ref } from "vue";
import FeedbackController from "@/actions/App/Http/Controllers/FeedbackController";

const isOpen = ref(false);
const body = ref('');
const errors = ref<Record<string, string[]>>({});
const isSubmitting = ref(false);

const submitFeedback = async () => {
    isSubmitting.value = true;
    errors.value = {};

    try {
        const response = await fetch(FeedbackController.store().url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                body: body.value,
                is_public: false
            })
        });

        if (response.ok) {
            body.value = '';
            isOpen.value = false;

            alert('Thank you for your feedback!');
        } else if (response.status === 422) {
            const data = await response.json();
            errors.value = data.errors || {};
        } else {
            const data = await response.json().catch(() => ({}));
            console.error('Failed to save feedback:', data);
        }
    } catch (error) {
        // This catch block only triggers on severe network errors (e.g. CORS, offline)
        console.error('Network or parsing error:', error);
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <!-- trigger button -->
    <button
        @click="isOpen = true"
        class="bg-zinc-800 hover:bg-zinc-700 text-white px-6 py-2 rounded-md font-mono text-xs uppercase tracking-wider transition-colors"
        aria-label="Submit Feedback"
    >
        Provide Feedback
    </button>

    <!-- Modal dialog -->
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm px-4"
        @click.self="isOpen = false"
    >
        <!-- Modal Content -->
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-300">

            <!-- Modal Header -->
            <div class="p-4 border-b border-[#3E3E3A] flex justify-between items-center bg-[#0a0a0a]/50">
                <h2 class="text-sm font-bold uppercase tracking-widest text-zinc-300 font-mono">Send Feedback</h2>
                <button @click="isOpen = false" class="text-zinc-500 hover:text-zinc-300 focus:outline-none transition-colors">
                    <!-- Close (X) Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10
  4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>

            <!-- Modal Body (Form) -->
            <form @submit.prevent="submitFeedback" class="p-6">
                <div class="mb-6">
                    <label for="feedback-body" class="sr-only">Your Feedback</label>
                    <textarea
                        id="feedback-body"
                        v-model="body"
                        class="w-full bg-[#0a0a0a] border border-[#3E3E3A] rounded-md p-4 text-white focus:outline-none focus:border-zinc-500 min-h-[140px] resize-y font-mono text-sm placeholder-zinc-600 transition-colors"
                        placeholder="What's on your mind? Found a bug? Have an idea?"
                        required
                    ></textarea>
                    <!-- Display validation errors for body if they exist -->
                    <div v-if="errors.body" class="text-red-500 font-mono text-xs mt-2 uppercase tracking-wide">
                        {{ errors.body[0] }}
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end items-center gap-4">
                    <button
                        type="button"
                        @click="isOpen = false"
                        class="px-4 py-2 text-xs uppercase tracking-widest text-zinc-500 hover:text-zinc-300 font-mono transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="isSubmitting"
                        class="bg-white text-black hover:bg-zinc-200 px-6 py-2 rounded-md font-mono text-xs font-bold uppercase tracking-wider transition-colors disabled:opacity-50"
                    >
                        {{ isSubmitting ? 'Sending...' : 'Commit' }}
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>
