<template>
    <AuthenticatedLayout :hideBottomNav="true">
        <div class="flex flex-col h-screen bg-white">
            <!-- Chat Header -->
            <div class="flex items-center justify-between px-4 py-3 bg-purple-600 text-white shadow-md">
                <button @click="goBack" class="text-white focus:outline-none mr-3">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="text-lg font-semibold">{{ t('Support Chat') }}</div>
                <button class="text-white focus:outline-none">
                </button>
            </div>

            <!-- Chat Area -->
            <div class="flex-1 overflow-y-auto p-4">
                <div v-if="messages.length === 0" class="flex items-center justify-center h-full text-gray-500">
                    {{ t('No messages yet') }}
                </div>
                <div v-else v-for="message in messages" :key="message.id" 
                     class="mb-4 flex"
                     :class="{'justify-end': message.sender_id === page.props.auth.user.mobile_number, 'justify-start': message.sender_id !== page.props.auth.user.mobile_number}">
                    <div class="flex items-start space-x-2 max-w-[80%]"
                         :class="message.sender_id === page.props.auth.user.mobile_number ? 'flex-row-reverse space-x-reverse' : ''">
                        <div v-if="(message.sender_id === page.props.auth.user.mobile_number && page.props.auth.user.avatar_url) || (message.sender_id !== page.props.auth.user.mobile_number && page.props.adminAvatar)" 
                             class="w-8 h-8 rounded-full overflow-hidden border flex-shrink-0">
                            <img :src="message.sender_id === page.props.auth.user.mobile_number 
                                ? (page.props.auth.user.avatar_url.startsWith('/storage') || page.props.auth.user.avatar_url.startsWith('/assets') ? page.props.auth.user.avatar_url : `/storage/${page.props.auth.user.avatar_url}`)
                                : (page.props.adminAvatar.startsWith('/storage') || page.props.adminAvatar.startsWith('/assets') ? page.props.adminAvatar : `/storage/${page.props.adminAvatar}`)" 
                                 :alt="message.sender_id === page.props.auth.user.mobile_number ? 'You' : 'Support'" 
                                 class="w-full h-full object-cover"
                                 @error="handleAvatarError">
                        </div>
                        <div v-else class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-user text-gray-400 text-xs"></i>
                        </div>
                        <div class="rounded-lg p-3 text-sm"
                             :class="message.sender_id === page.props.auth.user.mobile_number ? 'bg-purple-100 self-end' : 'bg-gray-100 self-start'">
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
                            <p class="text-sm text-gray-800">{{ message.message }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message Input -->
            <div class="border-t p-3 bg-gray-100 flex items-center space-x-2">
                <input type="file" 
                       ref="imageInput" 
                       class="hidden" 
                       @change="handleImageUpload"
                       accept="image/*">
                <button type="button" 
                        @click="$refs.imageInput.click()"
                        class="p-2 bg-white rounded-full shadow-md focus:outline-none"
                        :title="t('Upload Image')">
                    <i class="fas fa-image text-purple-600"></i>
                </button>
                <input type="file" 
                       ref="videoInput" 
                       class="hidden" 
                       @change="handleVideoUpload"
                       accept="video/mp4,video/x-matroska">
                <button type="button" 
                        @click="$refs.videoInput.click()"
                        class="p-2 bg-white rounded-full shadow-md focus:outline-none"
                        :title="t('Upload Video')">
                    <i class="fas fa-video text-purple-600"></i>
                </button>
                <input v-model="newMessage" 
                       type="text" 
                       :placeholder="t('Type your message...')" 
                       class="flex-1 px-4 py-2 rounded-full border focus:outline-none focus:ring-2 focus:ring-purple-300">
                <button type="button" 
                        @click="sendMessage"
                        class="p-2 bg-purple-600 text-white rounded-full shadow-md focus:outline-none">
                    <i class="fas fa-paper-plane"></i>
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
    const mobileNumber = page.props.auth.user.mobile_number;
    if (!mobileNumber) {
        console.error('User mobile number not found in auth props:', page.props.auth);
        return;
    }

    if (window.Echo) {
        console.log('Subscribing to private channel:', `chat.${mobileNumber}`);
        const channel = window.Echo.private(`chat.${mobileNumber}`);

        // Log successful subscription
        channel.subscribed(() => {
            console.log('Successfully subscribed to channel:', `chat.${mobileNumber}`);
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
                if (e.chat.sender_id !== mobileNumber) {
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
            console.log('Current user mobile number:', page.props.auth.user.mobile_number);
            if (e.userMobile === page.props.auth.user.mobile_number) {
                console.log('Clearing messages for user:', e.userMobile);
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
