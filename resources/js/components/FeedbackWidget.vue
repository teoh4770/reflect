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
    <!-- floating trigger button -->
    <button
        @click="isOpen = true"
        class="fixed bottom-6 right-6 z-40 bg-blue-600 text-white rounded-full p-4 shadow-lg hover:bg-blue-700 transition-colors focus:outline-none"
        aria-label="Submit Feedback"
    >
        <!-- Help Icon (?) -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0
  11-18 0 9 9 0 0118 0z"/>
        </svg>
    </button>

    <!-- Modal dialog   -->
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        @click.self="isOpen = false"
    >
        <!-- Modal Content -->
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 overflow-hidden">

            <!-- Modal Header -->
            <div class="p-4 border-b flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">Send Feedback</h2>
                <button @click="isOpen = false" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                    <!-- Close (X) Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10
  4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>

            <!-- Modal Body (Form) -->
            <form @submit.prevent="submitFeedback" class="p-4">
                <div class="mb-4">
                    <label for="feedback-body" class="sr-only">Your Feedback</label>
                    <textarea
                        id="feedback-body"
                        v-model="body"
                        rows="4"
                        class="w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500 resize-none outline-none"
                        placeholder="What's on your mind? Found a bug? Have an idea?"
                        required
                    ></textarea>
                    <!-- Display validation errors for body if they exist -->
                    <div v-if="errors.body" class="text-red-500 text-sm mt-1">
                        {{ errors.body[0] }}
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        @click="isOpen = false"
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="isSubmitting"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none disabled:opacity-50"
                    >
                        {{ isSubmitting ? 'Sending...' : 'Send' }}
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>
