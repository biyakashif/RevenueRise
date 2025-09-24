<template>
    <AdminLayout>
        <div class="p-4 sm:p-6 lg:p-8 bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl min-h-screen">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-2xl sm:text-3xl font-bold text-white mb-6">User Management</h2>

                <!-- Flash Messages -->
                <transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0 transform -translate-y-4"
                    enter-to-class="opacity-100 transform translate-y-0"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100 transform translate-y-0"
                    leave-to-class="opacity-0 transform -translate-y-4"
                >
                    <div v-if="flashMessage" :class="flashClass" class="border-l-4 p-4 mb-4" role="alert">
                        <p>{{ flashMessage }}</p>
                    </div>
                </transition>

                <!-- Search -->
                <div class="mb-4">
                    <input
                        v-model="search"
                        @input="fetchUsers"
                        type="text"
                        placeholder="Search by name, mobile, or invitation code..."
                        class="w-full p-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-md shadow-sm text-white placeholder-white/70 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>

                <!-- Users Table -->
                <div class="bg-white/10 backdrop-blur-xl shadow-md rounded-lg overflow-x-auto border border-white/20">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white/5">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white/80 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white/80 uppercase tracking-wider">Mobile</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white/80 uppercase tracking-wider">VIP Level</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white/80 uppercase tracking-wider">Balance</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white/80 uppercase tracking-wider">Password</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white/80 uppercase tracking-wider">Withdraw Password</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white/80 uppercase tracking-wider">Role</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white/80 uppercase tracking-wider">Referred By</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white/80 uppercase tracking-wider">Register Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white/80 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white/5 divide-y divide-white/10">
                            <tr v-for="user in props.users" :key="user.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">{{ user.name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-white/80">{{ user.mobile_number }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-white/80">{{ user.vip_level }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-white/80">{{ user.balance.toFixed(2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-white/80">{{ user.password }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-white/80">{{ user.withdraw_password }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-white/80">{{ user.role }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-white/80">{{ user.referred_by || 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-white/80">{{ user.register_date }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button @click="openEditModal(user)" class="text-blue-400 hover:text-blue-300 mr-3">Edit</button>
                                    <button @click="openDeleteModal(user)" class="text-red-400 hover:text-red-300">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                 <p v-if="!props.users.length" class="text-center text-white/70 text-lg mt-6">No users found.</p>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-6 rounded-lg shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto border border-white/20">
                <h3 class="text-xl font-bold mb-4 text-white">Edit User: {{ form.name }}</h3>
                <form @submit.prevent="submitUpdate">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-white/80">Name</label>
                            <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md bg-white/10 backdrop-blur-sm border-white/20 text-white placeholder-white/70 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <p v-if="formErrors.name" class="text-xs text-red-600 mt-1">{{ formErrors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-white/80">Mobile Number</label>
                            <input v-model="form.mobile_number" type="text" class="mt-1 block w-full rounded-md bg-white/10 backdrop-blur-sm border-white/20 text-white placeholder-white/70 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <p v-if="formErrors.mobile_number" class="text-xs text-red-600 mt-1">{{ formErrors.mobile_number }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-white/80">Password (leave blank to keep unchanged)</label>
                            <input v-model="form.password" type="password" class="mt-1 block w-full rounded-md bg-white/10 backdrop-blur-sm border-white/20 text-white placeholder-white/70 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                             <p v-if="formErrors.password" class="text-xs text-red-600 mt-1">{{ formErrors.password }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-white/80">Withdraw Password (leave blank to keep unchanged)</label>
                            <input v-model="form.withdraw_password" type="password" class="mt-1 block w-full rounded-md bg-white/10 backdrop-blur-sm border-white/20 text-white placeholder-white/70 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                             <p v-if="formErrors.withdraw_password" class="text-xs text-red-600 mt-1">{{ formErrors.withdraw_password }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-white/80">Invitation Code</label>
                            <input v-model="form.invitation_code" type="text" class="mt-1 block w-full rounded-md bg-white/10 backdrop-blur-sm border-white/20 text-white placeholder-white/70 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <p v-if="formErrors.invitation_code" class="text-xs text-red-600 mt-1">{{ formErrors.invitation_code }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-white/80">Balance</label>
                            <input v-model="form.balance" type="number" step="0.01" class="mt-1 block w-full rounded-md bg-white/10 backdrop-blur-sm border-white/20 text-white placeholder-white/70 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <p v-if="formErrors.balance" class="text-xs text-red-600 mt-1">{{ formErrors.balance }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-white/80">Role</label>
                            <select v-model="form.role" class="mt-1 block w-full rounded-md bg-white/10 backdrop-blur-sm border-white/20 text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                             <p v-if="formErrors.role" class="text-xs text-red-600 mt-1">{{ formErrors.role }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-white/80">Referred By (Invitation Code)</label>
                            <input v-model="form.referred_by" type="text" class="mt-1 block w-full rounded-md bg-white/10 backdrop-blur-sm border-white/20 text-white placeholder-white/70 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <p v-if="formErrors.referred_by" class="text-xs text-red-600 mt-1">{{ formErrors.referred_by }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-white/80">VIP Level</label>
                            <input v-model="form.vip_level" type="text" class="mt-1 block w-full rounded-md bg-white/10 backdrop-blur-sm border-white/20 text-white placeholder-white/70 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <p v-if="formErrors.vip_level" class="text-xs text-red-600 mt-1">{{ formErrors.vip_level }}</p>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" @click="closeEditModal" class="bg-white/10 backdrop-blur-sm text-white px-4 py-2 rounded-md hover:bg-white/20 border border-white/20">Cancel</button>
                        <button type="submit" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 py-2 rounded-md hover:from-blue-600 hover:to-blue-700 transform hover:scale-105 transition-all duration-200">Update User</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-6 rounded-lg shadow-xl w-full max-w-md border border-white/20">
                <h3 class="text-lg font-bold text-white">Confirm Deletion</h3>
                <p class="mt-2 text-sm text-white/80">
                    Are you sure you want to delete the user
                    <span class="font-medium text-white">{{ userToDelete.name }}</span>? This action cannot be undone.
                </p>
                <div class="mt-6 flex justify-end space-x-3">
                    <button @click="closeDeleteModal" class="bg-white/10 backdrop-blur-sm text-white px-4 py-2 rounded-md hover:bg-white/20 border border-white/20">Cancel</button>
                    <button @click="executeDelete" class="bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-2 rounded-md hover:from-red-600 hover:to-red-700 transform hover:scale-105 transition-all duration-200">Delete User</button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { debounce } from 'lodash';

const page = usePage();

const props = defineProps({
    users: Array,
    search: String,
});

const search = ref(props.search);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const userToDelete = ref(null);

const form = reactive({
    id: null, name: '', mobile_number: '', password: '', withdraw_password: '',
    invitation_code: '', balance: 0, role: 'user', referred_by: '', vip_level: '',
});
const formErrors = reactive({});

// Flash message handling
const flashMessage = ref('');
const flashType = ref('');

const flashClass = computed(() => ({
    'bg-green-100 border-green-500 text-green-700': flashType.value === 'success',
    'bg-red-100 border-red-500 text-red-700': flashType.value === 'error',
}));

watch(() => page.props.flash, (flash) => {
    if (flash.success) {
        flashMessage.value = flash.success;
        flashType.value = 'success';
    } else if (flash.error) {
        flashMessage.value = flash.error;
        flashType.value = 'error';
    } else {
        flashMessage.value = '';
        flashType.value = '';
    }

    if (flashMessage.value) {
        setTimeout(() => {
            flashMessage.value = '';
            if (page.props.flash.success) page.props.flash.success = null;
            if (page.props.flash.error) page.props.flash.error = null;
        }, 2000);
    }
}, { deep: true });

const fetchUsers = debounce(() => {
    router.get(route('admin.users'), { search: search.value }, {
        preserveState: true,
        replace: true,
    });
}, 300);

const openEditModal = (user) => {
    Object.assign(form, user);
    form.password = '';
    form.withdraw_password = '';
    Object.keys(formErrors).forEach(key => delete formErrors[key]);
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
};

const submitUpdate = () => {
    Object.keys(formErrors).forEach(key => delete formErrors[key]);
    router.patch(route('admin.users.update', form.id), form, {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
        onError: (errors) => Object.assign(formErrors, errors),
    });
};

const openDeleteModal = (user) => {
    userToDelete.value = user;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    userToDelete.value = null;
    showDeleteModal.value = false;
};

const executeDelete = () => {
    if (!userToDelete.value) return;
    router.delete(route('admin.users.destroy', userToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
};
</script>
