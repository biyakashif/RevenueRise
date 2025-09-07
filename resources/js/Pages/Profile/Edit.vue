<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, reactive, computed } from 'vue';

const page = usePage();
const translations = computed(() => page.props.translations || {});
const locale = computed(() => page.props.locale || 'en');
const t = (key) => {
    const translation = translations.value[key];
    if (!translation && key && process.env.NODE_ENV === 'development') {
        console.warn(`Missing translation for key: ${key} in locale: ${locale.value}`);
    }
    return translation || key;
};
const getDefaultUser = () => ({
  id: null,
  vip_level: 'VIP1',
  mobile_number: t('Not set'),
  invitation_code: t('Not set'),
  frozen_balance: 0,
  avatar_url: null,
});

const user = reactive({
  ...getDefaultUser(),
  ...page.props.user
});

const balance = ref(0);
const frozenBalance = ref(Number(user.frozen_balance ?? 0));

let pollingInterval = null;

// Avatar upload state
const showAvatarPicker = ref(false);
const saving = ref(false);
const errorMessage = ref('');
const selectedFile = ref(null);
const previewUrl = ref('');
const fallbackAvatar = 'https://placehold.co/128x128?text=Avatar';

// Initialize avatar URL (may be null if none set)
const avatarUrl = ref(user.avatar_url || null);

// Keep avatarUrl in sync with server updates; neutral placeholder if missing
const computedAvatar = computed(() => user.avatar_url || avatarUrl.value || fallbackAvatar);

function handleImgError(e) {
  if (e && e.target) {
    e.target.src = fallbackAvatar;
  }
  if (avatarUrl.value && typeof avatarUrl.value === 'string' && avatarUrl.value.startsWith('blob:')) {
    try { URL.revokeObjectURL(avatarUrl.value); } catch (_) {}
    avatarUrl.value = null;
  }
}

function handlePreviewError(e) {
  if (e && e.target) {
    e.target.src = computedAvatar.value || fallbackAvatar;
  }
}

// Open/close picker
function openAvatarPicker() {
  showAvatarPicker.value = true;
}
function closeAvatarPicker() {
  showAvatarPicker.value = false;
  errorMessage.value = '';
  saving.value = false;
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value);
  }
  selectedFile.value = null;
  previewUrl.value = '';
}
// Handle file choose
function onFileChange(e) {
  const file = e?.target?.files?.[0];
  if (!file) return;
  selectedFile.value = file;
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
  previewUrl.value = URL.createObjectURL(file);
}

// Upload avatar to server
async function uploadAvatar() {
  if (!selectedFile.value) return;
  saving.value = true;
  errorMessage.value = '';
  try {
    const formData = new FormData();
    formData.append('avatar', selectedFile.value);
    await router.post(route('profile.avatar'), formData, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        // Optimistically show the local preview as the avatar until server URL arrives
        if (previewUrl.value) {
          avatarUrl.value = previewUrl.value;
        }
        user.avatar_url = undefined;
        // Reset modal state without revoking the blob we are now using as avatar
        selectedFile.value = null;
        previewUrl.value = '';
        showAvatarPicker.value = false;
        saving.value = false;
        // Fetch latest profile/balance which should include the new avatar_url
        fetchBalance();
      },
      onError: (errors) => {
        saving.value = false;
        errorMessage.value = errors?.avatar || t('Failed to upload avatar. Please try again.');
      },
    });
  } catch (e) {
    saving.value = false;
    errorMessage.value = `${t('Failed to upload avatar:')} ${e?.message || e}`;
  }
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
      // If we were showing a temporary blob URL, revoke it before swapping
      if (avatarUrl.value && typeof avatarUrl.value === 'string' && avatarUrl.value.startsWith('blob:')) {
        try { URL.revokeObjectURL(avatarUrl.value); } catch (_) {}
      }
      user.avatar_url = data.avatar_url;
      avatarUrl.value = data.avatar_url;
    }
  } catch (err) {
    // suppressed: error fetching balance
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
          // Clean up any temporary blob before updating to the server URL
          if (avatarUrl.value && typeof avatarUrl.value === 'string' && avatarUrl.value.startsWith('blob:')) {
            try { URL.revokeObjectURL(avatarUrl.value); } catch (_) {}
          }
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
  <Head :title="t('Profile')" />

  <AuthenticatedLayout>
    <div class="py-6 px-4 bg-gray-100">
      <section class="space-y-4">
        <div class="flex items-center space-x-3 bg-white p-4 rounded-lg shadow-sm">
          <!-- Avatar -->
          <div class="relative">
            <img
              :src="computedAvatar"
              :alt="t('Avatar')"
              class="w-16 h-16 rounded-full object-cover border"
              @error="handleImgError"
            />
            <button
              @click="openAvatarPicker"
              class="absolute -bottom-1 -right-1 bg-white border rounded-full p-1 shadow hover:bg-gray-50"
              :title="t('Change avatar')"
            >
              <svg class="w-4 h-4 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 11l6-6 3 3L12 14l-3 1 1-3z"/>
              </svg>
            </button>
          </div>

          <div class="flex-1">
            <div class="flex items-center">
              <h3 class="text-lg font-semibold text-gray-800 leading-none">
                {{ user?.mobile_number || t('Not set') }}
              </h3>
              <span class="ml-3 inline-flex items-center justify-center bg-black text-white text-xs font-semibold rounded px-2 py-0.5">
                {{ user.vip_level || 'VIP1' }}
              </span>
            </div>
            <p class="text-xs text-gray-500 mt-1">
              {{ t('Invitation ID') }}: {{ user?.invitation_code || t('Not set') }}
            </p>
          </div>
        </div>

        <div class="flex justify-between space-x-2 bg-white p-4 rounded-lg shadow-sm">
          <div class="text-center">
            <p class="text-xs font-medium text-gray-600">{{ t('Available Balance') }}</p>
            <p class="text-sm font-semibold text-gray-800">
              {{ balance.toFixed(2) }} USDT
            </p>
          </div>
          <div class="text-center">
            <p class="text-xs font-medium text-gray-600">{{ t('Frozen Balance') }}</p>
            <p class="text-sm font-semibold text-gray-800">
              {{ frozenBalance.toFixed(2) }} USDT
            </p>
          </div>
        </div>

        <!-- ✅ Navigation Links -->
        <nav class="mt-6 space-y-2">
          <Link :href="route('profile.edit')" class="flex items-center justify-between w-full py-2 px-4 text-purple-600 text-sm font-medium bg-gray-50 rounded-lg hover:bg-gray-100 transition-all duration-300">
            {{ t('Edit Profile') }}
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </Link>

          <Link :href="route('withdraw')" class="flex items-center justify-between w-full py-2 px-4 text-purple-600 text-sm font-medium bg-gray-50 rounded-lg hover:bg-gray-100 transition-all duration-300">
            {{ t('Withdraw') }}
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </Link>

          <Link :href="route('withdraw.history')" class="flex items-center justify-between w-full py-2 px-4 text-purple-600 text-sm font-medium bg-gray-50 rounded-lg hover:bg-gray-100 transition-all duration-300">
            {{ t('Withdraw History') }}
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </Link>

          <Link :href="route('password.change')" class="flex items-center justify-between w-full py-2 px-4 text-purple-600 text-sm font-medium bg-gray-50 rounded-lg hover:bg-gray-100 transition-all duration-300">
            {{ t('Change Password') }}
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
            {{ t('Log Out') }}
          </Link>
        </nav>
      </section>

      <!-- Avatar Picker Modal -->
      <div v-if="showAvatarPicker" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 overflow-y-auto overflow-x-hidden">
        <div class="bg-white rounded-lg w-11/12 max-w-sm sm:max-w-md p-4 shadow-lg max-h-[92vh] sm:max-h-[90vh] overflow-y-auto overflow-x-hidden">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">{{ t('Choose an Avatar') }}</h3>
            <button @click="closeAvatarPicker" class="text-gray-500 hover:text-gray-700">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div v-if="errorMessage" class="mb-2 text-sm text-red-600">{{ errorMessage }}</div>

          <div class="space-y-3">
            <div class="flex items-center space-x-4">
              <div class="w-24 h-24 rounded-full overflow-hidden border bg-gray-50">
                <img :src="previewUrl || computedAvatar" :alt="t('Avatar preview')" class="w-full h-full object-cover" @error="handlePreviewError" />
              </div>
              <div>
                <input type="file" accept="image/*" capture="user" @change="onFileChange" class="block w-full max-w-full" />
                <p class="text-xs text-gray-500 mt-1">{{ t('PNG, JPG, or WEBP up to 5MB. You can take a photo on mobile.') }}</p>
              </div>
            </div>
            <div class="flex justify-end space-x-2">
              <button @click="closeAvatarPicker" class="px-3 py-1 border rounded" :disabled="saving">{{ t('Cancel') }}</button>
              <button @click="uploadAvatar" class="px-3 py-1 bg-purple-600 text-white rounded disabled:opacity-50" :disabled="saving || !selectedFile">{{ saving ? t('Saving...') : t('Save') }}</button>
            </div>
          </div>

          
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
