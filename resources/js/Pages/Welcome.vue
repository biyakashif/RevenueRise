<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { ref } from 'vue';

const page = usePage();
const { translations } = page.props;
const t = (key) => translations[key] || key;

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const mobileNavOpen = ref(false);

function scrollToSection(id) {
    const el = document.getElementById(id);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        mobileNavOpen.value = false;
    }
}
</script>

<template>
    <Head :title="t('Task App - Boost Your Product Sales')" />
    <div class="min-h-screen bg-white">
        <!-- Header -->
    <header class="fixed top-0 left-0 w-full bg-white z-50" style="box-shadow: 0 2px 8px 0 rgba(0,0,0,0.04);">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16 md:h-14 relative">
                <!-- Logo -->
                <Link :href="route('dashboard')" class="flex items-center flex-shrink-0">
                    <ApplicationLogo class="h-7 w-auto fill-current text-blue-600" />
                    <span class="ml-2 text-xl font-bold text-gray-900 tracking-tight">Task App</span>
                </Link>
                <!-- Navigation (desktop) -->
                <nav class="hidden md:flex flex-1 justify-center">
                    <ul class="flex space-x-8 text-base font-medium text-gray-800">
                        <li><a href="#earn-money" class="hover:text-blue-600" @click.prevent="scrollToSection('earn-money')">Earn money</a></li>
                        <li><a href="#how-it-works" class="hover:text-blue-600" @click.prevent="scrollToSection('how-it-works')">How it works</a></li>
                        <li><a href="#for-business" class="hover:text-blue-600" @click.prevent="scrollToSection('for-business')">For business</a></li>
                        <li><a href="#learn" class="hover:text-blue-600" @click.prevent="scrollToSection('learn')">Learn</a></li>
                    </ul>
                </nav>
                <!-- Auth Buttons (desktop and mobile) -->
                <div class="flex items-center space-x-2">
                    <Link
                        v-if="!page.props.auth.user"
                        :href="route('register')"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 sm:px-4 py-2 rounded-full text-sm sm:text-base md:px-6 md:py-2 shadow transition-all duration-200"
                    >
                        Sign Up
                    </Link>
                    <Link
                        v-if="!page.props.auth.user"
                        :href="route('login')"
                        class="text-blue-600 hover:text-blue-800 font-medium text-sm sm:text-base"
                    >
                        Sign In
                    </Link>
                    <Link
                        v-if="page.props.auth.user"
                        :href="route('dashboard')"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 sm:px-4 py-2 rounded-full text-sm sm:text-base md:px-6 md:py-2 shadow transition-all duration-200"
                    >
                        Dashboard
                    </Link>
                </div>
                <!-- Hamburger (mobile) -->
                <button @click="mobileNavOpen = !mobileNavOpen" class="md:hidden ml-2 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Open navigation">
                    <svg v-if="!mobileNavOpen" class="h-6 w-6 text-gray-800" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg v-else class="h-6 w-6 text-gray-800" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <!-- Mobile nav -->
            <transition name="fade">
                <div v-if="mobileNavOpen" class="md:hidden absolute top-16 md:top-14 left-0 w-full bg-white shadow-lg z-50 border-t border-gray-100">
                    <nav class="px-4 py-4 flex flex-col space-y-4">
                        <a href="#earn-money" class="text-base font-medium text-gray-800 hover:text-blue-600" @click.prevent="scrollToSection('earn-money')">Earn money</a>
                        <a href="#how-it-works" class="text-base font-medium text-gray-800 hover:text-blue-600" @click.prevent="scrollToSection('how-it-works')">How it works</a>
                        <a href="#for-business" class="text-base font-medium text-gray-800 hover:text-blue-600" @click.prevent="scrollToSection('for-business')">For business</a>
                        <a href="#learn" class="text-base font-medium text-gray-800 hover:text-blue-600" @click.prevent="scrollToSection('learn')">Learn</a>
                    </nav>
                </div>
            </transition>
        </header>
        <div class="h-16 md:h-14"></div>

        <!-- Mobile Navigation Menu -->
        <div
            v-if="mobileNavOpen"
            class="fixed inset-0 z-40 bg-white bg-opacity-95 backdrop-blur-md overflow-y-auto"
            @click.self="mobileNavOpen = false"
        >
            <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <!-- Close button -->
                <div class="flex justify-end">
                    <button
                        @click="mobileNavOpen = false"
                        class="text-gray-800 hover:text-blue-600 focus:outline-none"
                        aria-label="Close menu"
                    >
                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
                <!-- Logo for mobile -->
                <div class="flex items-center justify-center mb-8">
                    <Link :href="route('dashboard')" class="flex items-center">
                        <ApplicationLogo class="h-8 w-auto fill-current text-blue-600" />
                        <span class="ml-2 text-2xl font-bold text-gray-900 tracking-tight">Task App</span>
                    </Link>
                </div>
                <!-- Navigation links -->
                <div class="space-y-4">
                    <div class="border-t border-gray-200 pt-4">
                        <a href="#earn-money" class="block text-center text-gray-800 hover:text-blue-600 font-medium" @click.prevent="scrollToSection('earn-money')">
                            Earn money
                        </a>
                        <a href="#how-it-works" class="block text-center text-gray-800 hover:text-blue-600 font-medium" @click.prevent="scrollToSection('how-it-works')">
                            How it works
                        </a>
                        <a href="#for-business" class="block text-center text-gray-800 hover:text-blue-600 font-medium" @click.prevent="scrollToSection('for-business')">
                            For business
                        </a>
                        <a href="#learn" class="block text-center text-gray-800 hover:text-blue-600 font-medium" @click.prevent="scrollToSection('learn')">
                            Learn
                        </a>
                    </div>
                </div>
            </div>
        </div>

    <!-- Hero Section -->
    <section id="earn-money" class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-4xl md:text-6xl font-bold mb-6">
                    Simple Ways to Make Money Online
                </h1>
                <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto">
                  Increase visibility and drive more sales of products on leading e-commerce platforms and explore other easy ways to make money online with Task App.
                </p>
                <div class="flex flex-wrap justify-center gap-4 mb-12">
                    <div class="bg-white/90 rounded-xl shadow-md px-6 py-4 flex flex-col items-center w-40 min-h-[110px]">
                        <img src="/assets/platforms/alibaba.png" alt="Alibaba" class="h-8 mb-2 object-contain">
                        <span class="text-gray-900 font-semibold text-sm">Alibaba</span>
                        <span class="text-xs text-gray-500 mt-1">Grab & earn</span>
                    </div>
                    <div class="bg-white/90 rounded-xl shadow-md px-6 py-4 flex flex-col items-center w-40 min-h-[110px]">
                        <img src="/assets/platforms/amazon.png" alt="Amazon" class="h-8 mb-2 object-contain">
                        <span class="text-gray-900 font-semibold text-sm">Amazon</span>
                        <span class="text-xs text-gray-500 mt-1">Grab & earn</span>
                    </div>
                    <div class="bg-white/90 rounded-xl shadow-md px-6 py-4 flex flex-col items-center w-40 min-h-[110px]">
                        <img src="/assets/platforms/ebay.png" alt="eBay" class="h-8 mb-2 object-contain">
                        <span class="text-gray-900 font-semibold text-sm">eBay</span>
                        <span class="text-xs text-gray-500 mt-1">Grab & earn</span>
                    </div>
                    <div class="bg-white/90 rounded-xl shadow-md px-6 py-4 flex flex-col items-center w-40 min-h-[110px]">
                        <img src="/assets/platforms/walmart.png" alt="Walmart" class="h-8 mb-2 object-contain">
                        <span class="text-gray-900 font-semibold text-sm">Walmart</span>
                        <span class="text-xs text-gray-500 mt-1">Grab & earn</span>
                    </div>
                    <div class="bg-white/90 rounded-xl shadow-md px-6 py-4 flex flex-col items-center w-40 min-h-[110px]">
                        <img src="/assets/platforms/aliexpress.png" alt="AliExpress" class="h-8 mb-2 object-contain">
                        <span class="text-gray-900 font-semibold text-sm">AliExpress</span>
                        <span class="text-xs text-gray-500 mt-1">Grab & earn</span>
                    </div>
                    <div class="bg-white/90 rounded-xl shadow-md px-6 py-4 flex flex-col items-center w-40 min-h-[110px]">
                        <img src="/assets/platforms/etsy.png" alt="Etsy" class="h-8 mb-2 object-contain">
                        <span class="text-gray-900 font-semibold text-sm">Etsy</span>
                        <span class="text-xs text-gray-500 mt-1">Grab & earn</span>
                    </div>
                    <div class="bg-white/90 rounded-xl shadow-md px-6 py-4 flex flex-col items-center w-40 min-h-[110px]">
                        <img src="/assets/platforms/shopify.png" alt="Shopify" class="h-8 mb-2 object-contain">
                        <span class="text-gray-900 font-semibold text-sm">Shopify</span>
                        <span class="text-xs text-gray-500 mt-1">Grab & earn</span>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <Link
                        v-if="!page.props.auth.user"
                        :href="route('register')"
                        class="bg-white text-blue-600 px-6 sm:px-8 py-3 rounded-full font-semibold text-base sm:text-lg hover:bg-gray-100 transition-all duration-300 text-center"
                    >
                        Start Earning Now
                    </Link>
                    <Link
                        v-if="page.props.auth.user"
                        :href="route('dashboard')"
                        class="bg-white text-blue-600 px-6 sm:px-8 py-3 rounded-full font-semibold text-base sm:text-lg hover:bg-gray-100 transition-all duration-300 text-center"
                    >
                        Go to Dashboard
                    </Link>
                </div>
            </div>
        </section>
    <!-- How It Works (Money Earning) -->
    <section id="how-it-works" class="py-16 bg-white">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-gray-900">
                    How does Task App work for online money earning
                </h2>
                <div class="grid md:grid-cols-3 gap-8 mb-8">
                    <div class="bg-blue-100 rounded-xl p-8 flex flex-col items-start text-left">
                        <div class="mb-4">
                            <svg width="40" height="40" fill="none" viewBox="0 0 40 40"><circle cx="20" cy="20" r="20" fill="#2563eb" opacity=".15"/><path d="M20 12a8 8 0 100 16 8 8 0 000-16zm0 14.4A6.4 6.4 0 1120 13.6a6.4 6.4 0 010 12.8z" fill="#2563eb"/></svg>
                        </div>
                        <div class="font-semibold text-lg text-blue-900 mb-1">Step 1.</div>
                        <div class="font-bold text-xl text-blue-900 mb-2">Sign up</div>
                        <div class="text-gray-700">Create an account.</div>
                    </div>
                    <div class="bg-blue-500 rounded-xl p-8 flex flex-col items-start text-left">
                        <div class="mb-4">
                            <svg width="40" height="40" fill="none" viewBox="0 0 40 40"><circle cx="20" cy="20" r="20" fill="#fff" opacity=".15"/><path d="M28 16.5l-8.25 8.25L12 17.5" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <div class="font-semibold text-lg text-white mb-1">Step 2.</div>
                        <div class="font-bold text-xl text-white mb-2">Complete tasks</div>
                        <div class="text-white">Browse a wide range of tasks, pick the easiest way to earn online.</div>
                    </div>
                    <div class="bg-blue-700 rounded-xl p-8 flex flex-col items-start text-left">
                        <div class="mb-4">
                            <svg width="40" height="40" fill="none" viewBox="0 0 40 40"><circle cx="20" cy="20" r="20" fill="#fff" opacity=".15"/><path d="M13 25h14M13 25v-2.5a2.5 2.5 0 012.5-2.5h9a2.5 2.5 0 012.5 2.5V25m-14 0v2.5A2.5 2.5 0 0015.5 30h9a2.5 2.5 0 002.5-2.5V25" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <div class="font-semibold text-lg text-white mb-1">Step 3.</div>
                        <div class="font-bold text-xl text-white mb-2">Get paid</div>
                        <div class="text-white">Follow the task guidelines, complete tasks, and get rewarded. Easily transfer your earnings to your crypto wallet.</div>
                    </div>
                </div>
                <div class="flex justify-center mb-8">
                    <Link
                        :href="route('register')"
                        class="bg-blue-500 hover:bg-blue-600 text-white font-semibold px-6 sm:px-8 py-3 rounded-full text-base sm:text-lg transition-all duration-300 inline-block text-center"
                    >
                        Start earning now
                    </Link>
                </div>
                <div class="flex flex-wrap justify-center items-center gap-8 opacity-80">
                    <img src="/assets/platforms/dailycoin.png" alt="DailyCoin" class="h-8 w-auto">
                    <img src="/assets/platforms/yahofinance.png" alt="Yahoo Finance" class="h-8 w-auto">
                    <img src="/assets/platforms/benzinga.png" alt="Benzinga" class="h-8 w-auto">
                    <img src="/assets/platforms/businessinside.png" alt="Business Insider" class="h-8 w-auto">
                    <img src="/assets/platforms/marketwatch.png" alt="MarketWatch" class="h-8 w-auto">
                </div>
            </div>
        </section>

    <!-- Our Vision -->
    <section id="for-business" class="py-20 bg-white">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-5xl font-extrabold mb-6">Our vision</h2>
                <p class="text-lg md:text-xl text-gray-700 mb-10 max-w-3xl mx-auto">
                    Task App helps people make money online by offering simple and easy ways to earn. Our platform gives everyone access to a wide variety of small tasks, allowing users to earn extra income from anywhere. With no special skills needed, Task App makes it possible for anyone to start earning and be part of the online economy.
                </p>
                <div class="grid md:grid-cols-3 gap-8 mt-8">
                    <!-- Blue Card -->
                    <div class="rounded-2xl bg-blue-600 text-white flex flex-col items-center justify-center relative overflow-hidden" style="min-height: 240px;">
                        <svg class="absolute left-0 top-0 w-full h-full" viewBox="0 0 400 240" fill="none">
                            <circle cx="80" cy="220" r="120" fill="#2563eb" fill-opacity="0.7" />
                            <circle cx="220" cy="220" r="80" fill="#1d4ed8" fill-opacity="0.7" />
                            <circle cx="320" cy="180" r="60" fill="#2563eb" fill-opacity="0.4" />
                        </svg>
                        <div class="relative z-10 w-full text-center flex flex-col justify-center h-full">
                            <div class="text-xl md:text-2xl font-bold mb-2 mt-2">Total paid out</div>
                            <div class="text-2xl md:text-3xl font-extrabold tracking-tight">3,621,117</div>
                        </div>
                    </div>
                    <!-- Purple Card -->
                    <div class="rounded-2xl bg-purple-500 text-white flex flex-col items-center justify-center relative overflow-hidden" style="min-height: 240px;">
                        <svg class="absolute left-0 top-0 w-full h-full" viewBox="0 0 400 240" fill="none">
                            <ellipse cx="90" cy="180" rx="30" ry="30" stroke="#fff" stroke-width="3" opacity="0.5" />
                            <ellipse cx="150" cy="200" rx="40" ry="40" stroke="#fff" stroke-width="3" opacity="0.5" />
                            <ellipse cx="250" cy="180" rx="60" ry="60" stroke="#fff" stroke-width="3" opacity="0.5" />
                            <ellipse cx="320" cy="120" rx="80" ry="80" stroke="#fff" stroke-width="3" opacity="0.5" />
                        </svg>
                        <div class="relative z-10 w-full text-center flex flex-col justify-center h-full">
                            <div class="text-xl md:text-2xl font-bold mb-2 mt-2">Registered users</div>
                            <div class="text-2xl md:text-3xl font-extrabold tracking-tight">12,883,854</div>
                        </div>
                    </div>
                    <!-- Black Card -->
                    <div class="rounded-2xl bg-gray-900 text-white flex flex-col items-center justify-center relative overflow-hidden" style="min-height: 240px;">
                        <svg class="absolute left-0 top-0 w-full h-full" viewBox="0 0 400 240" fill="none">
                            <circle cx="200" cy="200" r="100" stroke="#2563eb" stroke-width="3" opacity="0.5" />
                            <circle cx="200" cy="200" r="70" stroke="#2563eb" stroke-width="3" opacity="0.5" />
                            <circle cx="200" cy="200" r="40" stroke="#2563eb" stroke-width="3" opacity="0.5" />
                            <circle cx="320" cy="100" r="6" fill="#2563eb" />
                            <circle cx="100" cy="160" r="6" fill="#2563eb" />
                        </svg>
                        <div class="relative z-10 w-full text-center flex flex-col justify-center h-full">
                            <div class="text-xl md:text-2xl font-bold mb-2 mt-2">Completed payouts</div>
                            <div class="text-2xl md:text-3xl font-extrabold tracking-tight">2,637,650</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Ways to Earn -->
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center mb-12 text-gray-800">
                    Ways to Earn with Task App
                </h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Card 1 -->
                    <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center text-center h-full">
                        <svg width="56" height="56" viewBox="0 0 56 56" fill="none" class="mb-4">
                            <defs>
                                <linearGradient id="boostGradient" x1="0" y1="0" x2="56" y2="56" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#6366f1"/>
                                    <stop offset="1" stop-color="#3b82f6"/>
                                </linearGradient>
                            </defs>
                            <rect x="8" y="16" width="40" height="16" rx="8" fill="url(#boostGradient)"/>
                            <rect x="16" y="36" width="24" height="8" rx="4" fill="#e0e7ff"/>
                            <circle cx="28" cy="28" r="26" stroke="#e0e7ff" stroke-width="2"/>
                        </svg>
                        <h3 class="text-xl font-semibold mb-2">Grab & Boost Products</h3>
                        <p class="text-gray-600">Select products from top e-commerce stores, complete boost tasks, and help increase their sales.</p>
                    </div>
                    <!-- Card 2 -->
                    <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center text-center h-full">
                        <svg width="56" height="56" viewBox="0 0 56 56" fill="none" class="mb-4">
                            <defs>
                                <linearGradient id="commissionGradient" x1="0" y1="0" x2="56" y2="56" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#3b82f6"/>
                                    <stop offset="1" stop-color="#6366f1"/>
                                </linearGradient>
                            </defs>
                            <rect x="12" y="24" width="32" height="12" rx="6" fill="url(#commissionGradient)"/>
                            <circle cx="28" cy="28" r="26" stroke="#e0e7ff" stroke-width="2"/>
                            <rect x="20" y="12" width="16" height="8" rx="4" fill="#a5b4fc"/>
                        </svg>
                        <h3 class="text-xl font-semibold mb-2">Earn Commission</h3>
                        <p class="text-gray-600">Earn a commission for every successful product boost and sale you help generate.</p>
                    </div>
                    <!-- Card 3 -->
                    <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center text-center h-full">
                        <svg width="56" height="56" viewBox="0 0 56 56" fill="none" class="mb-4">
                            <defs>
                                <linearGradient id="storesGradient" x1="0" y1="0" x2="56" y2="56" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#a5b4fc"/>
                                    <stop offset="1" stop-color="#6366f1"/>
                                </linearGradient>
                            </defs>
                            <rect x="16" y="32" width="24" height="8" rx="4" fill="url(#storesGradient)"/>
                            <rect x="20" y="16" width="16" height="8" rx="4" fill="#e0e7ff"/>
                            <circle cx="28" cy="28" r="26" stroke="#e0e7ff" stroke-width="2"/>
                        </svg>
                        <h3 class="text-xl font-semibold mb-2">Multiple Stores</h3>
                        <p class="text-gray-600">Access tasks from Alibaba, Amazon, eBay, Walmart, AliExpress, Etsy, Shopify, and more.</p>
                    </div>
                    <!-- Card 4 -->
                    <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center text-center h-full">
                        <svg width="56" height="56" viewBox="0 0 56 56" fill="none" class="mb-4">
                            <defs>
                                <linearGradient id="referGradient" x1="0" y1="0" x2="56" y2="56" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#3b82f6"/>
                                    <stop offset="1" stop-color="#a5b4fc"/>
                                </linearGradient>
                            </defs>
                            <ellipse cx="28" cy="36" rx="12" ry="8" fill="url(#referGradient)"/>
                            <circle cx="28" cy="20" r="8" fill="#e0e7ff"/>
                            <circle cx="28" cy="28" r="26" stroke="#e0e7ff" stroke-width="2"/>
                        </svg>
                        <h3 class="text-xl font-semibold mb-2">Refer Friends</h3>
                        <p class="text-gray-600">Invite friends and earn a bonus every time they complete their first boost task.</p>
                    </div>
                    <!-- Card 5 -->
                    <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center text-center h-full">
                        <svg width="56" height="56" viewBox="0 0 56 56" fill="none" class="mb-4">
                            <defs>
                                <linearGradient id="repeatGradient" x1="0" y1="0" x2="56" y2="56" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#6366f1"/>
                                    <stop offset="1" stop-color="#3b82f6"/>
                                </linearGradient>
                            </defs>
                            <rect x="16" y="32" width="24" height="8" rx="4" fill="url(#repeatGradient)"/>
                            <rect x="20" y="16" width="16" height="8" rx="4" fill="#e0e7ff"/>
                            <circle cx="28" cy="28" r="26" stroke="#e0e7ff" stroke-width="2"/>
                        </svg>
                        <h3 class="text-xl font-semibold mb-2">Repeat & Earn More</h3>
                        <p class="text-gray-600">The more products you boost, the more you can earn. There’s no limit!</p>
                    </div>
                </div>
            </div>
        </section>

    <!-- Why Choose -->
    <section id="learn" class="py-16 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-gray-900">
                    Why choose Task App to earn money online?
                </h2>
                <p class="text-center text-gray-600 mb-12 max-w-2xl mx-auto">
                    Task App is a leading platform with thousands of users worldwide, offering endless opportunities to make money online easily and securely.
                </p>
                <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Card 1 -->
                    <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center text-center h-full">
                        <svg width="72" height="72" viewBox="0 0 72 72" fill="none" class="mb-4">
                            <defs>
                                <linearGradient id="tasksGradient" x1="0" y1="0" x2="72" y2="72" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#6366f1"/>
                                    <stop offset="1" stop-color="#3b82f6"/>
                                </linearGradient>
                            </defs>
                            <rect x="8" y="16" width="56" height="16" rx="8" fill="url(#tasksGradient)"/>
                            <rect x="16" y="36" width="40" height="12" rx="6" fill="#e0e7ff"/>
                            <rect x="24" y="52" width="24" height="8" rx="4" fill="#a5b4fc"/>
                        </svg>
                        <h3 class="text-lg font-semibold mb-2">Thousands of tasks</h3>
                        <p class="text-gray-600 text-sm">Access a huge variety of simple online tasks—no special skills needed. Earn in your spare time, anytime.</p>
                    </div>
                    <!-- Card 2 -->
                    <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center text-center h-full">
                        <svg width="72" height="72" viewBox="0 0 72 72" fill="none" class="mb-4">
                            <defs>
                                <linearGradient id="anywhereGradient" x1="0" y1="0" x2="72" y2="72" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#3b82f6"/>
                                    <stop offset="1" stop-color="#6366f1"/>
                                </linearGradient>
                            </defs>
                            <circle cx="36" cy="36" r="28" fill="#e0e7ff"/>
                            <circle cx="36" cy="36" r="20" fill="url(#anywhereGradient)"/>
                            <rect x="28" y="52" width="16" height="8" rx="4" fill="#a5b4fc"/>
                        </svg>
                        <h3 class="text-lg font-semibold mb-2">Earn anywhere, anytime</h3>
                        <p class="text-gray-600 text-sm">Work from home or on the go. Earn extra income from your phone, tablet, or computer—whenever you want.</p>
                    </div>
                    <!-- Card 3 -->
                    <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center text-center h-full">
                        <svg width="72" height="72" viewBox="0 0 72 72" fill="none" class="mb-4">
                            <defs>
                                <linearGradient id="paidGradient" x1="0" y1="0" x2="72" y2="72" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#a5b4fc"/>
                                    <stop offset="1" stop-color="#6366f1"/>
                                </linearGradient>
                            </defs>
                            <rect x="16" y="32" width="40" height="16" rx="8" fill="url(#paidGradient)"/>
                            <rect x="24" y="20" width="24" height="8" rx="4" fill="#e0e7ff"/>
                            <rect x="28" y="52" width="16" height="8" rx="4" fill="#3b82f6"/>
                        </svg>
                        <h3 class="text-lg font-semibold mb-2">Instant payouts</h3>
                        <p class="text-gray-600 text-sm">Get paid as soon as you finish a task. Withdraw your earnings instantly—no waiting, no hassle.</p>
                    </div>
                    <!-- Card 4 -->
                    <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center text-center h-full">
                        <svg width="72" height="72" viewBox="0 0 72 72" fill="none" class="mb-4">
                            <defs>
                                <linearGradient id="supportGradient" x1="0" y1="0" x2="72" y2="72" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#3b82f6"/>
                                    <stop offset="1" stop-color="#a5b4fc"/>
                                </linearGradient>
                            </defs>
                            <ellipse cx="36" cy="36" rx="28" ry="20" fill="#e0e7ff"/>
                            <ellipse cx="36" cy="36" rx="20" ry="12" fill="url(#supportGradient)"/>
                            <rect x="28" y="52" width="16" height="8" rx="4" fill="#6366f1"/>
                        </svg>
                        <h3 class="text-lg font-semibold mb-2">24/7 support</h3>
                        <p class="text-gray-600 text-sm">Get help whenever you need it. Our team and community are always here to support your earning journey.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Devices -->
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center mb-12 text-gray-800">
                    Earn from Any Device
                </h2>
                <div class="grid md:grid-cols-3 gap-8 text-center">
                    <!-- Card 1: Mobile -->
                    <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center text-center h-full">
                        <svg width="56" height="56" viewBox="0 0 56 56" fill="none" class="mb-4">
                            <rect x="16" y="8" width="24" height="40" rx="8" fill="#e0e7ff"/>
                            <rect x="22" y="14" width="12" height="28" rx="4" fill="#3b82f6"/>
                            <circle cx="28" cy="46" r="2" fill="#6366f1"/>
                        </svg>
                        <h3 class="text-xl font-semibold mb-2">Earn on Mobile</h3>
                        <p class="text-gray-600">Complete tasks and earn money from your phone, wherever you are.</p>
                    </div>
                    <!-- Card 2: Tablet -->
                    <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center text-center h-full">
                        <svg width="56" height="56" viewBox="0 0 56 56" fill="none" class="mb-4">
                            <rect x="10" y="12" width="36" height="32" rx="6" fill="#e0e7ff"/>
                            <rect x="16" y="18" width="24" height="20" rx="4" fill="#6366f1"/>
                            <circle cx="28" cy="40" r="2" fill="#3b82f6"/>
                        </svg>
                        <h3 class="text-xl font-semibold mb-2">Earn on Tablet</h3>
                        <p class="text-gray-600">Enjoy a bigger screen for multitasking and boosting your earnings.</p>
                    </div>
                    <!-- Card 3: Desktop -->
                    <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center text-center h-full">
                        <svg width="56" height="56" viewBox="0 0 56 56" fill="none" class="mb-4">
                            <rect x="8" y="16" width="40" height="24" rx="6" fill="#e0e7ff"/>
                            <rect x="14" y="22" width="28" height="12" rx="3" fill="#3b82f6"/>
                            <rect x="22" y="42" width="12" height="4" rx="2" fill="#6366f1"/>
                        </svg>
                        <h3 class="text-xl font-semibold mb-2">Earn on Desktop</h3>
                        <p class="text-gray-600">Maximize productivity and manage more tasks with ease.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-16 bg-blue-600 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-bold mb-6">Turn your spare time into earnings today.</h2>
                <p class="text-xl mb-8">Join thousands of sellers maximizing their product potential.</p>
                <Link
                    v-if="!page.props.auth.user"
                    :href="route('register')"
                    class="bg-white text-blue-600 px-6 sm:px-8 py-3 rounded-full font-semibold text-base sm:text-lg hover:bg-gray-100 transition-all duration-300 inline-block text-center"
                >
                    Get Started
                </Link>
            </div>
        </section>
    </div>
</template>