<script setup>
import Header from '@/Components/Header.vue';
import FloatingChatIcon from '@/Components/FloatingChatIcon.vue';
import GuestChatInterface from '@/Components/GuestChatInterface.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import axios from 'axios';


const page = usePage();
const currentRoute = computed(() => page.url);
const translations = computed(() => page.props.translations || {});
const t = (key) => translations.value[key] || key;

// Guest chat functionality
const showGuestChat = ref(false);
const showChatForm = ref(true);
const guestForm = ref({
    name: '',
    mobile_number: '',
    captcha: ''
});
const captchaImage = ref('');
const guestSessionId = ref(null);
const showCaptchaError = ref('');

// Generate image captcha
const generateCaptcha = () => {
    captchaImage.value = `/captcha?${Date.now()}`;
};

const openGuestChat = () => {
    showGuestChat.value = true;
    generateCaptcha();
};

const closeGuestChat = () => {
    showGuestChat.value = false;
    showChatForm.value = true;
    guestForm.value = { name: '', mobile_number: '', captcha: '' };
    guestSessionId.value = null;
};

const startGuestChat = async () => {
    try {
        // Verify captcha first
        const captchaResponse = await axios.post('/captcha/verify', {
            captcha: guestForm.value.captcha
        });
        
        if (!captchaResponse.data.valid) {
            showCaptchaError.value = t('Incorrect captcha. Please try again.');
            generateCaptcha();
            guestForm.value.captcha = '';
            return;
        }
        showCaptchaError.value = '';

        const response = await axios.post('/guest-chat/start', {
            name: guestForm.value.name,
            mobile_number: guestForm.value.mobile_number
        });
        
        if (response.data.blocked) {
            showCaptchaError.value = t('You have been blocked. You cannot send messages.');
            return;
        }
        
        guestSessionId.value = response.data.session_id;
        showChatForm.value = false;
    } catch (error) {
        alert(t('Error starting chat. Please try again.'));
    }
};


</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900">
        <Header class="relative z-50" />
        <div class="flex flex-col justify-center items-center p-4 min-h-[calc(100vh-64px)]">
            <!-- Auth Navigation Header -->
            <div class="w-full max-w-md">
                <div class="bg-white/10 backdrop-blur-md rounded-t-2xl p-1 border border-white/20 border-b-0">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 flex">
                            <Link 
                                :href="route('register')"
                                class="flex-1 text-center py-3 px-4 rounded-t-xl text-sm font-semibold transition-all duration-300"
                                :class="currentRoute.includes('/register') 
                                    ? 'bg-white text-blue-900 shadow-lg' 
                                    : 'text-white/80 hover:text-white hover:bg-white/10'"
                            >
                                {{ t('Create Account') }}
                            </Link>
                            <Link 
                                :href="route('login')"
                                class="flex-1 text-center py-3 px-4 rounded-t-xl text-sm font-semibold transition-all duration-300"
                                :class="currentRoute.includes('/login') 
                                    ? 'bg-white text-blue-900 shadow-lg' 
                                    : 'text-white/80 hover:text-white hover:bg-white/10'"
                            >
                                {{ t('Login') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
            <slot />
        </div>



        <!-- Floating Help Icon -->
        <FloatingChatIcon @click="openGuestChat" />

        <!-- Guest Chat Modal -->
        <div v-if="showGuestChat" class="fixed inset-0 bg-black/50 z-50">
            <div v-if="showChatForm" class="flex items-center justify-center h-full p-4">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
                    <div class="flex items-center justify-between p-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">{{ t('Contact Support') }}</h3>
                        <button @click="closeGuestChat" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div class="p-6">
                        <form @submit.prevent="startGuestChat" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('Name') }}</label>
                                <input 
                                    v-model="guestForm.name" 
                                    type="text" 
                                    required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    :placeholder="t('Enter your name')"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('Mobile Number') }}</label>
                                <input 
                                    v-model="guestForm.mobile_number" 
                                    type="tel" 
                                    required 
                                    pattern="[0-9+\-\s]*"
                                    @input="guestForm.mobile_number = guestForm.mobile_number.replace(/[^0-9+\-\s]/g, '')"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    :placeholder="t('Enter your mobile number')"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('Verification') }}</label>
                                <div class="flex items-center space-x-2 mb-2">
                                    <img 
                                        :src="captchaImage" 
                                        alt="Captcha" 
                                        class="border border-gray-300 rounded"
                                        @click="generateCaptcha"
                                        style="cursor: pointer;"
                                    >
                                    <button 
                                        type="button" 
                                        @click="generateCaptcha"
                                        class="text-blue-500 hover:text-blue-700 text-sm"
                                    >
                                        🔄
                                    </button>
                                </div>
                                <input 
                                    v-model="guestForm.captcha" 
                                    type="text" 
                                    required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    :placeholder="t('Enter the code shown above')"
                                >
                                <div v-if="showCaptchaError" class="text-red-500 text-sm mt-1">{{ showCaptchaError }}</div>
                            </div>
                            <button 
                                type="submit" 
                                class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white py-2 px-4 rounded-lg transition-all duration-200"
                            >
                                {{ t('Start Chat') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <GuestChatInterface 
                v-else
                :sessionId="guestSessionId" 
                :guestInfo="{ name: guestForm.name, mobile_number: guestForm.mobile_number }"
                @close="closeGuestChat"
            />
        </div>
    </div>
</template>