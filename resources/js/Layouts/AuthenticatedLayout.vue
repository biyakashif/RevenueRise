<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    hideBottomNav: {
        type: Boolean,
        default: false
    }
});

const page = usePage();
const translations = computed(() => page.props.translations || {});
const t = (key) => translations.value[key] || key;

const unreadCount = ref(0);
const showChatNotification = ref(false);
const chatNotificationTimeout = ref(null);

const fetchUnreadCount = async () => {
    try {
        const response = await axios.get('/chat/unread-count', {
            headers: {
                'X-CSRF-TOKEN': page.props.csrf_token
            }
        });
        unreadCount.value = response.data.count;
    } catch (error) {
        console.error('Error fetching unread count:', error);
    }
};

onMounted(() => {
    fetchUnreadCount();

    // Only setup Echo listeners if Echo is available and user is authenticated
    if (window.Echo && page.props.auth && page.props.auth.user && page.props.auth.user.id) {
        // Update unread count when new messages arrive
        window.Echo.private(`chat.${page.props.auth.user.id}`)
            .listen('NewChatMessage', (e) => {
                // Increment only for messages not sent by this user
                if (e?.chat?.sender_id !== page.props.auth.user.id) {
                    unreadCount.value++;
                    // Show notification if not on chat route
                    if (!window.location.pathname.startsWith('/chat')) {
                        showChatNotification.value = true;
                        // Play notification sound
                        const audio = new Audio('/notification.mp3');
                        audio.play().catch(e => console.log('Audio play failed:', e));
                        if (chatNotificationTimeout.value) {
                            clearTimeout(chatNotificationTimeout.value);
                        }
                        chatNotificationTimeout.value = setTimeout(() => {
                            showChatNotification.value = false;
                        }, 10000);
                    }
                }
            });

        // When chat view marks messages as read, sync the badge to zero
        window.addEventListener('chat:read', () => {
            unreadCount.value = 0;
            showChatNotification.value = false;
        });

        // Listen for chat history deletion
        window.Echo.private(`chat.${page.props.auth.user.id}`)
            .listen('ChatHistoryDeleted', (e) => {
                console.log('ChatHistoryDeleted event received in layout:', e);
                if (e.userId === page.props.auth.user.id) {
                    console.log('Resetting unread count for user:', e.userId);
                    unreadCount.value = 0; // Reset unread count
                    showChatNotification.value = false;
                }
            });
    }
});

const openChat = () => {
    showChatNotification.value = false;
    if (window.route) {
        window.location.href = window.route('chat.index');
    } else {
        window.location.href = '/chat';
    }
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 flex flex-col">

        <div class="flex flex-1" :class="hideBottomNav ? '' : 'pb-16 sm:pb-0'">
            <!-- Sidebar (visible on sm and larger) -->
            <aside class="hidden sm:flex flex-col w-64 bg-white/10 backdrop-blur-xl border-r border-white/20 shadow-2xl">
                <nav class="flex flex-col p-4 space-y-2">
                    <Link
                        href="/dashboard"
                        class="flex items-center px-4 py-3 text-white/90 hover:bg-white/10 hover:text-white rounded-xl transition-all duration-300 transform hover:scale-[1.02]"
                    >
                        <div class="w-8 h-8 mr-3 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0H9" />
                            </svg>
                        </div>
                        {{ t('Home') }}
                    </Link>
                    <Link
                        href="/my-orders"
                        class="flex items-center px-4 py-3 text-white/90 hover:bg-white/10 hover:text-white rounded-xl transition-all duration-300 transform hover:scale-[1.02]"
                    >
                        <div class="w-8 h-8 mr-3 bg-gradient-to-br from-green-400 to-green-600 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </div>
                        {{ t('Order') }}
                    </Link>
                    <Link
                        href="/deposit"
                        class="flex items-center px-4 py-3 text-white/90 hover:bg-white/10 hover:text-white rounded-xl transition-all duration-300 transform hover:scale-[1.02]"
                    >
                        <div class="w-8 h-8 mr-3 bg-gradient-to-br from-orange-400 to-orange-600 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        {{ t('Deposit') }}
                    </Link>
                    <Link
                        :href="route('chat.index')"
                        class="flex items-center px-4 py-3 text-white/90 hover:bg-white/10 hover:text-white rounded-xl transition-all duration-300 transform hover:scale-[1.02] relative"
                    >
                        <div class="w-8 h-8 mr-3 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg flex items-center justify-center relative">
                            <i class="fas fa-headset text-white text-sm"></i>
                            <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] leading-none rounded-full h-4 w-4 flex items-center justify-center">{{ unreadCount }}</span>
                        </div>
                        {{ t('Support Chat') }}
                    </Link>
                    <Link
                        :href="route('profile.index')"
                        class="flex items-center px-4 py-3 text-white/90 hover:bg-white/10 hover:text-white rounded-xl transition-all duration-300 transform hover:scale-[1.02]"
                    >
                        <div class="w-8 h-8 mr-3 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        {{ t('Profile') }}
                    </Link>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 min-h-[calc(100vh-4rem)] sm:min-h-screen bg-gradient-to-br from-white/95 via-blue-50/90 to-indigo-50/95 backdrop-blur-xl mx-2 sm:mx-4 mt-2 sm:mt-4 mb-2 sm:mb-0 rounded-2xl sm:rounded-3xl shadow-2xl border border-white/40 overflow-auto">
                <div class="h-full">
                    <div v-if="$slots.header" class="mb-2 sm:mb-6">
                        <slot name="header" />
                    </div>
                    <slot />
                </div>
                    <!-- Chat Notification -->
                                    <transition name="slide-right">
                                    <div v-if="showChatNotification" class="fixed top-1/2 right-0 z-50 flex items-center bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl shadow-2xl rounded-l-2xl px-3 py-2 border border-cyan-300/30" style="transform: translateY(-50%);">
                                        <button @click="openChat" class="flex items-center space-x-2 focus:outline-none">
                                            <i class="fas fa-bell text-blue-600 text-lg"></i>
                                            <span class="font-medium text-blue-600 text-sm">{{ t('You have a message') }}</span>
                                        </button>
                                    </div>
                                    </transition>
            </main>

            <!-- Bottom Bar (visible on small screens) -->
            <nav v-if="!hideBottomNav" class="sm:hidden fixed bottom-0 left-0 right-0 bg-gradient-to-br from-white/60 via-blue-50/50 to-indigo-50/50 backdrop-blur-xl border-t border-white/20 shadow-2xl z-10 rounded-t-2xl">
                <div class="flex justify-around items-center pb-[env(safe-area-inset-bottom)]">
                    <Link
                        href="/dashboard"
                        class="flex flex-col items-center justify-center min-w-0 flex-1 py-1 px-1 text-black hover:text-gray-700 transition-colors duration-200"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0H9" />
                        </svg>
                        <span class="text-[10px] font-medium mt-0.5">{{ t('Home') }}</span>
                    </Link>
                    <Link
                        href="/my-orders"
                        class="flex flex-col items-center justify-center min-w-0 flex-1 py-1 px-1 text-black hover:text-gray-700 transition-colors duration-200"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <span class="text-[10px] font-medium mt-0.5">{{ t('Order') }}</span>
                    </Link>
                    <Link
                        href="/deposit"
                        class="flex flex-col items-center justify-center min-w-0 flex-1 py-1 px-1 text-black hover:text-gray-700 transition-colors duration-200"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span class="text-[10px] font-medium mt-0.5">{{ t('Deposit') }}</span>
                    </Link>
                    <Link
                        :href="route('chat.index')"
                        class="flex flex-col items-center justify-center min-w-0 flex-1 py-1 px-1 text-black hover:text-gray-700 transition-colors duration-200 relative"
                    >
                        <div class="relative">
                            <i class="fas fa-headset text-lg"></i>
                            <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-[8px] leading-none rounded-full h-3 w-3 flex items-center justify-center">{{ unreadCount }}</span>
                        </div>
                        <span class="text-[10px] font-medium mt-0.5">{{ t('Support') }}</span>
                    </Link>
                    <Link
                        :href="route('profile.index')"
                        class="flex flex-col items-center justify-center min-w-0 flex-1 py-1 px-1 text-black hover:text-gray-700 transition-colors duration-200"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="text-[10px] font-medium mt-0.5">{{ t('Profile') }}</span>
                    </Link>
                </div>
            </nav>
        </div>
    </div>
</template>

<style>
.slide-right-enter-active, .slide-right-leave-active {
    transition: all 0.4s cubic-bezier(.55,0,.1,1);
}
.slide-right-enter-from, .slide-right-leave-to {
    transform: translateX(100%);
    opacity: 0;
}
.slide-right-enter-to, .slide-right-leave-from {
    transform: translateX(0);
    opacity: 1;
}
</style>