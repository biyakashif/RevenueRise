<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import fcaImage from '@/assets/fca.png';

const page = usePage();
const translations = computed(() => page.props.translations || {});
const t = (key) => translations.value[key] || key;
</script>

<template>
    <Head :title="t('Certificate')" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30 flex flex-col items-center justify-center">
            <div class="max-w-2xl w-full mx-auto">
                <div class="bg-gradient-to-br from-white/40 via-white/30 to-white/20 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl border border-white/30 p-6 sm:p-10 mb-8 flex flex-col items-center">
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-800 mb-4 drop-shadow-sm text-center">
                        {{ t('Agency Certificate') }}
                    </h1>
                    <p class="text-base sm:text-lg text-slate-700 mb-6 text-center">
                        {{ t('Congratulations on joining our agency program! As a valued member, you can earn commissions, rate and review stores, and access exclusive opportunities.') }}
                    </p>
                    <div class="w-full flex justify-center mb-6">
                        <div class="w-full max-w-xs aspect-[3/4] bg-slate-200/60 border-2 border-dashed border-cyan-300/40 rounded-xl flex items-center justify-center overflow-hidden">
                            <img :src="fcaImage" alt="Certificate Preview" class="object-contain w-full h-full" />
                        </div>
                    </div>
                    <div class="text-slate-600 text-sm text-center">
                        {{ t('Your official certificate will appear here once you complete the required steps. You can update or download it at any time.') }}
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
