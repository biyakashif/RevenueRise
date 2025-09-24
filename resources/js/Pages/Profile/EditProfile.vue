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
        <div class="flex justify-center items-center min-h-[calc(100vh-8rem)]">
            <div class="w-full max-w-md">
                <header class="flex justify-between items-center mb-6">
                    <Link
                        :href="route('profile.index')"
                        class="text-blue-600 text-sm font-medium hover:text-blue-700 flex items-center transition-colors duration-200 drop-shadow-sm"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        {{ t('Back') }}
                    </Link>
                </header>
                <form @submit.prevent="form.patch(route('profile.update'))" class="space-y-5 bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30">
                    <div>
                        <InputLabel for="name" :value="t('Name')" class="text-slate-700 font-medium drop-shadow-sm" />
                        <TextInput
                            id="name"
                            type="text"
                            class="mt-1 block w-full h-12 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 placeholder-slate-400 backdrop-blur-sm shadow-lg"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                        />
                        <InputError class="mt-2 text-red-500" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="mobile_number" :value="t('Mobile Number')" class="text-slate-700 font-medium drop-shadow-sm" />
                        <TextInput
                            id="mobile_number"
                            type="text"
                            class="mt-1 block w-full h-12 rounded-xl bg-white/30 border-0 text-slate-700 cursor-not-allowed px-4 backdrop-blur-sm shadow-lg"
                            v-model="form.mobile_number"
                            readonly
                        />
                    </div>

                    <div>
                        <InputLabel for="invitation_code" :value="t('Invitation Code')" class="text-slate-700 font-medium drop-shadow-sm" />
                        <TextInput
                            id="invitation_code"
                            type="text"
                            class="mt-1 block w-full h-12 rounded-xl bg-white/30 border-0 text-slate-700 cursor-not-allowed px-4 backdrop-blur-sm shadow-lg"
                            v-model="form.invitation_code"
                            readonly
                        />
                    </div>

                    <div class="flex items-center gap-4">
                        <PrimaryButton
                            class="w-full rounded-2xl px-4 py-3 bg-gradient-to-r from-cyan-500/80 to-blue-600/80 hover:from-cyan-600/90 hover:to-blue-700/90 text-white font-semibold text-lg text-center transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl backdrop-blur-sm"
                            :class="{ 'opacity-50': form.processing }"
                            :disabled="form.processing"
                        >
                            {{ t('Save') }}</PrimaryButton>

                        <Transition
                            enter-active-class="transition ease-in-out duration-300"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out duration-300"
                            leave-to-class="opacity-0"
                        >
                            <p v-if="form.recentlySuccessful" class="text-sm text-emerald-600 font-medium drop-shadow-sm">
                                {{ t('Saved') }}
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>