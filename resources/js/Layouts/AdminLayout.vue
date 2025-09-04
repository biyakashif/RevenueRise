<template>
    <div class="min-h-screen bg-gray-100 flex flex-col">
        <div class="flex flex-1">
            <!-- Sidebar -->
            <aside :class="{ 'w-64': !isCollapsed, 'w-16': isCollapsed }" class="flex flex-col bg-white border-r shadow-sm transition-all duration-300">
                <div class="flex justify-end p-2">
                    <button @click="toggleSidebar" class="text-purple-600 hover:text-purple-800 focus:outline-none">
                        <i :class="{ 'fa-rotate-180': isCollapsed }" class="fas fa-arrow-right fa-lg transition-transform duration-300"></i>
                    </button>
                </div>
                <nav class="flex flex-col p-2 space-y-6">
                    <div class="mt-4">
                        <Link
                            :href="route('admin.dashboard')"
                            :active="route().current('admin.dashboard')"
                            class="flex items-center justify-center sm:justify-start px-4 py-4 text-purple-600 hover:bg-gray-100 rounded-lg transition-all duration-300"
                            :class="{ 'bg-gray-100': route().current('admin.dashboard') }"
                        >
                            <i class="fas fa-tachometer-alt fa-lg"></i>
                            <span v-show="!isCollapsed" class="ml-3">Admin Dashboard</span>
                        </Link>
                        <Link
                            :href="route('admin.users')"
                            :active="route().current('admin.users')"
                            class="flex items-center justify-center sm:justify-start px-4 py-4 text-purple-600 hover:bg-gray-100 rounded-lg transition-all duration-300"
                            :class="{ 'bg-gray-100': route().current('admin.users') }"
                        >
                            <i class="fas fa-users fa-lg"></i>
                            <span v-show="!isCollapsed" class="ml-3">User Info</span>
                        </Link>
                        <Link
                            :href="route('admin.deposit-clients')"
                            :active="route().current('admin.deposit-clients')"
                            class="flex items-center justify-center sm:justify-start px-4 py-4 text-purple-600 hover:bg-gray-100 rounded-lg transition-all duration-300"
                            :class="{ 'bg-gray-100': route().current('admin.deposit-clients') }"
                        >
                            <i class="fas fa-money-bill-wave fa-lg"></i>
                            <span v-show="!isCollapsed" class="ml-3">Deposit Manager</span>
                        </Link>
                        <Link
                            :href="route('admin.withdraw')"
                            :active="route().current('admin.withdraw')"
                            class="flex items-center justify-center sm:justify-start px-4 py-4 text-purple-600 hover:bg-gray-100 rounded-lg transition-all duration-300"
                            :class="{ 'bg-gray-100': route().current('admin.withdraw') }"
                        >
                            <i class="fas fa-money-bill-alt fa-lg"></i>
                            <span v-show="!isCollapsed" class="ml-3">Withdraw Manager</span>
                        </Link>
                        <!-- <Link
                            :href="route('admin.orders')"
                            :active="route().current('admin.orders')"
                            class="flex items-center justify-center sm:justify-start px-4 py-4 text-purple-600 hover:bg-gray-100 rounded-lg transition-all duration-300"
                            :class="{ 'bg-gray-100': route().current('admin.orders') }"
                        >
                            <i class="fas fa-shopping-cart fa-lg"></i>
                            <span v-show="!isCollapsed" class="ml-3">Order Manager</span>
                        </Link> -->
                        <Link
                            :href="route('admin.qr-address-upload')"
                            :active="route().current('admin.qr-address-upload')"
                            class="flex items-center justify-center sm:justify-start px-4 py-4 text-purple-600 hover:bg-gray-100 rounded-lg transition-all duration-300"
                            :class="{ 'bg-gray-100': route().current('admin.qr-address-upload') }"
                        >
                            <i class="fas fa-qrcode fa-lg"></i>
                            <span v-show="!isCollapsed" class="ml-3">QR & Address Upload</span>
                        </Link>
                        <Link
                            :href="route('admin.products')"
                            :active="route().current('admin.products')"
                            class="flex items-center justify-center sm:justify-start px-4 py-4 text-purple-600 hover:bg-gray-100 rounded-lg transition-all duration-300"
                            :class="{ 'bg-gray-100': route().current('admin.products') }"
                        >
                            <i class="fas fa-boxes fa-lg"></i>
                            <span v-show="!isCollapsed" class="ml-3">Products</span>
                        </Link>
<Link
    :href="route('admin.task-manager')"
    :active="route().current('admin.task-manager')"
    class="flex items-center justify-center sm:justify-start px-4 py-4 text-purple-600 hover:bg-gray-100 rounded-lg transition-all duration-300"
    :class="{ 'bg-gray-100': route().current('admin.task-manager') }"
>
    <i class="fas fa-tasks fa-lg"></i>
    <span v-show="!isCollapsed" class="ml-3">Task Manager</span>
</Link>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="flex items-center justify-center sm:justify-start px-4 py-4 text-red-600 hover:bg-gray-100 rounded-lg transition-all duration-300"
                        >
                            <i class="fas fa-sign-out-alt fa-lg"></i>
                            <span v-show="!isCollapsed" class="ml-3">Logout</span>
                        </Link>
                    </div>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="flex-grow p-8 bg-white">
                <div v-if="$slots.header" class="mb-6">
                    <slot name="header" />
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const isCollapsed = ref(false);

const toggleSidebar = () => {
    isCollapsed.value = !isCollapsed.value;
};
</script>