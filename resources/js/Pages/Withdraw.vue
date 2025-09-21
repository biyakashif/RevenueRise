<script setup>

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
const page = usePage();
const translations = computed(() => page.props.translations || {});
const user = computed(() => page.props.auth?.user);
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
    const response = await fetch('/withdraw', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    });
    
    if (!response.ok) return;
    const data = await response.json();
    if (data.balances) {
      // update balances in-place with formatted values
      props.balances.usdt_balance = Number(data.balances.usdt_balance).toFixed(2);
      props.balances.withdraw_limit = Number(data.balances.withdraw_limit).toFixed(2);
    }
    if (Array.isArray(data.withdrawals)) {
      // Format amounts before updating array
      const formattedWithdrawals = data.withdrawals.map(w => ({
        ...w,
        amount_withdraw: Number(w.amount_withdraw).toFixed(2)
      }));
      props.withdrawals.splice(0, props.withdrawals.length, ...formattedWithdrawals);
    }
  } catch (err) {
    console.error('Error fetching status:', err);
  }
};

onMounted(() => {
  // Initial fetch
  fetchStatus();
  // Set up polling every 10 seconds
  pollingInterval = setInterval(fetchStatus, 10000);

  // Listen for live balance updates
  try {
    if (window.Echo && user.value && user.value.id) {
      window.Echo.private('user.' + user.value.id)
        .listen('.balance-updated', (e) => {
          props.balances.usdt_balance = Number(e.balance).toFixed(2);
        });
    }
  } catch (error) {
    console.error('Error setting up Echo listener for balance:', error);
  }
});

onUnmounted(() => {
  clearInterval(pollingInterval);
  try {
    if (window.Echo && user.value && user.value.id) {
      window.Echo.leave('user.' + user.value.id);
    }
  } catch (error) {
    console.error('Error cleaning up Echo listener for balance:', error);
  }
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
  const payload = {
    amount_withdraw: amount.value,
    crypto_wallet: cryptoWallet.value,
    withdraw_password: withdrawPassword.value
  };

  router.post('/withdraw', payload, {
    preserveState: true,
    preserveScroll: true,
    onError: (errors) => {
      // Handle validation errors
      if (errors.amount_withdraw) {
        modalError.value = Array.isArray(errors.amount_withdraw) 
          ? errors.amount_withdraw[0] 
          : errors.amount_withdraw;
      } else if (errors.withdraw_password) {
        modalError.value = Array.isArray(errors.withdraw_password) 
          ? errors.withdraw_password[0] 
          : errors.withdraw_password;
      } else if (errors.crypto_wallet) {
        modalError.value = Array.isArray(errors.crypto_wallet) 
          ? errors.crypto_wallet[0] 
          : errors.crypto_wallet;
      } else {
        modalError.value = 'An error occurred while processing your withdrawal';
      }
    },
    onSuccess: () => {
      showModal.value = false;
      withdrawPassword.value = '';
      amount.value = '';
      cryptoWallet.value = '';
      fetchStatus(); // Refresh data after successful withdrawal
    },
  });
};

const setMax = () => {
  const bal = props.balances?.usdt_balance ?? 0;
  amount.value = Number(bal).toFixed(2);
};

// helper to format USDT amounts with 2 decimal places
const formatUSDT = (val) => {
  if (val === null || val === undefined || val === '') return '0.00';
  const n = Number(val);
  if (!isFinite(n)) return '0.00';
  return n.toFixed(2);
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
          <p class="text-sm text-gray-600 mb-2">{{ t('Available USDT') }}: <strong>{{ formatUSDT(balances.usdt_balance) }}</strong></p>
          <p class="text-sm text-gray-600 mb-4">{{ t('Minimum Withdrawal') }}: <strong>{{ formatUSDT(balances.withdraw_limit) }}</strong> USDT</p>        <form @submit.prevent="submit" class="space-y-4">
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
                  'bg-yellow-100 text-yellow-800': w.status === 'under review',
                  'bg-green-100 text-green-800': w.status === 'approved',
                  'bg-red-100 text-red-800': w.status === 'rejected'
                }">
                  {{ w.status.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ') }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>