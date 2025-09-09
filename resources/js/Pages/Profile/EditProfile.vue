<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

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
const user = page.props.auth.user;

const form = useForm({
    name: user.name || t('Not set'),
    mobile_number: user.mobile_number || t('Not set'),
    invitation_code: user.invitation_code || t('Not set'),
});
</script>

<template>
    <Head :title="t('Edit Profile')" />

    <AuthenticatedLayout>
        <div class="py-6 px-4 bg-gray-100">
            <section>
                <header class="flex justify-between items-center mb-6">
                    <Link
                        :href="route('profile.index')"
                        class="text-purple-600 text-sm font-medium hover:text-purple-700 flex items-center"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        {{ t('Back') }}
                    </Link>
                </header>
                <h2 class="text-2xl font-bold text-gray-800">{{ t('Profile Information') }}</h2>
                <p class="mb-4 text-sm font-medium text-gray-500 text-center">
                    {{ t('Update your account profile information') }}
                </p>

                <form @submit.prevent="form.patch(route('profile.update'))" class="space-y-5 bg-white p-6 rounded-lg shadow-sm">
                    <div>
                        <InputLabel for="name" :value="t('Name')" class="text-gray-700" />
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

                    <div>
                        <InputLabel for="mobile_number" :value="t('Mobile Number')" class="text-gray-700" />
                        <TextInput
                            id="mobile_number"
                            type="text"
                            class="mt-1 block w-full rounded-full border-none bg-gray-50 text-gray-900 cursor-not-allowed"
                            v-model="form.mobile_number"
                            readonly
                        />
                    </div>

                    <div>
                        <InputLabel for="invitation_code" :value="t('Invitation Code')" class="text-gray-700" />
                        <TextInput
                            id="invitation_code"
                            type="text"
                            class="mt-1 block w-full rounded-full border-none bg-gray-50 text-gray-900 cursor-not-allowed"
                            v-model="form.invitation_code"
                            readonly
                        />
                    </div>

                    <div class="flex items-center gap-4">
                        <PrimaryButton
                            class="w-full rounded-full px-4 py-3 bg-purple-600 text-white font-semibold text-lg text-center transition-all duration-300 hover:bg-purple-700 hover:scale-105 shadow-lg"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            {{ t('Save') }}</PrimaryButton>

                        <Transition
                            enter-active-class="transition ease-in-out duration-300"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out duration-300"
                            leave-to-class="opacity-0"
                        >
                            <p v-if="form.recentlySuccessful" class="text-sm text-green-600">
                                {{ t('Saved') }}
                            </p>
                        </Transition>
                    </div>
                </form>
            </section>
        </div>
    </AuthenticatedLayout>
</template>