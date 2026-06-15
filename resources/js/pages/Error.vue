<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    status: number;
}>();

const title = computed(() => {
    return {
        503: '503: Service Unavailable',
        500: '500: Server Error',
        404: '404: Page Not Found',
        403: '403: Forbidden',
    }[props.status] || 'Error';
});

const description = computed(() => {
    return {
        503: 'Sorry, we are doing some maintenance. Please check back soon.',
        500: 'Whoops, something went wrong on our servers.',
        404: 'Sorry, the page you are looking for could not be found.',
        403: 'Sorry, you are forbidden from accessing this page.',
    }[props.status] || 'An unexpected error occurred.';
});
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-black text-white font-mono p-4">
        <div class="text-center max-w-md">
            <h1 class="text-4xl md:text-6xl font-bold mb-4 tracking-widest">{{ title }}</h1>
            <p class="text-zinc-400 mb-8">{{ description }}</p>
            <a href="/" class="text-xs uppercase tracking-widest hover:text-white transition-colors border border-zinc-700 px-4 py-2">
                Return Home
            </a>
        </div>
    </div>
</template>
