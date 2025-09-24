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
    { code: 'en', name: 'English', flag: 'https://flagcdn.com/w20/gb.png' },
    { code: 'es', name: 'Español', flag: 'https://flagcdn.com/w20/es.png' },
    { code: 'it', name: 'Italiano', flag: 'https://flagcdn.com/w20/it.png' },
    { code: 'ro', name: 'Română', flag: 'https://flagcdn.com/w20/ro.png' },
    { code: 'ru', name: 'Русский', flag: 'https://flagcdn.com/w20/ru.png' },
    { code: 'de', name: 'Deutsch', flag: 'https://flagcdn.com/w20/de.png' },
    { code: 'bn', name: 'বাংলা', flag: 'https://flagcdn.com/w20/bd.png' },
    { code: 'hi', name: 'हिन्दी', flag: 'https://flagcdn.com/w20/in.png' },
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
                        class="flex items-center px-3 py-2 rounded-lg text-white font-semibold transition-all duration-300 hover:bg-white/10 hover:scale-105"
                    >
                        <img :src="currentLanguage.flag" alt="Lang" class="h-4 w-auto mr-2 rounded-sm" />
                        <span class="text-sm">{{ t('Language') }}</span>
                        <svg class="ml-1 h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div
                        v-if="dropdownOpen"
                        class="absolute right-0 mt-2 w-40 bg-white/95 backdrop-blur-md border border-white/30 rounded-lg shadow-xl z-[9999]"
                    >
                        <ul>
                            <li v-for="lang in languages" :key="lang.code">
                                <button
                                    @click="changeLanguage(lang.code)"
                                    class="flex items-center w-full px-3 py-2 text-sm text-gray-800 font-medium hover:bg-blue-50 transition-all duration-300 first:rounded-t-lg last:rounded-b-lg"
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