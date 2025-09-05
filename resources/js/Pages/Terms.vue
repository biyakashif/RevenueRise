<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
const { translations = {} } = usePage().props;
const t = (key) => translations[key] || key;
</script>

<template>
    <Head :title="t('T&C')" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-bold">{{ t('Terms for Agency & Promotion') }}</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        {{ t('By joining our agency program, you agree to promote our platform and earn commissions.') }}<br>
                        {{ t('You may also rate and review stores like Alibaba, Amazon, and eBay as part of our community.') }}
                        {{ t('All ratings should be honest and based on real experiences.') }}
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
