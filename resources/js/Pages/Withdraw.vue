<script setup>

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
const page = usePage();
const translations = computed(() => page.props.translations || {});
const t = (key) => translations.value[key] || key;

const props = defineProps({
  balances: Object,
  withdrawals: Array,
});

const amount = ref('');
const cryptoWallet = ref('');

// Polling for live updates
let pollingInterval = null;

const fetchStatus = async () => {
  try {
    const res = await fetch(route('withdraw'), { headers: { Accept: 'application/json' } });
    if (!res.ok) return;
    const data = await res.json();
    if (data.balances) {
      // update balances in-place
      props.balances.usdt_balance = data.balances.usdt_balance;
    }
    if (Array.isArray(data.withdrawals)) {
      // replace withdrawals array
      while (props.withdrawals.length) props.withdrawals.pop();
      data.withdrawals.forEach(w => props.withdrawals.push(w));
    }
  } catch (err) {
    // silent
  }
};

onMounted(() => {
  pollingInterval = setInterval(fetchStatus, 4000);
});

onUnmounted(() => {
  clearInterval(pollingInterval);
});

const submit = (e) => {
  // open confirmation modal instead of immediate submit
  showModal.value = true;
};

const withdrawPassword = ref('');
const showModal = ref(false);
const modalError = ref('');

const validateThenSubmit = async () => {
  modalError.value = '';
  // Use Inertia POST so CSRF/session are handled by Inertia and Laravel
  const payload = {
    amount_withdraw: amount.value,
    crypto_wallet: cryptoWallet.value,
    withdraw_password: withdrawPassword.value,
    // mark this as coming from the modal so controller can treat errors accordingly if needed
    from_modal: true,
  };

  router.post(route('withdraw.store'), payload, {
    preserveState: true,
    onError: (errors) => {
      // keep the modal open and show withdraw_password error if present
      // Inertia validation errors appear as { errors: { field: ['msg'] } } or as a flat errors object
      let msg = 'Validation failed';
      if (errors && typeof errors === 'object') {
        if (errors.withdraw_password) {
          msg = Array.isArray(errors.withdraw_password) ? errors.withdraw_password[0] : errors.withdraw_password;
        } else if (errors.errors && errors.errors.withdraw_password) {
          msg = Array.isArray(errors.errors.withdraw_password) ? errors.errors.withdraw_password[0] : errors.errors.withdraw_password;
        } else if (errors.error) {
          msg = errors.error;
        } else {
          // take first error message available
          const firstKey = Object.keys(errors)[0];
          const val = errors[firstKey];
          msg = Array.isArray(val) ? val[0] : String(val);
        }
      }
      modalError.value = msg;
    },
    onSuccess: () => {
      showModal.value = false;
      withdrawPassword.value = '';
    },
  });
};

const setMax = () => {
  const bal = props.balances?.usdt_balance ?? 0;
  // preserve reasonable decimal precision
  amount.value = String(Number(bal));
};

// helper to format USDT amounts: up to 8 decimals, but trim trailing zeros
const formatUSDT = (val) => {
  if (val === null || val === undefined || val === '') return '0';
  const n = Number(val);
  if (!isFinite(n)) return String(val);
  let s = n.toFixed(8); // keep up to 8 decimals
  s = s.replace(/\.?(0+)$/, ''); // remove trailing zeros and optional dot
  // fallback to 0 if empty
  return s === '' ? '0' : s;
};
</script>

<template>
  <Head :title="t('Withdraw')" />
  <AuthenticatedLayout>
    <template #header>
      <h1 class="text-2xl font-bold">{{ t('Withdraw') }}</h1>
    </template>

    <div class="p-4 max-w-xl mx-auto">
      <div class="bg-white p-6 rounded-lg shadow">
        <p class="text-sm text-gray-600 mb-4">{{ t('Available USDT') }}: <strong>{{ balances.usdt_balance ?? 0 }}</strong></p>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">{{ t('USDT Amount') }}</label>
            <div class="mt-1 flex">
              <input name="amount_withdraw" v-model="amount" type="number" step="any" required class="flex-1 border rounded-l px-3 py-2" />
              <button type="button" @click="setMax" class="ml-2 bg-gray-200 px-3 rounded-r">{{ t('MAX') }}</button>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">{{ t('Recipient USDT Wallet Address') }}</label>
            <input name="crypto_wallet" v-model="cryptoWallet" type="text" required class="mt-1 w-full border rounded px-3 py-2" />
          </div>

          <div class="text-right">
            <button @click="submit" class="bg-green-600 text-white px-4 py-2 rounded">{{ t('Withdraw') }}</button>
          </div>
        </form>
      </div>

      <!-- Withdraw Password Modal -->
      <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
          <h3 class="text-lg font-semibold mb-4">{{ t('Confirm Withdraw') }}</h3>
          <p class="text-sm text-gray-600 mb-2">{{ t('Enter withdraw password') }}</p>
          <input v-model="withdrawPassword" type="password" class="w-full border rounded px-3 py-2 mb-2" autocomplete="off" />
          <div v-if="modalError" class="text-xs text-red-600 mb-2">{{ modalError }}</div>
          <div class="flex justify-end space-x-2">
            <button @click="showModal = false; withdrawPassword = ''; modalError = ''" class="px-3 py-1 bg-gray-200 rounded text-sm">{{ t('Cancel') }}</button>
            <button @click="validateThenSubmit" class="px-3 py-1 bg-green-600 text-white rounded text-sm">{{ t('Confirm Withdraw') }}</button>
          </div>
        </div>
      </div>

      <div class="mt-6">
        <h2 class="text-lg font-semibold">{{ t('Withdrawal History') }}</h2>
        <div v-if="withdrawals.length === 0" class="text-gray-500 mt-2">{{ t('No withdrawals yet') }}.</div>
        <div v-else class="space-y-3 mt-2">
          <div v-for="w in withdrawals" :key="w.id" class="border rounded p-3 bg-gray-50">
            <div class="flex justify-between items-center">
              <div class="text-sm">
                <div><strong>{{ formatUSDT(w.amount_withdraw) }}</strong> USDT</div>
                <div class="text-xs text-gray-600">To: {{ w.crypto_wallet }}</div>
                <div class="text-xs text-gray-500">{{ new Date(w.created_at).toLocaleString() }}</div>
              </div>
              <div>
                <span :class="{
                  'px-2 py-1 rounded text-xs': true,
                  'bg-yellow-100 text-yellow-800': w.status === 'pending',
                  'bg-green-100 text-green-800': w.status === 'approved',
                  'bg-red-100 text-red-800': w.status === 'rejected'
                }">
                  {{ w.status.charAt(0).toUpperCase() + w.status.slice(1) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>