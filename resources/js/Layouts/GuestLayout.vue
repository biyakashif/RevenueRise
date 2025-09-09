<script setup>
import Header from '@/Components/Header.vue';
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <Header />
        <div class="flex flex-col justify-center items-center p-4">
            <slot />
        </div>
    </div>
</template>