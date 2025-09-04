<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

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
  try {
    const res = await fetch(route('withdraw.store'), {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify({ validate_only: true, withdraw_password: withdrawPassword.value }),
    });
    if (!res.ok) {
      const body = await res.json().catch(() => ({}));
      modalError.value = body.error || 'Invalid withdraw password';
      return;
    }

    // on success, submit the full request via Inertia
    const payload = {
      amount_withdraw: amount.value,
      crypto_wallet: cryptoWallet.value,
      withdraw_password: withdrawPassword.value,
    };
    router.post(route('withdraw.store'), payload, { preserveState: true });
    showModal.value = false;
    withdrawPassword.value = '';
  } catch (err) {
    modalError.value = 'Unable to validate password';
  }
};

const setMax = () => {
  const bal = props.balances?.usdt_balance ?? 0;
  // preserve reasonable decimal precision
  amount.value = String(Number(bal));
};
</script>

<template>
  <Head title="Withdraw" />
  <AuthenticatedLayout>
    <template #header>
      <h1 class="text-2xl font-bold">Withdraw USDT</h1>
    </template>

    <div class="p-4 max-w-xl mx-auto">
      <div class="bg-white p-6 rounded-lg shadow">
        <p class="text-sm text-gray-600 mb-4">Available USDT: <strong>{{ balances.usdt_balance ?? 0 }}</strong></p>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">USDT Amount</label>
            <div class="mt-1 flex">
              <input name="amount_withdraw" v-model="amount" type="number" step="any" required class="flex-1 border rounded-l px-3 py-2" />
              <button type="button" @click="setMax" class="ml-2 bg-gray-200 px-3 rounded-r">MAX</button>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Recipient USDT Wallet Address</label>
            <input name="crypto_wallet" v-model="cryptoWallet" type="text" required class="mt-1 w-full border rounded px-3 py-2" />
          </div>

          <div class="text-right">
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Request Withdraw</button>
          </div>
        </form>
      </div>

      <!-- Withdraw Password Modal -->
      <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
          <h3 class="text-lg font-semibold mb-4">Confirm Withdraw</h3>
          <p class="text-sm text-gray-600 mb-2">Enter withdraw password</p>
          <input v-model="withdrawPassword" type="password" class="w-full border rounded px-3 py-2 mb-2" autocomplete="off" />
          <div v-if="modalError" class="text-xs text-red-600 mb-2">{{ modalError }}</div>
          <div class="flex justify-end space-x-2">
            <button @click="() => { showModal = false; withdrawPassword = ''; modalError = ''; }" class="px-3 py-1 bg-gray-200 rounded text-sm">Cancel</button>
            <button @click="validateThenSubmit" class="px-3 py-1 bg-green-600 text-white rounded text-sm">Confirm</button>
          </div>
        </div>
      </div>

      <div class="mt-6">
        <h2 class="text-lg font-semibold">Withdrawal History</h2>
        <div v-if="withdrawals.length === 0" class="text-gray-500 mt-2">No withdrawals yet.</div>
        <div v-else class="space-y-3 mt-2">
          <div v-for="w in withdrawals" :key="w.id" class="border rounded p-3 bg-gray-50">
            <div class="flex justify-between items-center">
              <div class="text-sm">
                <div><strong>{{ w.amount_withdraw }}</strong> USDT</div>
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