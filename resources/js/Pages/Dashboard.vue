<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import VIP1Icon from '@/assets/VIP1.png';

const page = usePage();
const translations = computed(() => page.props.translations || {});
const t = (key) => translations.value[key] || key;
const user = page.props.auth.user || {};

// VIP levels with costs and icons
const levels = [
  { id: 1, name: 'VIP1', cost: null, icon: VIP1Icon },
  { id: 2, name: 'VIP2', cost: 300.0, icon: VIP1Icon },
  { id: 3, name: 'VIP3', cost: 750.0, icon: VIP1Icon },
  { id: 4, name: 'VIP4', cost: 1500.0, icon: VIP1Icon },
  { id: 5, name: 'VIP5', cost: 3500.0, icon: VIP1Icon },
  { id: 6, name: 'VIP6', cost: 6500.0, icon: VIP1Icon },
  { id: 7, name: 'VIP7', cost: 10000.0, icon: VIP1Icon }
];

// Normalize user's VIP name robustly and compute numeric level.
// Handles variants like "VIP2", "vip 2", 2, null, or messy strings.
const currentVipName = computed(() => {
  let v = user.vip_level ?? user.vip ?? null;
  if (v === null || v === undefined || v === '') return 'VIP1';
  if (typeof v === 'object') {
    if (v.name) v = v.name;
    else v = String(v);
  }
  v = String(v).trim().toUpperCase();
  if (/^\d+$/.test(v)) return `VIP${v}`;
  const m = v.match(/VIP\s*([0-9]+)/i);
  if (m && m[1]) return `VIP${m[1]}`;
  const digits = v.replace(/\D/g, '');
  if (digits) return `VIP${digits}`;
  return 'VIP1';
});

const currentLevel = computed(() => {
  const num = Number(currentVipName.value.replace(/\D/g, '')) || 1;
  return num >= 1 ? num : 1;
});

// Expand/collapse control for showing all VIPs inline
const showAll = ref(false);
const visibleLevels = computed(() => (showAll.value ? levels : levels.slice(0, 4)));

function fmt(n) {
  return Number(n || 0).toFixed(2);
}
</script>

<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout>
    <div class="py-6 px-4 sm:px-6 lg:px-8 bg-gray-100">
      <!-- Service Boxes -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-sm text-center">
          <svg class="w-8 h-8 mx-auto text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
          </svg>
                    <Link href="/service" class="mt-2 block text-purple-600 text-sm font-medium">{{ t('Service') }}</Link>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm text-center">
          <svg class="w-8 h-8 mx-auto text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
                    <Link href="/event" class="mt-2 block text-purple-600 text-sm font-medium">{{ t('Event') }}</Link>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm text-center">
          <svg class="w-8 h-8 mx-auto text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
                    <Link :href="route('withdraw')" class="mt-2 block text-purple-600 text-sm font-medium">{{ t('Withdraw') }}</Link>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm text-center">
          <svg class="w-8 h-8 mx-auto text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
          </svg>
                    <Link href="/deposit" class="mt-2 block text-purple-600 text-sm font-medium">{{ t('Deposit') }}</Link>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm text-center">
          <svg class="w-8 h-8 mx-auto text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v12a2 2 0 01-2 2z" />
          </svg>
                    <Link href="/terms" class="mt-2 block text-purple-600 text-sm font-medium">{{ t('T&C') }}</Link>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm text-center">
          <svg class="w-8 h-8 mx-auto text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
          </svg>
                    <Link href="/certificate" class="mt-2 block text-purple-600 text-sm font-medium">{{ t('Certificate') }}</Link>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm text-center">
          <svg class="w-8 h-8 mx-auto text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
                    <Link href="/faqs" class="mt-2 block text-purple-600 text-sm font-medium">{{ t('FAQs') }}</Link>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm text-center">
          <svg class="w-8 h-8 mx-auto text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
                    <Link href="/about" class="mt-2 block text-purple-600 text-sm font-medium">{{ t('About') }}</Link>
        </div>
      </div>

      <!-- VIP Levels -->
      <div class="bg-white p-4 rounded-lg shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-800">{{ t('VIP Levels') }}</h2>

          <button
            type="button"
            @click="showAll = !showAll"
            class="text-purple-600 inline-flex items-center gap-2 text-sm font-medium"
          >
            <span v-if="!showAll">{{ t('View More') }}</span>
            <span v-else>{{ t('View Less') }}</span>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path v-if="!showAll" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <template v-for="level in visibleLevels" :key="level.id">
            <div class="bg-gray-50 p-4 rounded-lg flex items-center gap-4 shadow-sm">
              <img :src="level.icon" alt="" class="w-12 h-12 rounded-full object-cover"/>

              <div class="flex-1">
                <div class="flex items-center gap-3">
                  <div class="text-lg font-semibold text-gray-800">{{ level.name }}</div>

                  <!-- Rules:
                       - Show "Current" only on the level that matches user's vip_level
                       - Show "Upgrade now" for levels > 1 when they are NOT the current level
                       - VIP1 is free and should never show "Upgrade now"; it only shows "Current" if the user actually has VIP1
                  -->
                  <span v-if="level.id === currentLevel" class="px-2 py-0.5 text-xs bg-purple-100 text-purple-700 rounded-full">{{ t('Current') }}</span>

                  <Link
                    v-else-if="level.id !== 1"
                    :href="route('vip.purchase', { level: level.name })"
                    class="text-sm text-purple-600 underline ml-2"
                  >
                    {{ t('Upgrade now') }}
                  </Link>
                </div>

                <div class="mt-2 text-purple-600 font-medium">
                  <span v-if="level.id === 1">{{ t('Free') }}</span>
                  <span v-else>{{ fmt(level.cost) }} USDT</span>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>