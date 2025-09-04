<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
const { translations } = usePage().props;
const t = (key) => translations[key] || key;
</script>

<template>
    <Head :title="t('About')" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-bold">{{ t('About Us') }}</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">{{ t('Learn more about our company.') }}</div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
