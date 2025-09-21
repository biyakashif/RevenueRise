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

// Avatar upload state
const showAvatarPicker = ref(false);
const saving = ref(false);
const errorMessage = ref('');
const selectedFile = ref(null);
const previewUrl = ref('');
const fallbackAvatar = 'https://placehold.co/128x128?text=Avatar';

// Copy state
const copySuccess = ref(false);

// Default avatars
const defaultAvatars = [
  // Boys avatars
  '/assets/avatar/boys/1.jpg',
  '/assets/avatar/boys/2.jpg',
  '/assets/avatar/boys/3.jpg',
  '/assets/avatar/boys/4.jpg',
  '/assets/avatar/boys/5.jpg',
  '/assets/avatar/boys/6.jpg',
  // Girls avatars
  '/assets/avatar/girls/1.jpg',
  '/assets/avatar/girls/2.jpg',
  '/assets/avatar/girls/3.jpg',
  '/assets/avatar/girls/4.jpg',
  '/assets/avatar/girls/5.jpg',
  '/assets/avatar/girls/6.jpg',
];
const selectedDefaultAvatar = ref(null);

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

// Copy invitation code to clipboard
function copyInvitationCode() {
  const code = user.invitation_code || '';
  if (code) {
    navigator.clipboard.writeText(code).then(() => {
      copySuccess.value = true;
    }).catch(() => {
      // Fallback for older browsers
      const textArea = document.createElement('textarea');
      textArea.value = code;
      document.body.appendChild(textArea);
      textArea.select();
      document.execCommand('copy');
      document.body.removeChild(textArea);
      copySuccess.value = true;
    });
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
  selectedDefaultAvatar.value = null;
}
// Handle file choose
function onFileChange(e) {
  const file = e?.target?.files?.[0];
  if (!file) return;
  selectedFile.value = file;
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
  previewUrl.value = URL.createObjectURL(file);
  selectedDefaultAvatar.value = null; // Clear default selection when custom file is chosen
}

// Select default avatar
async function selectDefaultAvatar(avatarPath) {
  try {
    const response = await fetch(avatarPath);
    const blob = await response.blob();
    const fileName = avatarPath.split('/').pop();
    const file = new File([blob], fileName, { type: blob.type });
    
    selectedFile.value = file;
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = URL.createObjectURL(blob);
    selectedDefaultAvatar.value = avatarPath;
    
    // Clear file input
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput) fileInput.value = '';
  } catch (error) {
    console.error('Error loading default avatar:', error);
    errorMessage.value = t('Failed to load avatar. Please try again.');
  }
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
  // Check if user is still authenticated
  if (!page.props.auth?.user) {
    return;
  }
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
  if (window?.Echo && user?.id) {
    window.Echo.private(`user.${user.id}`)
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
  // Removed polling since using Pusher for real-time updates
});

onUnmounted(() => {
  if (window?.Echo && user?.id) {
    window.Echo.leave(`user.${user.id}`);
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
                {{ user?.name || t('Not set') }}
              </h3>
              <span class="ml-3 inline-flex items-center justify-center bg-black text-white text-xs font-semibold rounded px-2 py-0.5">
                {{ user.vip_level || 'VIP1' }}
              </span>
            </div>
            <p class="text-sm font-medium text-gray-600 leading-none mt-1">
              {{ user?.mobile_number || t('Not set') }}
            </p>
            <div class="flex items-center mt-2">
              <p class="text-xs text-gray-500">
                {{ t('Invitation Code') }}: {{ user?.invitation_code || t('Not set') }}
              </p>
              <button
                @click="copyInvitationCode"
                :class="copySuccess ? 'text-blue-600' : 'text-gray-500'"
                class="ml-2 hover:text-blue-800"
                :title="t('Copy Invitation Code')"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
              </button>
            </div>
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
            <!-- Default Avatars -->
            <div>
              <h4 class="text-sm font-medium text-gray-700 mb-2">{{ t('Choose from defaults') }}</h4>
              <div class="grid grid-cols-3 gap-2">
                <div
                  v-for="(avatar, index) in defaultAvatars"
                  :key="avatar"
                  @click="selectDefaultAvatar(avatar)"
                  class="relative cursor-pointer rounded-lg overflow-hidden border-2 transition-all duration-200 hover:scale-105"
                  :class="selectedDefaultAvatar === avatar ? 'border-purple-500 ring-2 ring-purple-200' : 'border-gray-200'"
                >
                  <img
                    :src="avatar"
                    :alt="`Avatar ${index + 1}`"
                    class="w-full h-16 object-cover"
                    @error="handlePreviewError"
                  />
                  <div v-if="selectedDefaultAvatar === avatar" class="absolute inset-0 bg-purple-500 bg-opacity-20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            <div class="border-t pt-3">
              <h4 class="text-sm font-medium text-gray-700 mb-2">{{ t('Or upload your own') }}</h4>
              <div class="flex items-center space-x-4">
                <div class="w-24 h-24 rounded-full overflow-hidden border bg-gray-50">
                  <img :src="previewUrl || computedAvatar" :alt="t('Avatar preview')" class="w-full h-full object-cover" @error="handlePreviewError" />
                </div>
                <div>
                  <input type="file" accept="image/*" capture="user" @change="onFileChange" class="block w-full max-w-full" />
                  <p class="text-xs text-gray-500 mt-1">{{ t('PNG, JPG, or WEBP up to 5MB. You can take a photo on mobile.') }}</p>
                </div>
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
