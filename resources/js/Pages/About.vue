
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const page = usePage();
const translations = computed(() => page.props.translations || {});
const t = (key) => translations.value[key] || key;
</script>

<template>
    <Head :title="t('About Revenue Rise')" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30 flex flex-col items-center justify-center">
            <div class="max-w-4xl w-full mx-auto">
                <div class="bg-gradient-to-br from-white/40 via-white/30 to-white/20 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl border border-white/30 p-6 sm:p-10 mb-8">
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-800 mb-4 drop-shadow-sm text-center">{{ t('About Revenue Rise') }}</h1>
                    <p class="text-base sm:text-lg text-slate-700 mb-4 text-center">
                        {{ t('Revenue Rise is a modern platform that empowers anyone to earn money online by completing simple tasks for top e-commerce brands. Our mission is to make online earning accessible, transparent, and rewarding for everyone—no special skills required!') }}
                    </p>
                    <p class="text-base sm:text-lg text-slate-700 mb-2 text-center">
                        {{ t('With Revenue Rise, you can boost products, earn commissions, and grow your income from anywhere, on any device. Join thousands of users who are maximizing their potential and turning spare time into real earnings!') }}
                    </p>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl shadow-lg border border-cyan-300/30 p-6 flex flex-col items-center text-center">
                        <svg width="48" height="48" fill="none" viewBox="0 0 48 48" class="mb-3">
                            <circle cx="24" cy="24" r="24" fill="#06b6d4" fill-opacity="0.15" />
                            <path d="M16 24h16M24 16v16" stroke="#06b6d4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h2 class="text-lg font-bold text-slate-800 mb-1">{{ t('Simple Tasks, Real Rewards') }}</h2>
                        <p class="text-slate-700 text-sm">{{ t('Complete easy online tasks for leading stores like Amazon, Alibaba, eBay, and more. No experience needed—just grab, boost, and earn!') }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-purple-400/20 via-blue-500/10 to-indigo-600/20 backdrop-blur-xl rounded-2xl shadow-lg border border-purple-300/30 p-6 flex flex-col items-center text-center">
                        <svg width="48" height="48" fill="none" viewBox="0 0 48 48" class="mb-3">
                            <circle cx="24" cy="24" r="24" fill="#a78bfa" fill-opacity="0.15" />
                            <path d="M16 32l8-8 8 8" stroke="#a78bfa" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h2 class="text-lg font-bold text-slate-800 mb-1">{{ t('Instant Payouts') }}</h2>
                        <p class="text-slate-700 text-sm">{{ t('Withdraw your earnings instantly to your crypto wallet. No waiting, no hassle—just fast, secure payments.') }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-blue-400/20 via-blue-500/10 to-indigo-600/20 backdrop-blur-xl rounded-2xl shadow-lg border border-blue-300/30 p-6 flex flex-col items-center text-center">
                        <svg width="48" height="48" fill="none" viewBox="0 0 48 48" class="mb-3">
                            <circle cx="24" cy="24" r="24" fill="#3b82f6" fill-opacity="0.15" />
                            <path d="M16 24h16" stroke="#3b82f6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h2 class="text-lg font-bold text-slate-800 mb-1">{{ t('Earn Anywhere, Anytime') }}</h2>
                        <p class="text-slate-700 text-sm">{{ t('Work from your phone, tablet, or desktop—Revenue Rise is always with you. Flexible earning, on your schedule!') }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-pink-400/20 via-blue-500/10 to-indigo-600/20 backdrop-blur-xl rounded-2xl shadow-lg border border-pink-300/30 p-6 flex flex-col items-center text-center">
                        <svg width="48" height="48" fill="none" viewBox="0 0 48 48" class="mb-3">
                            <circle cx="24" cy="24" r="24" fill="#ec4899" fill-opacity="0.15" />
                            <path d="M24 16v16" stroke="#ec4899" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h2 class="text-lg font-bold text-slate-800 mb-1">{{ t('24/7 Support & Community') }}</h2>
                        <p class="text-slate-700 text-sm">{{ t('Get help whenever you need it. Our team and community are always here to support your earning journey.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
