<template>
    <div class="min-h-screen bg-gray-100 flex flex-col">
        <div class="flex flex-1">
            <!-- Chat Notification -->
            <transition name="slide-right">
                <div v-if="supportHasNewMessage && !route().current('admin.support')" class="fixed top-1/2 right-0 z-50 flex items-center bg-white shadow-lg rounded-l-lg px-4 py-3 border border-purple-300" style="transform: translateY(-50%);">
                    <button @click="() => { supportHasNewMessage = false; router.visit(route('admin.support')); }" class="flex items-center space-x-2 focus:outline-none">
                        <i class="fas fa-bell text-purple-600 text-xl"></i>
                        <span class="font-semibold text-purple-700">You have a support message</span>
                    </button>
                </div>
            </transition>
            <!-- Sidebar -->
                <!-- Sidebar -->
            <aside :class="{ 'w-40': !isCollapsed, 'w-12': isCollapsed }" class="flex flex-col bg-white border-r shadow-sm transition-all duration-300">
                <div class="flex justify-end p-1">
                    <button @click="toggleSidebar" class="text-purple-600 hover:text-purple-800 focus:outline-none">
                        <i :class="{ 'fa-rotate-180': isCollapsed }" class="fas fa-arrow-right fa-lg transition-transform duration-300"></i>
                    </button>
                </div>
                <nav class="p-1" v-show="!isCollapsed">
                    <ul class="list-none space-y-0.5">
                        <!-- Support Chat -->
                        <li>
                            <Link
                                :href="route('admin.support')"
                                :active="route().current('admin.support')"
                                class="block w-full px-2 py-1 text-sm text-purple-600 hover:bg-gray-100 rounded-md transition-all duration-200 truncate"
                                :class="{ 'bg-gray-100': route().current('admin.support') }"
                                @click="supportHasNewMessage = false"
                            >
                                Support Chat
                            </Link>
                        </li>
                        <!-- User Info -->
                        <li>
                            <Link
                                :href="route('admin.users')"
                                :active="route().current('admin.users')"
                                class="block w-full px-2 py-1 text-sm text-purple-600 hover:bg-gray-100 rounded-md transition-all duration-200 truncate"
                                :class="{ 'bg-gray-100': route().current('admin.users') }"
                            >
                                User Info
                            </Link>
                        </li>
                        <!-- Task Manager -->
                        <li>
                            <Link
                                :href="route('admin.task-manager')"
                                :active="route().current('admin.task-manager')"
                                class="block w-full px-2 py-1 text-sm text-purple-600 hover:bg-gray-100 rounded-md transition-all duration-200 truncate"
                                :class="{ 'bg-gray-100': route().current('admin.task-manager') }"
                            >
                                Task Manager
                            </Link>
                        </li>
                        <!-- Deposit Manager -->
                        <li>
                            <Link
                                :href="route('admin.deposit-clients')"
                                :active="route().current('admin.deposit-clients')"
                                class="block w-full px-2 py-1 text-sm text-purple-600 hover:bg-gray-100 rounded-md transition-all duration-200 truncate"
                                :class="{ 'bg-gray-100': route().current('admin.deposit-clients') }"
                            >
                                Deposit Manager
                            </Link>
                        </li>
                        <!-- Withdraw Manager -->
                        <li>
                            <Link
                                :href="route('admin.withdrawals')"
                                :active="route().current('admin.withdrawals')"
                                class="block w-full px-2 py-1 text-sm text-purple-600 hover:bg-gray-100 rounded-md transition-all duration-200 truncate"
                                :class="{ 'bg-gray-100': route().current('admin.withdrawals') }"
                            >
                                Withdraw Manager
                            </Link>
                        </li>
                        <!-- QR & Address (renamed) -->
                        <li>
                            <Link
                                :href="route('admin.qr-address-upload')"
                                :active="route().current('admin.qr-address-upload')"
                                class="block w-full px-2 py-1 text-sm text-purple-600 hover:bg-gray-100 rounded-md transition-all duration-200 truncate"
                                :class="{ 'bg-gray-100': route().current('admin.qr-address-upload') }"
                            >
                                QR & Address
                            </Link>
                        </li>
                        <!-- Products -->
                        <li>
                            <Link
                                :href="route('admin.products')"
                                :active="route().current('admin.products')"
                                class="block w-full px-2 py-1 text-sm text-purple-600 hover:bg-gray-100 rounded-md transition-all duration-200 truncate"
                                :class="{ 'bg-gray-100': route().current('admin.products') }"
                            >
                                Products
                            </Link>
                        </li>
                        <!-- Slider Images -->
                        <li>
                            <Link
                                :href="route('admin.sliders')"
                                :active="route().current('admin.sliders')"
                                class="block w-full px-2 py-1 text-sm text-purple-600 hover:bg-gray-100 rounded-md transition-all duration-200 truncate"
                                :class="{ 'bg-gray-100': route().current('admin.sliders') }"
                            >
                                Slider Images
                            </Link>
                        </li>
                        <!-- Logout -->
                        <li>
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="block w-full px-2 py-1 text-sm text-red-600 hover:bg-gray-100 rounded-md transition-all duration-200 truncate"
                            >
                                Logout
                            </Link>
                        </li>
                    </ul>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="flex-grow p-4 bg-white">
                <!-- Flash Messages -->
                <div v-if="page.props.flash?.success" class="mb-4 px-4 py-2 bg-green-100 text-green-800 rounded">
                    {{ page.props.flash.success }}
                </div>
                <div v-if="page.props.flash?.error" class="mb-4 px-4 py-2 bg-red-100 text-red-800 rounded">
                    {{ page.props.flash.error }}
                </div>

                <div v-if="$slots.header" class="mb-6">
                    <slot name="header" />
                </div>
                <slot />
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
    const adminMobile = page.props.auth?.user?.mobile_number;
    if (adminMobile && window?.Echo) {
        window.Echo.private(`chat.${adminMobile}`)
            .listen('NewChatMessage', (e) => {
                // If user isn't on the support page, show a dot
                if (!route().current('admin.support')) {
                    // Only mark when message originated from a user (not admin self)
                    if (e?.chat?.sender_id && String(e.chat.sender_id) !== String(adminMobile)) {
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