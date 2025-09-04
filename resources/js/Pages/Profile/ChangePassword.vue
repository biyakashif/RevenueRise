<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Change Password" />
    <AuthenticatedLayout>
        <div class="py-6 px-4">
            <section>
                <header class="flex justify-between items-center mb-6">
                    <Link
                        :href="route('profile.index')"
                        class="text-purple-600 text-sm font-medium hover:text-purple-700 flex items-center"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back
                    </Link>
                </header>

                <UpdatePasswordForm />
            </section>
        </div>
    </AuthenticatedLayout>
</template>