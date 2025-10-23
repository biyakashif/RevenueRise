<template>
    <AdminLayout>
        <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-6 rounded-2xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto">
            <h1 class="text-2xl font-bold text-slate-800 drop-shadow-sm mb-2 text-center">Welcome to Admin Dashboard</h1>
            <p class="text-slate-600 text-center mb-6">Manage your platform efficiently</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <a v-for="route in routes" :key="route.name" :href="route.href" class="group block p-5 bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm rounded-xl shadow-lg hover:shadow-2xl transition-all duration-200 border border-white/30 hover:scale-105 hover:border-cyan-300/50">
                    <div class="flex items-center space-x-4">
                        <div :class="route.bgColor" class="w-14 h-14 rounded-lg flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-200">
                            <i :class="route.icon" class="text-white text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-semibold text-slate-800 group-hover:text-blue-600 transition-colors duration-200">{{ route.name }}</h3>
                            <p class="text-xs text-slate-600 mt-1">{{ route.description }}</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed } from 'vue';

const routes = computed(() => [
    { name: 'Support Chat', href: route('admin.support'), icon: 'fas fa-headset', bgColor: 'bg-gradient-to-br from-purple-400 to-purple-600', description: 'Manage support tickets' },
    { name: 'User Info', href: route('admin.users'), icon: 'fas fa-users', bgColor: 'bg-gradient-to-br from-indigo-400 to-indigo-600', description: 'View and manage users' },
    { name: 'Task Manager', href: route('admin.task-manager'), icon: 'fas fa-tasks', bgColor: 'bg-gradient-to-br from-green-400 to-green-600', description: 'Assign and track tasks' },
    { name: 'Deposit Manager', href: route('admin.deposit-clients'), icon: 'fas fa-credit-card', bgColor: 'bg-gradient-to-br from-orange-400 to-orange-600', description: 'Process deposits' },
    { name: 'Withdraw Manager', href: route('admin.withdrawals'), icon: 'fas fa-money-bill-wave', bgColor: 'bg-gradient-to-br from-yellow-400 to-yellow-600', description: 'Process withdrawals' },
    { name: 'QR & Address', href: route('admin.qr-address-upload'), icon: 'fas fa-qrcode', bgColor: 'bg-gradient-to-br from-pink-400 to-pink-600', description: 'Manage payment methods' },
    { name: 'Products', href: route('admin.products'), icon: 'fas fa-box', bgColor: 'bg-gradient-to-br from-teal-400 to-teal-600', description: 'Manage products' },
    { name: 'Slider Images', href: route('admin.sliders'), icon: 'fas fa-images', bgColor: 'bg-gradient-to-br from-cyan-400 to-cyan-600', description: 'Update homepage sliders' },
    { name: 'Contact Settings', href: route('admin.contact-settings'), icon: 'fas fa-envelope', bgColor: 'bg-gradient-to-br from-emerald-400 to-emerald-600', description: 'Configure contact info' },
]);
</script>