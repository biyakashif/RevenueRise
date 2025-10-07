<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name || '',
});
</script>

<template>
    <section>


        <form @submit.prevent="form.patch(route('profile.update'))" class="space-y-5">
            <div>
                <InputLabel for="name" value="Name" class="text-white" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full rounded-full border-none focus:ring-2 focus:ring-blue-300 text-gray-900"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2 text-pink-300" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="mobile_number" value="Mobile Number" class="text-white" />
                <input
                    id="mobile_number"
                    type="text"
                    class="mt-1 block w-full rounded-full border-none bg-white/10 text-gray-900 cursor-not-allowed"
                    :value="user.mobile_number || 'Not set'"
                    readonly
                />
            </div>

            <div>
                <InputLabel for="invitation_code" value="Invitation Code" class="text-white" />
                <input
                    id="invitation_code"
                    type="text"
                    class="mt-1 block w-full rounded-full border-none bg-white/10 text-gray-900 cursor-not-allowed"
                    :value="user.invitation_code || 'Not set'"
                    readonly
                />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton
                    class="w-full rounded-full px-4 py-3 bg-gradient-to-r from-blue-700 to-blue-900 text-white font-semibold text-lg text-center transition-all duration-300 hover:from-blue-800 hover:to-blue-950 hover:scale-105 shadow-lg"
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
                    <p v-if="form.recentlySuccessful" class="text-sm text-green-300">
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>