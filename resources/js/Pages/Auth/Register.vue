<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const currentCard = ref(0);

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
    const inviteCode = urlParams.get('invitation_code');
    if (inviteCode) {
        form.invitation_code = inviteCode;
    }
});

const submit = async () => {
    try {
        await refreshCSRFToken();
        await form.post(route('register'), {
            onFinish: () => form.reset('password', 'password_confirmation', 'withdraw_password', 'withdraw_password_confirmation'),
        });
    } catch (error) {
        if (error.response && error.response.status === 419) {
            window.location.reload();
        } else {
            throw error;
        }
    }
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

const refreshCSRFToken = async () => {
    const res = await fetch(route('csrf-token'), {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    });
    const data = await res.json();
    const token = data.token;
    document.head.querySelector('meta[name="csrf-token"]').content = token;
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('Register')" />

    <div class="bg-gradient-to-br from-white/95 via-blue-50/90 to-indigo-50/95 backdrop-blur-xl rounded-b-3xl rounded-t-none shadow-2xl max-w-md w-full border border-white/40 border-t-0 mx-auto overflow-hidden">
        <div class="relative">
            <div class="flex transition-transform duration-500 ease-in-out" :style="{ transform: `translateX(-${currentCard * 100}%)` }">
                <!-- Card 1: Name & Mobile -->
                <div class="w-full flex-shrink-0 p-4 md:p-8 flex flex-col justify-between h-full">
                    <div class="space-y-4 md:space-y-6">
                        <div class="text-center mb-4">
                            <p class="text-xs text-gray-600 leading-relaxed">{{ t('Please provide your basic information to begin the registration process') }}</p>
                        </div>
                        <div>
                            <InputLabel for="name" :value="t('Name')" class="text-xs font-medium text-gray-600" />
                            <TextInput
                                id="name"
                                type="text"
                                class="w-full h-9 rounded-lg bg-gray-50 border-0 focus:ring-1 focus:ring-blue-500 text-gray-900 px-3 text-sm placeholder:text-xs placeholder:text-gray-400 mt-1"
                                v-model="form.name"
                                :placeholder="t('Enter your name')"
                                required
                                autofocus
                                autocomplete="name"
                            />
                            <InputError class="mt-1 text-xs text-red-500" :message="form.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="mobile_number" :value="t('Mobile Number')" class="text-xs font-medium text-gray-600" />
                            <TextInput
                                id="mobile_number"
                                type="text"
                                class="w-full h-9 rounded-lg bg-gray-50 border-0 focus:ring-1 focus:ring-blue-500 text-gray-900 px-3 text-sm placeholder:text-xs placeholder:text-gray-400 mt-1"
                                v-model="form.mobile_number"
                                :placeholder="t('Enter mobile number')"
                                required
                                autocomplete="username"
                                @input="form.mobile_number = form.mobile_number.replace(/[^0-9]/g, '')"
                            />
                            <InputError class="mt-1 text-xs text-red-500" :message="form.errors.mobile_number" />
                        </div>
                    </div>
                    
                    <div class="flex justify-center mt-6">
                        <button
                            type="button"
                            @click="currentCard = 1"
                            class="flex items-center justify-center gap-2 px-6 py-2.5 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-medium text-sm transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
                        >
                            <span>{{ t('Next') }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Card 2: Passwords -->
                <div class="w-full flex-shrink-0 p-4 md:p-8 flex flex-col justify-between h-full">
                    <div class="space-y-4 md:space-y-6">
                        <div class="text-center mb-4">
                            <h3 class="text-base font-semibold text-gray-800">{{ t('Set Password') }}</h3>
                            <p class="text-xs text-gray-600 mt-1">{{ t('Create a secure password for your account') }}</p>
                        </div>

                        <div>
                            <InputLabel for="password" :value="t('Password')" class="text-xs font-medium text-gray-600" />
                            <TextInput
                                id="password"
                                type="password"
                                class="w-full h-9 rounded-lg bg-gray-50 border-0 focus:ring-1 focus:ring-blue-500 text-gray-900 px-3 text-sm placeholder:text-xs placeholder:text-gray-400 mt-1"
                                v-model="form.password"
                                :placeholder="t('Enter password')"
                                required
                                autocomplete="new-password"
                            />
                            <InputError class="mt-1 text-xs text-red-500" :message="form.errors.password" />
                        </div>

                        <div>
                            <InputLabel for="password_confirmation" :value="t('Confirm Password')" class="text-xs font-medium text-gray-600" />
                            <TextInput
                                id="password_confirmation"
                                type="password"
                                class="w-full h-9 rounded-lg bg-gray-50 border-0 focus:ring-1 focus:ring-blue-500 text-gray-900 px-3 text-sm placeholder:text-xs placeholder:text-gray-400 mt-1"
                                v-model="form.password_confirmation"
                                :placeholder="t('Confirm password')"
                                required
                                autocomplete="new-password"
                            />
                            <InputError class="mt-1 text-xs text-red-500" :message="form.errors.password_confirmation" />
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center mt-6 gap-3">
                        <button
                            type="button"
                            @click="currentCard = 0"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 rounded-full bg-white/40 hover:bg-white/60 backdrop-blur-sm border border-white/50 text-gray-700 font-medium text-sm transition-all duration-300 transform hover:scale-105 shadow-md"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span>{{ t('Back') }}</span>
                        </button>
                        <button
                            type="button"
                            @click="currentCard = 2"
                            class="flex items-center justify-center gap-2 px-6 py-2.5 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-medium text-sm transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
                        >
                            <span>{{ t('Next') }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Card 3: PIN & Register -->
                <div class="w-full flex-shrink-0 p-4 md:p-8 flex flex-col h-full">
                    <div class="space-y-4">
                        <div class="text-center mb-4">
                            <h3 class="text-base font-semibold text-gray-800">{{ t('Final Step') }}</h3>
                            <p class="text-xs text-gray-600 mt-1">{{ t('Set your withdrawal PIN and complete registration') }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="withdraw_password" :value="t('Withdraw PIN')" class="text-xs font-medium text-gray-600" />
                                <TextInput
                                    id="withdraw_password"
                                    type="password"
                                    class="w-full h-9 rounded-lg bg-gray-50 border-0 focus:ring-1 focus:ring-blue-500 text-gray-900 px-3 text-sm placeholder:text-xs placeholder:text-gray-400 mt-1"
                                    v-model="form.withdraw_password"
                                    :placeholder="t('6-digit PIN')"
                                    required
                                    autocomplete="off"
                                />
                                <InputError class="mt-1 text-xs text-red-500" :message="form.errors.withdraw_password" />
                            </div>

                            <div>
                                <InputLabel for="withdraw_password_confirmation" :value="t('Confirm PIN')" class="text-xs font-medium text-gray-600" />
                                <TextInput
                                    id="withdraw_password_confirmation"
                                    type="password"
                                    class="w-full h-9 rounded-lg bg-gray-50 border-0 focus:ring-1 focus:ring-blue-500 text-gray-900 px-3 text-sm placeholder:text-xs placeholder:text-gray-400 mt-1"
                                    v-model="form.withdraw_password_confirmation"
                                    :placeholder="t('Confirm PIN')"
                                    required
                                    autocomplete="off"
                                />
                                <InputError class="mt-1 text-xs text-red-500" :message="form.errors.withdraw_password_confirmation" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="invitation_code" :value="t('Invitation Code (Optional)')" class="text-xs font-medium text-gray-600" />
                            <TextInput
                                id="invitation_code"
                                type="text"
                                class="w-full h-9 rounded-lg bg-gray-50 border-0 focus:ring-1 focus:ring-blue-500 text-gray-900 px-3 text-sm placeholder:text-xs placeholder:text-gray-400 mt-1"
                                v-model="form.invitation_code"
                                :placeholder="t('Enter invitation code')"
                                autocomplete="off"
                            />
                            <InputError class="mt-1 text-xs text-red-500" :message="form.errors.invitation_code" />
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center mt-6 gap-3">
                        <button
                            type="button"
                            @click="currentCard = 1"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 rounded-full bg-white/40 hover:bg-white/60 backdrop-blur-sm border border-white/50 text-gray-700 font-medium text-sm transition-all duration-300 transform hover:scale-105 shadow-md"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span>{{ t('Back') }}</span>
                        </button>
                        <PrimaryButton
                            @click="submit"
                            class="flex-1 h-10 rounded-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold text-sm transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
                            :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">{{ t('Creating Account...') }}</span>
                            <span v-else>{{ t('Create Account') }}</span>
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </GuestLayout>
</template>
