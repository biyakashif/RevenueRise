<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const props = defineProps({
    blockedUsers: Array,
});

const unblockUser = (userId) => {
    if (confirm('Are you sure you want to unblock this user?')) {
        router.post(route('admin.users.unblock', userId), {
            _token: page.props.csrf_token
        }, {
            preserveScroll: true,
            onSuccess: () => {
                // Success handled by redirect
            },
            onError: (errors) => {
                if (errors && (errors.message?.includes('419') || errors.status === 419)) {
                    window.location.reload();
                }
            }
        });
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleString();
};
</script>

<template>
    <Head title="Blocked Users" />
    <AdminLayout>
        <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-6 rounded-2xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto">
            <div class="flex justify-between items-center mb-4 sm:mb-6">
                <div class="flex items-center gap-3">
                    <Link href="/admin/support" class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-slate-500/80 to-gray-600/80 hover:from-slate-600/90 hover:to-gray-700/90 rounded-xl shadow-lg transition-all duration-200 transform hover:scale-105">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold text-slate-800 drop-shadow-sm">Blocked Users</h1>
                        <p class="text-xs sm:text-sm text-slate-600 font-medium">Manage blocked users</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm rounded-xl shadow-lg border border-white/30 overflow-hidden">
                <div v-if="blockedUsers.length === 0" class="text-center py-8 text-slate-600">
                    No blocked users
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-white/20 text-slate-700 text-sm font-medium">
                                <th class="p-4 text-left">User</th>
                                <th class="p-4 text-left">Mobile Number</th>
                                <th class="p-4 text-left">Blocked At</th>
                                <th class="p-4 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="blockedUser in blockedUsers" :key="blockedUser.id" class="border-t border-white/20 hover:bg-white/10 transition-all duration-200">
                                <td class="p-4 text-sm text-slate-800 font-medium">{{ blockedUser.user?.name || 'Unknown User' }}</td>
                                <td class="p-4 text-sm text-slate-700">{{ blockedUser.user?.mobile_number || 'N/A' }}</td>
                                <td class="p-4 text-sm text-slate-700">{{ formatDate(blockedUser.blocked_at) }}</td>
                                <td class="p-4">
                                    <button
                                        @click="unblockUser(blockedUser.user_id)"
                                        class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-3 py-1 rounded-lg text-xs font-medium transition-all duration-200 shadow-lg"
                                    >
                                        Unblock
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>