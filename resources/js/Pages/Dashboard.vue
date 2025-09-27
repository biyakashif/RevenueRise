<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import VIP1Icon from '@/assets/VIP1.png';
import VIP2Icon from '@/assets/VIP2.png';
import VIP3Icon from '@/assets/VIP3.png';
import VIP4Icon from '@/assets/VIP4.png';
import VIP5Icon from '@/assets/VIP5.png';
import VIP6Icon from '@/assets/VIP6.png';
import VIP7Icon from '@/assets/VIP7.png';

const page = usePage();
const translations = computed(() => page.props.translations || {});
const t = (key) => translations.value[key] || key;
const user = page.props.auth.user || {};
const desktopSliders = ref(page.props.desktopSliders || []);
const mobileSliders = ref(page.props.mobileSliders || []);

// Slider state
const currentSlide = ref(0);
const autoSlideInterval = ref(null);

// Computed property to get current sliders based on screen size
const currentSliders = computed(() => {
  // For mobile screens, use mobile sliders only
  if (window.innerWidth < 768) {
    return mobileSliders.value;
  }
  // For desktop screens, use desktop sliders only
  return desktopSliders.value;
});

// Slider methods
const nextSlide = () => {
  currentSlide.value = (currentSlide.value + 1) % currentSliders.value.length;
};

const prevSlide = () => {
  currentSlide.value = currentSlide.value === 0 ? currentSliders.value.length - 1 : currentSlide.value - 1;
};

const goToSlide = (index) => {
  currentSlide.value = index;
};

// Auto slide functionality
const startAutoSlide = () => {
  if (autoSlideInterval.value) {
    clearInterval(autoSlideInterval.value);
  }
  if (currentSliders.value.length > 1) {
    autoSlideInterval.value = setInterval(() => {
      nextSlide();
    }, 5000); // Change slide every 5 seconds
  }
};

const stopAutoSlide = () => {
  if (autoSlideInterval.value) {
    clearInterval(autoSlideInterval.value);
    autoSlideInterval.value = null;
  }
};

// Watch for changes in sliders and restart auto slide
watch(currentSliders, (newSliders) => {
  currentSlide.value = 0;
  if (newSliders.length > 1) {
    startAutoSlide();
  } else {
    stopAutoSlide();
  }
}, { immediate: true });

// Handle window resize to switch between desktop and mobile sliders
const handleResize = () => {
  // Reset to first slide when switching between desktop/mobile
  currentSlide.value = 0;
};

onMounted(() => {
  window.addEventListener('resize', handleResize);
  if (currentSliders.value.length > 1) {
    startAutoSlide();
  }

  // Listen for real-time slider updates
  try {
    if (window.Echo) {
      window.Echo.channel('sliders')
        .listen('.slider.updated', (e) => {
          console.log('Slider updated event received:', e);
          // Update the slider data reactively
          desktopSliders.value = e.desktopSliders || [];
          mobileSliders.value = e.mobileSliders || [];
        });
    }
  } catch (error) {
    console.error('Error setting up Echo listener for sliders:', error);
  }
});

onUnmounted(() => {
  window.removeEventListener('resize', handleResize);
  stopAutoSlide();

  // Cleanup Echo listener
  try {
    if (window.Echo) {
      window.Echo.leaveChannel('sliders');
    }
  } catch (error) {
    console.error('Error cleaning up Echo listener for sliders:', error);
  }
});

// VIP levels with costs and icons
const levels = [
  { id: 1, name: 'VIP1', cost: null, icon: VIP1Icon },
  { id: 2, name: 'VIP2', cost: 300.0, icon: VIP2Icon },
  { id: 3, name: 'VIP3', cost: 750.0, icon: VIP3Icon },
  { id: 4, name: 'VIP4', cost: 1500.0, icon: VIP4Icon },
  { id: 5, name: 'VIP5', cost: 3500.0, icon: VIP5Icon },
  { id: 6, name: 'VIP6', cost: 6500.0, icon: VIP6Icon },
  { id: 7, name: 'VIP7', cost: 10000.0, icon: VIP7Icon }
];

// Normalize user's VIP name robustly and compute numeric level.
// Handles variants like "VIP2", "vip 2", 2, null, or messy strings.
const currentVipName = computed(() => {
  let v = user.vip_level ?? user.vip ?? null;
  if (v === null || v === undefined || v === '') return 'VIP1';
  if (typeof v === 'object') {
    if (v.name) v = v.name;
    else v = String(v);
  }
  v = String(v).trim().toUpperCase();
  if (/^\d+$/.test(v)) return `VIP${v}`;
  const m = v.match(/VIP\s*([0-9]+)/i);
  if (m && m[1]) return `VIP${m[1]}`;
  const digits = v.replace(/\D/g, '');
  if (digits) return `VIP${digits}`;
  return 'VIP1';
});

const currentLevel = computed(() => {
  const num = Number(currentVipName.value.replace(/\D/g, '')) || 1;
  return num >= 1 ? num : 1;
});

// Expand/collapse control for showing all VIPs inline
const showAll = ref(false);
const visibleLevels = computed(() => (showAll.value ? levels : levels.slice(0, 4)));

// Invite functionality
const showShareModal = ref(false);
const inviteUrl = computed(() => {
  return `${window.location.origin}/register?invitation_code=${user.invitation_code}`;
});

const openShareModal = () => {
  showShareModal.value = true;
};

const closeShareModal = () => {
  showShareModal.value = false;
};

const shareToFacebook = () => {
  const url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(inviteUrl.value)}`;
  window.open(url, '_blank', 'width=600,height=400');
};

const shareToTwitter = () => {
  const text = 'Join me on this amazing platform!';
  const url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(inviteUrl.value)}`;
  window.open(url, '_blank', 'width=600,height=400');
};

const shareToWhatsApp = () => {
  const text = `Join me on this amazing platform! ${inviteUrl.value}`;
  const url = `https://wa.me/?text=${encodeURIComponent(text)}`;
  window.open(url, '_blank');
};

const copyLink = async () => {
  try {
    await navigator.clipboard.writeText(inviteUrl.value);
    alert('Link copied to clipboard!');
  } catch (err) {
    const textArea = document.createElement('textarea');
    textArea.value = inviteUrl.value;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand('copy');
    document.body.removeChild(textArea);
    alert('Link copied to clipboard!');
  }
};

function fmt(n) {
  return Number(n || 0).toFixed(2);
}
</script>

<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout>
    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto">
      <!-- Image Slider -->
      <div v-if="currentSliders.length > 0" class="mb-4 sm:mb-6">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-cyan-400/30 via-blue-500/20 to-indigo-600/30 backdrop-blur-xl shadow-2xl border border-cyan-300/30">
          <div class="flex transition-transform duration-500 ease-in-out" :style="{ transform: `translateX(-${currentSlide * 100}%)` }">
            <div
              v-for="(slider, index) in currentSliders"
              :key="slider.id"
              class="flex-shrink-0 w-full"
            >
              <img
                :src="`/storage/${slider.image_path}`"
                :alt="slider.title || 'Slider image'"
                class="w-full h-40 sm:h-48 md:h-64 object-cover"
              />
              <div v-if="slider.title" class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent text-cyan-100 p-6">
                <h3 class="text-lg font-semibold drop-shadow-lg">{{ slider.title }}</h3>
              </div>
            </div>
          </div>



          <!-- Dots indicator -->
          <div v-if="currentSliders.length > 1" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2">
            <button
              v-for="(slider, index) in currentSliders"
              :key="slider.id"
              @click="goToSlide(index)"
              :class="index === currentSlide ? 'bg-cyan-300' : 'bg-white/50'"
              class="w-3 h-3 rounded-full transition-all duration-300 shadow-lg backdrop-blur-sm"
            ></button>
          </div>
        </div>
      </div>

      <!-- Services Card -->
      <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30 mb-4 sm:mb-6">
        <div class="grid grid-cols-4 sm:grid-cols-4 lg:grid-cols-8 gap-3">
          <Link href="/service" class="block text-center transition-all duration-300 transform hover:scale-105">
            <div class="w-10 h-10 sm:w-12 sm:h-12 mx-auto mb-2 sm:mb-3 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl sm:rounded-2xl flex items-center justify-center shadow-lg transform rotate-3 hover:rotate-0 transition-transform">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
              </svg>
            </div>
            <span class="block text-slate-700 text-xs sm:text-sm font-medium hover:text-slate-900 transition-colors drop-shadow-sm">{{ t('Service') }}</span>
          </Link>
          <Link href="/event" class="block text-center transition-all duration-300 transform hover:scale-105">
            <div class="w-10 h-10 sm:w-12 sm:h-12 mx-auto mb-2 sm:mb-3 bg-gradient-to-br from-green-400 to-green-600 rounded-xl sm:rounded-2xl flex items-center justify-center shadow-lg transform rotate-3 hover:rotate-0 transition-transform">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <span class="block text-slate-700 text-xs sm:text-sm font-medium hover:text-slate-900 transition-colors drop-shadow-sm">{{ t('Event') }}</span>
          </Link>
          <Link :href="route('withdraw')" class="block text-center transition-all duration-300 transform hover:scale-105">
            <div class="w-10 h-10 sm:w-12 sm:h-12 mx-auto mb-2 sm:mb-3 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl sm:rounded-2xl flex items-center justify-center shadow-lg transform rotate-3 hover:rotate-0 transition-transform">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <span class="block text-slate-700 text-xs sm:text-sm font-medium hover:text-slate-900 transition-colors drop-shadow-sm">{{ t('Withdraw') }}</span>
          </Link>
          <Link href="/deposit" class="block text-center transition-all duration-300 transform hover:scale-105">
            <div class="w-10 h-10 sm:w-12 sm:h-12 mx-auto mb-2 sm:mb-3 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl sm:rounded-2xl flex items-center justify-center shadow-lg transform rotate-3 hover:rotate-0 transition-transform">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
            </div>
            <span class="block text-slate-700 text-xs sm:text-sm font-medium hover:text-slate-900 transition-colors drop-shadow-sm">{{ t('Deposit') }}</span>
          </Link>
          <Link href="/terms" class="block text-center transition-all duration-300 transform hover:scale-105">
            <div class="w-10 h-10 sm:w-12 sm:h-12 mx-auto mb-2 sm:mb-3 bg-gradient-to-br from-red-400 to-red-600 rounded-xl sm:rounded-2xl flex items-center justify-center shadow-lg transform rotate-3 hover:rotate-0 transition-transform">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v12a2 2 0 01-2 2z" />
              </svg>
            </div>
            <span class="block text-slate-700 text-xs sm:text-sm font-medium hover:text-slate-900 transition-colors drop-shadow-sm">{{ t('T&C') }}</span>
          </Link>
          <Link href="/certificate" class="block text-center transition-all duration-300 transform hover:scale-105">
            <div class="w-10 h-10 sm:w-12 sm:h-12 mx-auto mb-2 sm:mb-3 bg-gradient-to-br from-teal-400 to-teal-600 rounded-xl sm:rounded-2xl flex items-center justify-center shadow-lg transform rotate-3 hover:rotate-0 transition-transform">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
            </div>
            <span class="block text-slate-700 text-xs sm:text-sm font-medium hover:text-slate-900 transition-colors drop-shadow-sm">{{ t('Certificate') }}</span>
          </Link>
          <Link href="/faqs" class="block text-center transition-all duration-300 transform hover:scale-105">
            <div class="w-10 h-10 sm:w-12 sm:h-12 mx-auto mb-2 sm:mb-3 bg-gradient-to-br from-pink-400 to-pink-600 rounded-xl sm:rounded-2xl flex items-center justify-center shadow-lg transform rotate-3 hover:rotate-0 transition-transform">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <span class="block text-slate-700 text-xs sm:text-sm font-medium hover:text-slate-900 transition-colors drop-shadow-sm">{{ t('FAQs') }}</span>
          </Link>
          <Link href="/about" class="block text-center transition-all duration-300 transform hover:scale-105">
            <div class="w-10 h-10 sm:w-12 sm:h-12 mx-auto mb-2 sm:mb-3 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-xl sm:rounded-2xl flex items-center justify-center shadow-lg transform rotate-3 hover:rotate-0 transition-transform">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <span class="block text-slate-700 text-xs sm:text-sm font-medium hover:text-slate-900 transition-colors drop-shadow-sm">{{ t('About') }}</span>
          </Link>
        </div>
      </div>


      <!-- VIP Levels -->
      <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-base sm:text-lg font-bold text-slate-800 drop-shadow-sm">{{ t('VIP Levels') }}</h2>

          <button
            type="button"
            @click="showAll = !showAll"
            class="text-slate-600 inline-flex items-center gap-1 sm:gap-2 text-xs font-semibold hover:text-slate-800 transition-colors px-2 sm:px-3 py-1 rounded-lg sm:rounded-xl hover:bg-slate-200/20 backdrop-blur-sm"
          >
            <span v-if="!showAll">{{ t('View More') }}</span>
            <span v-else>{{ t('View Less') }}</span>
            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path v-if="!showAll" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
        </div>

        <div class="space-y-2 sm:space-y-3">
          <template v-for="level in visibleLevels" :key="level.id">
            <div class="bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm p-3 sm:p-4 rounded-xl flex items-center justify-between shadow-lg hover:shadow-xl transition-all duration-300 border border-white/30">
              <div class="flex items-center gap-3">
                <img :src="level.icon" alt="" class="w-10 h-10 sm:w-12 sm:h-12 rounded-full object-cover shadow-md"/>
                <div>
                  <div class="text-sm sm:text-base font-bold text-slate-800">{{ level.name }}</div>
                  <div class="text-xs sm:text-sm text-slate-600 font-medium">
                    <span v-if="level.id === 1">{{ t('Free') }}</span>
                    <span v-else>{{ fmt(level.cost) }} USDT</span>
                  </div>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <span v-if="level.id === currentLevel" class="px-2 py-1 text-xs bg-gradient-to-r from-emerald-400/80 to-green-400/80 text-white rounded-full font-medium shadow-sm">{{ t('Current') }}</span>
                
                <Link
                  v-else-if="level.id !== 1"
                  :href="route('vip.purchase', { level: level.name })"
                  class="text-xs text-white bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 font-medium px-3 py-1 rounded-full shadow-sm transition-all"
                >
                  {{ t('Upgrade now') }}
                </Link>
              </div>
            </div>
          </template>
        </div>
      </div>

      <!-- Invite Card -->
      <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30 mt-4 sm:mt-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-2xl flex items-center justify-center shadow-lg transform rotate-3 hover:rotate-0 transition-transform">
              <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
              </svg>
            </div>
            <div>
              <h3 class="text-base sm:text-lg font-bold text-slate-800 drop-shadow-sm">Invite Friends</h3>
              <p class="text-xs sm:text-sm text-slate-600 font-medium">Share and earn rewards</p>
            </div>
          </div>
          
          <button
            @click="openShareModal"
            class="text-xs sm:text-sm text-white bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 font-medium px-4 py-2 rounded-full shadow-sm transition-all transform hover:scale-105"
          >
            Invite Now
          </button>
        </div>
      </div>

      <!-- Share Modal -->
      <div v-if="showShareModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-start justify-center z-50 p-4 pt-32" @click="closeShareModal">
        <div class="bg-white/95 backdrop-blur-xl rounded-3xl p-6 max-w-sm w-full shadow-2xl border border-white/40" @click.stop>
          <div class="text-center mb-6">
            <h3 class="text-lg font-bold text-slate-800 mb-2">Share Invite Link</h3>
            <p class="text-sm text-slate-600">Invite friends and earn rewards</p>
          </div>
          
          <div class="grid grid-cols-2 gap-4 mb-6">
            <button @click="shareToFacebook" class="flex flex-col items-center p-4 bg-blue-500 hover:bg-blue-600 rounded-2xl text-white transition-all transform hover:scale-105">
              <svg class="w-6 h-6 mb-2" fill="currentColor" viewBox="0 0 24 24">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
              </svg>
              <span class="text-xs font-medium">Facebook</span>
            </button>
            
            <button @click="shareToTwitter" class="flex flex-col items-center p-4 bg-black hover:bg-gray-800 rounded-2xl text-white transition-all transform hover:scale-105">
              <svg class="w-6 h-6 mb-2" fill="currentColor" viewBox="0 0 24 24">
                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
              </svg>
              <span class="text-xs font-medium">X (Twitter)</span>
            </button>
            
            <button @click="shareToWhatsApp" class="flex flex-col items-center p-4 bg-green-500 hover:bg-green-600 rounded-2xl text-white transition-all transform hover:scale-105">
              <svg class="w-6 h-6 mb-2" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.465 3.488"/>
              </svg>
              <span class="text-xs font-medium">WhatsApp</span>
            </button>
            
            <button @click="copyLink" class="flex flex-col items-center p-4 bg-gray-500 hover:bg-gray-600 rounded-2xl text-white transition-all transform hover:scale-105">
              <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
              <span class="text-xs font-medium">Copy Link</span>
            </button>
          </div>
          
          <div class="bg-gray-50 rounded-xl p-3 mb-4">
            <p class="text-xs text-gray-600 mb-1">Your invite link:</p>
            <p class="text-sm font-mono text-gray-800 break-all">{{ inviteUrl }}</p>
          </div>
          
          <button @click="closeShareModal" class="w-full py-3 bg-gray-200 hover:bg-gray-300 rounded-xl text-gray-700 font-medium transition-all">
            Close
          </button>
        </div>
      </div>

    </div>
  </AuthenticatedLayout>
</template>