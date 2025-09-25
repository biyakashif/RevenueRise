<template>
    <AdminLayout>

        <!-- Sound permission banner -->
        <div v-if="showSoundPrompt" class="bg-yellow-50 border-b border-yellow-200">
            <div class="max-w-7xl mx-auto px-4 py-2 flex items-center justify-between text-sm">
                <div class="text-yellow-800 flex items-center space-x-2">
                    <i class="fas fa-bell mr-1"></i>
                    <span>Click enable to allow sound notifications.</span>
                </div>
                <button @click="enableSound" class="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700">Enable sound</button>
            </div>
        </div>

        <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 rounded-2xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto">
            <h1 class="text-lg font-bold text-slate-800 drop-shadow-sm mb-4">Support Chat</h1>
            <div class="flex h-[calc(100vh-8rem)]">
                            <!-- Users List with Chat Status -->
                            <div class="w-1/3 border-r border-white/20 overflow-y-auto">
                                <div class="p-2">
                                    <input v-model="searchQuery" 
                                           placeholder="Search by name or mobile number" 
                                           class="w-full h-12 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 placeholder-slate-400 backdrop-blur-sm shadow-lg" />
                                </div>
                                

                                
                                <div v-for="user in filteredUsers" :key="`${user.is_guest ? 'guest' : 'user'}-${user.id}`" 
                                     @click="selectUser(user)"
                                     class="relative p-4 hover:bg-white/10 cursor-pointer transition-all duration-200 rounded-xl mx-2 mb-2"
                                     :class="{
                                         'bg-white/20': selectedUser?.id === user.id,
                                         'opacity-60': user.is_blocked,
                                         'border-l-4 border-red-500': user.is_blocked
                                     }">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div v-if="user.avatar_url" class="w-10 h-10 rounded-full overflow-hidden border flex-shrink-0">
                                                <img :src="user.avatar_url.startsWith('/storage') || user.avatar_url.startsWith('/assets') ? user.avatar_url : `/storage/${user.avatar_url}`" 
                                                     :alt="user.name" 
                                                     class="w-full h-full object-cover"
                                                     @error="handleAvatarError">
                                            </div>
                                            <div v-else class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-user text-gray-400"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium text-slate-800">
                                                    {{ user.name }}
                                                    <span v-if="user.is_guest" class="ml-2 px-2 py-1 bg-orange-100 text-orange-800 text-xs rounded-full">Guest</span>
                                                </div>
                                                <div class="text-xs text-slate-600">{{ user.mobile_number }}</div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <div v-if="user.unread_count" 
                                                 class="bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center animate-bounce">
                                                {{ user.unread_count }}
                                            </div>
                                            <div class="flex flex-col space-y-1">
                                                <button v-if="user.is_guest && !user.is_blocked" 
                                                        @click.stop="blockGuestUser(user)" 
                                                        class="text-orange-500 text-xs hover:underline">
                                                    Block
                                                </button>
                                                <button v-if="user.is_guest && user.is_blocked" 
                                                        @click.stop="unblockGuestUser(user)" 
                                                        class="text-green-500 text-xs hover:underline">
                                                    Unblock
                                                </button>
                                                <button @click.stop="openDeleteModal(user)" 
                                                        class="text-red-500 text-xs hover:underline">
                                                    Delete Chat
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Chat Area -->
                            <div class="w-2/3 flex flex-col">
                                <!-- Chat Header -->
                                <div v-if="selectedUser" class="border-b border-white/20 p-3 bg-white/10 backdrop-blur-sm rounded-t-xl">
                                    <div class="flex items-center space-x-3">
                                        <div v-if="selectedUser.avatar_url" class="w-10 h-10 rounded-full overflow-hidden border flex-shrink-0">
                                            <img :src="selectedUser.avatar_url.startsWith('/storage') || selectedUser.avatar_url.startsWith('/assets') ? selectedUser.avatar_url : `/storage/${selectedUser.avatar_url}`" 
                                                 :alt="selectedUser.name" 
                                                 class="w-full h-full object-cover border"
                                                 @error="handleAvatarError">
                                        </div>
                                        <div v-else class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-user text-gray-400"></i>
                                        </div>
                                        <div>
                                            <div class="font-medium text-slate-800">
                                                {{ selectedUser.name }}
                                                <span v-if="selectedUser.is_guest" class="ml-2 px-2 py-1 bg-orange-100 text-orange-800 text-xs rounded-full">Guest</span>
                                                <span v-if="selectedUser.is_blocked" class="ml-2 px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">Blocked</span>
                                            </div>
                                            <div class="text-xs text-slate-600">{{ selectedUser.mobile_number }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="selectedUser" ref="chatContainer" class="flex-1 overflow-y-auto p-3">

                                    
                                    <div v-for="message in messages" :key="message.id" 
                                         class="mb-3 flex"
                                         :class="{'justify-end': message.sender_id === page.props.auth.user.id, 'justify-start': message.sender_id !== page.props.auth.user.id}">
                                        <div class="flex items-start space-x-2 max-w-2xl"
                                             :class="message.sender_id === page.props.auth.user.id ? 'flex-row-reverse space-x-reverse' : ''">
                                            <div v-if="(message.sender_id === page.props.auth.user.id && page.props.auth.user.avatar_url) || (message.sender_id !== page.props.auth.user.id && selectedUser.avatar_url)" 
                                                 class="w-8 h-8 rounded-full overflow-hidden border flex-shrink-0">
                                                <img :src="message.sender_id === page.props.auth.user.id 
                                                    ? (page.props.auth.user.avatar_url.startsWith('/storage') || page.props.auth.user.avatar_url.startsWith('/assets') ? page.props.auth.user.avatar_url : `/storage/${page.props.auth.user.avatar_url}`)
                                                    : (selectedUser.avatar_url.startsWith('/storage') || selectedUser.avatar_url.startsWith('/assets') ? selectedUser.avatar_url : `/storage/${selectedUser.avatar_url}`)" 
                                                     :alt="message.sender_id === page.props.auth.user.id ? 'You' : selectedUser.name" 
                                                     class="w-full h-full object-cover"
                                                     @error="handleAvatarError">
                                            </div>
                                            <div v-else class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-user text-gray-400 text-xs"></i>
                                            </div>
                                            <div class="rounded-lg p-3"
                                                 :class="message.sender_id === page.props.auth.user.id ? 'bg-purple-100' : 'bg-gray-100'">
                                                <div v-if="message.image_path" class="mb-2">
                                                    <img :src="message.image_path" alt="chat image" class="max-w-sm rounded">
                                                </div>
                                                <div v-if="message.video_path" class="mb-2">
                                                    <video controls class="max-w-sm rounded">
                                                        <source :src="message.video_path" type="video/mp4">
                                                        Your browser does not support the video tag.
                                                    </video>
                                                </div>
                                                <p>{{ message.message }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Message Input -->
                                <div v-if="selectedUser" class="border-t border-white/20 p-3">
                                    <div v-if="selectedUser.is_blocked" class="text-center py-4 text-red-600">
                                        This user is blocked. Unblock to send messages.
                                    </div>
                                    <form v-else @submit.prevent="sendMessage" class="flex space-x-2">
                                        <input type="file" 
                                               ref="imageInput" 
                                               class="hidden" 
                                               @change="handleImageUpload"
                                               accept="image/*">
                                        <button type="button" 
                                                @click="$refs.imageInput.click()"
                                                class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-lg hover:bg-white/30 transition-all duration-200 text-slate-700">
                                            <i class="fas fa-image"></i>
                                        </button>
                                        <input type="file" 
                                               ref="videoInput" 
                                               class="hidden" 
                                               @change="handleVideoUpload"
                                               accept="video/mp4,video/x-matroska">
                                        <button type="button" 
                                                @click="$refs.videoInput.click()"
                                                class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-lg hover:bg-white/30 transition-all duration-200 text-slate-700">
                                            <i class="fas fa-video"></i>
                                        </button>
                                        <input v-model="newMessage" 
                                               type="text" 
                                               placeholder="Type your message..." 
                                               class="flex-1 h-12 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 placeholder-slate-400 backdrop-blur-sm shadow-lg"
                                               @keydown.enter.exact.prevent="sendMessage">
                                        <button type="submit" 
                                                class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg transition-all duration-300 transform hover:scale-[1.02] shadow-lg">
                                            Send
                                        </button>
                                    </form>
                                </div>
                                <div v-else class="flex-1 flex items-center justify-center text-slate-600">
                                    Select a user to start chatting
                                </div>
                            </div>
                        </div>
        </div>

        <!-- Modal for delete confirmation -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center">
            <div class="bg-white p-6 rounded shadow-lg">
                <h3 class="text-lg font-semibold mb-4">Confirm Deletion</h3>
                <p>Are you sure you want to delete all chat history with this user?</p>
                <div class="mt-4 flex justify-end space-x-2">
                    <button @click="cancelDelete" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button @click="confirmDelete" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, onMounted, nextTick, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const users = ref([]);
const selectedUser = ref(null);
const messages = ref([]);

const newMessage = ref('');
const imageInput = ref(null);
const videoInput = ref(null);
const chatContainer = ref(null);
const notificationSound = new Audio('/notification.mp3');

const showSoundPrompt = ref(false);
const soundEnabled = ref(false);
const searchQuery = ref('');
const lastUsersUpdate = ref(0);
const lastMessagesUpdate = ref(0);
const filteredUsers = computed(() => {
    const query = searchQuery.value.toLowerCase();
    return users.value
        .filter(user => {
            const name = user.name ? user.name.toLowerCase() : '';
            const mobile = user.mobile_number ? user.mobile_number.toString() : '';
            return name.includes(query) || mobile.includes(query);
        })
        .sort((a, b) => {
            // Sort by last message time descending (most recent first)
            const aTime = new Date(a.last_message_at || 0);
            const bTime = new Date(b.last_message_at || 0);
            return bTime - aTime;
        });
});

const playNotification = () => {
    if (!soundEnabled.value) {
        showSoundPrompt.value = true;
        return;
    }
    notificationSound.play().catch(error => {
        console.error('Error playing notification sound:', error);
        // If autoplay blocked, show the enable sound prompt
        showSoundPrompt.value = true;
    });
};

const loadUsers = async (force = false) => {
    const now = Date.now();
    if (!force && now - lastUsersUpdate.value < 1000) return;
    
    try {
        lastUsersUpdate.value = now;
        
        const [chatResponse, guestResponse] = await Promise.all([
            axios.get('/admin/chat/users'),
            axios.get('/admin/guest-chat/users')
        ]);
        
        const chatUsers = (chatResponse.data || []).map(u => ({
            ...u,
            mobile_number: u.mobile_number != null ? String(u.mobile_number) : u.mobile_number,
            unread_count: u.unread_count ?? 0,
            is_guest: false,
        }));
        
        const guestUsers = (guestResponse.data || []).map(u => ({
            ...u,
            mobile_number: u.mobile_number != null ? String(u.mobile_number) : u.mobile_number,
            unread_count: u.unread_count ?? 0,
            is_guest: true,
            last_message_at: u.last_message_at,
        }));
        
        const newUsers = [...chatUsers, ...guestUsers];
        
        // Check for new messages by comparing with previous state
        if (users.value.length > 0) {
            newUsers.forEach(newUser => {
                const oldUser = users.value.find(u => u.id === newUser.id && u.is_guest === newUser.is_guest);
                if (oldUser && (newUser.unread_count || 0) > (oldUser.unread_count || 0)) {
                    // New message detected
                    playNotification();
                    
                    if ('Notification' in window && Notification.permission === 'granted') {
                        new Notification('New Support Message', {
                            body: `New message from ${newUser.name || 'User'}`,
                            icon: '/favicon.ico'
                        });
                    }
                }
            });
        }
        
        users.value = newUsers;
    } catch (error) {
        console.error('Error loading users:', error);
    }
};

// Track received message IDs to prevent duplicates
const receivedMessageIds = new Set();

const scrollToBottom = () => {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
        }
    });
};

const selectUser = async (user) => {
    try {
        selectedUser.value = user;
        lastMessagesUpdate.value = Date.now();
        receivedMessageIds.clear(); // Clear to allow new messages
        
        // Clear unread badge immediately for instant UI feedback
        const idx = users.value.findIndex(u => u.id === user.id && u.is_guest === user.is_guest);
        if (idx !== -1) {
            const u = users.value[idx];
            u.unread_count = 0;
            users.value.splice(idx, 1, { ...u });
        }
        
        // Load messages in parallel without blocking UI
        const endpoint = user.is_guest 
            ? `/admin/guest-chat/${user.id}/messages`
            : `/admin/chat/${user.id}/messages`;
        
        // Don't await - load messages asynchronously
        axios.get(endpoint).then(response => {
            // Only update if this user is still selected
            if (selectedUser.value?.id === user.id && selectedUser.value?.is_guest === user.is_guest) {
                messages.value = response.data.sort((a, b) => 
                    new Date(a.created_at) - new Date(b.created_at)
                );
                
                // Immediate scroll to bottom
                nextTick(() => {
                    if (chatContainer.value) {
                        chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
                    }
                });
            }
        }).catch(error => {
            console.error('Error loading messages:', error);
            if (selectedUser.value?.id === user.id) {
                alert('Error loading messages. Please try again.');
            }
        });
        
    } catch (error) {
        console.error('Error selecting user:', error);
    }
};

const sendMessage = async () => {
    let tempMessage = null;
    const messageText = newMessage.value;
    
    try {
        if (!newMessage.value.trim() && !imageInput.value?.files[0] && !videoInput.value?.files[0]) return;
        if (selectedUser.value.is_blocked) return;

        newMessage.value = ''; // Clear immediately
        
        // Add optimistic message
        tempMessage = {
            id: Date.now(),
            message: messageText,
            image_path: null,
            video_path: null,
            created_at: new Date().toISOString(),
            sender_id: page.props.auth.user.id,
            recipient_id: selectedUser.value.id,
        };
        messages.value.push(tempMessage);
        scrollToBottom();

        const formData = new FormData();
        formData.append('message', messageText);
        
        if (imageInput.value?.files[0]) {
            formData.append('image', imageInput.value.files[0]);
        }

        if (videoInput.value?.files[0]) {
            formData.append('video', videoInput.value.files[0]);
        }

        const endpoint = selectedUser.value.is_guest 
            ? `/admin/guest-chat/${selectedUser.value.id}/send`
            : `/admin/chat/${selectedUser.value.id}/send`;
        
        const { data } = await axios.post(endpoint, formData);
        
        if (imageInput.value) {
            imageInput.value.value = '';
        }
        if (videoInput.value) {
            videoInput.value.value = '';
        }
        
        // Replace temp message with real one
        const tempIndex = messages.value.findIndex(m => m.id === tempMessage.id);
        if (tempIndex !== -1) {
            messages.value[tempIndex] = {
                id: data.id,
                message: data.message,
                image_path: data.image_path,
                video_path: data.video_path,
                created_at: data.created_at,
                sender_id: data.sender_id,
                recipient_id: data.recipient_id,
            };
        }
        
        if (data && data.id) {
            receivedMessageIds.add(String(data.id));
        }
    } catch (error) {
        // Remove temp message on error
        if (tempMessage) {
            messages.value = messages.value.filter(m => m.id !== tempMessage.id);
        }
        newMessage.value = messageText; // Restore message
        console.error('Error sending message:', error);
        alert('Error sending message. Please try again.');
    }
};

const handleImageUpload = async (event) => {
    try {
        const file = event.target.files[0];
        if (!file || selectedUser.value.is_blocked) return;

        const formData = new FormData();
        formData.append('image', file);

        const endpoint = selectedUser.value.is_guest 
            ? `/admin/guest-chat/${selectedUser.value.id}/send`
            : `/admin/chat/${selectedUser.value.id}/send`;
        
        const { data } = await axios.post(endpoint, formData);
        event.target.value = '';
        // Optimistically append sent image and mark as seen
        if (data && data.id) {
            receivedMessageIds.add(String(data.id));
        }
        messages.value.push({
            id: data.id,
            message: data.message,
            image_path: data.image_path,
            video_path: data.video_path,
            created_at: data.created_at,
            sender_id: data.sender_id,
            recipient_id: data.recipient_id,
        });
        scrollToBottom();
    } catch (error) {
        console.error('Error uploading image:', error);
        alert('Error uploading image. Please try again.');
    }
};

const videoError = ref('');

const handleVideoUpload = async (event) => {
    const file = event.target.files[0];
    if (file && file.size > 30 * 1024 * 1024) { // 30MB limit
        videoError.value = 'The video file size must not exceed 30MB.';
        event.target.value = '';
        return;
    }
    videoError.value = '';
    // Proceed with upload logic

    try {
        if (selectedUser.value.is_blocked) return;
        
        const formData = new FormData();
        formData.append('video', file);

        const endpoint = selectedUser.value.is_guest 
            ? `/admin/guest-chat/${selectedUser.value.id}/send`
            : `/admin/chat/${selectedUser.value.id}/send`;
        
        const { data } = await axios.post(endpoint, formData);
        event.target.value = '';
        // Optimistically append sent video and mark as seen
        if (data && data.id) {
            receivedMessageIds.add(String(data.id));
        }
        messages.value.push({
            id: data.id,
            message: data.message,
            image_path: data.image_path,
            video_path: data.video_path,
            created_at: data.created_at,
            sender_id: data.sender_id,
            recipient_id: data.recipient_id,
        });
        scrollToBottom();
    } catch (error) {
        console.error('Error uploading video:', error);
        alert('Error uploading video. Please try again.');
    }
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



onMounted(() => {
    notificationSound.src = '/notification.mp3';
    notificationSound.load();

    if (localStorage.getItem('soundEnabled') === '1') {
        soundEnabled.value = true;
    } else {
        showSoundPrompt.value = true;
    }

    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    // Initial load
    loadUsers(true);

    // Polling for users every 4 seconds
    const usersInterval = setInterval(() => {
        loadUsers();
    }, 4000);

    // Polling for messages in selected chat every 3 seconds
    const messagesInterval = setInterval(async () => {
        if (selectedUser.value) {
            try {
                const endpoint = selectedUser.value.is_guest 
                    ? `/admin/guest-chat/${selectedUser.value.id}/messages`
                    : `/admin/chat/${selectedUser.value.id}/messages`;
                
                const response = await axios.get(endpoint);
                const newMessages = response.data.sort((a, b) => 
                    new Date(a.created_at) - new Date(b.created_at)
                );
                
                // Only update if there are actually new messages
                if (newMessages.length !== messages.value.length) {
                    const hasNewMessages = newMessages.some(newMsg => 
                        !messages.value.some(existingMsg => existingMsg.id === newMsg.id)
                    );
                    
                    if (hasNewMessages) {
                        messages.value = newMessages;
                        scrollToBottom();
                    }
                }
            } catch (error) {
                // If chat was deleted (404), clear selection
                if (error.response?.status === 404) {
                    selectedUser.value = null;
                    messages.value = [];
                    loadUsers(true);
                }
            }
        }
    }, 3000);

    // Real-time with Echo for admin chat
    if (window.Echo) {
        console.log('🔄 Setting up Echo listeners for admin chat...');
        
        window.Echo.private(`chat.${page.props.auth.user.id}`)
            .listen('NewChatMessage', (e) => {
                console.log('📨 Received NewChatMessage event:', e);
                // Only handle messages not from admin (incoming from users)
                if (e.chat.sender_id !== page.props.auth.user.id) {
                    // Play notification sound
                    playNotification();
                    
                    // Show browser notification
                    if ('Notification' in window && Notification.permission === 'granted') {
                        new Notification('New Support Message', {
                            body: `New message from user`,
                            icon: '/favicon.ico'
                        });
                    }
                    
                    // Update users list
                    const userIndex = users.value.findIndex(u => u.id === e.chat.sender_id && !u.is_guest);
                    if (userIndex !== -1) {
                        const user = users.value[userIndex];
                        user.last_message_at = e.chat.created_at; // Update last message time
                        if (!selectedUser.value || selectedUser.value.id !== user.id) {
                            // Increment unread count
                            user.unread_count = (user.unread_count || 0) + 1;
                            users.value.splice(userIndex, 1, { ...user });
                        } else {
                            // If selected, add to messages and mark as read
                            if (!receivedMessageIds.has(String(e.chat.id))) {
                                messages.value.push({
                                    id: e.chat.id,
                                    message: e.chat.message,
                                    image_path: e.chat.image_path,
                                    video_path: e.chat.video_path,
                                    created_at: e.chat.created_at,
                                    sender_id: e.chat.sender_id,
                                    recipient_id: e.chat.recipient_id,
                                });
                                messages.value.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                                scrollToBottom();
                                receivedMessageIds.add(String(e.chat.id));
                            }
                            // Clear unread for this user
                            user.unread_count = 0;
                            users.value.splice(userIndex, 1, { ...user });
                        }
                    }
                }
            });

        // Listen for guest chat messages
        window.Echo.private('guest-chat')
            .listen('NewGuestChatMessage', (e) => {
                console.log('📨 Received NewGuestChatMessage event:', e);
                if (e.message.sender_id !== page.props.auth.user.id) {
                    // If this message is for the currently selected user, add it to messages
                    if (selectedUser.value && selectedUser.value.is_guest && selectedUser.value.id === e.message.sender_id) {
                        if (!receivedMessageIds.has(String(e.message.id))) {
                            messages.value.push({
                                id: e.message.id,
                                message: e.message.message,
                                image_path: e.message.image_path,
                                video_path: e.message.video_path,
                                created_at: e.message.created_at,
                                sender_id: e.message.sender_id,
                                recipient_id: e.message.recipient_id,
                            });
                            messages.value.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                            scrollToBottom();
                            receivedMessageIds.add(String(e.message.id));
                        }
                    }
                    
                    // Update user list
                    const userIndex = users.value.findIndex(u => u.id === e.message.sender_id && u.is_guest);
                    if (userIndex !== -1) {
                        const user = users.value[userIndex];
                        user.last_message_at = e.message.created_at;
                        if (!selectedUser.value || selectedUser.value.id !== user.id || !selectedUser.value.is_guest) {
                            user.unread_count = (user.unread_count || 0) + 1;
                        }
                        users.value.splice(userIndex, 1, { ...user });
                        
                        playNotification();
                        if ('Notification' in window && Notification.permission === 'granted') {
                            new Notification('New Support Message', {
                                body: `New message from ${user.name || 'Guest'}`,
                                icon: '/favicon.ico'
                            });
                        }
                    }
                }
            });
            
        console.log('✅ Echo listeners set up successfully');
    } else {
        console.log('❌ Echo not available, using polling fallback only');
    }

    const primeAudio = () => {
        notificationSound.play().then(() => {
            notificationSound.pause();
            notificationSound.currentTime = 0;
        }).catch(() => {});
        window.removeEventListener('click', primeAudio);
        window.removeEventListener('keydown', primeAudio);
        window.removeEventListener('touchstart', primeAudio);
    };
    window.addEventListener('click', primeAudio);
    window.addEventListener('keydown', primeAudio);
    window.addEventListener('touchstart', primeAudio);

    return () => {
        clearInterval(usersInterval);
        clearInterval(messagesInterval);
    };
});

// Explicit user gesture to enable/prime audio
const enableSound = () => {
    notificationSound.play().then(() => {
        notificationSound.pause();
        notificationSound.currentTime = 0;
        soundEnabled.value = true;
        showSoundPrompt.value = false;
        localStorage.setItem('soundEnabled', '1');
    }).catch(() => {
        // Keep showing the prompt until user interacts
        showSoundPrompt.value = true;
    });
};

const showDeleteModal = ref(false);
const userToDelete = ref(null);

const openDeleteModal = (user) => {
    userToDelete.value = user;
    showDeleteModal.value = true;
};

const cancelDelete = () => {
    userToDelete.value = null;
    showDeleteModal.value = false;
};

const confirmDelete = async () => {
    if (!userToDelete.value) return;

    try {
        const endpoint = userToDelete.value.is_guest 
            ? `/admin/guest-chat/${userToDelete.value.id}/delete-history`
            : `/admin/chat/${userToDelete.value.id}/delete-history`;
        
        await axios.delete(endpoint);
        
        // Remove user from list immediately
        users.value = users.value.filter(u => {
            if (userToDelete.value.is_guest) {
                return !(u.id === userToDelete.value.id && u.is_guest);
            } else {
                return !(u.id === userToDelete.value.id && !u.is_guest);
            }
        });
        
        // Clear selected user if it was deleted
        if (selectedUser.value?.id === userToDelete.value.id && 
            selectedUser.value?.is_guest === userToDelete.value.is_guest) {
            selectedUser.value = null;
            messages.value = [];
        }
        
        showDeleteModal.value = false;
        userToDelete.value = null;
        
        // Refresh users list
        setTimeout(() => loadUsers(true), 500);
    } catch (error) {
        console.error('Error deleting chat history:', error);
        alert('Failed to delete chat history. Please try again.');
    }
};

const blockGuestUser = async (user) => {
    try {
        await axios.post(`/admin/guest-chat/${user.id}/block`);
        loadUsers();
        if (selectedUser.value?.id === user.id) {
            selectedUser.value = { ...selectedUser.value, is_blocked: true };
        }
    } catch (error) {
        console.error('Error blocking user:', error);
        alert('Failed to block user. Please try again.');
    }
};

const unblockGuestUser = async (user) => {
    try {
        await axios.post(`/admin/guest-chat/${user.id}/unblock`);
        loadUsers();
        if (selectedUser.value?.id === user.id) {
            selectedUser.value = { ...selectedUser.value, is_blocked: false };
        }
    } catch (error) {
        console.error('Error unblocking user:', error);
        alert('Failed to unblock user. Please try again.');
    }
};
</script>
