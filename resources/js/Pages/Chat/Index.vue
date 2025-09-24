<template>
    <AuthenticatedLayout :hideBottomNav="true">
        <div class="absolute inset-0 flex flex-col">
            <!-- Chat Header -->
            <div class="flex items-center justify-between px-4 py-3 bg-gradient-to-br from-cyan-400/30 via-blue-500/25 to-indigo-600/30 backdrop-blur-xl text-white shadow-md border-b border-cyan-300/30 rounded-t-2xl sm:rounded-t-3xl">
                <button @click="goBack" class="text-blue-500 focus:outline-none mr-3">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="text-lg text-blue-500 font-semibold">{{ t('Support Chat') }}</div>
                <button class="text-white focus:outline-none">
                </button>
            </div>

            <!-- Chat Area -->
            <div class="flex-1 overflow-y-auto p-4 bg-gradient-to-br from-white/95 via-blue-50/90 to-indigo-50/95 backdrop-blur-xl">
                <div v-if="messages.length === 0" class="flex items-center justify-center h-full text-slate-600">
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
                             :class="message.sender_id === page.props.auth.user.id ? 'bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl border border-cyan-300/30 self-end' : 'bg-white/50 backdrop-blur-sm border border-white/30 self-start'">
                            <div v-if="message.image_path" class="mb-2">
                                <img :src="message.image_path" 
                                     alt="chat image" 
                                     class="max-h-48 w-auto rounded cursor-pointer"
                                     @click="openImage(message.image_path)">
                            </div>
                            <div v-if="message.video_path" class="mb-2">
                                <video controls class="max-w-full rounded">
                                    <source :src="message.video_path" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                            <p class="text-sm text-slate-800">{{ message.message }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message Input -->
            <div class="border-t border-cyan-300/30 p-3 bg-gradient-to-br from-cyan-400/30 via-blue-500/25 to-indigo-600/30 backdrop-blur-xl flex items-center space-x-2 rounded-b-2xl sm:rounded-b-3xl">
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
                       class="flex-1 px-3 py-2 rounded-full bg-white/50 backdrop-blur-sm border border-white/30 focus:outline-none focus:ring-2 focus:ring-cyan-400 text-slate-900 placeholder-slate-500 text-sm">
                <button type="button" 
                        @click="sendMessage"
                        class="p-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-full shadow-md focus:outline-none transition-all duration-200">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </div>
        </div>
    </AuthenticatedLayout>

    <!-- Image Viewer Modal -->
    <div v-if="showImageViewer" 
         class="fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center"
         @click="closeImageViewer">
        <div class="relative w-full h-full flex items-center justify-center p-4">
            <button @click="closeImageViewer" 
                    class="absolute top-4 right-4 text-white text-xl p-2">
                <i class="fas fa-times"></i>
            </button>
            <img :src="selectedImage" 
                 alt="Full size image" 
                 class="max-w-full max-h-full object-contain">
        </div>
    </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const messages = ref([]);
const t = (key) => page.props.translations[key] || key;
const newMessage = ref('');
const imageInput = ref(null);
const videoInput = ref(null);
const showImageViewer = ref(false);
const selectedImage = ref('');
const notificationSound = new Audio('/notification.mp3');
const videoError = ref('');

// Configure axios with CSRF token and credentials
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
axios.defaults.withCredentials = true;

const loadMessages = async () => {
    const response = await axios.get('/chat/messages');
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
};

const sendMessage = async () => {
    try {
        if (!newMessage.value.trim() && !imageInput.value?.files[0] && !videoInput.value?.files[0]) return;

        const formData = new FormData();
        if (newMessage.value.trim()) {
            formData.append('message', newMessage.value);
        }
        
        if (imageInput.value?.files[0]) {
            formData.append('image', imageInput.value.files[0]);
        }

        if (videoInput.value?.files[0]) {
            formData.append('video', videoInput.value.files[0]);
        }

        await axios.post('/chat/send', formData);
        newMessage.value = '';
        if (imageInput.value) {
            imageInput.value.value = '';
        }
        if (videoInput.value) {
            videoInput.value.value = '';
        }
        
        // Reload messages to show the sent message immediately
        await loadMessages();
    } catch (error) {
        console.error('Error sending message:', error);
        alert(error.response?.data?.error || t('Error sending message. Please try again.'));
    }
};

const handleImageUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('image', file);
    await axios.post('/chat/send', formData);
    event.target.value = '';
    
    // Reload messages to show the sent image immediately
    await loadMessages();
};

const handleVideoUpload = async (event) => {
    const file = event.target.files[0];
    if (file && file.size > 30 * 1024 * 1024) { // 30MB limit
        videoError.value = t('The video file size must not exceed 30MB.');
        event.target.value = '';
        return;
    }
    videoError.value = '';

    const formData = new FormData();
    formData.append('video', file);
    await axios.post('/chat/send', formData);
    event.target.value = '';
    
    // Reload messages to show the sent video immediately
    await loadMessages();
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

const openImage = (imagePath) => {
    selectedImage.value = imagePath;
    showImageViewer.value = true;
};

const closeImageViewer = () => {
    showImageViewer.value = false;
    selectedImage.value = '';
};

const goBack = () => {
    window.history.back();
};

onMounted(() => {
    loadMessages();

    // Listen for new messages only if Echo is available
    const userId = page.props.auth.user.id;
    if (!userId) {
        console.error('User id not found in auth props:', page.props.auth);
        return;
    }

    if (window.Echo) {
        console.log('Subscribing to private channel:', `chat.${userId}`);
        const channel = window.Echo.private(`chat.${userId}`);

        // Log successful subscription
        channel.subscribed(() => {
            console.log('Successfully subscribed to channel:', `chat.${userId}`);
        });

        // Log connection errors
        channel.error((error) => {
            console.error('Echo connection error:', error);
        });

        // Listen for new messages
        channel.listen('NewChatMessage', (e) => {
            console.log('Received message:', e);
            if (e.chat) {
                // Play notification if message is from support (not this user)
                if (e.chat.sender_id !== userId) {
                    notificationSound.play().catch(() => {/* autoplay blocked until user gesture */});
                }
                // Add the new message to the list
                messages.value.push({
                    id: e.chat.id,
                    message: e.chat.message,
                    image_path: e.chat.image_path,
                    video_path: e.chat.video_path,
                    created_at: e.chat.created_at,
                    sender_id: e.chat.sender_id,
                    recipient_id: e.chat.recipient_id
                });
                // Sort messages by date
                messages.value.sort((a, b) => 
                    new Date(a.created_at) - new Date(b.created_at)
                );
                
                // Scroll to bottom when new message arrives
                setTimeout(() => {
                    const chatArea = document.querySelector('.overflow-y-auto');
                    if (chatArea) {
                        chatArea.scrollTop = chatArea.scrollHeight;
                    }
                }, 100);
                // Reading in the chat view should clear unread badge
                window.dispatchEvent(new CustomEvent('chat:read'));
            }
        });

        // Listen for chat history deletion
        channel.listen('ChatHistoryDeleted', (e) => {
            console.log('ChatHistoryDeleted event received:', e);
            console.log('Current user id:', page.props.auth.user.id);
            if (e.userId === page.props.auth.user.id) {
                console.log('Clearing messages for user:', e.userId);
                messages.value = []; // Clear messages
            } else {
                console.log('Event not relevant for this user.');
            }
        });
    }

    // Prime audio on first user interaction to satisfy autoplay policies
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
});
</script>
