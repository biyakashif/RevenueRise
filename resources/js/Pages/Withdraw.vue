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
      } else if (errors.message) {
        modalError.value = errors.message;
      } else {
        modalError.value = 'An error occurred while processing your withdrawal';
      }
    },
    onSuccess: (page) => {
      // Check for flash error messages even on "success"
      if (page.props.flash && page.props.flash.error) {
        modalError.value = page.props.flash.error;
        return;
      }
      
      // Check for flash success message to confirm actual success
      if (page.props.flash && page.props.flash.success) {
        showModal.value = false;
        withdrawPassword.value = '';
        amount.value = '';
        cryptoWallet.value = '';
        fetchStatus(); // Refresh data after successful withdrawal
      } else {
        // If no success message, something went wrong
        modalError.value = 'Withdrawal validation failed. Please check your details.';
      }
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
    <div class="py-4 sm:py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30 mb-4 sm:mb-6">
          <div class="mb-4">
            <h1 class="text-xl font-bold text-slate-800 drop-shadow-sm mb-4">{{ t('Withdraw') }}</h1>
            <div class="bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm p-4 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-white/30">
              <div class="flex justify-between w-full items-center">
                <p class="text-xs text-slate-600 font-medium">{{ t('Available USDT') }}</p>
                <p class="text-lg font-bold text-slate-800 flex items-center gap-1">
                  {{ formatUSDT(balances.usdt_balance) }}
                  <span class="text-xs text-slate-500">USDT</span>
                </p>
              </div>
              <div class="mt-2 text-xs text-center text-slate-500">
                {{ t('Minimum Withdrawal') }} {{ formatUSDT(balances.withdraw_limit) }} USDT
              </div>
            </div>
          </div>
        </div>

        <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30 mb-4 sm:mb-6">
          <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2 drop-shadow-sm">{{ t('USDT Amount') }}</label>
            <div class="mt-1 flex">
              <input name="amount_withdraw" v-model="amount" type="number" step="any" required class="flex-1 h-12 rounded-l-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 placeholder-slate-400 backdrop-blur-sm shadow-lg" />
              <button type="button" @click="setMax" class="bg-gradient-to-r from-white/60 to-white/40 hover:from-white/70 hover:to-white/50 px-4 rounded-r-xl border-l border-white/30 text-slate-700 font-medium transition-all duration-200 shadow-lg">{{ t('MAX') }}</button>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2 drop-shadow-sm">{{ t('Recipient USDT Wallet Address') }}</label>
            <input name="crypto_wallet" v-model="cryptoWallet" type="text" required class="mt-1 w-full h-12 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 placeholder-slate-400 backdrop-blur-sm shadow-lg" />
          </div>

          <div class="text-right">
            <button @click="submit" class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg text-sm font-medium transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl">{{ t('Withdraw') }}</button>
          </div>
          </form>
        </div>

      <!-- Withdraw Password Modal -->
      <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-start justify-center pt-40 z-50 p-4">
        <div class="bg-gradient-to-br from-white/95 via-blue-50/90 to-indigo-50/95 backdrop-blur-xl rounded-2xl sm:rounded-3xl p-6 w-full max-w-md shadow-2xl border border-white/40">
          <h3 class="text-lg font-semibold mb-4 text-slate-800 drop-shadow-sm">{{ t('Confirm Withdraw') }}</h3>
          <p class="text-sm text-slate-600 mb-2 drop-shadow-sm">{{ t('Enter withdraw password') }}</p>
          <input v-model="withdrawPassword" type="password" class="w-full h-12 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 placeholder-slate-400 backdrop-blur-sm shadow-lg mb-2" autocomplete="off" />
          <div v-if="modalError" class="text-xs text-red-600 mb-2 bg-red-50 p-2 rounded-lg border border-red-200">{{ modalError }}</div>
          <div class="flex justify-end space-x-2">
            <button @click="showModal = false; withdrawPassword = ''; modalError = ''" class="px-4 py-2 bg-white/50 hover:bg-white/70 rounded-xl text-sm border border-white/40 transition-all duration-200">{{ t('Cancel') }}</button>
            <button @click="validateThenSubmit" class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl text-sm transition-all duration-200 shadow-lg">{{ t('Confirm Withdraw') }}</button>
          </div>
        </div>
      </div>

        <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30">
          <h2 class="text-lg font-semibold text-slate-800 drop-shadow-sm mb-4">{{ t('Withdrawal History') }}</h2>
          <div v-if="withdrawals.length === 0" class="text-slate-500 mt-2 drop-shadow-sm">{{ t('No withdrawals yet') }}.</div>
          <div v-else class="space-y-3">
            <div v-for="w in withdrawals" :key="w.id" class="bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm rounded-xl p-4 border border-white/30 shadow-lg">
              <div class="flex justify-between items-center">
                <div class="text-sm">
                  <div class="text-slate-800 font-semibold drop-shadow-sm"><strong>{{ formatUSDT(w.amount_withdraw) }}</strong> USDT</div>
                  <div class="text-xs text-slate-600 drop-shadow-sm">To: {{ w.crypto_wallet }}</div>
                  <div class="text-xs text-slate-500 drop-shadow-sm">{{ new Date(w.created_at).toLocaleString() }}</div>
                </div>
                <div>
                  <span :class="{
                    'px-3 py-1 rounded-full text-xs font-medium shadow-sm': true,
                    'bg-yellow-100/80 text-yellow-800 border border-yellow-200': w.status === 'under review',
                    'bg-green-100/80 text-green-800 border border-green-200': w.status === 'approved',
                    'bg-red-100/80 text-red-800 border border-red-200': w.status === 'rejected'
                  }">
                    {{ w.status.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ') }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>