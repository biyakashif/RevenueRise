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
    users.value = data.data || [];
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
          </div>
        </div>

        <div class="mt-2 space-y-3">
          <div v-for="w in user.withdraws" :key="w.id" class="bg-gray-50 p-4 rounded">
            <div class="flex justify-between">
              <div>
                <div class="text-sm"><strong>{{ w.amount_withdraw }}</strong> USDT</div>
                <div class="text-xs text-gray-600">To: {{ w.crypto_wallet }}</div>
                <div class="text-xs text-gray-500">{{ new Date(w.created_at).toLocaleString() }}</div>
              </div>
              <div class="flex flex-col items-end space-y-2">
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

                <div class="flex space-x-2">
                  <button v-if="w.status === 'pending'" @click="approve(w.id)" class="bg-green-500 text-white px-3 py-1 rounded text-xs">Approve</button>
                  <button v-if="w.status === 'pending'" @click="rejectW(w.id)" class="bg-red-500 text-white px-3 py-1 rounded text-xs">Reject</button>
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
  </AdminLayout>
</template>