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

const fetchUsers = async (search = '') => {
  try {
    const url = search
      ? `/admin/withdrawals?search=${encodeURIComponent(search)}`
      : `/admin/withdrawals`;
  const res = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    // Ensure withdraw_limit is properly formatted for each user
    users.value = (data.data || []).map(user => ({
      ...user,
      withdraw_limit: formatUSDT(user.withdraw_limit)
    }));
  } catch (err) {
  // suppressed: failed to load withdrawals
  }
};

onMounted(() => {
  fetchUsers(searchQuery.value);
  pollingInterval = setInterval(() => fetchUsers(searchQuery.value), 4000);
});

onUnmounted(() => {
  clearInterval(pollingInterval);
});

const searchUsers = () => {
  fetchUsers(searchQuery.value);
};

const approve = async (id) => {
  try {
  const res = await fetch(route('admin.withdrawals.approve', id), { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken } });
    if (!res.ok) throw new Error('Approve failed');
    await fetchUsers(searchQuery.value);
  } catch (err) {
  // suppressed: approve failed
  }
};

const rejectW = async (id) => {
  try {
  const res = await fetch(route('admin.withdrawals.reject', id), { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken } });
    if (!res.ok) throw new Error('Reject failed');
    await fetchUsers(searchQuery.value);
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
    await fetchUsers(searchQuery.value);
  } catch (err) {
    limitModalError.value = err.message || 'Failed to update withdraw limit';
  }
};
</script>

<template>
  <Head title="Withdrawals" />
  <AdminLayout>

    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 rounded-2xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto">
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-lg font-bold text-slate-800 drop-shadow-sm">Withdrawals</h1>
        <div class="w-1/3">
          <input v-model="searchQuery" @input="searchUsers" type="text" placeholder="Search name, mobile or code..." class="w-full h-10 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 placeholder-slate-400 backdrop-blur-sm shadow-lg" />
        </div>
      </div>
      <div v-for="user in users" :key="user.id" class="mb-4 bg-white/20 backdrop-blur-sm p-4 rounded-xl shadow-lg border border-white/30">
        <div class="flex justify-between items-start">
          <div>
            <h3 class="font-semibold text-slate-800 text-sm">{{ user.name }} <span class="text-xs text-slate-600">— {{ user.mobile_number }} / {{ user.invitation_code }}</span></h3>
            <div class="text-xs text-slate-600 mt-1">
              Withdraw Limit: <strong class="text-slate-800">{{ formatUSDT(user.withdraw_limit) }}</strong> USDT
              <button @click="openLimitModal(user.id, formatUSDT(user.withdraw_limit))" 
                class="ml-2 text-xs bg-white/20 backdrop-blur-sm px-2 py-1 rounded-lg hover:bg-white/30 transition-colors text-slate-700 border border-white/30">
                Change Limit
              </button>
            </div>
          </div>
        </div>

        <div class="mt-2 space-y-2">
          <div v-for="w in user.withdraws" :key="w.id" class="bg-white/10 backdrop-blur-sm p-3 rounded-lg border border-white/20">
            <div class="flex justify-between">
              <div>
                <div class="text-sm text-slate-800 font-semibold">{{ formatUSDT(w.amount_withdraw) }} USDT</div>
                <div class="text-xs text-slate-600">To: {{ w.crypto_wallet }}</div>
                <div class="text-xs text-slate-500">{{ new Date(w.created_at).toLocaleString() }}</div>
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
                  <button v-if="w.status === 'under review'" @click="approve(w.id)" class="bg-gradient-to-r from-green-500 to-green-600 text-white px-3 py-1 rounded text-xs hover:from-green-600 hover:to-green-700 transform hover:scale-105 transition-all duration-200">Approve</button>
                  <button v-if="w.status === 'under review'" @click="rejectW(w.id)" class="bg-gradient-to-r from-red-500 to-red-600 text-white px-3 py-1 rounded text-xs hover:from-red-600 hover:to-red-700 transform hover:scale-105 transition-all duration-200">Reject</button>
                  <button @click="edit(w.id)" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-3 py-1 rounded text-xs hover:from-blue-600 hover:to-blue-700 transform hover:scale-105 transition-all duration-200">Edit</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>


    </div>

    <!-- Withdraw Limit Modal -->
    <div v-if="showLimitModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
      <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-lg p-6 w-full max-w-md border border-white/20">
        <h3 class="text-lg font-semibold mb-4 text-slate-800">Update Withdraw Limit</h3>
        <div class="mb-4">
          <label class="block text-sm font-medium text-slate-700 mb-2">Minimum Withdraw Amount (USDT)</label>
          <input 
            v-model="newWithdrawLimit" 
            type="number" 
            step="0.01" 
            class="w-full h-10 rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-3 placeholder-slate-400 backdrop-blur-sm shadow-lg"
            min="0.01"
          />
        </div>
        <div v-if="limitModalError" class="text-sm text-red-600 mb-4 bg-red-100/80 p-2 rounded-lg">{{ limitModalError }}</div>
        <div class="flex justify-end space-x-2">
          <button 
            @click="showLimitModal = false; limitModalError = ''" 
            class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-lg text-sm hover:bg-white/30 text-slate-700 border border-white/30"
          >
            Cancel
          </button>
          <button 
            @click="updateWithdrawLimit()" 
            class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded text-sm hover:from-blue-600 hover:to-blue-700 transform hover:scale-105 transition-all duration-200"
          >
            Update
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>