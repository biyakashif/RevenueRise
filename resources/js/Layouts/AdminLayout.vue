<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 flex flex-col">
        <div class="flex flex-1">
            <!-- Chat Notification -->
            <transition name="slide-right">
                <div v-if="supportHasNewMessage && !route().current('admin.support')" class="fixed top-1/2 right-0 z-50 flex items-center bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl shadow-2xl rounded-l-2xl px-3 py-2 border border-cyan-300/30" style="transform: translateY(-50%);">
                    <button @click="() => { supportHasNewMessage = false; router.visit(route('admin.support')); }" class="flex items-center space-x-2 focus:outline-none">
                        <i class="fas fa-bell text-blue-600 text-lg"></i>
                        <span class="font-medium text-blue-600 text-sm">You have a support message</span>
                    </button>
                </div>
            </transition>
            <!-- Sidebar -->
            <aside :class="{ 'w-56': !isCollapsed, 'w-14': isCollapsed }" class="flex flex-col bg-white/10 backdrop-blur-xl border-r border-white/20 shadow-2xl transition-all duration-300">
                <div class="flex justify-end p-3">
                    <button @click="toggleSidebar" class="text-white hover:text-white/80 focus:outline-none">
                        <i :class="{ 'fa-rotate-180': isCollapsed }" class="fas fa-arrow-right text-sm transition-transform duration-300"></i>
                    </button>
                </div>
                <nav class="flex flex-col p-3 space-y-1" v-show="!isCollapsed">
                    <!-- Support Chat -->
                    <Link
                        :href="route('admin.support')"
                        class="flex items-center px-3 py-2 text-white hover:bg-white/10 hover:text-white/90 rounded-lg transition-all duration-200 relative"
                        :class="{ 'bg-white/10 text-white': route().current('admin.support') }"
                        @click="supportHasNewMessage = false"
                    >
                        <div class="w-6 h-6 mr-2 bg-gradient-to-br from-purple-400 to-purple-600 rounded-md flex items-center justify-center">
                            <i class="fas fa-headset text-white text-xs"></i>
                        </div>
                        <span class="text-sm">Support Chat</span>
                        <span v-if="supportHasNewMessage" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center animate-pulse">!</span>
                    </Link>

                    <!-- User Info -->
                    <Link
                        :href="route('admin.users')"
                        class="flex items-center px-3 py-2 text-white hover:bg-white/10 hover:text-white/90 rounded-lg transition-all duration-200"
                        :class="{ 'bg-white/10 text-white': route().current('admin.users') }"
                    >
                        <div class="w-6 h-6 mr-2 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-md flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <span class="text-sm">User Info</span>
                    </Link>
                    <!-- Task Manager -->
                    <Link
                        :href="route('admin.task-manager')"
                        class="flex items-center px-3 py-2 text-white hover:bg-white/10 hover:text-white/90 rounded-lg transition-all duration-200 relative"
                        :class="{ 'bg-white/10 text-white': route().current('admin.task-manager') }"
                    >
                        <div class="w-6 h-6 mr-2 bg-gradient-to-br from-green-400 to-green-600 rounded-md flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </div>
                        <span class="text-sm">Task Manager</span>
                        <span v-if="unassignedUsersCount > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">{{ unassignedUsersCount }}</span>
                    </Link>
                    <!-- Deposit Manager -->
                    <Link
                        :href="route('admin.deposit-clients')"
                        class="flex items-center px-3 py-2 text-white hover:bg-white/10 hover:text-white/90 rounded-lg transition-all duration-200 relative"
                        :class="{ 'bg-white/10 text-white': route().current('admin.deposit-clients') }"
                    >
                        <div class="w-6 h-6 mr-2 bg-gradient-to-br from-orange-400 to-orange-600 rounded-md flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <span class="text-sm">Deposit Manager</span>
                        <span v-if="pendingDepositsCount > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">{{ pendingDepositsCount }}</span>
                    </Link>
                    <!-- Withdraw Manager -->
                    <Link
                        :href="route('admin.withdrawals')"
                        class="flex items-center px-3 py-2 text-white hover:bg-white/10 hover:text-white/90 rounded-lg transition-all duration-200 relative"
                        :class="{ 'bg-white/10 text-white': route().current('admin.withdrawals') }"
                    >
                        <div class="w-6 h-6 mr-2 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-md flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                            </svg>
                        </div>
                        <span class="text-sm">Withdraw Manager</span>
                        <span v-if="pendingWithdrawalsCount > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">{{ pendingWithdrawalsCount }}</span>
                    </Link>
                    <!-- QR & Address -->
                    <Link
                        :href="route('admin.qr-address-upload')"
                        class="flex items-center px-3 py-2 text-white hover:bg-white/10 hover:text-white/90 rounded-lg transition-all duration-200"
                        :class="{ 'bg-white/10 text-white': route().current('admin.qr-address-upload') }"
                    >
                        <div class="w-6 h-6 mr-2 bg-gradient-to-br from-pink-400 to-pink-600 rounded-md flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                            </svg>
                        </div>
                        <span class="text-sm">QR & Address</span>
                    </Link>
                    <!-- Products -->
                    <Link
                        :href="route('admin.products')"
                        class="flex items-center px-3 py-2 text-white hover:bg-white/10 hover:text-white/90 rounded-lg transition-all duration-200"
                        :class="{ 'bg-white/10 text-white': route().current('admin.products') }"
                    >
                        <div class="w-6 h-6 mr-2 bg-gradient-to-br from-teal-400 to-teal-600 rounded-md flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <span class="text-sm">Products</span>
                    </Link>
                    <!-- Slider Images -->
                    <Link
                        :href="route('admin.sliders')"
                        class="flex items-center px-3 py-2 text-white hover:bg-white/10 hover:text-white/90 rounded-lg transition-all duration-200"
                        :class="{ 'bg-white/10 text-white': route().current('admin.sliders') }"
                    >
                        <div class="w-6 h-6 mr-2 bg-gradient-to-br from-cyan-400 to-cyan-600 rounded-md flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <span class="text-sm">Slider Images</span>
                    </Link>
                    <!-- Contact Settings -->
                    <Link
                        :href="route('admin.contact-settings')"
                        class="flex items-center px-3 py-2 text-white hover:bg-white/10 hover:text-white/90 rounded-lg transition-all duration-200"
                        :class="{ 'bg-white/10 text-white': route().current('admin.contact-settings') }"
                    >
                        <div class="w-6 h-6 mr-2 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-md flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <span class="text-sm">Contact Settings</span>
                    </Link>
                    <!-- Logout -->
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        :data="{ _token: $page.props.csrf_token }"
                        class="flex items-center px-3 py-2 text-red-400 hover:bg-white/10 hover:text-red-300 rounded-lg transition-all duration-200"
                    >
                        <div class="w-6 h-6 mr-2 bg-gradient-to-br from-red-400 to-red-600 rounded-md flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </div>
                        <span class="text-sm">Logout</span>
                    </Link>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 min-h-screen bg-gradient-to-br from-white/95 via-blue-50/90 to-indigo-50/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/40 overflow-auto">
                <!-- Flash Messages -->
                <!-- <div v-if="page.props.flash?.success" class="mx-4 mt-4 mb-2 px-4 py-2 bg-green-100 text-green-800 rounded">
                    {{ page.props.flash.success }}
                </div>
                <div v-if="page.props.flash?.error" class="mx-4 mt-4 mb-2 px-4 py-2 bg-red-100 text-red-800 rounded">
                    {{ page.props.flash.error }}
                </div> -->

                <div class="h-full">
                    <div v-if="$slots.header">
                        <slot name="header" />
                    </div>
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';

const page = usePage(); // Ensure usePage is properly used
const isCollapsed = ref(false);
const supportHasNewMessage = ref(false);
const usersWithNewMessages = ref(new Set());

// Load notifications from sessionStorage
const loadNotifications = () => {
    const stored = sessionStorage.getItem('adminChatNotifications');
    if (stored) {
        usersWithNewMessages.value = new Set(JSON.parse(stored));
        supportHasNewMessage.value = usersWithNewMessages.value.size > 0;
    }
};

// Save notifications to sessionStorage
const saveNotifications = () => {
    sessionStorage.setItem('adminChatNotifications', JSON.stringify([...usersWithNewMessages.value]));
};

// Update support badge based on pending notifications
const updateSupportBadge = () => {
    const stored = sessionStorage.getItem('adminChatNotifications');
    if (stored) {
        const notifications = JSON.parse(stored);
        supportHasNewMessage.value = notifications.length > 0;
    } else {
        supportHasNewMessage.value = false;
    }
};
const pendingDepositsCount = ref(0);
const pendingWithdrawalsCount = ref(0);
const unassignedUsersCount = ref(0);

const toggleSidebar = () => {
    isCollapsed.value = !isCollapsed.value;
};

// Simple notification system
window.adminNotifications = {
    addNotification: (userId, isGuest) => {
        const userKey = `${isGuest ? 'guest' : 'user'}-${userId}`;
        if (!window.pendingNotifications) {
            window.pendingNotifications = new Set();
        }
        window.pendingNotifications.add(userKey);
        sessionStorage.setItem('adminChatNotifications', JSON.stringify([...window.pendingNotifications]));
        
        // Update badge immediately
        if (window.updateAdminLayoutBadge) {
            window.updateAdminLayoutBadge();
        }
    },
    clearNotification: (userId, isGuest) => {
        const userKey = `${isGuest ? 'guest' : 'user'}-${userId}`;
        if (!window.pendingNotifications) {
            window.pendingNotifications = new Set();
        }
        window.pendingNotifications.delete(userKey);
        sessionStorage.setItem('adminChatNotifications', JSON.stringify([...window.pendingNotifications]));
        
        // Update badge immediately
        if (window.updateAdminLayoutBadge) {
            window.updateAdminLayoutBadge();
        }
    },
    hasNotification: (userId, isGuest) => {
        if (!window.pendingNotifications) {
            window.pendingNotifications = new Set();
        }
        const userKey = `${isGuest ? 'guest' : 'user'}-${userId}`;
        return window.pendingNotifications.has(userKey);
    },
    clearAllNotifications: () => {
        window.pendingNotifications = new Set();
        sessionStorage.setItem('adminChatNotifications', JSON.stringify([]));
        
        // Update badge immediately
        if (window.updateAdminLayoutBadge) {
            window.updateAdminLayoutBadge();
        }
    }
};

// Load from session on page load
const stored = sessionStorage.getItem('adminChatNotifications');
if (stored) {
    window.pendingNotifications = new Set(JSON.parse(stored));
} else {
    window.pendingNotifications = new Set();
}

// Expose function to update badge
window.updateAdminLayoutBadge = () => {
    updateSupportBadge();
};

// Only clear main badge when visiting support, keep individual user badges
watch(
    () => page.url,
    () => {
        if (route().current('admin.support')) {
            // Don't clear notifications here - let Support.vue handle it when user clicks on chat
            updateSupportBadge();
        }
    },
    { immediate: true }
);

const fetchPendingDeposits = async () => {
    try {
        const response = await fetch('/admin/pending-deposits-count', {
            headers: { 'Accept': 'application/json' }
        });
        if (response.ok) {
            const data = await response.json();
            pendingDepositsCount.value = data.count || 0;
        }
    } catch (error) {
        // Silent fail
    }
};

const fetchPendingWithdrawals = async () => {
    try {
        const response = await fetch('/admin/pending-withdrawals-count', {
            headers: { 'Accept': 'application/json' }
        });
        if (response.ok) {
            const data = await response.json();
            pendingWithdrawalsCount.value = data.count || 0;
        }
    } catch (error) {
        // Silent fail
    }
};

const fetchUnassignedUsers = async () => {
    try {
        const response = await fetch('/admin/unassigned-users-count', {
            headers: { 'Accept': 'application/json' }
        });
        if (response.ok) {
            const data = await response.json();
            unassignedUsersCount.value = data.count || 0;
            console.log('[AdminLayout] Unassigned users count:', unassignedUsersCount.value);
        }
    } catch (error) {
        console.error('[AdminLayout] Failed to fetch unassigned users:', error);
    }
};

// Expose function to window for TaskManager to call
window.taskAssignmentUpdated = () => {
    console.log('[AdminLayout] Task assignment updated, refreshing count...');
    fetchUnassignedUsers();
};

onMounted(() => {
    loadNotifications();
    fetchPendingDeposits();
    fetchPendingWithdrawals();
    fetchUnassignedUsers();
    
    // Initialize currentSelectedUser if not set
    if (!window.currentSelectedUser) {
        window.currentSelectedUser = null;
    }
    
    // Update badge on mount
    updateSupportBadge();
    
    // Set interval to check for notifications every second
    setInterval(() => {
        updateSupportBadge();
    }, 1000);

    const adminId = page.props.auth?.user?.id;
    
    // Wait for Echo to be ready
    const setupEchoListeners = () => {
        if (!window.Echo) {
            console.log('[AdminLayout] Echo not ready, waiting...');
            setTimeout(setupEchoListeners, 100);
            return;
        }
        
        console.log('[AdminLayout] Echo ready, setting up listeners');
        console.log('[AdminLayout] Admin ID:', adminId);
        
        // Listen for deposit events on public channel
        window.Echo.channel('deposits')
            .listen('DepositCreated', (e) => {
                console.log('[AdminLayout] ✅ DepositCreated event received:', e);
                // Increment badge count immediately
                pendingDepositsCount.value++;
                
                // Trigger update in other components
                if (window.depositUpdated) {
                    window.depositUpdated();
                }
            })
            .listen('DepositStatusUpdated', (e) => {
                console.log('[AdminLayout] ✅ DepositStatusUpdated event received:', e);
                // Refresh count when status changes
                fetchPendingDeposits();
                
                // Trigger update in other components
                if (window.depositUpdated) {
                    window.depositUpdated();
                }
            });
        
        console.log('[AdminLayout] Deposit channel listeners attached');
        
        // Listen for withdrawal events on public channel
        const withdrawalsChannel = window.Echo.channel('withdrawals');
        console.log('[AdminLayout] Attempting to join withdrawals channel...', withdrawalsChannel);
        
        withdrawalsChannel
            .listen('WithdrawalCreated', (e) => {
                console.log('[AdminLayout] ✅ WithdrawalCreated event received:', e);
                // Increment badge count immediately
                pendingWithdrawalsCount.value++;
            })
            .listen('WithdrawalStatusUpdated', (e) => {
                console.log('[AdminLayout] ✅ WithdrawalStatusUpdated event received:', e);
                // Refresh count when status changes
                fetchPendingWithdrawals();
            });
        
        console.log('[AdminLayout] Withdrawal channel listeners attached');
        
        // Listen for regular chat messages
        window.Echo.private(`chat.${adminId}`)
            .listen('NewChatMessage', (e) => {
                // Only add notification if message is from someone else and they're not currently selected
                if (e?.chat?.sender_id && String(e.chat.sender_id) !== String(adminId)) {
                    // Check if this user is currently selected in Support.vue
                    const isCurrentlySelected = window.currentSelectedUser && 
                        !window.currentSelectedUser.is_guest && 
                        String(window.currentSelectedUser.id) === String(e.chat.sender_id);
                    
                    if (!isCurrentlySelected) {
                        window.adminNotifications.addNotification(e.chat.sender_id, false);
                        updateSupportBadge();
                    }
                }
            })
            .listen('NewGuestChatMessage', (e) => {
                // Only add notification if message is from a guest and they're not currently selected
                if (e?.message?.sender_id && e?.message?.is_guest) {
                    // Check if this guest is currently selected in Support.vue
                    const isCurrentlySelected = window.currentSelectedUser && 
                        window.currentSelectedUser.is_guest && 
                        String(window.currentSelectedUser.session_id) === String(e.message.sender_id);
                    
                    if (!isCurrentlySelected) {
                        window.adminNotifications.addNotification(e.message.sender_id, true);
                        updateSupportBadge();
                    }
                }
            });
    };
    
    if (adminId) {
        setupEchoListeners();
    }
});
</script>

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