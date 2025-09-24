<script setup>
import Header from '@/Components/Header.vue';
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
const messages = ref([]);
const newMessage = ref('');
const guestSessionId = ref(null);
const messagesContainer = ref(null);
const notificationSound = new Audio('/notification.mp3');

// Generate image captcha
const generateCaptcha = () => {
    captchaImage.value = `/captcha?${Date.now()}`;
};

const openGuestChat = () => {
    showGuestChat.value = true;
    generateCaptcha();
};

const closeGuestChat = () => {
    stopRealTimeChat();
    showGuestChat.value = false;
    showChatForm.value = true;
    guestForm.value = { name: '', mobile_number: '', captcha: '' };
    messages.value = [];
    newMessage.value = '';
    guestSessionId.value = null;
};

const startGuestChat = async () => {
    try {
        // Verify captcha first
        const captchaResponse = await axios.post('/captcha/verify', {
            captcha: guestForm.value.captcha
        });
        
        if (!captchaResponse.data.valid) {
            alert('Incorrect captcha. Please try again.');
            generateCaptcha();
            guestForm.value.captcha = '';
            return;
        }

        const response = await axios.post('/guest-chat/start', {
            name: guestForm.value.name,
            mobile_number: guestForm.value.mobile_number
        });
        
        guestSessionId.value = response.data.session_id;
        showChatForm.value = false;
        loadGuestMessages();
        startRealTimeChat();
    } catch (error) {
        alert('Error starting chat. Please try again.');
    }
};

const loadGuestMessages = async () => {
    if (!guestSessionId.value) return;
    
    try {
        const response = await axios.get(`/guest-chat/${guestSessionId.value}/messages`);
        const oldLength = messages.value.length;
        messages.value = response.data.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        
        // Auto-scroll and play sound if new messages from admin
        if (messages.value.length > oldLength) {
            const newMessages = messages.value.slice(oldLength);
            const hasAdminMessage = newMessages.some(msg => !msg.is_guest);
            
            if (hasAdminMessage) {
                notificationSound.play().catch(() => {/* ignore autoplay restrictions */});
            }
            
            setTimeout(() => {
                if (messagesContainer.value) {
                    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
                }
            }, 100);
        }
    } catch (error) {
        console.error('Error loading messages:', error);
    }
};

const sendGuestMessage = async () => {
    if (!newMessage.value.trim() || !guestSessionId.value) return;
    
    try {
        await axios.post(`/guest-chat/${guestSessionId.value}/send`, {
            message: newMessage.value
        });
        
        newMessage.value = '';
        loadGuestMessages();
    } catch (error) {
        alert('Error sending message. Please try again.');
    }
};

// Real-time messaging with Echo
let echoChannel = null;

const startRealTimeChat = () => {
    if (!guestSessionId.value) return;
    
    // Setup Echo for guest chat if available
    if (window.Echo) {
        console.log('Setting up Echo for guest chat:', guestSessionId.value);
        echoChannel = window.Echo.channel(`guest-chat.${guestSessionId.value}`);
        
        echoChannel.listen('NewGuestChatMessage', (e) => {
            console.log('Received message:', e);
            if (!e.chat.is_guest) {
                messages.value.push({
                    id: e.chat.id,
                    message: e.chat.message,
                    is_guest: e.chat.is_guest,
                    created_at: e.chat.created_at
                });
                
                notificationSound.play().catch(() => {});
                
                setTimeout(() => {
                    if (messagesContainer.value) {
                        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
                    }
                }, 100);
            }
        });
    } else {
        console.log('Echo not available, using polling fallback');
        const pollInterval = setInterval(() => {
            if (guestSessionId.value) {
                loadGuestMessages();
            } else {
                clearInterval(pollInterval);
            }
        }, 3000);
    }
};

const stopRealTimeChat = () => {
    if (echoChannel) {
        window.Echo?.leaveChannel(`guest-chat.${guestSessionId.value}`);
        echoChannel = null;
    }
};

// Initialize Echo for guests
onMounted(() => {
    // Echo should already be initialized in bootstrap.js for public channels
    if (!window.Echo && window.Pusher && window.Laravel?.pusher) {
        try {
            window.Echo = new window.LaravelEcho({
                broadcaster: 'pusher',
                key: window.Laravel.pusher.key,
                cluster: window.Laravel.pusher.cluster,
                forceTLS: true,
                encrypted: true,
                disableStats: true
            });
        } catch (error) {
            console.log('Echo not available for guest chat');
        }
    }
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900">
        <Header class="relative z-50" />
        <div class="flex flex-col justify-center items-center p-4 min-h-[calc(100vh-64px)]">
            <!-- Auth Navigation Header -->
            <div class="w-full max-w-md">
                <div class="bg-white/10 backdrop-blur-md rounded-t-2xl p-1 border border-white/20 border-b-0">
                    <div class="flex">
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
            <slot />
        </div>

        <!-- Floating Help Icon -->
        <button 
            @click="openGuestChat"
            class="floating-help-btn fixed bottom-6 right-6 w-14 h-14 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-full flex items-center justify-center transition-all duration-300 transform hover:scale-110 z-50"
            title="Help & Support"
        >
            <i class="fas fa-headset text-lg"></i>
        </button>

        <!-- Guest Chat Modal -->
        <div v-if="showGuestChat" class="guest-chat-modal fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[80vh] flex flex-col">
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">{{ showChatForm ? 'Contact Support' : 'Guest Chat' }}</h3>
                    <button @click="closeGuestChat" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Guest Form -->
                <div v-if="showChatForm" class="p-6">
                    <form @submit.prevent="startGuestChat" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input 
                                v-model="guestForm.name" 
                                type="text" 
                                required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter your name"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number</label>
                            <input 
                                v-model="guestForm.mobile_number" 
                                type="tel" 
                                required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter your mobile number"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Verification</label>
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
                                placeholder="Enter the code shown above"
                            >
                        </div>
                        <button 
                            type="submit" 
                            class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white py-2 px-4 rounded-lg transition-all duration-200"
                        >
                            Start Chat
                        </button>
                    </form>
                </div>

                <!-- Chat Interface -->
                <div v-else class="flex flex-col flex-1 min-h-0">
                    <!-- Messages Area -->
                    <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-3 min-h-0">
                        <div v-if="messages.length === 0" class="text-center text-gray-500 py-8">
                            No messages yet. Start the conversation!
                        </div>
                        <div v-for="message in messages" :key="message.id" 
                             class="guest-chat-message flex" 
                             :class="message.is_guest ? 'justify-end' : 'justify-start'">
                            <div class="max-w-[80%] rounded-lg p-3 text-sm"
                                 :class="message.is_guest 
                                     ? 'bg-blue-500 text-white' 
                                     : 'bg-gray-100 text-gray-800'">
                                <p>{{ message.message }}</p>
                                <div class="text-xs mt-1 opacity-70">
                                    {{ new Date(message.created_at).toLocaleTimeString() }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Message Input -->
                    <div class="border-t p-4">
                        <form @submit.prevent="sendGuestMessage" class="flex space-x-2">
                            <input 
                                v-model="newMessage" 
                                type="text" 
                                placeholder="Type your message..." 
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                            <button 
                                type="submit" 
                                class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2 rounded-lg transition-all duration-200"
                            >
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>