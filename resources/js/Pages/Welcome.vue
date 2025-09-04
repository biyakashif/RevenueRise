<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Header from '@/Components/Header.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Welcome" />
    <div class="bg-gray-100 min-h-screen flex flex-col items-center justify-center text-gray-800 px-6">
        <div class="bg-white p-6 rounded-lg shadow-sm w-full max-w-md text-center">
            <div class="flex justify-center mb-6">
                <Link :href="route('dashboard')">
                    <ApplicationLogo class="block h-10 w-auto fill-current text-purple-600" />
                </Link>
            </div>

            <h1 class="text-2xl font-bold mb-2">Task-App</h1>
            <p class="text-sm text-gray-500 mb-8">
                Earn effortlessly from anywhere — no skills, no experience, just your time.
            </p>

            <nav v-if="canLogin" class="flex flex-col gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="w-full text-center rounded-full px-4 py-3 bg-purple-600 font-semibold text-lg transition-all duration-300 hover:bg-purple-700 hover:scale-105 shadow-lg"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="w-full text-center rounded-full px-4 py-3 bg-purple-600 font-semibold text-white transition-all duration-300 hover:bg-purple-700 hover:scale-105 shadow-lg"
                    >
                        Log in
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="w-full text-center rounded-full px-4 py-3 bg-gray-200 font-semibold text-lg transition-all duration-300 hover:bg-gray-300 hover:scale-105 shadow-lg"
                    >
                        Register
                    </Link>
                </template>
            </nav>
        </div>
    </div>
</template>