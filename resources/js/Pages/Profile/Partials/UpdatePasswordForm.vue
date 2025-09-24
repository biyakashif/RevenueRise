<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

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
    <div class="flex justify-center items-center min-h-[calc(100vh-8rem)]">
        <div class="w-full max-w-md">
            <section class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30">


        <form @submit.prevent="updatePassword" class="space-y-5">
            <div>
                <InputLabel for="current_password" :value="t('Current Password')" class="text-slate-700 font-medium drop-shadow-sm" />
                <TextInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="mt-1 block w-full h-12 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 placeholder-slate-400 backdrop-blur-sm shadow-lg"
                    autocomplete="current-password"
                />
                <InputError class="mt-2 text-red-500" :message="form.errors.current_password" />
            </div>

            <div>
                <InputLabel for="password" :value="t('New Password')" class="text-slate-700 font-medium drop-shadow-sm" />
                <TextInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full h-12 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 placeholder-slate-400 backdrop-blur-sm shadow-lg"
                    autocomplete="new-password"
                />
                <InputError class="mt-2 text-red-500" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation" :value="t('Confirm Password')" class="text-slate-700 font-medium drop-shadow-sm" />
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full h-12 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 placeholder-slate-400 backdrop-blur-sm shadow-lg"
                    autocomplete="new-password"
                />
                <InputError class="mt-2 text-red-500" :message="form.errors.password_confirmation" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton
                    class="w-full rounded-2xl px-4 py-3 bg-gradient-to-r from-cyan-500/80 to-blue-600/80 hover:from-cyan-600/90 hover:to-blue-700/90 text-white font-semibold text-lg text-center transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl backdrop-blur-sm"
                    :class="{ 'opacity-50': form.processing }"
                    :disabled="form.processing"
                >
                    {{ t('Save') }}
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out duration-300"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out duration-300"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-emerald-600 font-medium drop-shadow-sm">
                        {{ t('Saved.') }}
                    </p>
                </Transition>
            </div>
            </form>
            </section>
        </div>
    </div>
</template>