<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">
                Update Password
            </h2>
            <p class="mb-4 text-sm font-medium text-gray-500 text-center">
                Update your account's password to keep it secure.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="space-y-5">
            <div>
                <InputLabel for="current_password" value="Current Password" class="text-gray-700" />
                <TextInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="mt-1 block w-full rounded-full border-none focus:ring-2 focus:ring-purple-300 text-gray-900"
                    autocomplete="current-password"
                />
                <InputError class="mt-2 text-red-500" :message="form.errors.current_password" />
            </div>

            <div>
                <InputLabel for="password" value="New Password" class="text-gray-700" />
                <TextInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full rounded-full border-none focus:ring-2 focus:ring-purple-300 text-gray-900"
                    autocomplete="new-password"
                />
                <InputError class="mt-2 text-red-500" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Confirm Password" class="text-gray-700" />
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full rounded-full border-none focus:ring-2 focus:ring-purple-300 text-gray-900"
                    autocomplete="new-password"
                />
                <InputError class="mt-2 text-red-500" :message="form.errors.password_confirmation" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton
                    class="w-full rounded-full px-4 py-3 bg-purple-600 text-white font-semibold text-lg text-center transition-all duration-300 hover:bg-purple-700 hover:scale-105 shadow-lg"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Save
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out duration-300"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out duration-300"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-green-600">
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>