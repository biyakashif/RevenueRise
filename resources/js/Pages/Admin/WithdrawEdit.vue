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
    <template #header>
      <h1 class="text-2xl font-bold">Edit Withdrawal</h1>
    </template>

    <div class="p-4 max-w-xl">
      <div class="bg-white p-6 rounded shadow">
        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Amount (USDT)</label>
            <input v-model="amount" type="number" step="any" required class="mt-1 w-full border rounded px-3 py-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Crypto Wallet</label>
            <input v-model="cryptoWallet" type="text" class="mt-1 w-full border rounded px-3 py-2" />
          </div>

          <div class="text-right">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Update</button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>