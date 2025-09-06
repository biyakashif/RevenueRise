<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

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
</script>

<template>
    <Head :title="t('Change Password')" />
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
                        {{ t('Back') }}
                    </Link>
                </header>

                <UpdatePasswordForm />
            </section>
        </div>
    </AuthenticatedLayout>
</template>