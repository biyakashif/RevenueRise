<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const page = usePage();
const translations = computed(() => page.props.translations || {});
const t = (key) => translations.value[key] || key;
</script>

<template>
    <Head :title="t('Service')" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-bold">{{ t('Agency & Promotion Service') }}</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        {{ t('Join our agency program') }}<br>
                        {{ t('Rate and review stores') }}
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
