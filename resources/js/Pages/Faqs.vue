<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const page = usePage();
const translations = computed(() => page.props.translations || {});
const t = (key) => translations.value[key] || key;
</script>

<template>
    <Head :title="t('FAQs')" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30 flex flex-col items-center justify-center">
            <div class="max-w-3xl w-full mx-auto">
                <div class="bg-gradient-to-br from-white/40 via-white/30 to-white/20 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl border border-white/30 p-6 sm:p-10 mb-8">
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-800 mb-4 drop-shadow-sm text-center">{{ t('FAQs about Earning & Promotion') }}</h1>
                    <p class="text-base sm:text-lg text-slate-700 mb-4 text-center">
                        {{ t('Find answers to the most common questions about earning, commissions, and agency opportunities with Revenue Rise.') }}
                    </p>
                </div>

                <div class="space-y-6">
                    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl shadow-lg border border-cyan-300/30 p-6">
                        <h2 class="text-lg font-bold text-slate-800 mb-2">{{ t('How do I earn commissions?') }}</h2>
                        <p class="text-slate-700 text-sm">{{ t('You earn commissions by completing product boost tasks and helping increase sales for our partner stores. Each completed task adds to your earnings, which you can withdraw instantly.') }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-purple-400/20 via-blue-500/10 to-indigo-600/20 backdrop-blur-xl rounded-2xl shadow-lg border border-purple-300/30 p-6">
                        <h2 class="text-lg font-bold text-slate-800 mb-2">{{ t('How do I join the agency program?') }}</h2>
                        <p class="text-slate-700 text-sm">{{ t('Apply through your dashboard to become an official Revenue Rise agency. Manage your own team, access exclusive campaigns, and earn higher commissions for your network.') }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-pink-400/20 via-blue-500/10 to-indigo-600/20 backdrop-blur-xl rounded-2xl shadow-lg border border-pink-300/30 p-6">
                        <h2 class="text-lg font-bold text-slate-800 mb-2">{{ t('Can I rate and review stores?') }}</h2>
                        <p class="text-slate-700 text-sm">{{ t('Yes! After completing certain tasks, you can rate and review stores to help brands build trust and improve their services. Your feedback is valuable and rewarded.') }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl shadow-lg border border-cyan-300/30 p-6">
                        <h2 class="text-lg font-bold text-slate-800 mb-2">{{ t('How do I reset the Orders?') }}</h2>
                        <p class="text-slate-700 text-sm">{{ t('🔄 To reset your order, you need to maintain your account balance at around $100 to easily reset them. Once you reach this amount, your account will automatically reset, and new tasks will become available.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
