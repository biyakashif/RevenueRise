<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const props = page.props;

// get level param from route props (Inertia passes URL param via page.props)
// but Inertia server will pass via props, we'll fallback to route query if needed.
const urlLevel = (page.props.ziggy?.current?.params?.level) || (new URLSearchParams(window.location.search)).get('level') || page.props.level || 'VIP2';

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

const levelPrice = page.props.levelPrice ?? (priceMapping[urlLevel] ?? null);

const displayPrice = computed(() => (levelPrice !== null ? Number(levelPrice).toFixed(2) + ' USDT' : '—'));
</script>

<template>
  <Head title="Purchase VIP" />
  <AuthenticatedLayout>
    <div class="py-6 px-4 bg-gray-100">
      <div class="max-w-md mx-auto">
        <div class="bg-white p-6 rounded-lg shadow-sm text-center">
          <h1 class="text-2xl font-bold mb-4">Upgrade to {{ (page.props.level || urlLevel) }}</h1>

          <div class="mb-6 text-lg text-purple-600 font-semibold">
            Price: <span class="ml-2">{{ displayPrice }}</span>
          </div>

          <p class="text-sm text-gray-600 mb-6">Click the button below to proceed to deposit. The amount will be prefilled and locked on the deposit page.</p>

          <Link :href="route('deposit.index', { vip: page.props.level || urlLevel, amount: levelPrice })" class="inline-block px-6 py-3 bg-purple-600 text-white rounded-full hover:bg-purple-700">
            Upgrade to {{ (page.props.level || urlLevel) }}
          </Link>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>