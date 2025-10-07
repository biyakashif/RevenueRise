<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import VIP1Icon from '@/assets/VIP1.png';
import VIP2Icon from '@/assets/VIP2.png';
import VIP3Icon from '@/assets/VIP3.png';
import VIP4Icon from '@/assets/VIP4.png';
import VIP5Icon from '@/assets/VIP5.png';
import VIP6Icon from '@/assets/VIP6.png';
import VIP7Icon from '@/assets/VIP7.png';

const page = usePage();
const props = computed(() => page.props);
const translations = computed(() => page.props.translations || {});
const t = (key) => translations.value[key] || key;

const urlLevel = computed(() => (props.value.ziggy?.current?.params?.level) || (new URLSearchParams(window.location.search)).get('level') || props.value.level || 'VIP2');

const priceMapping = {
  VIP1: null,
  VIP2: 300.0,
  VIP3: 750.0,
  VIP4: 1500.0,
  VIP5: 3500.0,
  VIP6: 6500.0,
  VIP7: 10000.0
};

const vipIcons = {
  VIP1: VIP1Icon,
  VIP2: VIP2Icon,
  VIP3: VIP3Icon,
  VIP4: VIP4Icon,
  VIP5: VIP5Icon,
  VIP6: VIP6Icon,
  VIP7: VIP7Icon
};

const commissionMapping = {
  VIP1: { min: 3, max: 9 },
  VIP2: { min: 10, max: 20 },
  VIP3: { min: 25, max: 50 },
  VIP4: { min: 40, max: 100 },
  VIP5: { min: 50, max: 130 },
  VIP6: { min: 60, max: 160 },
  VIP7: { min: 70, max: 200 }
};

const vipColors = {
  VIP1: 'from-gray-500 to-slate-600',
  VIP2: 'from-blue-500 to-blue-600', 
  VIP3: 'from-yellow-600 to-amber-700',
  VIP4: 'from-purple-500 to-violet-600',
  VIP5: 'from-orange-500 to-amber-600',
  VIP6: 'from-red-500 to-rose-600',
  VIP7: 'from-yellow-500 to-yellow-600'
};



const levelPrice = computed(() => props.value.levelPrice ?? (priceMapping[urlLevel.value] ?? null));
const displayPrice = computed(() => (levelPrice.value !== null ? Number(levelPrice.value).toFixed(2) + ' USDT' : '—'));
const vipIcon = computed(() => vipIcons[urlLevel.value] || VIP2Icon);
const dailyEarnings = computed(() => commissionMapping[urlLevel.value] || { min: 3, max: 9 });
const vipColor = computed(() => vipColors[urlLevel.value] || 'from-cyan-500 to-blue-600');

</script>

<template>
  <Head :title="t('Purchase VIP')" />
  <AuthenticatedLayout>
    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-none sm:shadow-2xl border-none sm:border sm:border-cyan-300/30 w-full flex flex-col justify-between">
          
          <!-- Header with Back Button -->
          <div class="flex justify-between items-center mb-3">
            <div class="flex items-center gap-3">
              <Link href="/dashboard" class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-slate-500/80 to-gray-600/80 hover:from-slate-600/90 hover:to-gray-700/90 rounded-xl shadow-lg transition-all duration-200 transform hover:scale-105">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
              </Link>
              <div>
                <h1 class="text-md font-bold text-slate-800 drop-shadow-sm">{{ t('Purchase VIP') }}</h1>
                <p class="text-sm text-slate-600 font-medium">{{ t('Upgrade your account') }}</p>
              </div>
            </div>
          </div>

          <!-- VIP Icon -->
          <div class="mb-3 flex justify-center">
            <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-gradient-to-br from-white/40 via-white/30 to-white/20 backdrop-blur-sm shadow-2xl border border-white/30 flex items-center justify-center">
              <img :src="vipIcon" :alt="urlLevel" class="w-16 h-16 sm:w-20 sm:h-20 rounded-full object-cover shadow-lg" />
            </div>
          </div>

          <!-- VIP Level Title -->
          <div class="mb-3 text-center">
            <h2 class="text-xl sm:text-2xl font-bold bg-gradient-to-r bg-clip-text text-transparent drop-shadow-sm mb-1" :class="`bg-gradient-to-r ${vipColor}`">
              {{ urlLevel }}
            </h2>
            <div class="bg-gradient-to-r from-white/50 via-white/40 to-white/30 backdrop-blur-sm px-4 py-2 rounded-full border border-white/40 shadow-lg">
              <span class="text-xs text-slate-600 font-medium">{{ t('Price') }}</span>
              <div class="text-lg font-bold bg-gradient-to-r bg-clip-text text-transparent" :class="`bg-gradient-to-r ${vipColor}`">
                {{ displayPrice }}
              </div>
            </div>
          </div>

          <!-- Daily Earnings -->
          <div class="mb-3 bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm p-3 rounded-xl border border-white/30 shadow-lg">
            <h3 class="text-xs font-semibold text-emerald-700 mb-1 text-center">{{ t('Daily Commission') }}</h3>
            <div class="text-md font-bold text-emerald-800 text-center">
              ${{ dailyEarnings.min }} - ${{ dailyEarnings.max }}
            </div>
            <p class="text-xs text-emerald-600 mt-1 text-center">{{ t('Per day earning potential') }}</p>
          </div>

          <!-- Description -->
          <div class="mb-4">
            <p class="text-sm text-slate-600 font-medium text-center">
              {{ t('Click the button below to proceed to deposit. The amount will be prefilled and locked on the deposit page.') }}
            </p>
          </div>

          <!-- Purchase Button -->
          <Link 
            :href="route('deposit.index', { vip: props.level || urlLevel, amount: levelPrice })" 
            class="w-full px-4 py-2 bg-gradient-to-r from-cyan-500/80 to-blue-600/80 hover:from-cyan-600/90 hover:to-blue-700/90 text-white font-medium rounded-lg transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl text-sm text-center block"
          >
            {{ t('Upgrade to') }} {{ urlLevel }}
          </Link>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>