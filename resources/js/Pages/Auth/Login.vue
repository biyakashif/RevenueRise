<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    status: String,
});

const form = useForm({
    mobile_number: '',
    password: '',
    remember: false,
});

// i18n
const page = usePage();
const translations = computed(() => page.props.translations || {});
const locale = computed(() => page.props.locale || 'en');
const t = (key) => {
    const translation = translations.value[key];
    if (!translation && key && process.env.NODE_ENV === 'development') {
        console.warn(`Missing translation for key: ${key} in locale: ${locale.value}`);
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

const submit = async () => {
    try {
        await refreshCSRFToken();
        await form.post(route('login'), {
            onFinish: () => form.reset('password'),
        });
    } catch (error) {
        if (error.response && error.response.status === 419) {
            window.location.reload();
        } else {
            throw error;
        }
    }
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('login.title')" />

        <div class="bg-gradient-to-br from-white/95 via-blue-50/90 to-indigo-50/95 backdrop-blur-xl p-4 md:p-8 rounded-b-3xl rounded-t-none shadow-2xl max-w-md w-full border border-white/40 border-t-0 mx-auto">
            <div class="text-center mb-4">
                <h3 class="text-base font-semibold text-gray-800">{{ t('Welcome Back') }}</h3>
                <p class="text-xs text-gray-600 mt-1">{{ t('Sign in to your account to continue') }}</p>
            </div>

            <div v-if="status" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-sm font-medium text-green-700 text-center">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-4 md:space-y-6">
                <!-- Mobile Number -->
                <div>
                    <InputLabel for="mobile_number" :value="t('Mobile Number')" class="text-xs font-medium text-gray-600" />
                    <TextInput
                        id="mobile_number"
                        type="text"
                        class="w-full h-9 rounded-lg bg-gray-50 border-0 focus:ring-1 focus:ring-blue-500 text-gray-900 px-3 text-sm placeholder:text-xs placeholder:text-gray-400 mt-1"
                        v-model="form.mobile_number"
                        :placeholder="t('Mobile Number')"
                        required
                        autofocus
                        autocomplete="username"
                        @input="form.mobile_number = form.mobile_number.replace(/[^0-9]/g, '')"
                    />
                    <InputError class="mt-1 text-xs text-red-500" :message="form.errors.mobile_number" />
                </div>

                <!-- Set Password -->
                <div>
                    <InputLabel for="password" :value="t('Password')" class="text-xs font-medium text-gray-600" />
                    <TextInput
                        id="password"
                        type="password"
                        class="w-full h-9 rounded-lg bg-gray-50 border-0 focus:ring-1 focus:ring-blue-500 text-gray-900 px-3 text-sm placeholder:text-xs placeholder:text-gray-400 mt-1"
                        v-model="form.password"
                        :placeholder="t('Password')"
                        required
                        autocomplete="current-password"
                    />
                    <InputError class="mt-1 text-xs text-red-500" :message="form.errors.password" />
                </div>


                <!-- Remember Me -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <Checkbox name="remember" v-model:checked="form.remember" class="rounded" />
                        <span class="ml-2 text-xs text-gray-600">{{ t('Remember me') }}</span>
                    </div>
                    <Link :href="route('welcome') + '#chat'" class="text-xs text-blue-600 hover:text-blue-800 transition-colors duration-200">
                        {{ t('Need Help') }}
                    </Link>
                </div>

                <!-- Submit Button -->
                <PrimaryButton
                    class="w-full h-9 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold text-sm transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl"
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">{{ t('Signing in...') }}</span>
                    <span v-else>{{ t('Sign In') }}</span>
                </PrimaryButton>
            </form>
        </div>
    </GuestLayout>
</template>