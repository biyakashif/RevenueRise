<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3'; // Ensure usePage is imported
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';

const props = defineProps({
  deposits: { type: Array, default: () => [] },
  selectedUserId: { type: [String, Number], default: null },
  balance: { type: [Number, String], default: 0 },
  frozen_balance: { type: [Number, String], default: 0 },
  userName: { type: String, default: null },
  mobile_number: { type: String, default: null },
});

const page = usePage(); // Define page using usePage
const localDeposits = ref([...props.deposits]);
const liveBalance = ref(parseFloat(props.balance) || 0);
const liveFrozenBalance = ref(parseFloat(props.frozen_balance) || 0);
const userName = ref(props.userName);
const mobileNumber = ref(props.mobile_number);
const amount = ref('');
const errorMessage = ref('');
const pendingCount = computed(() => localDeposits.value.filter(d => d.status === 'pending').length);

// Flash message handling
const flashMessage = ref('');
const flashType = ref('');

const flashClass = computed(() => ({
    'bg-green-100 border-green-500 text-green-700': flashType.value === 'success',
    'bg-red-100 border-red-500 text-red-700': flashType.value === 'error',
}));

watch(() => page.props.flash, (flash) => {
    if (flash.success) {
        flashMessage.value = flash.success;
        flashType.value = 'success';
    } else if (flash.error) {
        flashMessage.value = flash.error;
        flashType.value = 'error';
    } else {
        flashMessage.value = '';
        flashType.value = '';
    }

    if (flashMessage.value) {
        setTimeout(() => {
            flashMessage.value = '';
            if (page.props.flash.success) page.props.flash.success = null;
            if (page.props.flash.error) page.props.flash.error = null;
        }, 2000);
    }
}, { deep: true });

const fetchData = async () => {
  if (!props.selectedUserId) return;
  try {
    const response = await fetch(`/admin/update-wallet?user_id=${props.selectedUserId}`, {
      headers: { 'Accept': 'application/json' },
    });
    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
    const data = await response.json();
    localDeposits.value = data.deposits || [];
    liveBalance.value = parseFloat(data.balance) || 0;
    liveFrozenBalance.value = parseFloat(data.frozen_balance) || 0;
    userName.value = data.userName || 'Unknown';
    mobileNumber.value = data.mobile_number || 'Unknown';
    errorMessage.value = '';
  } catch (error) {
    errorMessage.value = `Failed to fetch wallet data: ${error.message}. Please try again.`;
    setTimeout(() => errorMessage.value = '', 5000);
  }
};

onMounted(() => {
  fetchData();
  
  console.log('[UpdateWallet] Component mounted for user:', props.selectedUserId);
  
  // Wait for Echo to be ready
  const setupEchoListeners = () => {
    if (!window.Echo) {
      console.log('[UpdateWallet] Echo not ready, waiting...');
      setTimeout(setupEchoListeners, 100);
      return;
    }
    
    if (!props.selectedUserId) {
      console.log('[UpdateWallet] No user selected, skipping Echo setup');
      return;
    }
    
    console.log('[UpdateWallet] Echo ready, setting up listeners');
    
    window.Echo.channel('deposits')
      .listen('DepositCreated', (e) => {
        console.log('[UpdateWallet] ✅ DepositCreated event received:', e);
        // Only update if deposit is for the selected user
        if (e?.deposit?.user_id && String(e.deposit.user_id) === String(props.selectedUserId)) {
          console.log('[UpdateWallet] Deposit is for current user, fetching data');
          fetchData();
        }
      })
      .listen('DepositStatusUpdated', (e) => {
        console.log('[UpdateWallet] ✅ DepositStatusUpdated event received:', e);
        // Only update if deposit is for the selected user
        if (e?.deposit?.user_id && String(e.deposit.user_id) === String(props.selectedUserId)) {
          console.log('[UpdateWallet] Status update is for current user');
          // Update the specific deposit in the list without full refresh
          const depositIndex = localDeposits.value.findIndex(d => d.id === e.deposit.id);
          if (depositIndex !== -1) {
            localDeposits.value[depositIndex].status = e.deposit.status;
            // Trigger reactivity
            localDeposits.value = [...localDeposits.value];
          }
          // Refresh balance
          fetchData();
        }
      });
    
    console.log('[UpdateWallet] Deposit channel listeners attached');
  };
  
  setupEchoListeners();
});

onUnmounted(() => {
  if (window.Echo) {
    window.Echo.leaveChannel('deposits');
  }
});

const updateDepositStatus = async (depositId, action) => {
  if (action === 'approve') {
    // Find the deposit to get its symbol and amount
    const deposit = localDeposits.value.find(d => d.id === depositId);
    if (!deposit) {
      errorMessage.value = 'Deposit not found.';
      setTimeout(() => errorMessage.value = '', 3000);
      return;
    }

    try {
      // Fetch live exchange rate from Coinbase API
      const response = await fetch(`https://api.coinbase.com/v2/exchange-rates?currency=${deposit.symbol}`);
      if (!response.ok) {
        throw new Error(`Failed to fetch exchange rate: ${response.status}`);
      }
      const rateData = await response.json();
      
      if (!rateData.data || !rateData.data.rates || !rateData.data.rates.USDT) {
        throw new Error('Invalid exchange rate data received');
      }
      
      const usdtRate = parseFloat(rateData.data.rates.USDT);
      const convertedAmount = parseFloat(deposit.amount) * usdtRate;
      
      // Send the converted USDT amount to backend
      router.post(`/admin/update-deposit/${depositId}`, { 
        action, 
        converted_amount: convertedAmount,
        original_amount: deposit.amount,
        symbol: deposit.symbol,
        exchange_rate: usdtRate,
        _token: page.props.csrf_token
      }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          fetchData();
        },
        onError: (errors) => {
          if (errors && (errors.message?.includes('419') || errors.status === 419)) {
            window.location.reload();
            return;
          }
          errorMessage.value = errors.action || 'Failed to approve deposit.';
          setTimeout(() => errorMessage.value = '', 5000);
          fetchData();
        },
      });
    } catch (error) {
      errorMessage.value = `Failed to get exchange rate: ${error.message}. Please try again.`;
      setTimeout(() => errorMessage.value = '', 5000);
      return;
    }
  } else {
    // For reject action, proceed normally
    router.post(`/admin/update-deposit/${depositId}`, { 
      action,
      _token: page.props.csrf_token
    }, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        fetchData();
      },
      onError: (errors) => {
        if (errors && (errors.message?.includes('419') || errors.status === 419)) {
          window.location.reload();
          return;
        }
        errorMessage.value = errors.action || 'Failed to update deposit status.';
        setTimeout(() => errorMessage.value = '', 5000);
        fetchData();
      },
    });
  }
};

const updateBalance = (action) => {
  const amt = parseFloat(amount.value);
  if (isNaN(amt) || amt <= 0) {
    errorMessage.value = 'Please enter a valid amount greater than 0.';
    setTimeout(() => errorMessage.value = '', 3000);
    return;
  }

  let routeUrl;
  if (action === 'freeze') {
    routeUrl = route('admin.freeze-balance', props.selectedUserId);
  } else if (action === 'unfreeze') {
    routeUrl = route('admin.unfreeze-balance', props.selectedUserId);
  } else {
    routeUrl = `/admin/update-balance/${props.selectedUserId}`;
  }

  router.post(routeUrl, { 
    amount: amt, 
    action: action === 'freeze' || action === 'unfreeze' ? 'subtract' : action,
    _token: page.props.csrf_token
  }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      fetchData();
      amount.value = '';
      errorMessage.value = '';
    },
    onError: (errors) => {
      if (errors && (errors.message?.includes('419') || errors.status === 419)) {
        window.location.reload();
        return;
      }
      errorMessage.value = errors.error || errors.amount || errors.action || 'Failed to update balance. Please check the server configuration.';
      setTimeout(() => errorMessage.value = '', 5000);
      fetchData();
    },
  });
};
</script>

<template>
  <Head title="Update Wallet" />
  <AdminLayout>
    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 rounded-2xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto">
      <h1 class="text-lg font-bold text-slate-800 drop-shadow-sm mb-4">
        Update Wallet
        <span v-if="selectedUserId && userName" class="block text-base font-medium text-slate-700">
          for {{ userName }}
          <span v-if="pendingCount > 0" class="ml-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 inline-flex items-center justify-center">{{ pendingCount }}</span>
        </span>
        <span v-if="selectedUserId && mobileNumber" class="text-sm font-medium text-slate-600">
          Mobile: {{ mobileNumber }}
        </span>
      </h1>

          <!-- Flash Messages -->
          <div v-if="flashMessage" :class="flashClass" class="mb-6 p-4 rounded-xl bg-green-100/80 border border-green-200 text-green-700 text-sm flex items-center gap-2 backdrop-blur-sm">
            {{ flashMessage }}
          </div>
          <div v-if="errorMessage" class="mb-6 p-4 rounded-xl bg-red-100/80 border border-red-200 text-red-700 text-sm flex items-center gap-2 backdrop-blur-sm">
            {{ errorMessage }}
          </div>

          <!-- Balance Section -->
          <div v-if="selectedUserId" class="mb-6">
            <div class="bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm p-4 rounded-xl shadow-lg border border-white/30">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <p class="text-sm font-medium text-slate-700">User Balance</p>
                  <p class="text-lg font-semibold text-slate-800">${{ liveBalance.toFixed(2) }}</p>
                </div>
                <div>
                  <p class="text-sm font-medium text-slate-700">Frozen Balance</p>
                  <p class="text-lg font-semibold text-slate-800">${{ liveFrozenBalance.toFixed(2) }}</p>
                </div>
              </div>
              <input
                v-model="amount"
                type="number"
                step="0.01"
                min="0.01"
                placeholder="Enter amount"
                class="mt-4 w-full max-w-md h-12 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 placeholder-slate-400 backdrop-blur-sm shadow-lg"
              />
              <div class="mt-4 flex flex-wrap gap-3">
                <button
                  @click="updateBalance('add')"
                  class="px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-lg text-sm font-medium transition-all duration-300 transform hover:scale-[1.02] shadow-lg disabled:opacity-50"
                  :disabled="!amount || parseFloat(amount) <= 0"
                >
                  Add
                </button>
                <button
                  @click="updateBalance('subtract')"
                  class="px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-lg text-sm font-medium transition-all duration-300 transform hover:scale-[1.02] shadow-lg disabled:opacity-50"
                  :disabled="!amount || parseFloat(amount) <= 0 || liveBalance < parseFloat(amount)"
                >
                  Subtract
                </button>
                <button
                  @click="updateBalance('freeze')"
                  class="px-4 py-2 bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white rounded-lg text-sm font-medium transition-all duration-300 transform hover:scale-[1.02] shadow-lg disabled:opacity-50"
                  :disabled="!amount || parseFloat(amount) <= 0 || liveBalance < parseFloat(amount)"
                >
                  Freeze Balance
                </button>
                <button
                  @click="updateBalance('unfreeze')"
                  class="px-4 py-2 bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white rounded-lg text-sm font-medium transition-all duration-300 transform hover:scale-[1.02] shadow-lg disabled:opacity-50"
                  :disabled="!amount || parseFloat(amount) <= 0 || liveFrozenBalance < parseFloat(amount)"
                >
                  Unfreeze Balance
                </button>
              </div>
            </div>
          </div>

          <!-- Deposit History -->
          <div class="bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm rounded-xl shadow-lg overflow-hidden border border-white/30">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr class="bg-white/20 text-slate-700 text-sm font-medium">
                    <th class="p-4 text-left">Deposits</th>
                  </tr>
                </thead>
              <tbody>
                  <tr>
                    <td class="p-4 align-top">
                      <div v-if="localDeposits.length === 0" class="text-slate-500 text-sm">No deposits</div>
                      <div v-else class="space-y-3">
                        <div v-for="deposit in localDeposits" :key="deposit.id" class="bg-white/30 backdrop-blur-sm rounded-xl p-3 border border-white/30 shadow-lg">
                        <div class="flex items-start">
                          <div v-if="deposit.slip_path" class="mr-3">
                            <a :href="'/storage/' + deposit.slip_path" target="_blank">
                              <img :src="'/storage/' + deposit.slip_path" alt="Deposit Slip" class="w-10 h-10 object-cover rounded">
                            </a>
                          </div>
                          <div v-else class="mr-3 text-gray-500 text-xs">No Image</div>
                          <div class="flex-1">
                            <div class="flex items-center gap-2">
                              <div class="text-sm font-medium text-slate-700"><strong>User:</strong> {{ deposit.user?.name || 'Unknown' }}</div>
                              <span v-if="deposit.vip_level || deposit.title && deposit.title.toLowerCase().includes('vip')" class="inline-flex items-center px-2 py-0.5 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full">
                                VIP Purchase
                              </span>
                              <span v-if="deposit.title" class="ml-2 text-xs text-slate-600 italic">{{ deposit.title }}</span>
                            </div>
                            <div class="text-sm text-slate-700"><strong>Amount:</strong> {{ deposit.amount }} {{ deposit.symbol }}</div>
                            <div class="text-sm text-slate-700"><strong>Date:</strong> {{ new Date(deposit.created_at).toLocaleString() }}</div>
                            <div class="mt-2 flex justify-end gap-2">
                              <span v-if="deposit.status === 'approved'" class="text-green-600 text-xs font-semibold">Approved</span>
                              <span v-else-if="deposit.status === 'rejected'" class="text-red-600 text-xs font-semibold">Rejected</span>
                              <template v-else>
                                <button @click="updateDepositStatus(deposit.id, 'approve')" class="px-3 py-1 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-lg text-xs font-medium transition-all duration-200 shadow-lg">Approve</button>
                                <button @click="updateDepositStatus(deposit.id, 'reject')" class="px-3 py-1 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-lg text-xs font-medium transition-all duration-200 shadow-lg">Reject</button>
                              </template>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
.transition-all {
  transition: all 0.3s ease;
}
</style>