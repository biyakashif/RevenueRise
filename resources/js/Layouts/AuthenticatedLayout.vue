<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import Header from '@/Components/Header.vue';
import { computed } from 'vue';

const page = usePage();
const translations = computed(() => page.props.translations || {});
const t = (key) => translations.value[key] || key;
</script>

<template>
    <div class="min-h-screen bg-gray-100 flex flex-col">
        <Header />

        <div class="flex flex-1">
            <!-- Sidebar (visible on sm and larger) -->
            <aside class="hidden sm:flex flex-col w-64 bg-white border-r shadow-sm">
                <nav class="flex flex-col p-4 space-y-2">
                    <Link
                        href="/dashboard"
                        class="flex items-center px-4 py-3 text-purple-600 hover:bg-gray-100 rounded-lg transition-all duration-300"
                    >
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0H9" />
                        </svg>
                        {{ t('Home') }}
                    </Link>
                    <Link
                        href="/my-orders"
                        class="flex items-center px-4 py-3 text-purple-600 hover:bg-gray-100 rounded-lg transition-all duration-300"
                    >
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        {{ t('Order') }}
                    </Link>
                    <Link
                        href="/deposit"
                        class="flex items-center px-4 py-3 text-purple-600 hover:bg-gray-100 rounded-lg transition-all duration-300"
                    >
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        {{ t('Deposit') }}
                    </Link>
                    <Link
                        href="/dashboard"
                        class="flex items-center px-4 py-3 text-purple-600 hover:bg-gray-100 rounded-lg transition-all duration-300"
                    >
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ t('Help') }}
                    </Link>
                    <Link
                        :href="route('profile.index')"
                        class="flex items-center px-4 py-3 text-purple-600 hover:bg-gray-100 rounded-lg transition-all duration-300"
                    >
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ t('Profile') }}
                    </Link>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="flex-grow p-4 sm:p-6 lg:p-8 pb-20 sm:pb-6">
                <div v-if="$slots.header" class="mb-6">
                    <slot name="header" />
                </div>
                <slot />
            </main>

            <!-- Bottom Bar (visible on small screens) -->
            <!-- added z-10 so modals with z-50 will overlay this bar -->
            <nav class="sm:hidden fixed bottom-0 left-0 right-0 bg-white border-t shadow-sm z-10">
                <div class="flex justify-around py-3">
                    <Link
                        href="/dashboard"
                        class="flex flex-col items-center text-purple-600 font-medium text-sm transition-all duration-300 hover:bg-gray-100 p-2 rounded-lg"
                    >
                        <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0H9" />
                        </svg>
                        {{ t('Home') }}
                    </Link>
                    <Link
                        href="/my-orders"
                        class="flex flex-col items-center text-purple-600 font-medium text-sm transition-all duration-300 hover:bg-gray-100 p-2 rounded-lg"
                    >
                        <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        {{ t('Order') }}
                    </Link>
                    <Link
                        href="/deposit"
                        class="flex flex-col items-center text-purple-600 font-medium text-sm transition-all duration-300 hover:bg-gray-100 p-2 rounded-lg"
                    >
                        <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        {{ t('Deposit') }}
                    </Link>
                    <Link
                        href="/dashboard"
                        class="flex flex-col items-center text-purple-600 font-medium text-sm transition-all duration-300 hover:bg-gray-100 p-2 rounded-lg"
                    >
                        <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ t('Help') }}
                    </Link>
                    <Link
                        :href="route('profile.index')"
                        class="flex flex-col items-center text-purple-600 font-medium text-sm transition-all duration-300 hover:bg-gray-100 p-2 rounded-lg"
                    >
                        <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ t('Profile') }}
                    </Link>
                </div>
            </nav>
        </div>
    </div>
</template>