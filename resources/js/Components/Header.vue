<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

// Get translations and auth from Inertia shared props, default translations to empty object
const { translations = {}, auth } = usePage().props;

const languages = [
    { code: 'en', name: translations['English'] || 'English', flag: 'https://flagcdn.com/w20/gb.png' },
    { code: 'es', name: translations['Español'] || 'Español', flag: 'https://flagcdn.com/w20/es.png' },
    { code: 'it', name: translations['Italiano'] || 'Italiano', flag: 'https://flagcdn.com/w20/it.png' },
    { code: 'ro', name: translations['Română'] || 'Română', flag: 'https://flagcdn.com/w20/ro.png' },
    { code: 'ru', name: translations['Русский'] || 'Русский', flag: 'https://flagcdn.com/w20/ru.png' },
    { code: 'de', name: translations['Deutsch'] || 'Deutsch', flag: 'https://flagcdn.com/w20/de.png' },
    { code: 'bn', name: translations['বাংলা'] || 'বাংলা', flag: 'https://flagcdn.com/w20/bd.png' },
    { code: 'hi', name: translations['हिन्दी'] || 'हिन्दी', flag: 'https://flagcdn.com/w20/in.png' },
];

const dropdownOpen = ref(false);

const changeLanguage = (lang) => {
    router.post(route('locale.change'), { locale: lang }, { preserveScroll: true });
    dropdownOpen.value = false;
};

// Helper for translation
const t = (key) => translations[key] || key;
</script>
<template>
    <header class="bg-white border-b shadow-sm">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-row justify-between items-center h-16">
                <!-- Logo -->
                <Link :href="auth.user ? route('dashboard') : route('welcome')">
                    <ApplicationLogo class="block h-9 w-auto fill-current text-purple-600" />
                </Link>

                <!-- Language Dropdown -->
                <div class="relative">
                    <button
                        @click="dropdownOpen = !dropdownOpen"
                        class="flex items-center px-3 py-2 rounded-lg text-gray-800 font-semibold transition-all duration-300 hover:bg-gray-100 hover:scale-105"
                    >
                        <img :src="languages[0].flag" alt="Lang" class="h-4 w-auto mr-2 rounded-sm" />
                        <span class="text-sm">{{ t('Language') }}</span>
                        <svg class="ml-1 h-4 w-4 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div
                        v-if="dropdownOpen"
                        class="absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-50"
                    >
                        <ul>
                            <li v-for="lang in languages" :key="lang.code">
                                <button
                                    @click="changeLanguage(lang.code)"
                                    class="flex items-center w-full px-3 py-2 text-sm text-gray-800 font-medium hover:bg-gray-100 transition-all duration-300"
                                >
                                    <img :src="lang.flag" :alt="lang.name" class="h-4 w-auto mr-2 rounded-sm" />
                                    <span>{{ lang.name }}</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>