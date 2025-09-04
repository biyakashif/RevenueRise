<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    mobile_number: '',
    password: '',
    password_confirmation: '',
    invitation_code: '',
    withdraw_password: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <div class="bg-white p-8 rounded-lg shadow-sm max-w-md w-full text-gray-800">
            <h1 class="text-2xl font-bold text-center mb-6">Create Account</h1>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Name -->
                <div>
                    <InputLabel for="name" value="Name" class="text-gray-700" />
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full rounded-full border-none focus:ring-2 focus:ring-purple-300 text-gray-900"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                    />
                    <InputError class="mt-2 text-red-500" :message="form.errors.name" />
                </div>

                <!-- Mobile Number -->
                <div>
                    <InputLabel for="mobile_number" value="Mobile Number" class="text-gray-700" />
                    <TextInput
                        id="mobile_number"
                        type="text"
                        class="mt-1 block w-full rounded-full border-none focus:ring-2 focus:ring-purple-300 text-gray-900"
                        v-model="form.mobile_number"
                        required
                        autocomplete="username"
                        @input="form.mobile_number = form.mobile_number.replace(/[^0-9]/g, '')"
                    />
                    <InputError class="mt-2 text-red-500" :message="form.errors.mobile_number" />
                </div>

                <!-- Password -->
                <div>
                    <InputLabel for="password" value="Password" class="text-gray-700" />
                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1 block w-full rounded-full border-none focus:ring-2 focus:ring-purple-300 text-gray-900"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                    />
                    <InputError class="mt-2 text-red-500" :message="form.errors.password" />
                </div>

                <!-- Confirm Password -->
                <div>
                    <InputLabel for="password_confirmation" value="Confirm Password" class="text-gray-700" />
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        class="mt-1 block w-full rounded-full border-none focus:ring-2 focus:ring-purple-300 text-gray-900"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                    />
                    <InputError class="mt-2 text-red-500" :message="form.errors.password_confirmation" />
                </div>

                <!-- Invitation Code -->
                <div>
                    <InputLabel for="invitation_code" value="Invitation Code (Optional)" class="text-gray-700" />
                    <TextInput
                        id="invitation_code"
                        type="text"
                        class="mt-1 block w-full rounded-full border-none focus:ring-2 focus:ring-purple-300 text-gray-900"
                        v-model="form.invitation_code"
                        autocomplete="off"
                    />
                    <InputError class="mt-2 text-red-500" :message="form.errors.invitation_code" />
                </div>

                <!-- Withdraw Password -->
                <div>
                    <InputLabel for="withdraw_password" value="Withdraw Password" class="text-gray-700" />
                    <TextInput
                        id="withdraw_password"
                        type="text"
                        class="mt-1 block w-full rounded-full border-none focus:ring-2 focus:ring-purple-300 text-gray-900"
                        v-model="form.withdraw_password"
                        required
                        autocomplete="off"
                    />
                    <InputError class="mt-2 text-red-500" :message="form.errors.withdraw_password" />
                </div>

                <!-- Footer Actions -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4">
                    <Link
                        :href="route('login')"
                        class="text-sm text-gray-500 hover:underline"
                    >
                        Already registered?
                    </Link>

                    <PrimaryButton
                        class="w-full sm:w-auto rounded-full px-4 py-3 bg-purple-600 text-white font-semibold text-lg text-center transition-all duration-300 hover:bg-purple-700 hover:scale-105 shadow-lg"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Register
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>