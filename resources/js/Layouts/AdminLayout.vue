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
                        class="flex items-center px-3 py-2 text-white hover:bg-white/10 hover:text-white/90 rounded-lg transition-all duration-200"
                        :class="{ 'bg-white/10 text-white': route().current('admin.support') }"
                        @click="supportHasNewMessage = false"
                    >
                        <div class="w-6 h-6 mr-2 bg-gradient-to-br from-purple-400 to-purple-600 rounded-md flex items-center justify-center">
                            <i class="fas fa-headset text-white text-xs"></i>
                        </div>
                        <span class="text-sm">Support Chat</span>
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
                        class="flex items-center px-3 py-2 text-white hover:bg-white/10 hover:text-white/90 rounded-lg transition-all duration-200"
                        :class="{ 'bg-white/10 text-white': route().current('admin.task-manager') }"
                    >
                        <div class="w-6 h-6 mr-2 bg-gradient-to-br from-green-400 to-green-600 rounded-md flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </div>
                        <span class="text-sm">Task Manager</span>
                    </Link>
                    <!-- Deposit Manager -->
                    <Link
                        :href="route('admin.deposit-clients')"
                        class="flex items-center px-3 py-2 text-white hover:bg-white/10 hover:text-white/90 rounded-lg transition-all duration-200"
                        :class="{ 'bg-white/10 text-white': route().current('admin.deposit-clients') }"
                    >
                        <div class="w-6 h-6 mr-2 bg-gradient-to-br from-orange-400 to-orange-600 rounded-md flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <span class="text-sm">Deposit Manager</span>
                    </Link>
                    <!-- Withdraw Manager -->
                    <Link
                        :href="route('admin.withdrawals')"
                        class="flex items-center px-3 py-2 text-white hover:bg-white/10 hover:text-white/90 rounded-lg transition-all duration-200"
                        :class="{ 'bg-white/10 text-white': route().current('admin.withdrawals') }"
                    >
                        <div class="w-6 h-6 mr-2 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-md flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                            </svg>
                        </div>
                        <span class="text-sm">Withdraw Manager</span>
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

const toggleSidebar = () => {
    isCollapsed.value = !isCollapsed.value;
};

// Clear the indicator when navigating to support route
watch(
    () => page.url,
    () => {
        if (route().current('admin.support')) {
            supportHasNewMessage.value = false;
        }
    },
    { immediate: true }
);

onMounted(() => {
    // Subscribe to admin private channel and toggle the sidebar dot on new messages
    const adminId = page.props.auth?.user?.id;
    if (adminId && window?.Echo) {
        // Listen for regular chat messages
        window.Echo.private(`chat.${adminId}`)
            .listen('NewChatMessage', (e) => {
                // If user isn't on the support page, show a dot
                if (!route().current('admin.support')) {
                    // Only mark when message originated from a user (not admin self)
                    if (e?.chat?.sender_id && String(e.chat.sender_id) !== String(adminId)) {
                        supportHasNewMessage.value = true;
                    }
                }
            });

        // Listen for guest chat messages
        window.Echo.private('guest-chat')
            .listen('NewGuestChatMessage', (e) => {
                // If user isn't on the support page, show notification
                if (!route().current('admin.support')) {
                    // Only mark when message originated from guest (not admin self)
                    if (e?.message?.sender_id && String(e.message.sender_id) !== String(adminId)) {
                        supportHasNewMessage.value = true;
                    }
                }
            });
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