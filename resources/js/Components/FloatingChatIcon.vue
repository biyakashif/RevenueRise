<template>
    <button
        @click="$emit('click')"
        class="floating-help-btn fixed bottom-6 right-6 w-14 h-14 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-full flex items-center justify-center transition-all duration-300 transform hover:scale-110 z-50"
        :title="title"
    >
        <i class="fas fa-headset text-lg"></i>
    </button>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        default: 'Help & Support'
    }
});

defineEmits(['click']);
</script>