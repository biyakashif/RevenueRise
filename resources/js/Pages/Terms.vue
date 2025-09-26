<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
const { translations = {} } = usePage().props;
const t = (key) => translations[key] || key;
</script>

<template>
    <Head :title="t('T&C')" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30 flex flex-col items-center justify-center">
            <div class="max-w-3xl w-full mx-auto">
                <div class="bg-gradient-to-br from-white/40 via-white/30 to-white/20 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl border border-white/30 p-6 sm:p-10 mb-8">
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-800 mb-4 drop-shadow-sm text-center">
                        {{ t('Terms & Conditions') }}
                    </h1>
                    <h2 class="text-2xl font-bold text-slate-800 mb-4 text-center">
                        {{ t('Agency & Promotion Program Terms') }}
                    </h2>
                    <p class="text-base sm:text-lg text-slate-700 mb-6 text-center">
                        {{ t('By joining our agency program, you agree to promote our platform and earn commissions for successful referrals and activities. Please read the following terms carefully:') }}
                    </p>
                    <ul class="space-y-4 text-slate-700 list-disc list-inside mb-8 text-left">
                        <li>
                            {{ t('You must represent our platform honestly and professionally in all promotions and communications.') }}
                        </li>
                        <li>
                            {{ t('Commissions are earned based on successful referrals and completed activities as outlined in your agency dashboard.') }}
                        </li>
                        <li>
                            {{ t('You may rate and review stores such as Alibaba, Amazon, and eBay as part of our community engagement.') }}
                        </li>
                        <li>
                            {{ t('All ratings and reviews must be honest, unbiased, and based on genuine experiences.') }}
                        </li>
                        <li>
                            {{ t('Any misuse, fraudulent activity, or violation of these terms may result in removal from the program and forfeiture of commissions.') }}
                        </li>
                        <li>
                            {{ t('We reserve the right to update these terms at any time. Continued participation constitutes acceptance of any changes.') }}
                        </li>
                    </ul>
                    <div class="text-slate-600 text-sm text-center">
                        {{ t('For questions or clarifications, please contact our support team.') }}
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
