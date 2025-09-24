<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

const form = useForm({
    name: '',
    mobile_number: '',
    password: '',
    password_confirmation: '',
    invitation_code: '',
    withdraw_password: '',
    withdraw_password_confirmation: '',
});

// Handle invitation code from URL
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const inviteCode = urlParams.get('invite');
    if (inviteCode) {
        form.invitation_code = inviteCode;
    }
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation', 'withdraw_password', 'withdraw_password_confirmation'),
    });
};

// i18n
const page = usePage();
const translations = computed(() => page.props.translations || {});
const locale = computed(() => page.props.locale || 'en');
const t = (key) => {
    const translation = translations.value[key];
    if (!translation && key && process.env.NODE_ENV === 'development') {
        console.warn(`Missing translation for key: ${key} in locale: ${locale.value}`);
        // Log both the key and current translations for debugging
        console.log('Translation key:', key);
        console.log('Current locale:', locale.value);
        console.log('Current translations:', translations.value);
    }
    return translation || key;
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('Register')" />

    <div class='bg-gradient-to-br from-white/95 via-blue-50/90 to-indigo-50/95 backdrop-blur-xl p-4 md:p-8 rounded-b-3xl rounded-t-none shadow-2xl max-w-md w-full border border-white/40 border-t-0 mx-auto'>
            <form @submit.prevent='submit' class='space-y-4 md:space-y-6'>
                <div class='grid grid-cols-1 md:grid-cols-2 gap-4'>
                    <!-- Name -->
                    <div>
                        <InputLabel for='name' :value='t("Name")' class='text-sm font-medium text-gray-700 mb-1' />
                        <TextInput
                            id='name'
                            type='text'
                            class='w-full h-10 md:h-12 rounded-xl bg-gray-50 border-0 focus:ring-2 focus:ring-blue-500 text-gray-900 px-4 placeholder-gray-400'
                            v-model='form.name'
                            placeholder='Enter your name'
                            required
                            autofocus
                            autocomplete='name'
                        />
                        <InputError class='mt-1 text-xs text-red-500' :message='form.errors.name' />
                    </div>

                    <!-- Mobile Number -->
                    <div>
                        <InputLabel for="mobile_number" :value="t('Mobile Number')" class="text-sm font-medium text-gray-700 mb-1" />
                        <TextInput
                            id='mobile_number'
                            type='text'
                            class='w-full h-10 md:h-12 rounded-xl bg-gray-50 border-0 focus:ring-2 focus:ring-blue-500 text-gray-900 px-4 placeholder-gray-400'
                            v-model='form.mobile_number'
                            placeholder='Enter mobile number'
                            required
                            autocomplete='username'
                            @input='form.mobile_number = form.mobile_number.replace(/[^0-9]/g, "")'
                        />
                        <InputError class='mt-1 text-xs text-red-500' :message='form.errors.mobile_number' />
                    </div>
                </div>

                <div class='grid grid-cols-1 md:grid-cols-2 gap-4'>
                    <!-- Password -->
                    <div>
                        <InputLabel for='password' :value='t("Password")' class='text-sm font-medium text-gray-700 mb-1' />
                        <TextInput
                            id='password'
                            type='password'
                            class='w-full h-10 md:h-12 rounded-xl bg-gray-50 border-0 focus:ring-2 focus:ring-blue-500 text-gray-900 px-4 placeholder-gray-400'
                            v-model='form.password'
                            placeholder='Enter password'
                            required
                            autocomplete='new-password'
                        />
                        <InputError class='mt-1 text-xs text-red-500' :message='form.errors.password' />
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <InputLabel for='password_confirmation' :value='t("Confirm Password")' class='text-sm font-medium text-gray-700 mb-1' />
                        <TextInput
                            id='password_confirmation'
                            type='password'
                            class='w-full h-10 md:h-12 rounded-xl bg-gray-50 border-0 focus:ring-2 focus:ring-blue-500 text-gray-900 px-4 placeholder-gray-400'
                            v-model='form.password_confirmation'
                            placeholder='Confirm password'
                            required
                            autocomplete='new-password'
                        />
                        <InputError class='mt-1 text-xs text-red-500' :message='form.errors.password_confirmation' />
                    </div>
                </div>

                <div class='grid grid-cols-1 md:grid-cols-2 gap-4'>
                    <!-- Withdraw Password -->
                    <div>
                        <InputLabel for='withdraw_password' :value='t("Withdraw PIN")' class='text-sm font-medium text-gray-700 mb-1' />
                        <TextInput
                            id='withdraw_password'
                            type='password'
                            class='w-full h-10 md:h-12 rounded-xl bg-gray-50 border-0 focus:ring-2 focus:ring-blue-500 text-gray-900 px-4 placeholder-gray-400'
                            v-model='form.withdraw_password'
                            placeholder='6-digit PIN'
                            required
                            autocomplete='off'
                        />
                        <InputError class='mt-1 text-xs text-red-500' :message='form.errors.withdraw_password' />
                    </div>

                    <!-- Confirm Withdraw Password -->
                    <div>
                        <InputLabel for='withdraw_password_confirmation' :value='t("Confirm PIN")' class='text-sm font-medium text-gray-700 mb-1' />
                        <TextInput
                            id='withdraw_password_confirmation'
                            type='password'
                            class='w-full h-10 md:h-12 rounded-xl bg-gray-50 border-0 focus:ring-2 focus:ring-blue-500 text-gray-900 px-4 placeholder-gray-400'
                            v-model='form.withdraw_password_confirmation'
                            placeholder='Confirm PIN'
                            required
                            autocomplete='off'
                        />
                        <InputError class='mt-1 text-xs text-red-500' :message='form.errors.withdraw_password_confirmation' />
                    </div>
                </div>

                <!-- Invitation Code -->
                <div>
                    <InputLabel for='invitation_code' :value='t("Invitation Code (Optional)")' class='text-sm font-medium text-gray-700 mb-1' />
                    <TextInput
                        id='invitation_code'
                        type='text'
                        class='w-full h-10 md:h-12 rounded-xl bg-gray-50 border-0 focus:ring-2 focus:ring-blue-500 text-gray-900 px-4 placeholder-gray-400'
                        v-model='form.invitation_code'
                        placeholder='Enter invitation code'
                        :readonly="!!form.invitation_code"
                        autocomplete='off'
                    />
                    <InputError class='mt-1 text-xs text-red-500' :message='form.errors.invitation_code' />
                </div>

                <!-- Submit Button -->
                <PrimaryButton
                    class='w-full h-9 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold text-sm transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl'
                    :class='{ "opacity-50 cursor-not-allowed": form.processing }'
                    :disabled='form.processing'
                >
                    <span v-if='form.processing'>Creating Account...</span>
                    <span v-else>Create Account</span>
                </PrimaryButton>
            </form>
        </div>
    </GuestLayout>
</template>
