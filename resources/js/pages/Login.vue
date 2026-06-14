<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: 'test@example.com',
    password: 'password',
});

const submit = () => {
    form.post('/login');
};
</script>

<template>
    <Head title="Login" />
    <div class="min-h-screen flex items-center justify-center bg-[#FDFDFC] dark:bg-[#0a0a0a] p-6 text-[#1b1b18] dark:text-[#EDEDEC]">
        <div class="w-full max-w-md bg-white dark:bg-[#161615] p-8 rounded-lg shadow-sm border border-[#e3e3e0] dark:border-[#3E3E3A]">
            <h1 class="text-2xl font-medium mb-6 text-center">Login to Reflect</h1>
            
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium mb-1">Email</label>
                    <input 
                        id="email" 
                        v-model="form.email" 
                        type="email" 
                        class="w-full rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A] bg-transparent p-2 outline-none focus:ring-2 focus:ring-[#f53003] dark:focus:ring-[#FF4433]"
                        required autofocus 
                    />
                    <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium mb-1">Password</label>
                    <input 
                        id="password" 
                        v-model="form.password" 
                        type="password" 
                        class="w-full rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A] bg-transparent p-2 outline-none focus:ring-2 focus:ring-[#f53003] dark:focus:ring-[#FF4433]"
                        required 
                    />
                </div>

                <div class="pt-4">
                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="w-full bg-[#f53003] dark:bg-[#FF4433] text-white rounded-md py-2 px-4 font-medium hover:opacity-90 disabled:opacity-50 transition-opacity"
                    >
                        Sign In
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
