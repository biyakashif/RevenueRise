<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

const users = ref([]);
const currentPage = ref(1);
const lastPage = ref(1);
const searchQuery = ref('');
let pollingInterval = null;

const csrfToken = typeof document !== 'undefined' && document.querySelector('meta[name="csrf-token"]')
  ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
  : '';

const fetchUsers = async (page = 1, search = '') => {
  try {
    const url = search
      ? `/admin/withdrawals?page=${page}&search=${encodeURIComponent(search)}`
      : `/admin/withdrawals?page=${page}`;
  const res = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    // Ensure withdraw_limit is properly formatted for each user
    users.value = (data.data || []).map(user => ({
      ...user,
      withdraw_limit: formatUSDT(user.withdraw_limit)
    }));
    currentPage.value = data.current_page || 1;
    lastPage.value = data.last_page || 1;
  } catch (err) {
  // suppressed: failed to load withdrawals
  }
};

onMounted(() => {
  fetchUsers(currentPage.value, searchQuery.value);
  pollingInterval = setInterval(() => fetchUsers(currentPage.value, searchQuery.value), 4000);
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

const approve = async (id) => {
  try {
  const res = await fetch(route('admin.withdrawals.approve', id), { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken } });
    if (!res.ok) throw new Error('Approve failed');
    await fetchUsers(currentPage.value, searchQuery.value);
  } catch (err) {
  // suppressed: approve failed
  }
};

const rejectW = async (id) => {
  try {
  const res = await fetch(route('admin.withdrawals.reject', id), { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken } });
    if (!res.ok) throw new Error('Reject failed');
    await fetchUsers(currentPage.value, searchQuery.value);
  } catch (err) {
  // suppressed: reject failed
  }
};

const edit = (id) => {
  router.visit(route('admin.withdrawals.edit', id));
};

const showLimitModal = ref(false);
const selectedUserId = ref(null);
const newWithdrawLimit = ref('');
const limitModalError = ref('');

const openLimitModal = (userId, currentLimit) => {
  selectedUserId.value = userId;
  newWithdrawLimit.value = currentLimit;
  showLimitModal.value = true;
  limitModalError.value = '';
};

// Helper function to format USDT amounts
const formatUSDT = (val) => {
  if (val === null || val === undefined || val === '') return '0.00';
  const n = Number(val);
  if (!isFinite(n)) return '0.00';
  return n.toFixed(2);
};

const updateWithdrawLimit = async () => {
  limitModalError.value = '';
  
  try {
    const res = await fetch(route('admin.users.withdraw-limit.update', selectedUserId.value), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({ withdraw_limit: formatUSDT(newWithdrawLimit.value) })
    });

    const data = await res.json();
    
    if (!res.ok) {
      throw new Error(data.message || 'Failed to update withdraw limit');
    }

    showLimitModal.value = false;
    await fetchUsers(currentPage.value, searchQuery.value);
  } catch (err) {
    limitModalError.value = err.message || 'Failed to update withdraw limit';
  }
};
</script>

<template>
  <Head title="Withdrawals" />
  <AdminLayout>
    <template #header>
      <div class="flex items-center justify-between w-full">
        <div>
          <h1 class="text-2xl font-bold">Withdrawals</h1>
          <p class="text-sm text-gray-600">Search users by name, mobile or invitation code. Latest withdraws shown first.</p>
        </div>
        <div class="w-1/3">
          <input v-model="searchQuery" @input="searchUsers" type="text" placeholder="Search name, mobile or code..." class="w-full border rounded px-3 py-2" />
        </div>
      </div>
    </template>

    <div class="p-4">
      <div v-for="user in users" :key="user.id" class="mb-6 bg-white p-4 rounded shadow">
        <div class="flex justify-between items-start">
          <div>
            <h3 class="font-semibold">{{ user.name }} <span class="text-sm text-gray-600">— {{ user.mobile_number }} / {{ user.invitation_code }}</span></h3>
            <div class="text-sm text-gray-600 mt-1">
              Withdraw Limit: <strong>{{ formatUSDT(user.withdraw_limit) }}</strong> USDT
              <button @click="openLimitModal(user.id, formatUSDT(user.withdraw_limit))" 
                class="ml-2 text-xs bg-gray-200 px-2 py-1 rounded hover:bg-gray-300 transition-colors">
                Change Limit
              </button>
            </div>
          </div>
        </div>

        <div class="mt-2 space-y-3">
          <div v-for="w in user.withdraws" :key="w.id" class="bg-gray-50 p-4 rounded">
            <div class="flex justify-between">
              <div>
                <div class="text-sm"><strong>{{ formatUSDT(w.amount_withdraw) }}</strong> USDT</div>
                <div class="text-xs text-gray-600">To: {{ w.crypto_wallet }}</div>
                <div class="text-xs text-gray-500">{{ new Date(w.created_at).toLocaleString() }}</div>
              </div>
              <div class="flex flex-col items-end space-y-2">
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

                <div class="flex space-x-2">
                  <button v-if="w.status === 'under review'" @click="approve(w.id)" class="bg-green-500 text-white px-3 py-1 rounded text-xs">Approve</button>
                  <button v-if="w.status === 'under review'" @click="rejectW(w.id)" class="bg-red-500 text-white px-3 py-1 rounded text-xs">Reject</button>
                  <button @click="edit(w.id)" class="bg-blue-500 text-white px-3 py-1 rounded text-xs">Edit</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div class="mt-6 flex justify-between items-center">
        <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="px-4 py-2 bg-gray-200 rounded">Previous</button>
        <span>Page {{ currentPage }} of {{ lastPage }}</span>
        <button @click="goToPage(currentPage + 1)" :disabled="currentPage === lastPage" class="px-4 py-2 bg-gray-200 rounded">Next</button>
      </div>
    </div>

    <!-- Withdraw Limit Modal -->
    <div v-if="showLimitModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Update Withdraw Limit</h3>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Withdraw Amount (USDT)</label>
          <input 
            v-model="newWithdrawLimit" 
            type="number" 
            step="0.01" 
            class="w-full border rounded px-3 py-2"
            min="0.01"
          />
        </div>
        <div v-if="limitModalError" class="text-sm text-red-600 mb-4">{{ limitModalError }}</div>
        <div class="flex justify-end space-x-2">
          <button 
            @click="showLimitModal = false; limitModalError = ''" 
            class="px-4 py-2 bg-gray-200 rounded text-sm hover:bg-gray-300"
          >
            Cancel
          </button>
          <button 
            @click="updateWithdrawLimit()" 
            class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700"
          >
            Update
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>