<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  withdraw: Object,
});

const amount = ref(props.withdraw.amount_withdraw);
const cryptoWallet = ref(props.withdraw.crypto_wallet);

const submit = (e) => {
  const data = {
    amount_withdraw: amount.value,
    crypto_wallet: cryptoWallet.value
  };
  router.post(route('admin.withdrawals.update', props.withdraw.id), data);
};
</script>

<template>
  <Head title="Edit Withdraw" />
  <AdminLayout>
    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 rounded-2xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto">
      <h1 class="text-lg font-bold text-slate-800 drop-shadow-sm mb-4">Edit Withdrawal</h1>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2 drop-shadow-sm">Amount (USDT)</label>
              <input v-model="amount" type="number" step="any" required class="w-full h-12 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 placeholder-slate-400 backdrop-blur-sm shadow-lg" />
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2 drop-shadow-sm">Crypto Wallet</label>
              <input v-model="cryptoWallet" type="text" class="w-full h-12 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 placeholder-slate-400 backdrop-blur-sm shadow-lg" />
            </div>

            <div class="text-right">
              <button type="submit" class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg text-sm font-medium transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl">Update</button>
            </div>
          </form>
    </div>
  </AdminLayout>
</template>