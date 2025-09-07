<template>
    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Support Chat
            </h2>
        </template>

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

        <div class="py-2">
            <div class="max-w-[1400px] mx-auto px-2">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-3 bg-white border-b border-gray-200">
                        <div class="flex h-[75vh]">
                            <!-- Users List with Chat Status -->
                            <div class="w-1/3 border-r overflow-y-auto">
                                <div class="p-2">
                                    <input v-model="searchQuery" 
                                           placeholder="Search by name or mobile number" 
                                           class="w-full p-2 border rounded" />
                                </div>
                                <div v-for="user in filteredUsers" :key="user.mobile_number" 
                                     @click="selectUser(user)"
                                     class="relative p-4 hover:bg-gray-100 cursor-pointer"
                                     :class="{'bg-purple-50': selectedUser?.mobile_number === user.mobile_number}">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-medium">{{ user.name }}</div>
                                            <div class="text-xs text-gray-400">{{ user.mobile_number }}</div>
                                        </div>
                                        <div v-if="user.unread_count" 
                                             class="bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center animate-bounce">
                                            {{ user.unread_count }}
                                        </div>
                                        <button @click.stop="openDeleteModal(user)" 
                                                class="text-red-500 text-xs hover:underline">
                                            Delete Chat
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Chat Area -->
                            <div class="w-2/3 flex flex-col">
                                <div v-if="selectedUser" ref="chatContainer" class="flex-1 overflow-y-auto p-3">
                                    <div v-for="message in messages" :key="message.id" 
                                         class="mb-3"
                                         :class="{'text-right': message.sender_id === page.props.auth.user.mobile_number}">
                                        <div class="inline-block max-w-2xl rounded-lg p-3"
                                             :class="message.sender_id === page.props.auth.user.mobile_number ? 'bg-purple-100' : 'bg-gray-100'">
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
                                            <small class="text-gray-500">{{ formatDate(message.created_at) }}</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Message Input -->
                                <div v-if="selectedUser" class="border-t p-3">
                                    <form @submit.prevent="sendMessage" class="flex space-x-2">
                                        <input type="file" 
                                               ref="imageInput" 
                                               class="hidden" 
                                               @change="handleImageUpload"
                                               accept="image/*">
                                        <button type="button" 
                                                @click="$refs.imageInput.click()"
                                                class="px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                                            <i class="fas fa-image"></i>
                                        </button>
                                        <input type="file" 
                                               ref="videoInput" 
                                               class="hidden" 
                                               @change="handleVideoUpload"
                                               accept="video/mp4,video/x-matroska">
                                        <button type="button" 
                                                @click="$refs.videoInput.click()"
                                                class="px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                                            <i class="fas fa-video"></i>
                                        </button>
                                        <input v-model="newMessage" 
                                               type="text" 
                                               placeholder="Type your message..." 
                                               class="flex-1 rounded-lg border-gray-300 focus:border-purple-300 focus:ring focus:ring-purple-200 focus:ring-opacity-50"
                                               @keydown.enter.exact.prevent="sendMessage">
                                        <button type="submit" 
                                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                                            Send
                                        </button>
                                    </form>
                                </div>
                                <div v-else class="flex-1 flex items-center justify-center text-gray-500">
                                    Select a user to start chatting
                                </div>
                            </div>
                        </div>
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
const isFetchingMessages = ref(false);
const newMessage = ref('');
const imageInput = ref(null);
const videoInput = ref(null);
const chatContainer = ref(null);
const notificationSound = new Audio('/notification.mp3'); // Default system notification sound
const showNotification = ref(false);
// Track received message IDs to guard against duplicates
const receivedMessageIds = new Set();
const showSoundPrompt = ref(false);
const soundEnabled = ref(false);
const searchQuery = ref('');
const filteredUsers = computed(() => {
    const query = searchQuery.value.toLowerCase();
    return users.value
        .filter(user => user.name.toLowerCase().includes(query) || user.mobile_number.includes(query))
        .sort((a, b) => b.unread_count - a.unread_count);
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

const loadUsers = async (opts = { preserveCounts: true }) => {
    try {
        const response = await axios.get('/admin/chat/users');
        const serverUsers = (response.data || []).map(u => {
            const unread = (u.unread_count ?? u.sent_messages_count ?? 0);
            return {
                ...u,
                mobile_number: u.mobile_number != null ? String(u.mobile_number) : u.mobile_number,
                unread_count: unread,
            };
        });
        if (opts.preserveCounts) {
            const localMap = new Map(users.value.map(u => [String(u.mobile_number), u.unread_count || 0]));
            users.value = serverUsers.map(u => {
                const local = localMap.get(String(u.mobile_number)) || 0;
                return { ...u, unread_count: Math.max(u.unread_count || 0, local) };
            });
        } else {
            users.value = serverUsers;
        }
    } catch (error) {
        console.error('Error loading users:', error);
    }
};

// We rely solely on the admin's private channel (set up in onMounted)

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
    isFetchingMessages.value = true;
        const response = await axios.get(`/admin/chat/${user.mobile_number}/messages`);
        // Sort messages by created_at in ascending order to show latest at bottom
        messages.value = response.data.sort((a, b) => 
            new Date(a.created_at) - new Date(b.created_at)
        );
    // No per-user channel subscription needed; admin receives all on their private channel
        // Clear unread badge locally for this user
        const idx = users.value.findIndex(u => u.mobile_number === user.mobile_number);
        if (idx !== -1) {
            const u = users.value[idx];
            u.unread_count = 0;
            users.value.splice(idx, 1, { ...u });
        }
        // Force scroll to bottom after messages are loaded and DOM is updated
    nextTick(() => {
            if (chatContainer.value) {
                setTimeout(() => {
                    chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
                }, 50);
            }
        });
    isFetchingMessages.value = false;
    } catch (error) {
        console.error('Error loading messages:', error);
        alert('Error loading messages. Please try again.');
    isFetchingMessages.value = false;
    }
};

const sendMessage = async () => {
    try {
        if (!newMessage.value.trim() && !imageInput.value?.files[0] && !videoInput.value?.files[0]) return;

        const formData = new FormData();
        formData.append('message', newMessage.value);
        
        if (imageInput.value?.files[0]) {
            formData.append('image', imageInput.value.files[0]);
        }

        if (videoInput.value?.files[0]) {
            formData.append('video', videoInput.value.files[0]);
        }

        const { data } = await axios.post(`/admin/chat/${selectedUser.value.mobile_number}/send`, formData);
        newMessage.value = '';
        if (imageInput.value) {
            imageInput.value.value = '';
        }
        if (videoInput.value) {
            videoInput.value.value = '';
        }
        // Optimistically append sent message and mark as seen to avoid event duplicate
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
        console.error('Error sending message:', error);
        alert('Error sending message. Please try again.');
    }
};

const handleImageUpload = async (event) => {
    try {
        const file = event.target.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('image', file);

        const { data } = await axios.post(`/admin/chat/${selectedUser.value.mobile_number}/send`, formData);
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
        const formData = new FormData();
        formData.append('video', file);

        const { data } = await axios.post(`/admin/chat/${selectedUser.value.mobile_number}/send`, formData);
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

const formatDate = (date) => {
    return new Date(date).toLocaleString();
};

const handleNewMessage = async (e) => {
    // De-dup guard: ignore same message multiple times
    const dedupKey = e?.chat?.id ? String(e.chat.id) : [e?.chat?.sender_id, e?.chat?.recipient_id, e?.chat?.created_at, e?.chat?.message, e?.chat?.image_path].join('|');
    if (dedupKey && receivedMessageIds.has(dedupKey)) {
        return;
    }
    if (dedupKey) receivedMessageIds.add(dedupKey);
    // Play notification sound for new messages from users
    if (e.chat.sender_id !== page.props.auth.user.mobile_number) {
    playNotification();
        
        // Request notification permission if not granted
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
        
        // Show browser notification if permitted
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('New Support Message', {
                body: `New message from ${e.chat.sender_name || 'User'}`,
                icon: '/favicon.ico'
            });
        }

        // Update title with notification
        const originalTitle = document.title;
        document.title = '(New Message) ' + originalTitle;
        setTimeout(() => {
            document.title = originalTitle;
        }, 5000);
    }

    // Update messages if the current chat is open
    if (selectedUser.value?.mobile_number === e.chat.sender_id || 
        selectedUser.value?.mobile_number === e.chat.recipient_id) {
        messages.value.push({
            id: e.chat.id,
            message: e.chat.message,
            image_path: e.chat.image_path,
            video_path: e.chat.video_path,
            created_at: e.chat.created_at,
            sender_id: e.chat.sender_id,
            recipient_id: e.chat.recipient_id
        });
        scrollToBottom();
    } else {
        // Increment unread badge locally for the sender if chat not open
        const senderKey = e.chat && e.chat.sender_id != null ? String(e.chat.sender_id) : undefined;
        let idx = users.value.findIndex(u => String(u.mobile_number) === senderKey);
        if (idx === -1) {
            // Fallback: try by numeric id if structure differs
            idx = users.value.findIndex(u => String(u.id) === senderKey);
        }
        if (idx !== -1) {
            const u = users.value[idx];
            u.unread_count = (u.unread_count || 0) + 1;
            users.value.splice(idx, 1, { ...u });
        } else {
            // If not present yet, insert a placeholder so admin sees badge
            users.value.unshift({
                id: senderKey,
                mobile_number: senderKey,
                name: `User ${e.chat.sender_id}`,
                avatar_url: '/favicon.ico',
                unread_count: 1,
            });
        }
    }

    // Refresh users while preserving any local increments
    await loadUsers({ preserveCounts: true });
};

onMounted(() => {
    // Setup default notification sound
    notificationSound.src = '/notification.mp3';
    notificationSound.load();

    // Check stored preference
    if (localStorage.getItem('soundEnabled') === '1') {
        soundEnabled.value = true;
    } else {
        showSoundPrompt.value = true;
    }

    // Request notification permission
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    // Initial load of users
    loadUsers();

    // Subscribe admin to their own private channel to receive relevant messages
    const adminMobile = page.props.auth.user?.mobile_number;
    if (adminMobile) {
        window.Echo.private(`chat.${adminMobile}`)
            .listen('NewChatMessage', (e) => {
                handleNewMessage(e);
            })
            .listen('ChatHistoryDeleted', (e) => {
                if (selectedUser.value?.mobile_number === e.userMobile) {
                    selectedUser.value = null;
                    messages.value = [];
                }
                loadUsers();
            });
    }
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
        await axios.delete(`/admin/chat/${userToDelete.value.mobile_number}/delete-history`);
        // Replace alert with a user-friendly notification
        console.log('Chat history deleted successfully.');
        loadUsers(); // Refresh the user list
        if (selectedUser.value?.mobile_number === userToDelete.value.mobile_number) {
            selectedUser.value = null;
            messages.value = [];
        }
        showDeleteModal.value = false;
    } catch (error) {
        console.error('Error deleting chat history:', error);
        alert('Failed to delete chat history. Please try again.');
    }
};
</script>
