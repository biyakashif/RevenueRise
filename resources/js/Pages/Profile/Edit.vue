<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, reactive, computed } from 'vue';

const page = usePage();
const user = reactive(page.props.user || {
  id: null,
  vip_level: 'VIP1',
  mobile_number: 'Not set',
  invitation_code: 'Not set',
  frozen_balance: 0,
  avatar_url: null,
});

const balance = ref(0);
const frozenBalance = ref(Number(user.frozen_balance ?? 0));

let pollingInterval = null;

// Avatar picker state
const showAvatarPicker = ref(false);
const seeds = ref([]);
const saving = ref(false);
const errorMessage = ref('');

// DiceBear v6 Avatar URL
function diceBearUrl(seed) {
  const encoded = encodeURIComponent(String(seed));
  return `https://api.dicebear.com/6.x/adventurer/svg?seed=${encoded}`;
}

// Initialize avatar URL
const avatarUrl = ref(
  user.avatar_url || diceBearUrl(user.mobile_number || user.invitation_code || `user-${user.id || '0'}`)
);

// Keep avatarUrl in sync with server updates
const computedAvatar = computed(() => user.avatar_url || avatarUrl.value);

// Generate 12 seeds for avatar previews
function generateSeeds() {
  const base = (user.mobile_number || user.invitation_code || `user-${user.id || '0'}`).toString();
  const s = new Set();
  s.add(base);
  for (let i = 1; i <= 12; i++) {
    s.add(`${base}-${i}`);
  }
  seeds.value = Array.from(s);
}

// Open/close picker
function openAvatarPicker() {
  generateSeeds();
  showAvatarPicker.value = true;
}
function closeAvatarPicker() {
  showAvatarPicker.value = false;
  errorMessage.value = '';
  saving.value = false;
}

// Save avatar to server
async function saveAvatar(seed) {
  const url = diceBearUrl(seed);
  saving.value = true;
  errorMessage.value = '';

  try {
    await router.post(route('profile.update'), { avatar_url: url }, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        // Inertia refreshes props from `profile.index`
        user.avatar_url = url;
        avatarUrl.value = url;
        showAvatarPicker.value = false;
        saving.value = false;
      },
      onError: (errors) => {
        saving.value = false;
        errorMessage.value = errors?.avatar_url || 'Failed to save avatar. Please try again.';
      },
    });
  } catch (e) {
    saving.value = false;
    errorMessage.value = 'Failed to save avatar: ' + e.message;
  }
}


// Pick and save an avatar
function pickAvatar(seed) {
  saveAvatar(seed);
}

const fetchBalance = async () => {
  try {
    const res = await fetch(route('balance'), {
      headers: { 'Accept': 'application/json' },
    });
    if (!res.ok) throw new Error(`HTTP error ${res.status}`);
    const data = await res.json();
    balance.value = Number(data.balance ?? 0);
    user.vip_level = data.vip_level ?? user.vip_level;
    if (data.avatar_url) {
      user.avatar_url = data.avatar_url;
      avatarUrl.value = data.avatar_url;
    }
  } catch (err) {
    console.error('Error fetching balance:', err);
  }
};

onMounted(() => {
  fetchBalance();
  if (window?.Echo && user?.mobile_number) {
    window.Echo.private(`user.${user.mobile_number}`)
      .listen('BalanceUpdated', (e) => {
        if (e && e.balance !== undefined) balance.value = Number(e.balance);
      })
      .listen('VipLevelUpdated', (e) => {
        if (e && e.vip_level !== undefined) user.vip_level = e.vip_level;
      })
      .listen('AvatarUpdated', (e) => {
        if (e && e.avatar_url) {
          user.avatar_url = e.avatar_url;
          avatarUrl.value = e.avatar_url;
        }
      });
  }
  pollingInterval = setInterval(fetchBalance, 5000);
});

onUnmounted(() => {
  if (pollingInterval) clearInterval(pollingInterval);
  if (window?.Echo && user?.mobile_number) {
    window.Echo.leave(`user.${user.mobile_number}`);
  }
});
</script>

<template>
  <Head title="Profile" />

  <AuthenticatedLayout>
    <div class="py-6 px-4 bg-gray-100">
      <section class="space-y-4">
        <div class="flex items-center space-x-3 bg-white p-4 rounded-lg shadow-sm">
          <!-- Avatar -->
          <div class="relative">
            <img
              :src="computedAvatar"
              alt="Avatar"
              class="w-16 h-16 rounded-full object-cover border"
            />
            <button
              @click="openAvatarPicker"
              class="absolute -bottom-1 -right-1 bg-white border rounded-full p-1 shadow hover:bg-gray-50"
              title="Change avatar"
            >
              <svg class="w-4 h-4 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 11l6-6 3 3L12 14l-3 1 1-3z"/>
              </svg>
            </button>
          </div>

          <div class="flex-1">
            <div class="flex items-center">
              <h3 class="text-lg font-semibold text-gray-800 leading-none">
                {{ user?.mobile_number || 'Not set' }}
              </h3>
              <span class="ml-3 inline-flex items-center justify-center bg-black text-white text-xs font-semibold rounded px-2 py-0.5">
                {{ user.vip_level || 'VIP1' }}
              </span>
            </div>
            <p class="text-xs text-gray-500 mt-1">
              Invitation ID: {{ user?.invitation_code || 'Not set' }}
            </p>
          </div>
        </div>

        <div class="flex justify-between space-x-2 bg-white p-4 rounded-lg shadow-sm">
          <div class="text-center">
            <p class="text-xs font-medium text-gray-600">Available Balance</p>
            <p class="text-sm font-semibold text-gray-800">
              {{ balance.toFixed(2) }} USDT
            </p>
          </div>
          <div class="text-center">
            <p class="text-xs font-medium text-gray-600">Frozen Balance</p>
            <p class="text-sm font-semibold text-gray-800">
              {{ frozenBalance.toFixed(2) }} USDT
            </p>
          </div>
        </div>

        <!-- ✅ Navigation Links -->
        <nav class="mt-6 space-y-2">
          <Link :href="route('profile.edit')" class="flex items-center justify-between w-full py-2 px-4 text-purple-600 text-sm font-medium bg-gray-50 rounded-lg hover:bg-gray-100 transition-all duration-300">
            Edit Profile
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </Link>

          <Link :href="route('withdraw')" class="flex items-center justify-between w-full py-2 px-4 text-purple-600 text-sm font-medium bg-gray-50 rounded-lg hover:bg-gray-100 transition-all duration-300">
            Withdraw
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </Link>

          <Link :href="route('withdraw.history')" class="flex items-center justify-between w-full py-2 px-4 text-purple-600 text-sm font-medium bg-gray-50 rounded-lg hover:bg-gray-100 transition-all duration-300">
            Withdraw History
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </Link>

          <Link :href="route('password.change')" class="flex items-center justify-between w-full py-2 px-4 text-purple-600 text-sm font-medium bg-gray-50 rounded-lg hover:bg-gray-100 transition-all duration-300">
            Change Password
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </Link>

          <Link
            :href="route('logout')"
            method="post"
            as="button"
            class="w-full rounded-full px-4 py-3 bg-red-600 text-white font-semibold text-lg text-center transition-all duration-300 hover:bg-red-700 hover:scale-105 shadow-lg"
          >
            Log Out
          </Link>
        </nav>
      </section>

      <!-- Avatar Picker Modal -->
      <div v-if="showAvatarPicker" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="bg-white rounded-lg w-full max-w-3xl p-4">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Choose an Avatar</h3>
            <button @click="closeAvatarPicker" class="text-gray-500 hover:text-gray-700">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div v-if="errorMessage" class="mb-2 text-sm text-red-600">{{ errorMessage }}</div>

          <div class="grid grid-cols-3 sm:grid-cols-5 md:grid-cols-6 gap-3">
            <template v-for="seed in seeds" :key="seed">
              <button @click="() => pickAvatar(seed)" :disabled="saving" class="focus:outline-none">
                <img
                  :src="diceBearUrl(seed)"
                  :alt="seed"
                  class="w-full h-24 object-cover rounded border hover:scale-105 transition-transform"
                />
              </button>
            </template>
          </div>

          <div class="mt-4 flex justify-end">
            <button @click="closeAvatarPicker" class="px-3 py-1 border rounded">Cancel</button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
