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
    formData.append('_token', page.props.csrf_token);
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
      headers: { 
        'Accept': 'application/json',
        'X-CSRF-TOKEN': page.props.csrf_token
      },
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

const refreshCSRFToken = async () => {
    const res = await fetch(route('csrf-token'), {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    });
    const data = await res.json();
    const token = data.token;
    document.head.querySelector('meta[name="csrf-token"]').content = token;
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
};

const logout = async () => {
    try {
        await refreshCSRFToken();
        await router.post(route('logout'), {
            _token: page.props.csrf_token
        });
    } catch (error) {
        if (error.response && error.response.status === 419) {
            window.location.reload();
        }
    }
};
</script>

<template>
  <Head :title="t('Profile')" />

  <AuthenticatedLayout>
    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto flex items-center justify-center">
      <div class="w-full">
        <section class="space-y-4">
          <div class="flex items-center space-x-3 bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30">
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
                class="absolute -bottom-1 -right-1 bg-gradient-to-br from-white/95 to-blue-50/90 border border-cyan-300/30 rounded-full p-1 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105 backdrop-blur-sm"
                :title="t('Change avatar')"
              >
                <svg class="w-4 h-4 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 11l6-6 3 3L12 14l-3 1 1-3z"/>
                </svg>
              </button>
            </div>

            <div class="flex-1">
              <div class="flex items-center">
                <h3 class="text-sm font-semibold text-slate-800 leading-none drop-shadow-sm">
                  {{ user?.name || t('Not set') }}
                </h3>
                <span class="ml-3 inline-flex items-center justify-center bg-gradient-to-r from-slate-800 to-slate-900 text-white text-xs font-semibold rounded-full px-3 py-1 shadow-lg">
                  {{ user.vip_level || 'VIP1' }}
                </span>
              </div>
              <p class="text-sm font-medium text-slate-600 leading-none mt-1 drop-shadow-sm">
                {{ user?.mobile_number || t('Not set') }}
              </p>
              <div class="flex items-center mt-2">
                <p class="text-xs text-slate-600 drop-shadow-sm">
                  {{ t('Invitation Code') }}: {{ user?.invitation_code || t('Not set') }}
                </p>
                <button
                  @click="copyInvitationCode"
                  :class="copySuccess ? 'text-cyan-600' : 'text-slate-600'"
                  class="ml-2 hover:text-cyan-700 transition-colors duration-200"
                  :title="t('Copy Invitation Code')"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <div class="flex justify-between space-x-2 bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30">
            <div class="text-center">
              <p class="text-xs font-medium text-slate-600 drop-shadow-sm">{{ t('Available Balance') }}</p>
              <p class="text-xs font-semibold text-slate-800 drop-shadow-sm">
                {{ balance.toFixed(2) }} USDT
              </p>
            </div>
            <div class="text-center">
              <p class="text-xs font-medium text-slate-600 drop-shadow-sm">{{ t('Frozen Balance') }}</p>
              <p class="text-xs font-semibold text-slate-800 drop-shadow-sm">
                {{ frozenBalance.toFixed(2) }} USDT
              </p>
            </div>
          </div>

          <!-- Navigation Links -->
          <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl shadow-2xl border border-cyan-300/30 overflow-hidden">
            <nav class="divide-y divide-white/20">
              <Link :href="route('profile.edit')" class="flex items-center justify-between w-full py-4 px-5 text-slate-800 text-sm font-medium hover:bg-white/20 transition-all duration-300 group">
                <div class="flex items-center space-x-3">
                  <svg class="w-5 h-5 text-blue-600 group-hover:text-blue-700 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                  <span class="group-hover:text-slate-900 transition-colors">{{ t('Edit Profile') }}</span>
                </div>
                <svg class="w-4 h-4 text-slate-500 group-hover:text-slate-700 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
              </Link>

              <Link :href="route('balance.records')" class="flex items-center justify-between w-full py-4 px-5 text-slate-800 text-sm font-medium hover:bg-white/20 transition-all duration-300 group">
                <div class="flex items-center space-x-3">
                  <svg class="w-5 h-5 text-blue-600 group-hover:text-blue-700 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                  </svg>
                  <span class="group-hover:text-slate-900 transition-colors">{{ t('Balance Record') }}</span>
                </div>
                <svg class="w-4 h-4 text-slate-500 group-hover:text-slate-700 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
              </Link>

              <Link :href="route('withdraw')" class="flex items-center justify-between w-full py-4 px-5 text-slate-800 text-sm font-medium hover:bg-white/20 transition-all duration-300 group">
                <div class="flex items-center space-x-3">
                  <svg class="w-5 h-5 text-blue-600 group-hover:text-blue-700 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                  </svg>
                  <span class="group-hover:text-slate-900 transition-colors">{{ t('Withdraw') }}</span>
                </div>
                <svg class="w-4 h-4 text-slate-500 group-hover:text-slate-700 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
              </Link>

              <Link :href="route('withdraw.history')" class="flex items-center justify-between w-full py-4 px-5 text-slate-800 text-sm font-medium hover:bg-white/20 transition-all duration-300 group">
                <div class="flex items-center space-x-3">
                  <svg class="w-5 h-5 text-blue-600 group-hover:text-blue-700 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                  <span class="group-hover:text-slate-900 transition-colors">{{ t('Withdraw History') }}</span>
                </div>
                <svg class="w-4 h-4 text-slate-500 group-hover:text-slate-700 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
              </Link>

              <Link :href="route('password.change')" class="flex items-center justify-between w-full py-4 px-5 text-slate-800 text-sm font-medium hover:bg-white/20 transition-all duration-300 group">
                <div class="flex items-center space-x-3">
                  <svg class="w-5 h-5 text-blue-600 group-hover:text-blue-700 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                  </svg>
                  <span class="group-hover:text-slate-900 transition-colors">{{ t('Change Password') }}</span>
                </div>
                <svg class="w-4 h-4 text-slate-500 group-hover:text-slate-700 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
              </Link>
            </nav>
            
            <div class="p-4 border-t border-white/20">
              <form @submit.prevent="logout" class="w-full">
                <button
                  type="submit"
                  class="w-full rounded-xl px-4 py-3 bg-gradient-to-r from-red-500/80 to-red-600/80 hover:from-red-600/90 hover:to-red-700/90 text-white font-medium text-sm text-center transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl"
                >
                  {{ t('Log Out') }}
                </button>
              </form>
            </div>
          </div>
        </section>
      </div>
    </div>

    <!-- Avatar Picker Modal -->
    <div v-if="showAvatarPicker" class="fixed inset-0 z-50 flex items-start justify-center bg-black/50 backdrop-blur-sm p-4 pt-8 sm:pt-16">
      <div class="bg-gradient-to-br from-white/95 via-blue-50/90 to-indigo-50/95 backdrop-blur-xl rounded-2xl w-full max-w-sm p-4 shadow-2xl border border-white/40">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-base font-semibold text-slate-800">{{ t('Choose an Avatar') }}</h3>
          <button @click="closeAvatarPicker" class="text-slate-500 hover:text-slate-700">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div v-if="errorMessage" class="mb-3 text-xs text-red-600 bg-red-50 p-2 rounded-lg">{{ errorMessage }}</div>

        <div class="grid grid-cols-4 gap-2 mb-4">
          <div
            v-for="(avatar, index) in defaultAvatars"
            :key="avatar"
            @click="selectDefaultAvatar(avatar)"
            class="relative cursor-pointer rounded-lg overflow-hidden border-2 transition-all duration-200 hover:scale-105"
            :class="selectedDefaultAvatar === avatar ? 'border-cyan-500 ring-1 ring-cyan-200' : 'border-white/40'"
          >
            <img
              :src="avatar"
              :alt="`Avatar ${index + 1}`"
              class="w-full h-12 object-cover"
              @error="handlePreviewError"
            />
            <div v-if="selectedDefaultAvatar === avatar" class="absolute inset-0 bg-cyan-500 bg-opacity-20 flex items-center justify-center">
              <svg class="w-4 h-4 text-cyan-600" fill="currentColor" viewBox="0 0 24 24">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
              </svg>
            </div>
          </div>
        </div>

        <div class="border-t border-white/30 pt-3 mb-4">
          <h4 class="text-sm font-medium text-slate-700 mb-3">{{ t('Or upload your own') }}</h4>
          <div class="flex items-center space-x-3 mb-3">
            <div class="w-12 h-12 rounded-full overflow-hidden border border-white/40 bg-white/50 flex-shrink-0">
              <img :src="previewUrl || computedAvatar" :alt="t('Avatar preview')" class="w-full h-full object-cover" @error="handlePreviewError" />
            </div>
            <div class="flex-1">
              <input type="file" accept="image/*" capture="user" @change="onFileChange" class="block w-full text-xs" />
            </div>
          </div>
          <p class="text-xs text-slate-500 mb-3">{{ t('PNG, JPG, or WEBP up to 5MB. You can take a photo on mobile.') }}</p>
        </div>
        
        <div class="flex justify-end space-x-2">
          <button @click="closeAvatarPicker" class="px-3 py-2 text-xs border border-white/40 rounded-lg bg-white/50 hover:bg-white/70 transition-all" :disabled="saving">{{ t('Cancel') }}</button>
          <button @click="uploadAvatar" class="px-3 py-2 text-xs bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white rounded-lg disabled:opacity-50 transition-all shadow-lg" :disabled="saving || !selectedFile">{{ saving ? t('Saving...') : t('Save') }}</button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
