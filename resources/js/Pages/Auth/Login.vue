<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
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

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('Log in')" />

        <div class="bg-white p-8 rounded-lg shadow-sm max-w-md w-full text-gray-800">
            <h1 class="text-2xl font-bold text-center mb-6">{{ t('Log in') }}</h1>

            <div v-if="status" class="mb-4 text-sm font-medium text-green-600 text-center">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Mobile Number -->
                <div>
                    <InputLabel for="mobile_number" :value="t('Mobile Number')" class="text-gray-700" />
                    <TextInput
                        id="mobile_number"
                        type="text"
                        class="mt-1 block w-full rounded-full border-none focus:ring-2 focus:ring-purple-300 text-gray-900"
                        v-model="form.mobile_number"
                        required
                        autofocus
                        autocomplete="username"
                        @input="form.mobile_number = form.mobile_number.replace(/[^0-9]/g, '')"
                    />
                    <InputError class="mt-2 text-red-500" :message="form.errors.mobile_number" />
                </div>

                <!-- Password -->
                <div>
                    <InputLabel for="password" :value="t('Password')" class="text-gray-700" />
                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1 block w-full rounded-full border-none focus:ring-2 focus:ring-purple-300 text-gray-900"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                    />
                    <InputError class="mt-2 text-red-500" :message="form.errors.password" />
                </div>

                <!-- Remember Me -->
                <div class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-500">{{ t('Remember me') }}</span>
                </div>

                <!-- Submit Button -->
                <div>
                    <PrimaryButton
                        class="w-full rounded-full px-4 py-3 bg-purple-600 text-white font-semibold text-lg text-center transition-all duration-300 hover:bg-purple-700 hover:scale-105 shadow-lg"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        {{ t('Log in') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>