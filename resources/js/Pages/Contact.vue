<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    contactSettings: Object,
});

const page = usePage();
const translations = computed(() => page.props.translations || {});
const t = (key) => translations.value[key] || key;
</script>

<template>
    <Head :title="t('Contact')" />
    <AuthenticatedLayout>
        <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-3 sm:p-4 rounded-2xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto">
            <div class="max-w-2xl w-full mx-auto">
                <div class="bg-gradient-to-br from-white/40 via-white/30 to-white/20 backdrop-blur-xl rounded-xl shadow-xl border border-white/30 p-4 mb-4">
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-800 mb-2 text-center">{{ t('Contact') }}</h1>
                    <p class="text-sm text-slate-700 text-center">
                        {{ t('Need help? Contact our support team 24/7!') }}
                    </p>
                </div>

                <div class="space-y-3">
                    <!-- Email Contact -->
                    <div v-if="contactSettings?.show_email && contactSettings?.email" class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-xl shadow-lg border border-cyan-300/30 p-3 flex items-center">
                        <div class="w-10 h-10 mr-3 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-sm font-bold text-slate-800">{{ t('Email Support') }}</h2>
                            <a :href="`mailto:${contactSettings.email}`" class="text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors">{{ contactSettings.email }}</a>
                        </div>
                    </div>

                    <!-- WhatsApp Contact -->
                    <div v-if="contactSettings?.show_whatsapp && contactSettings?.whatsapp" class="bg-gradient-to-br from-green-400/20 via-green-500/15 to-green-600/20 backdrop-blur-xl rounded-xl shadow-lg border border-green-300/30 p-3 flex items-center">
                        <div class="w-10 h-10 mr-3 bg-gradient-to-br from-green-400 to-green-600 rounded-lg flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.465 3.488"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-sm font-bold text-slate-800">{{ t('WhatsApp') }}</h2>
                            <a :href="`https://wa.me/${contactSettings.whatsapp.replace(/[^0-9]/g, '')}`" target="_blank" class="text-green-600 hover:text-green-800 text-sm font-medium transition-colors">{{ contactSettings.whatsapp }}</a>
                        </div>
                    </div>

                    <!-- Telegram Contact -->
                    <div v-if="contactSettings?.show_telegram && contactSettings?.telegram" class="bg-gradient-to-br from-blue-400/20 via-blue-500/15 to-blue-600/20 backdrop-blur-xl rounded-xl shadow-lg border border-blue-300/30 p-3 flex items-center">
                        <div class="w-10 h-10 mr-3 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-sm font-bold text-slate-800">{{ t('Telegram') }}</h2>
                            <a :href="contactSettings.telegram.startsWith('http') ? contactSettings.telegram : `https://t.me/${contactSettings.telegram.replace('@', '')}`" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors">{{ contactSettings.telegram }}</a>
                        </div>
                    </div>

                    <!-- Office Address -->
                    <div v-if="contactSettings?.show_office && contactSettings?.office_address" class="bg-gradient-to-br from-purple-400/20 via-purple-500/15 to-purple-600/20 backdrop-blur-xl rounded-xl shadow-lg border border-purple-300/30 p-3 flex items-start">
                        <div class="w-10 h-10 mr-3 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-sm font-bold text-slate-800 mb-1">{{ t('Office Address') }}</h2>
                            <p class="text-slate-700 text-sm leading-relaxed">{{ contactSettings.office_address }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
