<template>
    <div class="absolute inset-0 flex flex-col">
        <!-- Chat Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-gradient-to-br from-cyan-400/30 via-blue-500/25 to-indigo-600/30 backdrop-blur-xl text-white shadow-md border-b border-cyan-300/30 rounded-t-2xl sm:rounded-t-3xl">
            <button @click="$emit('close')" class="text-blue-500 focus:outline-none mr-3">
                <i class="fas fa-arrow-left"></i>
            </button>
            <div class="text-lg text-blue-500 font-semibold">{{ t('Support Chat') }}</div>
            <button class="text-white focus:outline-none">
            </button>
        </div>

        <!-- Chat Area -->
        <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl">
            <div v-if="isBlocked" class="flex items-center justify-center h-full">
                <div class="text-center py-8 px-4 bg-red-50 rounded-lg border border-red-200">
                    <i class="fas fa-ban text-red-500 text-4xl mb-4"></i>
                    <h3 class="text-lg font-semibold text-red-800 mb-2">{{ t('Account Blocked') }}</h3>
                    <p class="text-red-600">{{ t('You have been blocked by the administrator. You cannot send or receive messages.') }}</p>
                </div>
            </div>
            <div v-else-if="messages.length === 0" class="flex items-center justify-center h-full text-slate-600">
                {{ t('No messages yet. Start the conversation!') }}
            </div>
            <div v-else v-for="message in messages" :key="message.id" 
                 class="mb-4 flex"
                 :class="{'justify-end': message.is_guest, 'justify-start': !message.is_guest}">
                <div class="flex items-start space-x-2 max-w-[85%]"
                     :class="message.is_guest ? 'flex-row-reverse space-x-reverse' : ''">
                    <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-user text-gray-400 text-xs"></i>
                    </div>
                    <div class="rounded-lg p-3 text-sm bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl border border-cyan-300/30"
                         :class="message.is_guest ? 'self-end' : 'self-start'">
                        <div v-if="message.image_path" class="mb-2">
                            <img :src="message.image_path.startsWith('blob:') ? message.image_path : (message.image_path.startsWith('/storage/') ? message.image_path : `/storage/${message.image_path}`)" 
                                 alt="chat image" 
                                 class="w-32 h-32 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity"
                                 @click="openMediaModal(message.image_path.startsWith('blob:') ? message.image_path : (message.image_path.startsWith('/storage/') ? message.image_path : `/storage/${message.image_path}`), 'image')">
                        </div>
                        <div v-if="message.video_path" class="mb-2 relative">
                            <div v-if="message.video_path.startsWith('blob:')" class="w-32 h-32 bg-gray-200 rounded flex items-center justify-center cursor-pointer hover:opacity-80 transition-opacity">
                                <i class="fas fa-video text-gray-600 text-2xl"></i>
                            </div>
                            <video v-else class="w-32 h-32 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity"
                                   @click="openMediaModal(message.video_path.startsWith('blob:') ? message.video_path : (message.video_path.startsWith('/storage/') ? message.video_path : `/storage/${message.video_path}`), 'video')">
                                <source :src="message.video_path.startsWith('blob:') ? message.video_path : (message.video_path.startsWith('/storage/') ? message.video_path : `/storage/${message.video_path}`)" type="video/mp4">
                            </video>
                            <div v-if="!message.video_path.startsWith('blob:')" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <i class="fas fa-play-circle text-white text-2xl opacity-80"></i>
                            </div>
                        </div>
                        <div v-if="message.message" class="text-sm text-white" v-html="message.message"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message Input -->
        <div class="border-t border-cyan-300/30 p-3 bg-gradient-to-br from-cyan-400/30 via-blue-500/25 to-indigo-600/30 backdrop-blur-xl flex items-center space-x-2 rounded-b-2xl sm:rounded-b-3xl">
            <div v-if="isBlocked" class="w-full text-center py-4 text-red-600 bg-red-50 rounded-lg">
                {{ t('You have been blocked. You cannot send messages.') }}
            </div>
            <template v-else>
            <input type="file" 
                   ref="imageInput" 
                   class="hidden" 
                   @change="handleImageUpload"
                   accept="image/*">
            <button type="button" 
                    @click="$refs.imageInput.click()"
                    class="p-2 bg-white/50 backdrop-blur-sm rounded-full shadow-md focus:outline-none border border-white/30 hover:bg-white/70 transition-all"
                    :title="t('Upload Image')">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </button>
            <input type="file" 
                   ref="videoInput" 
                   class="hidden" 
                   @change="handleVideoUpload"
                   accept="video/mp4,video/x-matroska">
            <button type="button" 
                    @click="$refs.videoInput.click()"
                    class="p-2 bg-white/50 backdrop-blur-sm rounded-full shadow-md focus:outline-none border border-white/30 hover:bg-white/70 transition-all"
                    :title="t('Upload Video')">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            </button>
            <input v-model="newMessage" 
                   type="text" 
                   :placeholder="t('Type your message...')" 
                   class="flex-1 px-3 py-2 rounded-full bg-white/50 backdrop-blur-sm border border-white/30 focus:outline-none focus:ring-2 focus:ring-cyan-400 text-slate-900 placeholder-slate-500 text-sm"
                   @keydown.enter.exact.prevent="sendGuestMessage">
            <button type="button" 
                    @click="sendGuestMessage"
                    class="p-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-full shadow-md focus:outline-none transition-all duration-200">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
            </button>
            </template>
        </div>
    </div>

    <!-- Media Modal -->
    <div v-if="showMediaModal" class="fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-50" @click="closeMediaModal">
        <div class="relative max-w-4xl max-h-4xl">
            <button @click="closeMediaModal" class="absolute -top-10 right-0 text-white text-2xl hover:text-gray-300">
                <i class="fas fa-times"></i>
            </button>
            <img v-if="modalMediaType === 'image'" 
                 :src="modalMediaSrc" 
                 alt="Full size image" 
                 class="max-w-full max-h-screen object-contain">
            <video v-if="modalMediaType === 'video'" 
                   :src="modalMediaSrc" 
                   controls 
                   autoplay 
                   class="max-w-full max-h-screen">
            </video>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    sessionId: String,
    guestInfo: Object
});

const emit = defineEmits(['close']);

const page = usePage();
const translations = computed(() => page.props.translations || {});
const t = (key) => translations.value[key] || key;

const messages = ref([]);
const newMessage = ref('');
const messagesContainer = ref(null);
const imageInput = ref(null);
const videoInput = ref(null);
const showMediaModal = ref(false);
const modalMediaSrc = ref('');
const modalMediaType = ref('');
const notificationSound = new Audio('/notification.mp3');
const isBlocked = ref(false);

let pollInterval = null;
let echoChannel = null;

const scrollToBottom = () => {
    setTimeout(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    }, 100);
};

const loadGuestMessages = async () => {
    if (!props.sessionId) return;
    
    try {
        const response = await axios.get(`/guest-chat/${props.sessionId}/messages`);
        const serverMessages = response.data.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        
        const tempMessages = messages.value.filter(m => m.isTemp);
        const currentIds = new Set(messages.value.filter(m => !m.isTemp).map(m => m.id));
        const newServerMessages = serverMessages.filter(m => !currentIds.has(m.id));
        
        if (newServerMessages.length > 0) {
            const hasAdminMessage = newServerMessages.some(msg => !msg.is_guest);
            const nonTempExisting = messages.value.filter(m => !m.isTemp);
            messages.value = [...nonTempExisting, ...newServerMessages, ...tempMessages]
                .sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
            
            if (hasAdminMessage) {
                notificationSound.play().catch(() => {});
            }
            scrollToBottom();
        }
    } catch (error) {
        if (error.response?.status === 404) {
            stopRealTimeChat();
            emit('close');
        }
    }
};

const sendGuestMessage = async () => {
    if (!newMessage.value.trim() || !props.sessionId || isBlocked.value) return;
    
    const messageText = newMessage.value;
    newMessage.value = '';
    
    try {
        const response = await axios.post(`/guest-chat/${props.sessionId}/broadcast`, {
            message: messageText,
            guest_name: props.guestInfo?.name,
            guest_mobile: props.guestInfo?.mobile_number
        });
        
        const messageData = {
            id: response.data.id,
            message: messageText,
            is_guest: true,
            created_at: response.data.created_at,
        };
        
        const exists = messages.value.some(m => m.id === messageData.id);
        if (!exists) {
            messages.value.push(messageData);
            messages.value.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
            scrollToBottom();
        }
        
        isBlocked.value = false;
    } catch (error) {
        newMessage.value = messageText;
        if (error.response?.status === 403) {
            isBlocked.value = true;
        }
    }
};

const handleImageUpload = async (event) => {
    try {
        const file = event.target.files[0];
        if (!file || isBlocked.value) return;

        const imageUrl = URL.createObjectURL(file);
        const tempMessage = {
            id: 'temp_' + Date.now(),
            message: '',
            is_guest: true,
            image_path: imageUrl,
            video_path: null,
            created_at: new Date().toISOString(),
            isTemp: true
        };
        messages.value.push(tempMessage);
        messages.value.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        scrollToBottom();

        const formData = new FormData();
        formData.append('image', file);
        const response = await axios.post(`/guest-chat/${props.sessionId}/send`, formData);
        
        // Remove temp message
        messages.value = messages.value.filter(m => !m.isTemp);
        
        // Add real message from response (don't wait for Echo to avoid duplicate)
        const realMessage = {
            id: response.data.id,
            message: response.data.message || '',
            is_guest: true,
            image_path: response.data.image_path,
            video_path: null,
            created_at: response.data.created_at
        };
        
        const exists = messages.value.some(m => m.id === realMessage.id);
        if (!exists) {
            messages.value.push(realMessage);
            messages.value.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
            scrollToBottom();
        }
        
        event.target.value = '';
    } catch (error) {
        console.error('Error uploading image:', error);
        if (error.response?.status === 403) {
            isBlocked.value = true;
            alert(t('You have been blocked and cannot send messages.'));
        } else {
            alert(t('Error uploading image. Please try again.'));
        }
        const tempIndex = messages.value.findIndex(m => m.isTemp);
        if (tempIndex > -1) {
            messages.value.splice(tempIndex, 1);
        }
    }
};

const handleVideoUpload = async (event) => {
    try {
        const file = event.target.files[0];
        if (!file || isBlocked.value) return;

        if (file.size > 30 * 1024 * 1024) {
            alert(t('Video file size must not exceed 30MB.'));
            event.target.value = '';
            return;
        }

        const videoUrl = URL.createObjectURL(file);
        const tempMessage = {
            id: 'temp_' + Date.now(),
            message: '',
            is_guest: true,
            image_path: null,
            video_path: videoUrl,
            created_at: new Date().toISOString(),
            isTemp: true
        };
        messages.value.push(tempMessage);
        messages.value.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        scrollToBottom();

        const formData = new FormData();
        formData.append('video', file);
        const response = await axios.post(`/guest-chat/${props.sessionId}/send`, formData);
        
        // Remove temp message
        messages.value = messages.value.filter(m => !m.isTemp);
        
        // Add real message from response (don't wait for Echo to avoid duplicate)
        const realMessage = {
            id: response.data.id,
            message: response.data.message || '',
            is_guest: true,
            image_path: null,
            video_path: response.data.video_path,
            created_at: response.data.created_at
        };
        
        const exists = messages.value.some(m => m.id === realMessage.id);
        if (!exists) {
            messages.value.push(realMessage);
            messages.value.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
            scrollToBottom();
        }
        
        event.target.value = '';
    } catch (error) {
        console.error('Error uploading video:', error);
        if (error.response?.status === 403) {
            isBlocked.value = true;
            alert(t('You have been blocked and cannot send messages.'));
        } else {
            alert(t('Error uploading video. Please try again.'));
        }
        const tempIndex = messages.value.findIndex(m => m.isTemp);
        if (tempIndex > -1) {
            messages.value.splice(tempIndex, 1);
        }
    }
};

const startRealTimeChat = () => {
    if (!props.sessionId) return;
    
    if (window.Echo) {
        try {
            echoChannel = window.Echo.channel(`guest-chat.${props.sessionId}`);
            echoChannel.listen('NewGuestChatMessage', (e) => {
                console.log('📨 Guest received NewGuestChatMessage:', e);
                
                // Skip messages sent by this guest (to avoid duplicates)
                if (e.message && e.message.is_guest && e.message.sender_id === props.sessionId) {
                    console.log('⏭️ Skipping own message to avoid duplicate');
                    // Remove temp message and replace with real one
                    messages.value = messages.value.filter(m => !m.isTemp);
                    
                    const newMessage = {
                        id: e.message.id,
                        message: e.message.message,
                        is_guest: true,
                        created_at: e.message.created_at,
                        image_path: e.message.image_path || null,
                        video_path: e.message.video_path || null
                    };
                    
                    const exists = messages.value.some(m => m.id === newMessage.id);
                    if (!exists) {
                        messages.value.push(newMessage);
                        messages.value.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                        scrollToBottom();
                    }
                    return;
                }
                
                // Handle messages from admin
                if (e.message && !e.message.is_guest) {
                    const newMessage = {
                        id: e.message.id,
                        message: e.message.message,
                        is_guest: false,
                        created_at: e.message.created_at,
                        image_path: e.message.image_path || null,
                        video_path: e.message.video_path || null
                    };
                    
                    messages.value = messages.value.filter(m => !m.isTemp);
                    const exists = messages.value.some(m => m.id === newMessage.id);
                    if (!exists) {
                        messages.value.push(newMessage);
                        messages.value.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                        notificationSound.play().catch(() => {});
                        scrollToBottom();
                    }
                }
            });
            
            echoChannel.listen('GuestChatDeleted', (e) => {
                if (e.session_id === props.sessionId) {
                    alert(t('Your chat session has been ended by the administrator.'));
                    emit('close');
                }
            });
        } catch (error) {
            console.log('Echo setup failed, using polling only');
        }
    }
    
    pollInterval = setInterval(() => {
        if (props.sessionId) {
            loadGuestMessages();
            checkBlockStatus();
        } else {
            clearInterval(pollInterval);
        }
    }, 2000);
};

const stopRealTimeChat = () => {
    if (echoChannel) {
        echoChannel.stopListening('NewGuestChatMessage');
        echoChannel.stopListening('GuestChatDeleted');
        if (window.Echo) {
            window.Echo.leaveChannel(`guest-chat.${props.sessionId}`);
        }
        echoChannel = null;
    }
    
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
};

const checkBlockStatus = async () => {
    if (!props.sessionId) return;
    
    try {
        const response = await axios.get(`/guest-chat/${props.sessionId}/block-status`);
        isBlocked.value = response.data.is_blocked;
    } catch (error) {
        console.error('Error checking block status:', error);
    }
};

const openMediaModal = (src, type) => {
    modalMediaSrc.value = src;
    modalMediaType.value = type;
    showMediaModal.value = true;
};

const closeMediaModal = () => {
    showMediaModal.value = false;
    modalMediaSrc.value = '';
    modalMediaType.value = '';
};

onMounted(() => {
    loadGuestMessages();
    startRealTimeChat();
});

onUnmounted(() => {
    stopRealTimeChat();
});
</script>