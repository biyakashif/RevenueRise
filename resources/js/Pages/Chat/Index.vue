<template>
    <AuthenticatedLayout :hideBottomNav="true">
        <div class="absolute inset-0 flex flex-col">
            <!-- Chat Header -->
            <div class="flex items-center justify-between px-4 py-3 bg-gradient-to-br from-cyan-400/30 via-blue-500/25 to-indigo-600/30 backdrop-blur-xl text-white shadow-md border-b border-cyan-300/30 rounded-t-2xl sm:rounded-t-3xl">
                <button @click="goBack" class="text-blue-500 focus:outline-none mr-3">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="text-lg text-blue-500 font-semibold">{{ t('Support Chat') }}</div>
                <div class="w-6"></div>
            </div>

            <!-- Chat Area -->
            <div class="flex-1 overflow-y-auto p-4 bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl">
                <div v-if="isBlocked" class="flex items-center justify-center h-full">
                    <div class="text-center py-8 px-4 bg-red-50 rounded-lg border border-red-200">
                        <i class="fas fa-ban text-red-500 text-4xl mb-4"></i>
                        <h3 class="text-lg font-semibold text-red-800 mb-2">{{ t('Account Blocked') }}</h3>
                        <p class="text-red-600">{{ t('You have been blocked by the administrator. You cannot send or receive messages.') }}</p>
                    </div>
                </div>
                <div v-else-if="messages.length === 0" class="flex items-center justify-center h-full text-slate-600">
                    {{ t('No messages yet') }}
                </div>
                <div v-else v-for="message in messages" :key="message.id" 
                     class="mb-4 flex"
                     :class="{'justify-end': message.sender_id === page.props.auth.user.id, 'justify-start': message.sender_id !== page.props.auth.user.id}">
                    <div class="flex items-start space-x-2 max-w-[85%]"
                         :class="message.sender_id === page.props.auth.user.id ? 'flex-row-reverse space-x-reverse' : ''">
                        <div v-if="(message.sender_id === page.props.auth.user.id && page.props.auth.user.avatar_url) || (message.sender_id !== page.props.auth.user.id && page.props.adminAvatar)" 
                             class="w-8 h-8 rounded-full overflow-hidden border flex-shrink-0">
                            <img :src="message.sender_id === page.props.auth.user.id 
                                ? (page.props.auth.user.avatar_url.startsWith('/storage') || page.props.auth.user.avatar_url.startsWith('/assets') ? page.props.auth.user.avatar_url : `/storage/${page.props.auth.user.avatar_url}`)
                                : (page.props.adminAvatar.startsWith('/storage') || page.props.adminAvatar.startsWith('/assets') ? page.props.adminAvatar : `/storage/${page.props.adminAvatar}`)" 
                                 :alt="message.sender_id === page.props.auth.user.id ? 'You' : 'Support'" 
                                 class="w-full h-full object-cover"
                                 @error="handleAvatarError">
                        </div>
                        <div v-else class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-user text-gray-400 text-xs"></i>
                        </div>
                        <div class="rounded-lg p-3 text-sm"
                             :class="message.sender_id === page.props.auth.user.id ? 'bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl border border-cyan-300/30 self-end' : 'bg-white/90 backdrop-blur-xl border border-white/50 self-start'">
                            <div v-if="message.image_path" class="mb-2">
                                <img :src="message.image_path.startsWith('/storage/') ? message.image_path : `/storage/${message.image_path}`" 
                                     alt="chat image" 
                                     class="w-32 h-32 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity"
                                     @click="openMediaModal(message.image_path.startsWith('/storage/') ? message.image_path : `/storage/${message.image_path}`, 'image')">
                            </div>
                            <div v-if="message.video_path" class="mb-2 relative">
                                <video class="w-32 h-32 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity"
                                       @click="openMediaModal(message.video_path.startsWith('/storage/') ? message.video_path : `/storage/${message.video_path}`, 'video')">
                                    <source :src="message.video_path.startsWith('/storage/') ? message.video_path : `/storage/${message.video_path}`" type="video/mp4">
                                </video>
                                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                    <i class="fas fa-play-circle text-white text-2xl opacity-80"></i>
                                </div>
                            </div>
                            <div v-if="message.message" class="text-sm text-slate-800 whitespace-pre-wrap">{{ message.message }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message Input -->
            <div class="border-t border-cyan-300/30 p-2 sm:p-3 bg-gradient-to-br from-cyan-400/30 via-blue-500/25 to-indigo-600/30 backdrop-blur-xl rounded-b-2xl sm:rounded-b-3xl">
                <div v-if="isBlocked" class="w-full text-center py-4 text-red-600 bg-red-50 rounded-lg">
                    {{ t('You are blocked and cannot send messages.') }}
                </div>
                <template v-else>
                <div class="flex items-center space-x-1 sm:space-x-2">
                    <input type="file" 
                           ref="imageInput" 
                           class="hidden" 
                           @change="handleImageUpload"
                           accept="image/*">
                    <button type="button" 
                            @click="$refs.imageInput.click()"
                            class="p-1.5 sm:p-2 bg-white/50 backdrop-blur-sm rounded-full shadow-md focus:outline-none border border-white/30 hover:bg-white/70 transition-all flex-shrink-0"
                            :title="t('Upload Image')">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            class="p-1.5 sm:p-2 bg-white/50 backdrop-blur-sm rounded-full shadow-md focus:outline-none border border-white/30 hover:bg-white/70 transition-all flex-shrink-0"
                            :title="t('Upload Video')">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
                    <input v-model="newMessage" 
                           type="text" 
                           :placeholder="t('Type your message...')" 
                           class="flex-1 min-w-0 px-2 sm:px-3 py-1.5 sm:py-2 rounded-full bg-white/50 backdrop-blur-sm border border-white/30 focus:outline-none focus:ring-2 focus:ring-cyan-400 text-slate-900 placeholder-slate-500 text-xs sm:text-sm"
                           @keydown.enter.exact.prevent="sendMessage">
                    <button type="button" 
                            @click="sendMessage"
                            class="p-1.5 sm:p-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-full shadow-md focus:outline-none transition-all duration-200 flex-shrink-0">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </div>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>

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
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const messages = ref([]);
const t = (key) => page.props.translations[key] || key;
const newMessage = ref('');
const imageInput = ref(null);
const videoInput = ref(null);
const showMediaModal = ref(false);
const modalMediaSrc = ref('');
const modalMediaType = ref('');
const notificationSound = new Audio('/notification.mp3');
const videoError = ref('');
const isBlocked = ref(false);
let blockStatusInterval = null;

// Configure axios with CSRF token and credentials
axios.defaults.headers.common['X-CSRF-TOKEN'] = page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
axios.defaults.withCredentials = true;

const loadMessages = async () => {
    try {
        const response = await axios.get('/chat/messages', {
            headers: {
                'X-CSRF-TOKEN': page.props.csrf_token
            }
        });
        // Sort messages by created_at in ascending order
        messages.value = response.data.sort((a, b) => 
            new Date(a.created_at) - new Date(b.created_at)
        );
        
        // Scroll to bottom after messages are loaded
        setTimeout(() => {
            const chatArea = document.querySelector('.overflow-y-auto');
            if (chatArea) {
                chatArea.scrollTop = chatArea.scrollHeight;
            }
        }, 100);
        // Notify layout that messages have been read
        window.dispatchEvent(new CustomEvent('chat:read'));
    } catch (error) {
        console.error('Error loading messages:', error);
    }
};

const sendMessage = async () => {
    try {
        if (isBlocked.value) return;
        
        // Prevent sending empty messages
        if (!newMessage.value.trim()) {
            newMessage.value = '';
            return;
        }

        const messageText = newMessage.value;
        newMessage.value = ''; // Clear immediately

        const response = await axios.post('/chat/broadcast', { 
            message: messageText,
            _token: page.props.csrf_token
        });
        const data = response.data;
        
        // Add message immediately to UI (Echo will handle real-time for admin)
        if (data && data.id) {
            const userMessage = {
                id: data.id,
                message: messageText,
                image_path: null,
                video_path: null,
                created_at: data.created_at,
                sender_id: page.props.auth.user.id,
                recipient_id: null
            };
            
            // Check if message doesn't already exist
            const exists = messages.value.some(m => m.id === userMessage.id);
            if (!exists) {
                messages.value.push(userMessage);
                messages.value.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                
                // Scroll to bottom
                setTimeout(() => {
                    const chatArea = document.querySelector('.overflow-y-auto');
                    if (chatArea) {
                        chatArea.scrollTop = chatArea.scrollHeight;
                    }
                }, 50);
            }
        }
        
        if (imageInput.value) {
            imageInput.value.value = '';
        }
        if (videoInput.value) {
            videoInput.value.value = '';
        }
    } catch (error) {
        newMessage.value = messageText;
        console.error('Error sending message:', error);
        alert(error.response?.data?.error || t('Error sending message. Please try again.'));
    }
};

const handleImageUpload = async (event) => {
    const file = event.target.files[0];
    if (!file || isBlocked.value) return;

    if (file.size > 5 * 1024 * 1024) {
        alert(t('Image file size must not exceed 5MB.'));
        event.target.value = '';
        return;
    }

    const formData = new FormData();
    formData.append('image', file);
    formData.append('_token', page.props.csrf_token);
    await axios.post('/chat/send', formData);
    event.target.value = '';
};

const handleVideoUpload = async (event) => {
    const file = event.target.files[0];
    if (!file || isBlocked.value) return;
    if (file.size > 30 * 1024 * 1024) {
        videoError.value = t('The video file size must not exceed 30MB.');
        event.target.value = '';
        return;
    }
    videoError.value = '';

    const formData = new FormData();
    formData.append('video', file);
    formData.append('_token', page.props.csrf_token);
    await axios.post('/chat/send', formData);
    event.target.value = '';
};

const handleAvatarError = (e) => {
    if (e && e.target) {
        // Hide the image and show user icon instead
        e.target.style.display = 'none';
        const parent = e.target.parentElement;
        if (parent) {
            parent.innerHTML = '<i class="fas fa-user text-gray-400"></i>';
            parent.classList.add('flex', 'items-center', 'justify-center');
        }
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

const goBack = () => {
    window.history.back();
};

onMounted(() => {
    loadMessages();
    checkBlockStatus();



    // Real-time with Echo
    if (window.Echo) {
        window.Echo.private(`chat.${page.props.auth.user.id}`)
            .listen('NewChatMessage', (e) => {
                // Add the message if not already in messages
                const exists = messages.value.some(m => m.id === e.chat.id);
                if (!exists) {
                    messages.value.push({
                        id: e.chat.id,
                        message: e.chat.message,
                        image_path: e.chat.image_path,
                        video_path: e.chat.video_path,
                        created_at: e.chat.created_at,
                        sender_id: e.chat.sender_id,
                        recipient_id: e.chat.recipient_id,
                    });
                    // Sort messages
                    messages.value.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                    // Scroll to bottom
                    setTimeout(() => {
                        const chatArea = document.querySelector('.overflow-y-auto');
                        if (chatArea) {
                            chatArea.scrollTop = chatArea.scrollHeight;
                        }
                    }, 50);
                    // Play sound if from support
                    if (e.chat.sender_id !== page.props.auth.user.id) {
                        notificationSound.play().catch(() => {});
                    }
                    // Clear unread badge
                    window.dispatchEvent(new CustomEvent('chat:read'));
                }
            });
    }

    // Prime audio on first user interaction
    const primeAudio = () => {
        notificationSound.play().then(() => {
            notificationSound.pause();
            notificationSound.currentTime = 0;
        }).catch(() => {/* ignore */});
        window.removeEventListener('click', primeAudio);
        window.removeEventListener('keydown', primeAudio);
        window.removeEventListener('touchstart', primeAudio);
    };
    window.addEventListener('click', primeAudio);
    window.addEventListener('keydown', primeAudio);
    window.addEventListener('touchstart', primeAudio);
    
    // Check block status periodically
    blockStatusInterval = setInterval(checkBlockStatus, 5000);
});

onBeforeUnmount(() => {
    if (blockStatusInterval) {
        clearInterval(blockStatusInterval);
        blockStatusInterval = null;
    }
});

const checkBlockStatus = async () => {
    // Don't check if user is not authenticated
    if (!page.props.auth?.user) {
        if (blockStatusInterval) {
            clearInterval(blockStatusInterval);
            blockStatusInterval = null;
        }
        return;
    }
    
    try {
        const response = await axios.get('/chat/block-status', {
            headers: {
                'X-CSRF-TOKEN': page.props.csrf_token
            }
        });
        isBlocked.value = response.data.is_blocked;
    } catch (error) {
        // Stop checking if user is logged out (401 error)
        if (error.response?.status === 401) {
            if (blockStatusInterval) {
                clearInterval(blockStatusInterval);
                blockStatusInterval = null;
            }
            return;
        }
    }
};
</script>
