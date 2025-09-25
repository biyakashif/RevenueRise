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
const showCaptchaError = ref('');
const isBlocked = ref(false);
const blockedSessions = ref(new Set());

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
    isBlocked.value = false;
    guestSessionId.value = null;
};

const startGuestChat = async () => {
    try {
        // Verify captcha first
        const captchaResponse = await axios.post('/captcha/verify', {
            captcha: guestForm.value.captcha
        });
        
        if (!captchaResponse.data.valid) {
            showCaptchaError.value = 'Incorrect captcha. Please try again.';
            generateCaptcha();
            guestForm.value.captcha = '';
            return;
        }
        showCaptchaError.value = '';

        const response = await axios.post('/guest-chat/start', {
            name: guestForm.value.name,
            mobile_number: guestForm.value.mobile_number
        });
        
        guestSessionId.value = response.data.session_id;
        isBlocked.value = blockedSessions.value.has(response.data.session_id);
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
        const serverMessages = response.data.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        
        // Preserve temp messages and merge with server messages
        const tempMessages = messages.value.filter(m => m.isTemp);
        const existingIds = new Set(serverMessages.map(m => m.id));
        
        // Only add new messages from server that don't exist yet
        const currentIds = new Set(messages.value.filter(m => !m.isTemp).map(m => m.id));
        const newServerMessages = serverMessages.filter(m => !currentIds.has(m.id));
        
        if (newServerMessages.length > 0) {
            // Check for admin messages for notification
            const hasAdminMessage = newServerMessages.some(msg => !msg.is_guest);
            
            // Merge: existing non-temp + new server + temp messages
            const nonTempExisting = messages.value.filter(m => !m.isTemp);
            messages.value = [...nonTempExisting, ...newServerMessages, ...tempMessages]
                .sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
            
            if (hasAdminMessage) {
                notificationSound.play().catch(() => {});
            }
            
            setTimeout(() => {
                if (messagesContainer.value) {
                    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
                }
            }, 100);
        }
    } catch (error) {
        // If chat is deleted (404), stop polling and close chat
        if (error.response?.status === 404) {
            stopRealTimeChat();
            closeGuestChat();
        }
    }
};

const sendGuestMessage = async () => {
    if (!newMessage.value.trim() || !guestSessionId.value) return;
    
    const messageText = newMessage.value;
    newMessage.value = ''; // Clear immediately
    
    // Add message optimistically with unique temp ID
    const tempId = `temp-${Date.now()}`;
    const tempMessage = {
        id: tempId,
        message: messageText,
        is_guest: true,
        created_at: new Date().toISOString(),
        isTemp: true
    };
    messages.value.push(tempMessage);
    
    // Auto-scroll
    setTimeout(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    }, 50);
    
    try {
        const response = await axios.post(`/guest-chat/${guestSessionId.value}/send`, {
            message: messageText
        });
        
        // Replace temp message with real one
        const index = messages.value.findIndex(m => m.id === tempId);
        if (index !== -1) {
            messages.value[index] = {
                id: response.data.id,
                message: response.data.message,
                is_guest: response.data.is_guest,
                created_at: response.data.created_at
            };
        }
        
        isBlocked.value = false;
        blockedSessions.value.delete(guestSessionId.value);
    } catch (error) {
        // Remove the optimistic message on error
        messages.value = messages.value.filter(m => m.id !== tempId);
        newMessage.value = messageText; // Restore message
        
        if (error.response?.status === 403) {
            isBlocked.value = true;
            blockedSessions.value.add(guestSessionId.value);
            // Silently handle 403 - no console logging
        }
    }
};

// Real-time chat with Echo + polling fallback
let pollInterval = null;
let echoChannel = null;

const startRealTimeChat = () => {
    if (!guestSessionId.value) return;
    
    // Set up Echo listener for real-time messages
    if (window.Echo) {
        try {
            echoChannel = window.Echo.channel(`guest-chat.${guestSessionId.value}`);
            echoChannel.listen('NewGuestChatMessage', (e) => {
                console.log('📨 Guest received NewGuestChatMessage:', e);
                if (e.message && e.message.sender_id !== guestSessionId.value) {
                    // Admin message received
                    const newMessage = {
                        id: e.message.id,
                        message: e.message.message,
                        is_guest: false,
                        created_at: e.message.created_at
                    };
                    
                    // Check if message already exists
                    const exists = messages.value.some(m => m.id === newMessage.id);
                    if (!exists) {
                        messages.value.push(newMessage);
                        messages.value.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                        
                        // Play notification and scroll
                        notificationSound.play().catch(() => {});
                        setTimeout(() => {
                            if (messagesContainer.value) {
                                messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
                            }
                        }, 100);
                    }
                }
            });
            
            // Listen for chat deletion
            echoChannel.listen('GuestChatDeleted', (e) => {
                console.log('🗑️ Guest chat deleted:', e);
                if (e.session_id === guestSessionId.value) {
                    alert('Your chat session has been ended by the administrator.');
                    closeGuestChat();
                }
            });
        } catch (error) {
            console.log('Echo setup failed, using polling only');
        }
    }
    
    // Polling fallback every 3 seconds
    pollInterval = setInterval(() => {
        if (guestSessionId.value) {
            loadGuestMessages();
        } else {
            clearInterval(pollInterval);
        }
    }, 3000);
};

const stopRealTimeChat = () => {
    if (echoChannel) {
        echoChannel.stopListening('NewGuestChatMessage');
        echoChannel.stopListening('GuestChatDeleted');
        if (window.Echo) {
            window.Echo.leaveChannel(`guest-chat.${guestSessionId.value}`);
        }
        echoChannel = null;
    }
    
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
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
                            <div v-if="showCaptchaError" class="text-red-500 text-sm mt-1">{{ showCaptchaError }}</div>
                        </div>
                        <button 
                            type="submit" 
                            class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white py-2 px-4 rounded-lg transition-all duration-200"
                        >
                            Start Chat
                        </button>
                    </form>
                </div>

                <div v-else class="flex flex-col flex-1 min-h-0">
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
                            </div>
                        </div>
                    </div>

                    <div class="border-t p-4">
                        <div v-if="isBlocked" class="text-center py-4 text-red-600 bg-red-50 rounded-lg mb-4">
                            You have been blocked by admin. You cannot send messages.
                        </div>
                        <form v-else @submit.prevent="sendGuestMessage" class="flex space-x-2">
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