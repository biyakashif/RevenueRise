<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const page = usePage();
const translations = computed(() => page.props.translations || {});
const t = (key) => translations.value[key] || key;
</script>

<template>
    <Head :title="t('Agency & Promotion Service')" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30 flex flex-col items-center justify-center">
            <div class="max-w-3xl w-full mx-auto">
                <div class="bg-gradient-to-br from-white/40 via-white/30 to-white/20 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl border border-white/30 p-6 sm:p-10 mb-8">
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-800 mb-4 drop-shadow-sm text-center">{{ t('Agency & Promotion Service') }}</h1>
                    <p class="text-base sm:text-lg text-slate-700 mb-4 text-center">
                        {{ t('Partner with Revenue Rise to promote products, boost sales, and grow your agency business!') }}
                    </p>
                    <p class="text-base sm:text-lg text-slate-700 mb-2 text-center">
                        {{ t('Join our agency program, access exclusive campaigns, and help brands reach new customers. Rate and review stores, complete promotional tasks, and earn more as a trusted partner.') }}
                    </p>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl shadow-lg border border-cyan-300/30 p-6 flex flex-col items-center text-center">
                        <svg width="48" height="48" fill="none" viewBox="0 0 48 48" class="mb-3">
                            <circle cx="24" cy="24" r="24" fill="#06b6d4" fill-opacity="0.15" />
                            <path d="M16 24h16M24 16v16" stroke="#06b6d4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h2 class="text-lg font-bold text-slate-800 mb-1">{{ t('Agency Program') }}</h2>
                        <p class="text-slate-700 text-sm">{{ t('Become an official Revenue Rise agency, manage teams, and unlock higher commissions for your network.') }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-purple-400/20 via-blue-500/10 to-indigo-600/20 backdrop-blur-xl rounded-2xl shadow-lg border border-purple-300/30 p-6 flex flex-col items-center text-center">
                        <svg width="48" height="48" fill="none" viewBox="0 0 48 48" class="mb-3">
                            <circle cx="24" cy="24" r="24" fill="#a78bfa" fill-opacity="0.15" />
                            <path d="M16 32l8-8 8 8" stroke="#a78bfa" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h2 class="text-lg font-bold text-slate-800 mb-1">{{ t('Promotion & Reviews') }}</h2>
                        <p class="text-slate-700 text-sm">{{ t('Promote products, rate and review stores, and help brands build trust with real feedback.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
