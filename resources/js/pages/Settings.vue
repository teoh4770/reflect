<script setup lang="ts">
import {Head, usePage} from '@inertiajs/vue3';
import axios from 'axios';
import {ref} from 'vue';
import AuthController from "@/actions/App/Http/Controllers/AuthController";
import Navigation from '@/components/Navigation.vue';

const props = defineProps<{
    identity_statement: string | null;
    schedule_slots: Array<{ id: number; time: string }>;
}>();

const statement = ref(props.identity_statement || '');
const saving = ref(false);
const saved = ref(false);

const save = async () => {
    saving.value = true;
    saved.value = false;

    try {
        await axios.post('/api/identity', {
            identity_statement: statement.value
        });
        saved.value = true;
        setTimeout(() => saved.value = false, 2000);
    } catch (e) {
        console.error(e);
    } finally {
        saving.value = false;
    }
};

const page = usePage();
const user = page.props.auth?.user as any;

const testingNotification = ref(false);
const testNotification = async () => {
    testingNotification.value = true;

    try {
        await axios.post('/api/test-notification');
        alert('Test notification sent! Check your device.');
    } catch (e: any) {
        // Log the actual response body from the server for better debugging
        console.error("Server Response Data:", e.response?.data);
        console.error(e);
        alert(`Failed to send test notification: ` + (e.response?.data?.message || e.response?.data?.error || e.message));
    } finally {
        testingNotification.value = false;
    }
};
</script>

<template>
    <Head title="Settings"/>
    <Navigation/>
    <div class="min-h-screen bg-[#0a0a0a] text-[#EDEDEC] pt-8 md:pt-24 pb-28 md:pb-6 px-4 md:px-6 font-sans">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-xl md:text-2xl font-bold mb-6 md:mb-8 uppercase tracking-widest text-zinc-300 font-mono">
                Settings</h1>

            <div class="bg-[#161615] rounded-lg border border-[#3E3E3A] p-4 md:p-6 shadow-sm">
                <h2 class="text-lg font-medium mb-2">Identity Statement</h2>
                <p class="text-sm text-zinc-400 mb-6">
                    Define who you are or who you are striving to be. This statement will be used by the system to
                    evaluate your weekly activities and hold you accountable.
                </p>

                <form @submit.prevent="save">
                    <textarea
                        v-model="statement"
                        class="w-full bg-[#0a0a0a] border border-[#3E3E3A] rounded-md p-4 text-white focus:outline-none focus:border-zinc-500 min-h-[120px] resize-y mb-4 font-mono text-sm"
                        placeholder="I am a disciplined individual who..."
                    ></textarea>

                    <div class="flex justify-end items-center gap-4">
                        <span v-if="saved" class="text-green-500 text-sm font-mono transition-opacity">Saved successfully</span>
                        <button
                            type="submit"
                            :disabled="saving"
                            class="bg-zinc-800 hover:bg-zinc-700 text-white px-6 py-2 rounded-md font-mono text-xs uppercase tracking-wider transition-colors disabled:opacity-50"
                        >
                            {{ saving ? 'Saving...' : 'Save Statement' }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-[#161615] rounded-lg border border-[#3E3E3A] p-4 md:p-6 shadow-sm mt-6">
                <h2 class="text-lg font-medium mb-2">Schedule Slots</h2>
                <p class="text-sm text-zinc-400 mb-4">
                    Current timeslots configured for interruptions. These are public and cannot be modified here.
                </p>

                <div v-if="props.schedule_slots && props.schedule_slots.length > 0" class="flex flex-wrap gap-2">
                    <span
                        v-for="slot in props.schedule_slots"
                        :key="slot.id"
                        class="bg-zinc-800 text-zinc-300 px-3 py-1 rounded-md font-mono text-sm border border-[#3E3E3A]"
                    >
                        {{ slot.time }}
                    </span>
                </div>
                <p v-else class="text-sm text-zinc-500 italic">No schedule slots configured.</p>
            </div>

            <div v-if="user?.email === 'ck@gmail.com'" class="bg-[#161615] rounded-lg border border-[#3E3E3A] p-4 md:p-6 shadow-sm mt-6">
                <h2 class="text-lg font-medium mb-2 text-yellow-500">Developer Tools</h2>
                <p class="text-sm text-zinc-400 mb-4">
                    Only visible to ck@gmail.com
                </p>
                <button
                    @click="testNotification"
                    :disabled="testingNotification"
                    class="bg-yellow-600 hover:bg-yellow-500 text-[#161615] font-bold px-6 py-2 rounded-md font-mono text-xs uppercase tracking-wider transition-colors disabled:opacity-50"
                >
                    {{ testingNotification ? 'Sending...' : 'Test Notification Ping' }}
                </button>
            </div>

            <form class="mt-4" method="post" :action="AuthController.logout().url">
                <button
                    class="bg-zinc-800 hover:bg-zinc-700 text-white px-6 py-2 rounded-md font-mono text-xs uppercase tracking-wider transition-colors disabled:opacity-50">
                    Logout
                </button>
            </form>
        </div>
    </div>
</template>
