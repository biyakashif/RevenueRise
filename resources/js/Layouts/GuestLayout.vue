<script setup>
import Header from '@/Components/Header.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const currentRoute = computed(() => page.url);
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900">
        <Header />
        <div class="flex flex-col justify-center items-center p-4 min-h-[calc(100vh-64px)]">
            <!-- Auth Navigation Header -->
            <div class="w-full max-w-md">
                <div class="bg-white/10 backdrop-blur-md rounded-t-2xl p-1 border border-white/20 border-b-0">
                    <div class="flex">
                        <Link 
                            :href="route('register')"
                            class="flex-1 text-center py-3 px-4 rounded-t-xl text-sm font-semibold transition-all duration-300"
                            :class="currentRoute.includes('/register') 
                                ? 'bg-white text-blue-900 shadow-lg' 
                                : 'text-white/80 hover:text-white hover:bg-white/10'"
                        >
                            Create Account
                        </Link>
                        <Link 
                            :href="route('login')"
                            class="flex-1 text-center py-3 px-4 rounded-t-xl text-sm font-semibold transition-all duration-300"
                            :class="currentRoute.includes('/login') 
                                ? 'bg-white text-blue-900 shadow-lg' 
                                : 'text-white/80 hover:text-white hover:bg-white/10'"
                        >
                            Login
                        </Link>
                    </div>
                </div>
            </div>
            <slot />
        </div>
    </div>
</template>