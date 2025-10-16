<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

// Get reactive props from Inertia
const page = usePage();
const translations = computed(() => page.props.translations || {});
const auth = computed(() => page.props.auth || {});
const locale = computed(() => page.props.locale || 'en');

const languages = [
    { code: 'en', name: 'English', flag: 'https://cdn.jsdelivr.net/npm/flag-icons@6.11.0/flags/4x3/gb.svg' },
    { code: 'es', name: 'Español', flag: 'https://cdn.jsdelivr.net/npm/flag-icons@6.11.0/flags/4x3/es.svg' },
    { code: 'it', name: 'Italiano', flag: 'https://cdn.jsdelivr.net/npm/flag-icons@6.11.0/flags/4x3/it.svg' },
    { code: 'ro', name: 'Română', flag: 'https://cdn.jsdelivr.net/npm/flag-icons@6.11.0/flags/4x3/ro.svg' },
    { code: 'ru', name: 'Русский', flag: 'https://cdn.jsdelivr.net/npm/flag-icons@6.11.0/flags/4x3/ru.svg' },
    { code: 'de', name: 'Deutsch', flag: 'https://cdn.jsdelivr.net/npm/flag-icons@6.11.0/flags/4x3/de.svg' },
    { code: 'bn', name: 'বাংলা', flag: 'https://cdn.jsdelivr.net/npm/flag-icons@6.11.0/flags/4x3/bd.svg' },
    { code: 'hi', name: 'हिन्दी', flag: 'https://cdn.jsdelivr.net/npm/flag-icons@6.11.0/flags/4x3/in.svg' },
];

// The currently selected language object
const currentLanguage = computed(() => {
    return languages.find(lang => lang.code === locale.value) || languages[0];
});

const dropdownOpen = ref(false);

const refreshCSRFToken = async () => {
    const res = await fetch(route('csrf-token'), {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    });
    const data = await res.json();
    const token = data.token;
    document.head.querySelector('meta[name="csrf-token"]').content = token;
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
};

const changeLanguage = async (lang) => {
    try {
        await refreshCSRFToken();
        await router.post(route('locale.change'), { locale: lang }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                dropdownOpen.value = false;
            },
        });
    } catch (error) {
        if (error.response && error.response.status === 419) {
            window.location.reload();
        } else {
            console.error('Failed to change language:', error);
        }
    }
};;

// Helper for translation, accessing the value of the computed prop
const t = (key) => translations.value[key] || key;
</script>

<template>
    <header class="bg-white/10 backdrop-blur-md border-b border-white/20 shadow-lg relative z-50">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-row justify-between items-center h-16">
                <!-- Logo -->
                <Link :href="auth.user ? route('dashboard') : route('welcome')">
                    <ApplicationLogo class="block h-9 w-auto fill-current text-white" />
                </Link>

                <!-- Language Dropdown -->
                <div class="relative">
                    <button
                        @click="dropdownOpen = !dropdownOpen"
                        class="flex items-center px-2 py-1 rounded-lg bg-gradient-to-r from-cyan-400/30 via-blue-500/20 to-indigo-600/30 text-xs text-white font-medium shadow-sm border border-cyan-300/30 focus:outline-none"
                    >
                        <img :src="currentLanguage.flag" alt="Lang" class="w-4 h-4 mr-1 rounded-full" />
                        <span class="text-xs">{{ t('Language') }}</span>
                        <svg class="ml-1 w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <transition name="fade">
                        <div
                            v-if="dropdownOpen"
                            class="absolute left-0 mt-2 w-32 sm:w-40 z-50 bg-gradient-to-br from-cyan-400/90 via-blue-500/80 to-indigo-600/90 rounded-xl shadow-xl border border-cyan-300/40 py-1 px-1"
                            style="min-width:7rem;"
                        >
                            <div v-for="lang in languages" :key="lang.code" @click="changeLanguage(lang.code)" class="flex items-center gap-2 px-2 py-1 rounded-lg cursor-pointer hover:bg-cyan-400/30 transition-all text-xs text-white font-medium">
                                <img :src="lang.flag" :alt="lang.name" class="w-3 h-3 rounded-full" />
                                <span class="text-xs">{{ lang.name }}</span>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
        </div>
    </header>
</template>