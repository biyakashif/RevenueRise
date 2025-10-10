<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  initialUsers: { type: Array, default: () => [] },
  initialPage: { type: Number, default: 1 },
  initialLastPage: { type: Number, default: 1 },
  search: { type: String, default: '' },
});

const users = ref([...props.initialUsers]);
const currentPage = ref(props.initialPage);
const lastPage = ref(props.initialLastPage);
const searchQuery = ref(props.search);
const errorMessage = ref('');

let pollingInterval = null;

const fetchUsers = async (search = '') => {
  try {
    const url = search
      ? `/admin/deposit-clients?search=${encodeURIComponent(search)}`
      : `/admin/deposit-clients`;
    const response = await fetch(url, {
      headers: { 'Accept': 'application/json' },
    });
    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
    const data = await response.json();
    users.value = data.data || [];
    errorMessage.value = '';
  } catch (error) {
  // suppressed: error fetching deposit clients
    errorMessage.value = 'Failed to fetch users. Please try again.';
    setTimeout(() => errorMessage.value = '', 5000);
  }
};

onMounted(() => {
  fetchUsers(searchQuery.value);
  pollingInterval = setInterval(() => fetchUsers(searchQuery.value), 5000);
});

onUnmounted(() => {
  clearInterval(pollingInterval);
});

const searchUsers = () => {
  fetchUsers(searchQuery.value);
};

const goToUpdateWallet = (userId) => {
  router.visit(`/admin/update-wallet?user_id=${userId}`);
};
</script>

<template>
  <Head title="Deposit Clients" />
  <AdminLayout>
    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 rounded-2xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto">
      <h1 class="text-lg font-bold text-slate-800 drop-shadow-sm mb-4">Deposit Clients</h1>

          <!-- Search Bar -->
          <div class="mb-6">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search by Mobile Number..."
              class="w-full max-w-md h-12 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 placeholder-slate-400 backdrop-blur-sm shadow-lg"
              @input="searchUsers"
            />
          </div>

          <!-- Error Message -->
          <div v-if="errorMessage" class="mb-6 p-4 rounded-xl bg-red-100/80 border border-red-200 text-red-700 text-sm flex items-center gap-2 backdrop-blur-sm">
            {{ errorMessage }}
          </div>

          <!-- Users Table -->
          <div class="bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm rounded-xl shadow-lg overflow-hidden border border-white/30">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr class="bg-white/20 text-slate-700 text-sm font-medium">
                    <th class="p-4 text-left">Name</th>
                    <th class="p-4 text-left">Mobile Number</th>
                    <th class="p-4 text-left">Balance</th>
                    <th class="p-4 text-left">Frozen Balance</th>
                    <th class="p-4 text-left">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="user in users"
                    :key="user.id"
                    class="border-t border-white/20 hover:bg-white/10 transition-all duration-200"
                  >
                    <td class="p-4 text-sm text-slate-800 font-medium">{{ user.name }}</td>
                    <td class="p-4 text-sm text-slate-700">{{ user.mobile_number }}</td>
                    <td class="p-4 text-sm text-slate-700 font-semibold">${{ (user.balance ?? 0).toFixed(2) }}</td>
                    <td class="p-4 text-sm text-slate-700 font-semibold">${{ (user.frozen_balance ?? 0).toFixed(2) }}</td>
                    <td class="p-4">
                      <button
                        @click="goToUpdateWallet(user.id)"
                        class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg text-sm font-medium transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl"
                      >
                        Update Wallet
                      </button>
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