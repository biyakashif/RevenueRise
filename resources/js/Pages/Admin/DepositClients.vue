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

const fetchUsers = async (page = 1, search = '') => {
  try {
    const url = search
      ? `/admin/deposit-clients?page=${page}&search=${encodeURIComponent(search)}`
      : `/admin/deposit-clients?page=${page}`;
    const response = await fetch(url, {
      headers: { 'Accept': 'application/json' },
    });
    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
    const data = await response.json();
    users.value = data.data || [];
    currentPage.value = data.current_page || 1;
    lastPage.value = data.last_page || 1;
    errorMessage.value = '';
  } catch (error) {
    console.error('Error fetching users:', error);
    errorMessage.value = 'Failed to fetch users. Please try again.';
  }
};

onMounted(() => {
  fetchUsers(currentPage.value, searchQuery.value);
  pollingInterval = setInterval(() => fetchUsers(currentPage.value, searchQuery.value), 5000);
});

onUnmounted(() => {
  clearInterval(pollingInterval);
});

const goToPage = (page) => {
  if (page >= 1 && page <= lastPage.value) {
    fetchUsers(page, searchQuery.value);
  }
};

const searchUsers = () => {
  fetchUsers(1, searchQuery.value);
};

const goToUpdateWallet = (userId) => {
  router.visit(`/admin/update-wallet?user_id=${userId}`);
};
</script>

<template>
  <Head title="Deposit Clients" />
  <AdminLayout>
    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h1 class="text-2xl font-semibold text-gray-900 mb-2">Deposit Clients</h1>
          <p class="text-sm text-gray-600 mb-6">Manage client balances and deposits</p>

          <!-- Search Bar -->
          <div class="mb-6">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search by Mobile Number..."
              class="w-full max-w-md border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
              @input="searchUsers"
            />
          </div>

          <!-- Error Message -->
          <div v-if="errorMessage" class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm flex items-center gap-2">
            {{ errorMessage }}
          </div>

          <!-- Users Table -->
          <div class="overflow-x-auto">
            <table class="w-full border-collapse">
              <thead>
                <tr class="bg-gray-50 text-gray-700 text-sm font-medium">
                  <th class="p-3 text-left">Name</th>
                  <th class="p-3 text-left">Mobile Number</th>
                  <th class="p-3 text-left">Balance</th>
                  <th class="p-3 text-left">Frozen Balance</th>
                  <th class="p-3 text-left">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="user in users"
                  :key="user.id"
                  class="border-t border-gray-200 hover:bg-gray-50 transition"
                >
                  <td class="p-3 text-sm text-gray-700">{{ user.name }}</td>
                  <td class="p-3 text-sm text-gray-700">{{ user.mobile_number }}</td>
                  <td class="p-3 text-sm text-gray-700">${{ (user.balance ?? 0).toFixed(2) }}</td>
                  <td class="p-3 text-sm text-gray-700">${{ (user.frozen_balance ?? 0).toFixed(2) }}</td>
                  <td class="p-3">
                    <button
                      @click="goToUpdateWallet(user.id)"
                      class="px-3 py-1 bg-blue-600 text-white rounded-md text-xs font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition"
                    >
                      Update Wallet
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="mt-6 flex justify-between items-center">
            <button
              @click="goToPage(currentPage - 1)"
              :disabled="currentPage === 1"
              class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-300 focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 disabled:opacity-50 transition"
            >
              Previous
            </button>
            <span class="text-sm text-gray-600">Page {{ currentPage }} of {{ lastPage }}</span>
            <button
              @click="goToPage(currentPage + 1)"
              :disabled="currentPage === lastPage"
              class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-300 focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 disabled:opacity-50 transition"
            >
              Next
            </button>
          </div>
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