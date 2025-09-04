<template>
    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 bg-gray-100">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">User Info</h2>
            <div class="mb-6">
                <input
                    v-model="search"
                    @input="fetchUsers"
                    type="text"
                    placeholder="Search by mobile number or invitation code..."
                    class="mt-1 block w-full rounded-full border-none focus:ring-2 focus:ring-purple-300 text-gray-900"
                />
            </div>
            <div v-if="users.length" class="space-y-6">
                <div v-for="user in users" :key="user.id" class="p-6 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-300">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Name</label>
                            <input
                                v-model="user.name"
                                @change="updateUser(user)"
                                @keydown.enter="updateUser(user)"
                                type="text"
                                class="mt-1 block w-full rounded-full border-none focus:ring-2 focus:ring-purple-300 text-gray-900"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Mobile Number</label>
                            <input
                                v-model="user.mobile_number"
                                @change="updateUser(user)"
                                @keydown.enter="updateUser(user)"
                                type="text"
                                class="mt-1 block w-full rounded-full border-none focus:ring-2 focus:ring-purple-300 text-gray-900"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Invitation Code</label>
                            <input
                                v-model="user.invitation_code"
                                @change="updateUser(user)"
                                @keydown.enter="updateUser(user)"
                                type="text"
                                class="mt-1 block w-full rounded-full border-none focus:ring-2 focus:ring-purple-300 text-gray-900"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Balance</label>
                            <input
                                v-model="user.balance"
                                @change="updateUser(user)"
                                @keydown.enter="updateUser(user)"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full rounded-full border-none focus:ring-2 focus:ring-purple-300 text-gray-900"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Created At</label>
                            <input
                                v-model="user.created_at"
                                disabled
                                type="text"
                                class="mt-1 block w-full rounded-full border-none bg-gray-50 text-gray-900 cursor-not-allowed"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Updated At</label>
                            <input
                                v-model="user.updated_at"
                                disabled
                                type="text"
                                class="mt-1 block w-full rounded-full border-none bg-gray-50 text-gray-900 cursor-not-allowed"
                            />
                        </div>
                    </div>
                    <div v-if="user.updateSuccess" class="mt-4 text-green-600 text-sm">
                        Update successful
                    </div>
                </div>
            </div>
            <p v-else class="text-center text-gray-500 text-lg">No users found.</p>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    users: Array,
    search: String,
});

const users = ref(props.users.map(user => ({ ...user, updateSuccess: false })));
const search = ref(props.search);

watch(() => props.users, (newUsers) => {
    users.value = newUsers.map(user => ({ ...user, updateSuccess: false }));
});

const fetchUsers = () => {
    router.get(route('admin.users'), { search: search.value }, {
        preserveState: true,
        replace: true,
    });
};

const updateUser = (user) => {
    router.patch(route('admin.users.update', user.id), {
        name: user.name,
        mobile_number: user.mobile_number,
        invitation_code: user.invitation_code,
        balance: user.balance,
    }, {
        preserveScroll: true,
        onSuccess: (response) => {
            const updatedUser = response.props.users.find(u => u.id === user.id);
            Object.assign(user, updatedUser, { updateSuccess: true });
            setTimeout(() => (user.updateSuccess = false), 3000); // Hide message after 3 seconds
        },
    });
};
</script>