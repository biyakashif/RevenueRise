<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-5">
        <header>
            <h2 class="text-2xl font-bold text-center text-white mb-6">
                Delete Account
            </h2>
            <p class="mb-4 text-sm font-medium text-blue-100 text-center">
                Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.
            </p>
        </header>

        <DangerButton
            class="w-full rounded-full px-4 py-3 bg-gradient-to-r from-red-700 to-red-900 text-white font-semibold text-lg text-center transition-all duration-300 hover:from-red-800 hover:to-red-950 hover:scale-105 shadow-lg"
            @click="confirmUserDeletion"
        >
            Delete Account
        </DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6 bg-white/10 backdrop-blur-lg rounded-3xl text-white">
                <h2 class="text-2xl font-bold text-center mb-6">
                    Are you sure you want to delete your account?
                </h2>
                <p class="mb-4 text-sm font-medium text-blue-100 text-center">
                    Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.
                </p>

                <div class="mt-6">
                    <InputLabel for="password" value="Password" class="text-white" />
                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full rounded-full border-none focus:ring-2 focus:ring-blue-300 text-gray-900"
                        placeholder="Password"
                        @keyup.enter="deleteUser"
                    />
                    <InputError class="mt-2 text-pink-300" :message="form.errors.password" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton
                        class="rounded-full px-4 py-3 bg-white/10 text-white font-semibold text-lg transition-all duration-300 hover:bg-white/20 hover:scale-105 shadow-lg"
                        @click="closeModal"
                    >
                        Cancel
                    </SecondaryButton>
                    <DangerButton
                        class="ms-3 rounded-full px-4 py-3 bg-gradient-to-r from-red-700 to-red-900 text-white font-semibold text-lg text-center transition-all duration-300 hover:from-red-800 hover:to-red-950 hover:scale-105 shadow-lg"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Delete Account
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>