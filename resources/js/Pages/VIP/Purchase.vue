<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const props = computed(() => page.props);
const translations = computed(() => page.props.translations || {});
const t = (key) => translations.value[key] || key;

// get level param from route props (Inertia passes URL param via page.props)
// but Inertia server will pass via props, we'll fallback to route query if needed.
const urlLevel = computed(() => (props.value.ziggy?.current?.params?.level) || (new URLSearchParams(window.location.search)).get('level') || props.value.level || 'VIP2');

// server will send price via props.levelPrice when possible; else use client mapping fallback
const priceMapping = {
  VIP1: null,
  VIP2: 300.0,
  VIP3: 750.0,
  VIP4: 1500.0,
  VIP5: 3500.0,
  VIP6: 6500.0,
  VIP7: 10000.0
};

const levelPrice = computed(() => props.value.levelPrice ?? (priceMapping[urlLevel.value] ?? null));

const displayPrice = computed(() => (levelPrice.value !== null ? Number(levelPrice.value).toFixed(2) + ' USDT' : '—'));
</script>

<template>
  <Head :title="t('Purchase VIP')" />
  <AuthenticatedLayout>
    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto flex items-center justify-center">
      <div class="w-full max-w-md mx-auto">
        <div class="backdrop-blur-xl p-6 rounded-lg shadow-sm text-center border border-white/50 bg-gradient-to-br from-white/10 to-white/5">
          <h1 class="text-2xl font-bold mb-4 bg-gradient-to-r from-cyan-500 to-blue-600 bg-clip-text text-transparent">{{ t('Upgrade to') }} {{ (props.level || urlLevel) }}</h1>

          <div class="mb-6 text-lg text-blue-600 font-semibold">
            {{ t('Price') }}: <span class="ml-2 text-gray-900">{{ displayPrice }}</span>
          </div>

          <p class="text-sm text-gray-600 mb-6">{{ t('Click the button below to proceed to deposit. The amount will be prefilled and locked on the deposit page.') }}</p>

          <Link :href="route('deposit.index', { vip: props.level || urlLevel, amount: levelPrice })" class="inline-block px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white rounded-full hover:from-cyan-600 hover:to-blue-700 transition duration-150 ease-in-out shadow-lg">
            {{ t('Upgrade to') }} {{ (props.level || urlLevel) }}
          </Link>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>