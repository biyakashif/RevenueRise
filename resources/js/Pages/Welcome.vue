<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import FloatingChatIcon from '@/Components/FloatingChatIcon.vue';
import GuestChatInterface from '@/Components/GuestChatInterface.vue';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const page = usePage();
const translations = computed(() => page.props.translations || {});
const locale = computed(() => page.props.locale || 'en');
const t = (key) => translations.value[key] || key;

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
};

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const mobileNavOpen = ref(false);

// Guest chat functionality
const showGuestChat = ref(false);
const showChatForm = ref(true);
const guestForm = ref({ name: '', mobile_number: '', captcha: '' });
const captchaImage = ref('');
const guestSessionId = ref(null);
const showCaptchaError = ref('');

function scrollToSection(id) {
    const el = document.getElementById(id);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        mobileNavOpen.value = false;
    }
}

const generateCaptcha = () => {
    captchaImage.value = `/captcha?${Date.now()}`;
};

const openGuestChat = () => {
    showGuestChat.value = true;
    generateCaptcha();
};

const closeGuestChat = () => {
    showGuestChat.value = false;
    showChatForm.value = true;
    guestForm.value = { name: '', mobile_number: '', captcha: '' };
    guestSessionId.value = null;
};

const startGuestChat = async () => {
    try {
        const captchaResponse = await axios.post('/captcha/verify', {
            captcha: guestForm.value.captcha
        });
        
        if (!captchaResponse.data.valid) {
            showCaptchaError.value = t('Incorrect captcha. Please try again.');
            generateCaptcha();
            guestForm.value.captcha = '';
            return;
        }
        showCaptchaError.value = '';

        const response = await axios.post('/guest-chat/start', {
            name: guestForm.value.name,
            mobile_number: guestForm.value.mobile_number
        });
        
        if (response.data.blocked) {
            showCaptchaError.value = t('You have been blocked. You cannot send messages.');
            return;
        }
        
        guestSessionId.value = response.data.session_id;
        showChatForm.value = false;
    } catch (error) {
        alert(t('Error starting chat. Please try again.'));
    }
};


</script>

<template>
    <Head :title="t('Revenue Rise - Boost Your Product Sales')" />
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900">
        <!-- Header -->
        <header class="fixed top-0 left-0 w-full bg-white/5 backdrop-blur-2xl z-50 border-b border-white/10 shadow-2xl">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col">
                    <!-- Top Row -->
                    <div class="flex items-center justify-between h-16">
                        <!-- Logo -->
                        <Link :href="route('dashboard')" class="flex items-center group">
                            <div class="relative">
                                <ApplicationLogo class="h-10 w-auto fill-current text-white group-hover:text-cyan-300 transition-colors duration-300" />
                                <div class="absolute -inset-2 bg-gradient-to-r from-cyan-400/20 to-blue-500/20 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300 blur-sm"></div>
                            </div>
                            <span class="ml-3 text-xl font-bold text-white group-hover:text-cyan-300 transition-colors duration-300">Revenue Rise</span>
                        </Link>
                        
                        <!-- Desktop Navigation -->
                        <nav class="hidden md:flex items-center space-x-1">
                            <a href="#earn-money" @click.prevent="scrollToSection('earn-money')" 
                               class="px-4 py-2 text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200">
                                {{ t('Earn Money') }}
                            </a>
                            <a href="#how-it-works" @click.prevent="scrollToSection('how-it-works')" 
                               class="px-4 py-2 text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200">
                                {{ t('How It Works') }}
                            </a>
                            <a href="#for-business" @click.prevent="scrollToSection('for-business')" 
                               class="px-4 py-2 text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200">
                                {{ t('For Business') }}
                            </a>
                            <a href="#learn" @click.prevent="scrollToSection('learn')" 
                               class="px-4 py-2 text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200">
                                {{ t('Learn') }}
                            </a>
                        </nav>
                        
                        <!-- Right Section -->
                        <div class="flex items-center space-x-3 relative">
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
                            <!-- Mobile Menu Button -->
                            <button @click="mobileNavOpen = !mobileNavOpen" 
                                    class="md:hidden p-2 rounded-lg text-white hover:bg-white/10 transition-colors duration-200">
                                <svg v-if="!mobileNavOpen" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                                <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Auth Buttons Row -->
                    <div class="pb-2 border-t border-white/10">
                        <div class="flex flex-row gap-2 justify-center items-center pt-2">
                            <Link
                                v-if="!page.props.auth.user"
                                :href="route('register')"
                                class="group relative bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white font-semibold px-4 py-1.5 rounded-lg text-xs shadow-lg hover:shadow-cyan-500/25 transition-all duration-300 transform hover:scale-105 text-center overflow-hidden"
                            >
                                <div class="absolute inset-0 bg-gradient-to-r from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                <span class="relative z-10">{{ t('Sign Up') }}</span>
                            </Link>
                            <Link
                                v-if="!page.props.auth.user"
                                :href="route('login')"
                                class="group relative bg-white/10 backdrop-blur-sm border border-white/30 hover:border-white/50 text-white font-semibold px-4 py-1.5 rounded-lg text-xs hover:bg-white/20 transition-all duration-300 transform hover:scale-105 text-center overflow-hidden"
                            >
                                <div class="absolute inset-0 bg-gradient-to-r from-white/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                <span class="relative z-10">{{ t('Sign In') }}</span>
                            </Link>
                            <Link
                                v-if="page.props.auth.user"
                                :href="route('dashboard')"
                                class="group relative bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white font-semibold px-4 py-1.5 rounded-lg text-xs shadow-lg hover:shadow-cyan-500/25 transition-all duration-300 transform hover:scale-105 text-center overflow-hidden"
                            >
                                <div class="absolute inset-0 bg-gradient-to-r from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                <span class="relative z-10">{{ t('Dashboard') }}</span>
                            </Link>
                        </div>
                    </div>
                </div>
                
                <!-- Mobile Navigation -->
                <transition 
                    enter-active-class="transition duration-200 ease-out" 
                    enter-from-class="opacity-0 -translate-y-2" 
                    enter-to-class="opacity-100 translate-y-0" 
                    leave-active-class="transition duration-150 ease-in" 
                    leave-from-class="opacity-100 translate-y-0" 
                    leave-to-class="opacity-0 -translate-y-2">
                    <div v-if="mobileNavOpen" class="md:hidden border-t border-white/10">
                        <div class="px-4 py-4 space-y-2">
                            <a href="#earn-money" @click.prevent="scrollToSection('earn-money')" 
                               class="block px-3 py-2 text-base font-medium text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200">
                                {{ t('Earn Money') }}
                            </a>
                            <a href="#how-it-works" @click.prevent="scrollToSection('how-it-works')" 
                               class="block px-3 py-2 text-base font-medium text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200">
                                {{ t('How It Works') }}
                            </a>
                            <a href="#for-business" @click.prevent="scrollToSection('for-business')" 
                               class="block px-3 py-2 text-base font-medium text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200">
                                {{ t('For Business') }}
                            </a>
                            <a href="#learn" @click.prevent="scrollToSection('learn')" 
                               class="block px-3 py-2 text-base font-medium text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-200">
                                {{ t('Learn') }}
                            </a>
                        </div>
                    </div>
                </transition>
            </div>
        </header>
        <div class="h-20"></div>

        <!-- Hero Section -->
        <section id="earn-money" class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl text-white py-16 sm:py-24 relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-cyan-400/10 to-transparent"></div>
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-indigo-400/10 rounded-full blur-3xl"></div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">

                <h1 class="text-3xl sm:text-5xl md:text-7xl font-extrabold mb-6 sm:mb-8 leading-tight">
                    <span class="bg-gradient-to-r from-white to-cyan-100 bg-clip-text text-transparent">
                        {{ t('Simple Ways to Make') }}
                    </span>
                    <br>
                    <span class="text-white">{{ t('Money Online') }}</span>
                </h1>
                <p class="text-base sm:text-xl md:text-2xl mb-8 sm:mb-12 max-w-4xl mx-auto leading-relaxed text-cyan-50">
                  {{ t('Increase visibility and drive more sales of products on leading e-commerce platforms and explore other easy ways to make money online with Revenue Rise.') }}
                </p>
                <div class="flex flex-wrap justify-center gap-3 sm:gap-6 mb-12 sm:mb-16">
                    <div class="bg-gradient-to-br from-white/95 via-blue-50/90 to-indigo-50/95 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl hover:shadow-2xl px-3 py-3 sm:px-6 sm:py-5 flex flex-col items-center w-28 sm:w-44 min-h-[80px] sm:min-h-[120px] transition-all duration-300 hover:scale-[1.02] border border-white/40">
                        <img src="/assets/platforms/alibaba.png" alt="Alibaba" class="h-6 sm:h-10 mb-2 sm:mb-3 object-contain drop-shadow-lg">
                        <span class="text-gray-900 font-semibold text-xs sm:text-sm">Alibaba</span>
                        <span class="text-[10px] sm:text-xs text-blue-600 mt-1">{{ t('Grab & earn') }}</span>
                    </div>
                    <div class="bg-gradient-to-br from-white/95 via-blue-50/90 to-indigo-50/95 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl hover:shadow-2xl px-3 py-3 sm:px-6 sm:py-5 flex flex-col items-center w-28 sm:w-44 min-h-[80px] sm:min-h-[120px] transition-all duration-300 hover:scale-[1.02] border border-white/40">
                        <img src="/assets/platforms/amazon.png" alt="Amazon" class="h-6 sm:h-10 mb-2 sm:mb-3 object-contain drop-shadow-lg">
                        <span class="text-gray-900 font-semibold text-xs sm:text-sm">Amazon</span>
                        <span class="text-[10px] sm:text-xs text-blue-600 mt-1">{{ t('Grab & earn') }}</span>
                    </div>
                    <div class="bg-gradient-to-br from-white/95 via-blue-50/90 to-indigo-50/95 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl hover:shadow-2xl px-3 py-3 sm:px-6 sm:py-5 flex flex-col items-center w-28 sm:w-44 min-h-[80px] sm:min-h-[120px] transition-all duration-300 hover:scale-[1.02] border border-white/40">
                        <img src="/assets/platforms/ebay.png" alt="eBay" class="h-6 sm:h-10 mb-2 sm:mb-3 object-contain drop-shadow-lg">
                        <span class="text-gray-900 font-semibold text-xs sm:text-sm">eBay</span>
                        <span class="text-[10px] sm:text-xs text-blue-600 mt-1">{{ t('Grab & earn') }}</span>
                    </div>
                    <div class="bg-gradient-to-br from-white/95 via-blue-50/90 to-indigo-50/95 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl hover:shadow-2xl px-3 py-3 sm:px-6 sm:py-5 flex flex-col items-center w-28 sm:w-44 min-h-[80px] sm:min-h-[120px] transition-all duration-300 hover:scale-[1.02] border border-white/40">
                        <img src="/assets/platforms/walmart.png" alt="Walmart" class="h-6 sm:h-10 mb-2 sm:mb-3 object-contain drop-shadow-lg">
                        <span class="text-gray-900 font-semibold text-xs sm:text-sm">Walmart</span>
                        <span class="text-[10px] sm:text-xs text-blue-600 mt-1">{{ t('Grab & earn') }}</span>
                    </div>
                    <div class="bg-gradient-to-br from-white/95 via-blue-50/90 to-indigo-50/95 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl hover:shadow-2xl px-3 py-3 sm:px-6 sm:py-5 flex flex-col items-center w-28 sm:w-44 min-h-[80px] sm:min-h-[120px] transition-all duration-300 hover:scale-[1.02] border border-white/40">
                        <img src="/assets/platforms/aliexpress.png" alt="AliExpress" class="h-6 sm:h-10 mb-2 sm:mb-3 object-contain drop-shadow-lg">
                        <span class="text-gray-900 font-semibold text-xs sm:text-sm">AliExpress</span>
                        <span class="text-[10px] sm:text-xs text-blue-600 mt-1">{{ t('Grab & earn') }}</span>
                    </div>
                    <div class="bg-gradient-to-br from-white/95 via-blue-50/90 to-indigo-50/95 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl hover:shadow-2xl px-3 py-3 sm:px-6 sm:py-5 flex flex-col items-center w-28 sm:w-44 min-h-[80px] sm:min-h-[120px] transition-all duration-300 hover:scale-[1.02] border border-white/40">
                        <img src="/assets/platforms/etsy.png" alt="Etsy" class="h-6 sm:h-10 mb-2 sm:mb-3 object-contain drop-shadow-lg">
                        <span class="text-gray-900 font-semibold text-xs sm:text-sm">Etsy</span>
                        <span class="text-[10px] sm:text-xs text-blue-600 mt-1">{{ t('Grab & earn') }}</span>
                    </div>
                    <div class="bg-gradient-to-br from-white/95 via-blue-50/90 to-indigo-50/95 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl hover:shadow-2xl px-3 py-3 sm:px-6 sm:py-5 flex flex-col items-center w-28 sm:w-44 min-h-[80px] sm:min-h-[120px] transition-all duration-300 hover:scale-[1.02] border border-white/40">
                        <img src="/assets/platforms/shopify.png" alt="Shopify" class="h-6 sm:h-10 mb-2 sm:mb-3 object-contain drop-shadow-lg">
                        <span class="text-gray-900 font-semibold text-xs sm:text-sm">Shopify</span>
                        <span class="text-[10px] sm:text-xs text-blue-600 mt-1">{{ t('Grab & earn') }}</span>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-4 justify-center w-full max-w-md mx-auto">
                    <Link
                        v-if="!page.props.auth.user"
                        :href="route('register')"
                        class="bg-gradient-to-r from-cyan-400/70 via-blue-500/60 to-indigo-600/70 hover:from-cyan-500/80 hover:via-blue-600/70 hover:to-indigo-700/80 text-white px-6 py-4 rounded-full font-bold text-lg hover:shadow-2xl transition-all duration-300 text-center transform hover:scale-[1.02] shadow-xl w-full backdrop-blur-sm"
                    >
                        {{ t('Start Earning Now') }}
                    </Link>
                    <Link
                        v-if="page.props.auth.user"
                        :href="route('dashboard')"
                        class="bg-gradient-to-r from-cyan-400/70 via-blue-500/60 to-indigo-600/70 hover:from-cyan-500/80 hover:via-blue-600/70 hover:to-indigo-700/80 text-white px-6 py-4 rounded-full font-bold text-lg hover:shadow-2xl transition-all duration-300 text-center transform hover:scale-[1.02] shadow-xl w-full backdrop-blur-sm"
                    >
                        {{ t('Go to Dashboard') }}
                    </Link>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section id="how-it-works" class="py-16 sm:py-20 bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-center mb-12 sm:mb-16 text-white">
                    {{ t('How does Revenue Rise work for online money earning') }}
                </h2>
                <div class="grid md:grid-cols-3 gap-6 sm:gap-8 mb-8 sm:mb-12">
                    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl sm:rounded-3xl p-6 sm:p-8 flex flex-col items-start text-left hover:shadow-2xl transition-all duration-300 border border-cyan-300/30 transform hover:scale-[1.02]">
                        <div class="mb-4">
                            <svg width="40" height="40" fill="none" viewBox="0 0 40 40">
                                <defs>
                                    <linearGradient id="step1Gradient" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#06b6d4"/>
                                        <stop offset="1" stop-color="#3b82f6"/>
                                    </linearGradient>
                                </defs>
                                <circle cx="20" cy="20" r="20" fill="url(#step1Gradient)" opacity=".8"/>
                                <path d="M20 12a8 8 0 100 16 8 8 0 000-16zm0 14.4A6.4 6.4 0 1120 13.6a6.4 6.4 0 010 12.8z" fill="#fff"/>
                            </svg>
                        </div>
                        <div class="font-semibold text-lg text-cyan-300 mb-1">{{ t('Step 1.') }}</div>
                        <div class="font-bold text-xl text-white mb-2">{{ t('Sign up') }}</div>
                        <div class="text-slate-300">{{ t('Create an account.') }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-cyan-500/30 to-blue-600/30 backdrop-blur-xl rounded-2xl sm:rounded-3xl p-6 sm:p-8 flex flex-col items-start text-left hover:shadow-2xl transition-all duration-300 transform hover:scale-[1.05] border border-cyan-300/40">
                        <div class="mb-4">
                            <svg width="40" height="40" fill="none" viewBox="0 0 40 40">
                                <defs>
                                    <linearGradient id="step2Gradient" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#06b6d4"/>
                                        <stop offset="1" stop-color="#6366f1"/>
                                    </linearGradient>
                                </defs>
                                <circle cx="20" cy="20" r="20" fill="url(#step2Gradient)"/>
                                <path d="M28 16.5l-8.25 8.25L12 17.5" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="font-semibold text-lg text-white mb-1">{{ t('Step 2.') }}</div>
                        <div class="font-bold text-xl text-white mb-2">{{ t('Complete tasks') }}</div>
                        <div class="text-white">{{ t('Browse a wide range of tasks, pick the easiest way to earn online.') }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-blue-700/30 to-indigo-800/30 backdrop-blur-xl rounded-2xl sm:rounded-3xl p-6 sm:p-8 flex flex-col items-start text-left hover:shadow-2xl transition-all duration-300 transform hover:scale-[1.05] border border-indigo-300/40">
                        <div class="mb-4">
                            <svg width="40" height="40" fill="none" viewBox="0 0 40 40">
                                <defs>
                                    <linearGradient id="step3Gradient" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#3b82f6"/>
                                        <stop offset="1" stop-color="#6366f1"/>
                                    </linearGradient>
                                </defs>
                                <circle cx="20" cy="20" r="20" fill="url(#step3Gradient)"/>
                                <path d="M13 25h14M13 25v-2.5a2.5 2.5 0 012.5-2.5h9a2.5 2.5 0 012.5 2.5V25m-14 0v2.5A2.5 2.5 0 0015.5 30h9a2.5 2.5 0 002.5-2.5V25" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="font-semibold text-lg text-white mb-1">{{ t('Step 3.') }}</div>
                        <div class="font-bold text-xl text-white mb-2">{{ t('Get paid') }}</div>
                        <div class="text-white">{{ t('Follow the task guidelines, complete tasks, and get rewarded. Easily transfer your earnings to your crypto wallet.') }}</div>
                    </div>
                </div>
                <div class="flex justify-center mb-8 sm:mb-12">
                    <Link
                        :href="route('register')"
                        class="bg-gradient-to-r from-cyan-400/70 via-blue-500/60 to-indigo-600/70 hover:from-cyan-500/80 hover:via-blue-600/70 hover:to-indigo-700/80 text-white font-bold px-6 sm:px-8 py-4 rounded-full text-lg shadow-lg hover:shadow-xl transition-all duration-300 inline-block text-center transform hover:scale-[1.02] w-full sm:w-auto backdrop-blur-sm"
                    >
                        {{ t('Start earning now') }}
                    </Link>
                </div>
                <div class="flex flex-wrap justify-center items-center gap-6 sm:gap-8 opacity-80">
                    <img src="/assets/platforms/dailycoin.png" alt="DailyCoin" class="h-10 sm:h-12 w-auto">
                    <img src="/assets/platforms/yahofinance.png" alt="Yahoo Finance" class="h-10 sm:h-12 w-auto">
                    <img src="/assets/platforms/benzinga.png" alt="Benzinga" class="h-10 sm:h-12 w-auto">
                    <img src="/assets/platforms/businessinside.png" alt="Business Insider" class="h-10 sm:h-12 w-auto">
                    <img src="/assets/platforms/marketwatch.png" alt="MarketWatch" class="h-10 sm:h-12 w-auto">
                </div>
            </div>
        </section>

        <!-- Our Vision -->
        <section id="for-business" class="py-16 sm:py-20 bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-4xl sm:text-5xl font-extrabold mb-6 text-white">{{ t('Our vision') }}</h2>
                <p class="text-base sm:text-lg md:text-xl text-slate-300 mb-8 sm:mb-10 max-w-3xl mx-auto">
                    {{ t('Revenue Rise helps people make money online by offering simple and easy ways to earn. Our platform gives everyone access to a wide variety of small tasks, allowing users to earn extra income from anywhere. With no special skills needed, Revenue Rise makes it possible for anyone to start earning and be part of the online economy.') }}
                </p>
                <div class="grid md:grid-cols-3 gap-6 sm:gap-8 mt-6 sm:mt-8">
                    <!-- Blue Card -->
                    <div class="rounded-2xl sm:rounded-3xl bg-gradient-to-br from-cyan-600/30 to-blue-700/30 backdrop-blur-xl text-white flex flex-col items-center justify-center relative overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-[1.02] border border-cyan-300/30" style="min-height: 200px;">
                        <svg class="absolute left-0 top-0 w-full h-full" viewBox="0 0 400 240" fill="none">
                            <circle cx="80" cy="220" r="120" fill="#06b6d4" fill-opacity="0.3" />
                            <circle cx="220" cy="220" r="80" fill="#3b82f6" fill-opacity="0.3" />
                            <circle cx="320" cy="180" r="60" fill="#06b6d4" fill-opacity="0.2" />
                        </svg>
                        <div class="relative z-10 w-full text-center flex flex-col justify-center h-full p-4">
                            <div class="text-lg sm:text-xl md:text-2xl font-bold mb-2">{{ t('Total paid out') }}</div>
                            <div class="text-xl sm:text-2xl md:text-3xl font-extrabold tracking-tight">3,621,117</div>
                        </div>
                    </div>
                    <!-- Purple Card -->
                    <div class="rounded-2xl sm:rounded-3xl bg-gradient-to-br from-purple-500/30 to-purple-600/30 backdrop-blur-xl text-white flex flex-col items-center justify-center relative overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-[1.02] border border-purple-300/30" style="min-height: 200px;">
                        <svg class="absolute left-0 top-0 w-full h-full" viewBox="0 0 400 240" fill="none">
                            <ellipse cx="90" cy="180" rx="30" ry="30" stroke="#fff" stroke-width="3" opacity="0.3" />
                            <ellipse cx="150" cy="200" rx="40" ry="40" stroke="#fff" stroke-width="3" opacity="0.3" />
                            <ellipse cx="250" cy="180" rx="60" ry="60" stroke="#fff" stroke-width="3" opacity="0.3" />
                            <ellipse cx="320" cy="120" rx="80" ry="80" stroke="#fff" stroke-width="3" opacity="0.3" />
                        </svg>
                        <div class="relative z-10 w-full text-center flex flex-col justify-center h-full p-4">
                            <div class="text-lg sm:text-xl md:text-2xl font-bold mb-2">{{ t('Registered users') }}</div>
                            <div class="text-xl sm:text-2xl md:text-3xl font-extrabold tracking-tight">12,883,854</div>
                        </div>
                    </div>
                    <!-- Black Card -->
                    <div class="rounded-2xl sm:rounded-3xl bg-gradient-to-br from-gray-900/50 to-gray-800/50 backdrop-blur-xl text-white flex flex-col items-center justify-center relative overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-[1.02] border border-gray-300/30" style="min-height: 200px;">
                        <svg class="absolute left-0 top-0 w-full h-full" viewBox="0 0 400 240" fill="none">
                            <circle cx="200" cy="200" r="100" stroke="#06b6d4" stroke-width="3" opacity="0.3" />
                            <circle cx="200" cy="200" r="70" stroke="#06b6d4" stroke-width="3" opacity="0.3" />
                            <circle cx="200" cy="200" r="40" stroke="#06b6d4" stroke-width="3" opacity="0.3" />
                            <circle cx="320" cy="100" r="6" fill="#06b6d4" />
                            <circle cx="100" cy="160" r="6" fill="#06b6d4" />
                        </svg>
                        <div class="relative z-10 w-full text-center flex flex-col justify-center h-full p-4">
                            <div class="text-lg sm:text-xl md:text-2xl font-bold mb-2">{{ t('Completed payouts') }}</div>
                            <div class="text-xl sm:text-2xl md:text-3xl font-extrabold tracking-tight">2,637,650</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Ways to Earn -->
        <section class="py-16 sm:py-20 bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl sm:text-3xl font-bold text-center mb-8 sm:mb-12 text-white">
                    {{ t('Ways to Earn with Revenue Rise') }}
                </h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                    <!-- Card 1 -->
                    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl hover:shadow-2xl p-6 sm:p-8 flex flex-col items-center text-center h-full transition-all duration-300 transform hover:scale-[1.02] border border-cyan-300/30">
                        <svg width="56" height="56" viewBox="0 0 56 56" fill="none" class="mb-4">
                            <defs>
                                <linearGradient id="boostGradient" x1="0" y1="0" x2="56" y2="56" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#06b6d4"/>
                                    <stop offset="1" stop-color="#3b82f6"/>
                                </linearGradient>
                            </defs>
                            <rect x="8" y="16" width="40" height="16" rx="8" fill="url(#boostGradient)"/>
                            <rect x="16" y="36" width="24" height="8" rx="4" fill="#06b6d4" opacity="0.6"/>
                            <circle cx="28" cy="28" r="26" stroke="#06b6d4" stroke-width="2" opacity="0.4"/>
                        </svg>
                        <h3 class="text-lg sm:text-xl font-semibold mb-2 text-white">{{ t('Grab & Boost Products') }}</h3>
                        <p class="text-slate-300 text-sm sm:text-base">{{ t('Select products from top e-commerce stores, complete boost tasks, and help increase their sales.') }}</p>
                    </div>
                    <!-- Card 2 -->
                    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl hover:shadow-2xl p-6 sm:p-8 flex flex-col items-center text-center h-full transition-all duration-300 transform hover:scale-[1.02] border border-cyan-300/30">
                        <svg width="56" height="56" viewBox="0 0 56 56" fill="none" class="mb-4">
                            <defs>
                                <linearGradient id="commissionGradient" x1="0" y1="0" x2="56" y2="56" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#3b82f6"/>
                                    <stop offset="1" stop-color="#6366f1"/>
                                </linearGradient>
                            </defs>
                            <rect x="12" y="24" width="32" height="12" rx="6" fill="url(#commissionGradient)"/>
                            <circle cx="28" cy="28" r="26" stroke="#06b6d4" stroke-width="2" opacity="0.4"/>
                            <rect x="20" y="12" width="16" height="8" rx="4" fill="#06b6d4" opacity="0.6"/>
                        </svg>
                        <h3 class="text-lg sm:text-xl font-semibold mb-2 text-white">{{ t('Earn Commission') }}</h3>
                        <p class="text-slate-300 text-sm sm:text-base">{{ t('Earn a commission for every successful product boost and sale you help generate.') }}</p>
                    </div>
                    <!-- Card 3 -->
                    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl hover:shadow-2xl p-6 sm:p-8 flex flex-col items-center text-center h-full transition-all duration-300 transform hover:scale-[1.02] border border-cyan-300/30">
                        <svg width="56" height="56" viewBox="0 0 56 56" fill="none" class="mb-4">
                            <defs>
                                <linearGradient id="storesGradient" x1="0" y1="0" x2="56" y2="56" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#06b6d4"/>
                                    <stop offset="1" stop-color="#6366f1"/>
                                </linearGradient>
                            </defs>
                            <rect x="16" y="32" width="24" height="8" rx="4" fill="url(#storesGradient)"/>
                            <rect x="20" y="16" width="16" height="8" rx="4" fill="#06b6d4" opacity="0.6"/>
                            <circle cx="28" cy="28" r="26" stroke="#06b6d4" stroke-width="2" opacity="0.4"/>
                        </svg>
                        <h3 class="text-lg sm:text-xl font-semibold mb-2 text-white">{{ t('Multiple Stores') }}</h3>
                        <p class="text-slate-300 text-sm sm:text-base">{{ t('Access tasks from Alibaba, Amazon, eBay, Walmart, AliExpress, Etsy, Shopify, and more.') }}</p>
                    </div>
                    <!-- Card 4 -->
                    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl hover:shadow-2xl p-6 sm:p-8 flex flex-col items-center text-center h-full transition-all duration-300 transform hover:scale-[1.02] border border-cyan-300/30">
                        <svg width="56" height="56" viewBox="0 0 56 56" fill="none" class="mb-4">
                            <defs>
                                <linearGradient id="referGradient" x1="0" y1="0" x2="56" y2="56" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#3b82f6"/>
                                    <stop offset="1" stop-color="#06b6d4"/>
                                </linearGradient>
                            </defs>
                            <ellipse cx="28" cy="36" rx="12" ry="8" fill="url(#referGradient)"/>
                            <circle cx="28" cy="20" r="8" fill="#06b6d4" opacity="0.6"/>
                            <circle cx="28" cy="28" r="26" stroke="#06b6d4" stroke-width="2" opacity="0.4"/>
                        </svg>
                        <h3 class="text-lg sm:text-xl font-semibold mb-2 text-white">{{ t('Refer Friends') }}</h3>
                        <p class="text-slate-300 text-sm sm:text-base">{{ t('Invite friends and earn a bonus every time they complete their first boost task.') }}</p>
                    </div>
                    <!-- Card 5 -->
                    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl hover:shadow-2xl p-6 sm:p-8 flex flex-col items-center text-center h-full transition-all duration-300 transform hover:scale-[1.02] border border-cyan-300/30">
                        <svg width="56" height="56" viewBox="0 0 56 56" fill="none" class="mb-4">
                            <defs>
                                <linearGradient id="repeatGradient" x1="0" y1="0" x2="56" y2="56" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#6366f1"/>
                                    <stop offset="1" stop-color="#3b82f6"/>
                                </linearGradient>
                            </defs>
                            <rect x="16" y="32" width="24" height="8" rx="4" fill="url(#repeatGradient)"/>
                            <rect x="20" y="16" width="16" height="8" rx="4" fill="#06b6d4" opacity="0.6"/>
                            <circle cx="28" cy="28" r="26" stroke="#06b6d4" stroke-width="2" opacity="0.4"/>
                        </svg>
                        <h3 class="text-lg sm:text-xl font-semibold mb-2 text-white">{{ t('Repeat & Earn More') }}</h3>
                        <p class="text-slate-300 text-sm sm:text-base">{{ t('The more products you boost, the more you can earn. There\'s no limit!') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Choose -->
        <section id="learn" class="py-16 sm:py-20 bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-center mb-4 text-white">
                    {{ t('Why choose Revenue Rise to earn money online?') }}
                </h2>
                <p class="text-center text-slate-300 mb-8 sm:mb-12 max-w-2xl mx-auto text-sm sm:text-base">
                    {{ t('Revenue Rise is a leading platform with thousands of users worldwide, offering endless opportunities to make money online easily and securely.') }}
                </p>
                <div class="grid gap-6 sm:gap-8 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Card 1 -->
                    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl hover:shadow-2xl p-6 sm:p-8 flex flex-col items-center text-center h-full transition-all duration-300 transform hover:scale-[1.02] border border-cyan-300/30">
                        <svg width="72" height="72" viewBox="0 0 72 72" fill="none" class="mb-4">
                            <defs>
                                <linearGradient id="tasksGradient" x1="0" y1="0" x2="72" y2="72" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#06b6d4"/>
                                    <stop offset="1" stop-color="#3b82f6"/>
                                </linearGradient>
                            </defs>
                            <rect x="8" y="16" width="56" height="16" rx="8" fill="url(#tasksGradient)"/>
                            <rect x="16" y="36" width="40" height="12" rx="6" fill="#06b6d4" opacity="0.6"/>
                            <rect x="24" y="52" width="24" height="8" rx="4" fill="#06b6d4" opacity="0.4"/>
                        </svg>
                        <h3 class="text-base sm:text-lg font-semibold mb-2 text-white">{{ t('Thousands of tasks') }}</h3>
                        <p class="text-slate-300 text-xs sm:text-sm">{{ t('Access a huge variety of simple online tasks—no special skills needed. Earn in your spare time, anytime.') }}</p>
                    </div>
                    <!-- Card 2 -->
                    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl hover:shadow-2xl p-6 sm:p-8 flex flex-col items-center text-center h-full transition-all duration-300 transform hover:scale-[1.02] border border-cyan-300/30">
                        <svg width="72" height="72" viewBox="0 0 72 72" fill="none" class="mb-4">
                            <defs>
                                <linearGradient id="anywhereGradient" x1="0" y1="0" x2="72" y2="72" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#3b82f6"/>
                                    <stop offset="1" stop-color="#6366f1"/>
                                </linearGradient>
                            </defs>
                            <circle cx="36" cy="36" r="28" fill="#06b6d4" opacity="0.3"/>
                            <circle cx="36" cy="36" r="20" fill="url(#anywhereGradient)"/>
                            <rect x="28" y="52" width="16" height="8" rx="4" fill="#06b6d4" opacity="0.6"/>
                        </svg>
                        <h3 class="text-base sm:text-lg font-semibold mb-2 text-white">{{ t('Earn anywhere, anytime') }}</h3>
                        <p class="text-slate-300 text-xs sm:text-sm">{{ t('Work from home or on the go. Earn extra income from your phone, tablet, or computer—whenever you want.') }}</p>
                    </div>
                    <!-- Card 3 -->
                    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl hover:shadow-2xl p-6 sm:p-8 flex flex-col items-center text-center h-full transition-all duration-300 transform hover:scale-[1.02] border border-cyan-300/30">
                        <svg width="72" height="72" viewBox="0 0 72 72" fill="none" class="mb-4">
                            <defs>
                                <linearGradient id="paidGradient" x1="0" y1="0" x2="72" y2="72" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#06b6d4"/>
                                    <stop offset="1" stop-color="#6366f1"/>
                                </linearGradient>
                            </defs>
                            <rect x="16" y="32" width="40" height="16" rx="8" fill="url(#paidGradient)"/>
                            <rect x="24" y="20" width="24" height="8" rx="4" fill="#06b6d4" opacity="0.6"/>
                            <rect x="28" y="52" width="16" height="8" rx="4" fill="#3b82f6"/>
                        </svg>
                        <h3 class="text-base sm:text-lg font-semibold mb-2 text-white">{{ t('Instant payouts') }}</h3>
                        <p class="text-slate-300 text-xs sm:text-sm">{{ t('Get paid as soon as you finish a task. Withdraw your earnings instantly—no waiting, no hassle.') }}</p>
                    </div>
                    <!-- Card 4 -->
                    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl hover:shadow-2xl p-6 sm:p-8 flex flex-col items-center text-center h-full transition-all duration-300 transform hover:scale-[1.02] border border-cyan-300/30">
                        <svg width="72" height="72" viewBox="0 0 72 72" fill="none" class="mb-4">
                            <defs>
                                <linearGradient id="supportGradient" x1="0" y1="0" x2="72" y2="72" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#3b82f6"/>
                                    <stop offset="1" stop-color="#06b6d4"/>
                                </linearGradient>
                            </defs>
                            <ellipse cx="36" cy="36" rx="28" ry="20" fill="#06b6d4" opacity="0.3"/>
                            <ellipse cx="36" cy="36" rx="20" ry="12" fill="url(#supportGradient)"/>
                            <rect x="28" y="52" width="16" height="8" rx="4" fill="#6366f1"/>
                        </svg>
                        <h3 class="text-base sm:text-lg font-semibold mb-2 text-white">{{ t('24/7 support') }}</h3>
                        <p class="text-slate-300 text-xs sm:text-sm">{{ t('Get help whenever you need it. Our team and community are always here to support your earning journey.') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Floating Help Icon -->
        <FloatingChatIcon v-if="!page.props.auth.user" @click="openGuestChat" />

        <!-- Guest Chat Modal -->
        <div v-if="showGuestChat" class="fixed inset-0 bg-black/50 z-50">
            <div v-if="showChatForm" class="flex items-center justify-center h-full p-4">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
                    <div class="flex items-center justify-between p-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">{{ t('Contact Support') }}</h3>
                        <button @click="closeGuestChat" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div class="p-6">
                        <form @submit.prevent="startGuestChat" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('Name') }}</label>
                                <input 
                                    v-model="guestForm.name" 
                                    type="text" 
                                    required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    :placeholder="t('Enter your name')"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('Mobile Number') }}</label>
                                <input 
                                    v-model="guestForm.mobile_number" 
                                    type="tel" 
                                    required 
                                    pattern="[0-9+\-\s]*"
                                    @input="guestForm.mobile_number = guestForm.mobile_number.replace(/[^0-9+\-\s]/g, '')"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    :placeholder="t('Enter your mobile number')"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('Verification') }}</label>
                                <div class="flex items-center space-x-2 mb-2">
                                    <img 
                                        :src="captchaImage" 
                                        alt="Captcha" 
                                        class="border border-gray-300 rounded"
                                        @click="generateCaptcha"
                                        style="cursor: pointer;"
                                    >
                                    <button 
                                        type="button" 
                                        @click="generateCaptcha"
                                        class="text-blue-500 hover:text-blue-700 text-sm"
                                    >
                                        🔄
                                    </button>
                                </div>
                                <input 
                                    v-model="guestForm.captcha" 
                                    type="text" 
                                    required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    :placeholder="t('Enter the code shown above')"
                                >
                                <div v-if="showCaptchaError" class="text-red-500 text-sm mt-1">{{ showCaptchaError }}</div>
                            </div>
                            <button 
                                type="submit" 
                                class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white py-2 px-4 rounded-lg transition-all duration-200"
                            >
                                {{ t('Start Chat') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <GuestChatInterface 
                v-else
                :sessionId="guestSessionId" 
                :guestInfo="{ name: guestForm.name, mobile_number: guestForm.mobile_number }"
                @close="closeGuestChat"
            />
        </div>

        <!-- Devices -->
        <section class="py-16 sm:py-20 bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl sm:text-3xl font-bold text-center mb-8 sm:mb-12 text-white">
                    {{ t('Earn from Any Device') }}
                </h2>
                <div class="grid md:grid-cols-3 gap-6 sm:gap-8 text-center">
                    <!-- Card 1: Mobile -->
                    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl hover:shadow-2xl p-6 sm:p-8 flex flex-col items-center text-center h-full transition-all duration-300 transform hover:scale-[1.02] border border-cyan-300/30">
                        <svg width="56" height="56" viewBox="0 0 56 56" fill="none" class="mb-4">
                            <defs>
                                <linearGradient id="mobileGradient" x1="0" y1="0" x2="56" y2="56" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#06b6d4"/>
                                    <stop offset="1" stop-color="#3b82f6"/>
                                </linearGradient>
                            </defs>
                            <rect x="16" y="8" width="24" height="40" rx="8" fill="#06b6d4" opacity="0.3"/>
                            <rect x="22" y="14" width="12" height="28" rx="4" fill="url(#mobileGradient)"/>
                            <circle cx="28" cy="46" r="2" fill="#6366f1"/>
                        </svg>
                        <h3 class="text-lg sm:text-xl font-semibold mb-2 text-white">{{ t('Earn on Mobile') }}</h3>
                        <p class="text-slate-300 text-sm sm:text-base">{{ t('Complete tasks and earn money from your phone, wherever you are.') }}</p>
                    </div>
                    <!-- Card 2: Tablet -->
                    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl hover:shadow-2xl p-6 sm:p-8 flex flex-col items-center text-center h-full transition-all duration-300 transform hover:scale-[1.02] border border-cyan-300/30">
                        <svg width="56" height="56" viewBox="0 0 56 56" fill="none" class="mb-4">
                            <defs>
                                <linearGradient id="tabletGradient" x1="0" y1="0" x2="56" y2="56" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#06b6d4"/>
                                    <stop offset="1" stop-color="#6366f1"/>
                                </linearGradient>
                            </defs>
                            <rect x="10" y="12" width="36" height="32" rx="6" fill="#06b6d4" opacity="0.3"/>
                            <rect x="16" y="18" width="24" height="20" rx="4" fill="url(#tabletGradient)"/>
                            <circle cx="28" cy="40" r="2" fill="#3b82f6"/>
                        </svg>
                        <h3 class="text-lg sm:text-xl font-semibold mb-2 text-white">{{ t('Earn on Tablet') }}</h3>
                        <p class="text-slate-300 text-sm sm:text-base">{{ t('Enjoy a bigger screen for multitasking and boosting your earnings.') }}</p>
                    </div>
                    <!-- Card 3: Desktop -->
                    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl hover:shadow-2xl p-6 sm:p-8 flex flex-col items-center text-center h-full transition-all duration-300 transform hover:scale-[1.02] border border-cyan-300/30">
                        <svg width="56" height="56" viewBox="0 0 56 56" fill="none" class="mb-4">
                            <defs>
                                <linearGradient id="desktopGradient" x1="0" y1="0" x2="56" y2="56" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#3b82f6"/>
                                    <stop offset="1" stop-color="#6366f1"/>
                                </linearGradient>
                            </defs>
                            <rect x="8" y="16" width="40" height="24" rx="6" fill="#06b6d4" opacity="0.3"/>
                            <rect x="14" y="22" width="28" height="12" rx="3" fill="url(#desktopGradient)"/>
                            <rect x="22" y="42" width="12" height="4" rx="2" fill="#6366f1"/>
                        </svg>
                        <h3 class="text-lg sm:text-xl font-semibold mb-2 text-white">{{ t('Earn on Desktop') }}</h3>
                        <p class="text-slate-300 text-sm sm:text-base">{{ t('Maximize productivity and manage more tasks with ease.') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-16 sm:py-20 bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl text-white relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-cyan-400/10 to-transparent"></div>
            <div class="absolute top-0 left-1/3 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 right-1/3 w-96 h-96 bg-indigo-400/10 rounded-full blur-3xl"></div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold mb-6 bg-gradient-to-r from-white to-cyan-100 bg-clip-text text-transparent">{{ t('Turn your spare time into earnings today.') }}</h2>
                <p class="text-lg sm:text-xl md:text-2xl mb-8 sm:mb-12 text-cyan-50 max-w-3xl mx-auto">{{ t('Join thousands of sellers maximizing their product potential.') }}</p>
                <Link
                    v-if="!page.props.auth.user"
                    :href="route('register')"
                    class="bg-gradient-to-r from-white/90 to-cyan-50/90 text-blue-600 px-6 sm:px-8 py-3 sm:py-4 rounded-full font-bold text-base sm:text-lg hover:bg-white transition-all duration-300 transform hover:scale-[1.02] inline-block text-center shadow-2xl hover:shadow-3xl backdrop-blur-sm"
                >
                    {{ t('Get Started Now') }}
                </Link>
            </div>
        </section>
    </div>
</template>