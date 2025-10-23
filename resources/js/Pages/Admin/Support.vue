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
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-lg font-bold text-slate-800 drop-shadow-sm">Support Chat</h1>
                <div class="flex space-x-2">
                    <Link :href="route('admin.auto-reply')" class="px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm transition-all duration-200">
                        <i class="fas fa-robot mr-1"></i> Auto Reply
                    </Link>
                    <Link :href="route('admin.blocked-users')" class="px-3 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-sm transition-all duration-200">
                        <i class="fas fa-user-slash mr-1"></i> Block User
                    </Link>
                    <button @click="openBlockedGuestsModal" class="px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm transition-all duration-200">
                        <i class="fas fa-ban mr-1"></i> Blocked Guests
                    </button>
                </div>
            </div>
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
                                         'bg-white/20': selectedUser?.id === user.id && selectedUser?.is_guest === user.is_guest,
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
                                            <div class="flex items-center space-x-2">
                                                <div>
                                                    <div class="font-medium text-slate-800 flex items-center space-x-2">
                                                        <span>{{ user.name }}</span>
                                                        <span v-if="user.is_guest" class="px-2 py-1 bg-orange-100 text-orange-800 text-xs rounded-full">Guest</span>
                                                        <!-- Notification badge on name -->
                                                        <span v-if="user.hasNotification" 
                                                              class="bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs animate-pulse">
                                                            !
                                                        </span>
                                                    </div>
                                                    <div class="text-xs text-slate-600">{{ user.mobile_number }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
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
                                                <button v-if="!user.is_guest && !user.is_blocked" 
                                                        @click.stop="blockUser(user)" 
                                                        class="text-orange-500 text-xs hover:underline">
                                                    Block User
                                                </button>
                                                <button v-if="!user.is_guest && user.is_blocked" 
                                                        @click.stop="unblockUser(user)" 
                                                        class="text-green-500 text-xs hover:underline">
                                                    Unblock User
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
                                                    <img :src="message.image_path" 
                                                         alt="chat image" 
                                                         class="w-32 h-32 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity"
                                                         @click="openMediaModal(message.image_path, 'image')">
                                                </div>
                                                <div v-if="message.video_path" class="mb-2 relative">
                                                    <video class="w-32 h-32 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity"
                                                           @click="openMediaModal(message.video_path, 'video')">
                                                        <source :src="message.video_path" type="video/mp4">
                                                    </video>
                                                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                                        <i class="fas fa-play-circle text-white text-2xl opacity-80"></i>
                                                    </div>
                                                </div>
                                                <p v-if="message.message">{{ message.message }}</p>
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

        <!-- Modal for delete confirmation -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-40">
            <div class="bg-white p-6 rounded shadow-lg">
                <h3 class="text-lg font-semibold mb-4">Confirm Deletion</h3>
                <p>Are you sure you want to delete all chat history with this user?</p>
                <div class="mt-4 flex justify-end space-x-2">
                    <button @click="cancelDelete" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button @click="confirmDelete" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
                </div>
            </div>
        </div>

        <!-- Blocked Guests Modal -->
        <div v-if="showBlockedGuestsModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-40">
            <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl p-6 w-full max-w-md max-h-[80vh] overflow-y-auto border border-white/20 shadow-2xl">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-white">Blocked Guests</h3>
                    <button @click="closeBlockedGuestsModal" class="text-white/70 hover:text-white">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div v-if="blockedGuests.length === 0" class="text-center py-8 text-white/70">
                    No blocked guests
                </div>
                <div v-else class="space-y-3">
                    <div v-for="guest in blockedGuests" :key="guest.id" class="flex items-center justify-between p-3 bg-white/10 backdrop-blur-sm rounded-lg border border-white/20">
                        <div>
                            <div class="font-medium text-white">{{ guest.name }}</div>
                            <div class="text-sm text-white/70">{{ guest.mobile_number }}</div>
                            <div class="text-xs text-white/50">Blocked: {{ formatDate(guest.blocked_at) }}</div>
                        </div>
                        <button @click="unblockGuest(guest.session_id)" class="px-3 py-1 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded text-sm transform hover:scale-105 transition-all duration-200">
                            Unblock
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const users = ref([]);
const selectedUser = ref(null);
const messages = ref([]);
const messageCache = ref(new Map()); // Cache messages for each user
const guestUserCache = ref(new Map()); // Cache guest users by session ID

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
        .map(user => {
            const hasNotification = window.adminNotifications?.hasNotification(user.id, user.is_guest) || false;
            return {
                ...user,
                hasNotification,
                unread_count: hasNotification && !user.unread_count ? 1 : user.unread_count
            };
        })
        .sort((a, b) => {
            if (a.hasNotification && !b.hasNotification) return -1;
            if (!a.hasNotification && b.hasNotification) return 1;
            
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
            is_blocked: u.is_blocked || false,
        }));
        
        const guestUsers = (guestResponse.data || []).map(u => {
            const guestUser = {
                ...u,
                mobile_number: u.mobile_number != null ? String(u.mobile_number) : u.mobile_number,
                unread_count: u.unread_count ?? 0,
                is_guest: true,
                last_message_at: u.last_message_at,
            };
            // Cache guest user by session ID
            guestUserCache.value.set(u.session_id, guestUser);
            return guestUser;
        });
        
        // Create a map to store unique users, using session_id as the primary key for deduplication
        const userMap = new Map();
        
        // First process guest users
        guestUsers.forEach(guestUser => {
            const sessionKey = guestUser.id; // guest id is the session_id
            userMap.set(sessionKey, {
                ...guestUser,
                is_guest: true // Ensure guest flag is set
            });
        });
        
        // Then add chat users, overwriting guest entries if they match by session_id
        chatUsers.forEach(user => {
            const sessionKey = user.session_id || user.id;
            // If this user already exists as a guest, merge the data
            if (userMap.has(sessionKey)) {
                const existingUser = userMap.get(sessionKey);
                userMap.set(sessionKey, {
                    ...existingUser,
                    ...user,
                    is_guest: true // Maintain guest status
                });
            } else {
                userMap.set(sessionKey, user);
            }
        });
        
        // Convert map to array and ensure no duplicates by session_id
        const allUsers = Array.from(userMap.values()).reduce((acc, user) => {
            const isDuplicate = acc.some(existingUser => 
                (user.session_id && existingUser.session_id === user.session_id) ||
                (user.id && existingUser.id === user.id)
            );
            if (!isDuplicate) {
                acc.push(user);
            }
            return acc;
        }, []);
        
        // Check for new messages by comparing with previous state
        if (users.value.length > 0) {
            allUsers.forEach(newUser => {
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
        
        users.value = allUsers;
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
        
        // Track currently selected user globally so AdminLayout can check
        window.currentSelectedUser = {
            id: user.id,
            session_id: user.session_id,
            is_guest: user.is_guest
        };
        
        // Create unique key for user
        const userKey = `${user.is_guest ? 'guest' : 'user'}-${user.id}`;
        
        // Clear notification immediately when user is selected
        if (window.adminNotifications) {
            window.adminNotifications.clearNotification(user.id, user.is_guest);
        }
        
        // Update user badge in the list
        const idx = users.value.findIndex(u => u.id === user.id && u.is_guest === user.is_guest);
        if (idx !== -1) {
            const u = users.value[idx];
            u.unread_count = 0;
            u.hasNotification = false;
            users.value.splice(idx, 1, { ...u });
        }
        
        // Check if we have cached messages for this user
        if (messageCache.value.has(userKey)) {
            messages.value = messageCache.value.get(userKey);
        } else {
            messages.value = []; // Only clear if no cache
        }
        
        receivedMessageIds.clear(); // Clear to allow new messages
        
        // Track all current message IDs
        messages.value.forEach(msg => {
            receivedMessageIds.add(String(msg.id));
        });
        
        // Scroll to bottom immediately if we have cached messages
        if (messageCache.value.has(userKey)) {
            nextTick(() => {
                if (chatContainer.value) {
                    chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
                }
            });
        }
        
        // Load messages from database on first selection
        if (!messageCache.value.has(userKey)) {
            let endpoint;
            if (user.is_guest) {
                // Extract session ID from guest user ID (remove 'guest_' prefix)
                const sessionId = user.id.replace('guest_', '');
                endpoint = `/admin/guest-chat/${sessionId}/messages`;
            } else {
                endpoint = `/admin/chat/${user.id}/messages`;
            }
            
            axios.get(endpoint).then(response => {
                if (selectedUser.value?.id === user.id && selectedUser.value?.is_guest === user.is_guest) {
                    const loadedMessages = response.data.sort((a, b) => 
                        new Date(a.created_at) - new Date(b.created_at)
                    );
                    
                    messages.value = loadedMessages;
                    messageCache.value.set(userKey, loadedMessages);
                    
                    receivedMessageIds.clear();
                    loadedMessages.forEach(msg => {
                        receivedMessageIds.add(String(msg.id));
                    });
                    
                    scrollToBottom();
                }
            }).catch(error => {
                console.error('Error loading messages:', error);
            });
        }
        
    } catch (error) {
        console.error('Error selecting user:', error);
    }
};

const sendMessage = async () => {
    const messageText = newMessage.value;
    
    try {
        if (!newMessage.value.trim() && !imageInput.value?.files[0] && !videoInput.value?.files[0]) return;
        if (selectedUser.value.is_blocked) return;

        newMessage.value = ''; // Clear immediately

        let endpoint;
        if (selectedUser.value.is_guest) {
            // Extract session ID from guest user ID (remove 'guest_' prefix)
            const sessionId = selectedUser.value.id.replace('guest_', '');
            endpoint = `/admin/guest-chat/${sessionId}/broadcast`;
        } else {
            endpoint = `/admin/chat/${selectedUser.value.id}/broadcast`;
        }
        
        const response = await axios.post(endpoint, { 
            message: messageText,
            recipient_id: selectedUser.value.id 
        });
        const data = response.data;
        
        // Add admin message to chat immediately
        if (data && data.id) {
            const adminMessage = {
                id: data.id,
                message: messageText,
                image_path: null,
                video_path: null,
                created_at: data.created_at,
                sender_id: page.props.auth.user.id,
                recipient_id: selectedUser.value.id,
            };
            
            if (!receivedMessageIds.has(String(data.id))) {
                messages.value.push(adminMessage);
                messages.value.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                
                // Update cache
                const userKey = `${selectedUser.value.is_guest ? 'guest' : 'user'}-${selectedUser.value.id}`;
                messageCache.value.set(userKey, [...messages.value]);
                
                scrollToBottom();
                receivedMessageIds.add(String(data.id));
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
        alert('Error sending message. Please try again.');
    }
};

const handleImageUpload = async (event) => {
    try {
        const file = event.target.files[0];
        if (!file || selectedUser.value.is_blocked) return;

        if (file.size > 5 * 1024 * 1024) {
            alert('Image file size must not exceed 5MB.');
            event.target.value = '';
            return;
        }

        const formData = new FormData();
        formData.append('image', file);

        let endpoint;
        if (selectedUser.value.is_guest) {
            // Extract session ID from guest user ID (remove 'guest_' prefix)
            const sessionId = selectedUser.value.id.replace('guest_', '');
            endpoint = `/admin/guest-chat/${sessionId}/send`;
        } else {
            endpoint = `/admin/chat/${selectedUser.value.id}/send`;
        }
        
        const response = await axios.post(endpoint, formData);
        const data = response.data;
        
        // Add admin image message to chat immediately
        if (data && data.id) {
            const adminMessage = {
                id: data.id,
                message: data.message || '',
                image_path: data.image_path,
                video_path: null,
                created_at: data.created_at,
                sender_id: page.props.auth.user.id,
                recipient_id: selectedUser.value.id,
            };
            
            if (!receivedMessageIds.has(String(data.id))) {
                messages.value.push(adminMessage);
                messages.value.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                
                // Update cache
                const userKey = `${selectedUser.value.is_guest ? 'guest' : 'user'}-${selectedUser.value.id}`;
                messageCache.value.set(userKey, [...messages.value]);
                
                scrollToBottom();
                receivedMessageIds.add(String(data.id));
            }
        }
        
        event.target.value = '';
    } catch (error) {
        console.error('Error uploading image:', error);
        alert('Error uploading image. Please try again.');
    }
};

const videoError = ref('');

const handleVideoUpload = async (event) => {
    const file = event.target.files[0];
    if (file && file.size > 30 * 1024 * 1024) {
        videoError.value = 'The video file size must not exceed 30MB.';
        event.target.value = '';
        return;
    }
    videoError.value = '';

    try {
        if (selectedUser.value.is_blocked) return;
        
        const formData = new FormData();
        formData.append('video', file);

        let endpoint;
        if (selectedUser.value.is_guest) {
            // Extract session ID from guest user ID (remove 'guest_' prefix)
            const sessionId = selectedUser.value.id.replace('guest_', '');
            endpoint = `/admin/guest-chat/${sessionId}/send`;
        } else {
            endpoint = `/admin/chat/${selectedUser.value.id}/send`;
        }
        
        const response = await axios.post(endpoint, formData);
        const data = response.data;
        
        // Add admin video message to chat immediately
        if (data && data.id) {
            const adminMessage = {
                id: data.id,
                message: data.message || '',
                image_path: null,
                video_path: data.video_path,
                created_at: data.created_at,
                sender_id: page.props.auth.user.id,
                recipient_id: selectedUser.value.id,
            };
            
            if (!receivedMessageIds.has(String(data.id))) {
                messages.value.push(adminMessage);
                messages.value.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                
                // Update cache
                const userKey = `${selectedUser.value.is_guest ? 'guest' : 'user'}-${selectedUser.value.id}`;
                messageCache.value.set(userKey, [...messages.value]);
                
                scrollToBottom();
                receivedMessageIds.add(String(data.id));
            }
        }
        
        event.target.value = '';
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



let echoChannel = null;

onMounted(() => {
    // Initialize currently selected user tracking
    window.currentSelectedUser = null;
    
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

    // Clear users array and guest cache on mount to prevent duplicates
    users.value = [];
    guestUserCache.value.clear();
    
    // Remove any existing Echo listeners to prevent duplicates
    if (window.Echo) {
        try {
            window.Echo.leaveAllChannels();
        } catch (e) {
            console.log('No existing channels to leave');
        }
    }
    
    // Initial load - only once
    loadUsers(true);

    // No polling - pure Echo real-time

    // Real-time with Echo for admin chat
    if (window.Echo) {
        console.log('🔄 Setting up Echo listeners for admin chat...');
        
        // Listen to admin's own channel for user messages
        echoChannel = window.Echo.private(`chat.${page.props.auth.user.id}`)
            .listen('NewChatMessage', (e) => {
                console.log('📨 Received NewChatMessage event:', e);
                // Handle all messages on admin channel
                const isFromAdmin = String(e.chat.sender_id) === String(page.props.auth.user.id);
                
                if (!isFromAdmin) {
                    // This is a message TO admin FROM user
                    playNotification();
                    
                    if ('Notification' in window && Notification.permission === 'granted') {
                        new Notification('New Support Message', {
                            body: `New message from user`,
                            icon: '/favicon.ico'
                        });
                    }
                    
                    // Find or create user in list
                    let userIndex = users.value.findIndex(u => u.id === e.chat.sender_id && !u.is_guest);
                    if (userIndex === -1) {
                        // Only add if user doesn't exist in any form
                        const existsInAnyForm = users.value.some(u => u.id === e.chat.sender_id);
                        if (!existsInAnyForm) {
                            const newUser = {
                                id: e.chat.sender_id,
                                name: `User ${e.chat.sender_id}`,
                                mobile_number: '',
                                avatar_url: null,
                                unread_count: 1,
                                is_guest: false,
                                last_message_at: e.chat.created_at,
                                hasNotification: true
                            };
                            users.value.push(newUser);
                            window.adminNotifications.addNotification(e.chat.sender_id, false);
                            console.log('👥 Created new user from message:', newUser);
                        }
                    } else {
                        const user = users.value[userIndex];
                        user.last_message_at = e.chat.created_at;
                        
                        const userKey = `user-${user.id}`;
                        
                        if (selectedUser.value && !selectedUser.value.is_guest && selectedUser.value.id === user.id) {
                            // If selected, add to messages (no notification needed)
                            if (!receivedMessageIds.has(String(e.chat.id))) {
                                const newMsg = {
                                    id: e.chat.id,
                                    message: e.chat.message,
                                    image_path: e.chat.image_path,
                                    video_path: e.chat.video_path,
                                    created_at: e.chat.created_at,
                                    sender_id: e.chat.sender_id,
                                    recipient_id: e.chat.recipient_id,
                                };
                                
                                messages.value.push(newMsg);
                                messages.value.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                                messageCache.value.set(userKey, [...messages.value]); // Update cache
                                scrollToBottom();
                                receivedMessageIds.add(String(e.chat.id));
                                console.log('📨 Added user message to chat:', newMsg);
                            }
                            user.unread_count = 0;
                            user.hasNotification = false;
                        } else {
                            // Add to cache and show notification badge
                            const cachedMessages = messageCache.value.get(userKey) || [];
                            if (!cachedMessages.some(m => m.id === e.chat.id)) {
                                const newMsg = {
                                    id: e.chat.id,
                                    message: e.chat.message,
                                    image_path: e.chat.image_path,
                                    video_path: e.chat.video_path,
                                    created_at: e.chat.created_at,
                                    sender_id: e.chat.sender_id,
                                    recipient_id: e.chat.recipient_id,
                                };
                                cachedMessages.push(newMsg);
                                cachedMessages.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                                messageCache.value.set(userKey, cachedMessages);
                            }
                            user.unread_count = (user.unread_count || 0) + 1;
                            user.hasNotification = true;
                            window.adminNotifications.addNotification(user.id, false);
                        }
                        
                        users.value.splice(userIndex, 1, { ...user });
                    }
                }
            })
            .listen('NewGuestChatMessage', (e) => {
                console.log('📨 Received NewGuestChatMessage on admin chat channel:', e);
                if (e.message && e.message.sender_id && e.message.is_guest) {
                    const isFromAdmin = String(e.message.sender_id) === String(page.props.auth.user.id);
                    
                    // Check if guest is blocked
                    const isBlocked = blockedGuests.value.some(bg => bg.session_id === e.message.sender_id);
                    
                    if (!isFromAdmin && !isBlocked) {
                        playNotification();
                        
                        if ('Notification' in window && Notification.permission === 'granted') {
                            new Notification('New Support Message', {
                                body: `New message from guest`,
                                icon: '/favicon.ico'
                            });
                        }
                        
                        // Check cache first, then find or create guest user
                        const sessionId = e.message.sender_id;
                        let cachedGuest = guestUserCache.value.get(sessionId);
                        let userIndex = -1;
                        
                        if (cachedGuest) {
                            // Use cached guest user
                            userIndex = users.value.findIndex(u => u.id === cachedGuest.id && u.is_guest);
                            if (userIndex === -1) {
                                users.value.push(cachedGuest);
                                userIndex = users.value.length - 1;
                            }
                        } else {
                            // Create new guest user and cache it
                            userIndex = users.value.findIndex(u => u.id === sessionId && u.is_guest);
                            if (userIndex === -1) {
                                const newGuestUser = {
                                    id: sessionId,
                                    session_id: sessionId,
                                    name: e.message.guest_name || `Guest ${sessionId.substring(0, 8)}`,
                                    mobile_number: e.message.guest_mobile || 'N/A',
                                    avatar_url: null,
                                    unread_count: 1,
                                    is_guest: true,
                                    is_blocked: false,
                                    last_message_at: e.message.created_at
                                };
                                guestUserCache.value.set(sessionId, newGuestUser);
                                users.value.push(newGuestUser);
                                userIndex = users.value.length - 1;
                                console.log('👥 Created new guest user from admin chat channel:', newGuestUser);
                            }
                        }
                        
                        // Handle message for existing or newly created user (ALWAYS runs)
                        if (userIndex !== -1) {
                            const user = users.value[userIndex];
                            user.last_message_at = e.message.created_at;
                            
                            const userKey = `guest-${user.id}`;
                            
                            console.log('🔍 Checking if guest is selected:', {
                                selectedUserId: selectedUser.value?.id,
                                selectedUserIsGuest: selectedUser.value?.is_guest,
                                currentUserId: user.id,
                                match: selectedUser.value && selectedUser.value.is_guest && selectedUser.value.id === user.id
                            });
                            
                            if (selectedUser.value && selectedUser.value.is_guest && selectedUser.value.id === user.id) {
                                if (!receivedMessageIds.has(String(e.message.id))) {
                                    const newMsg = {
                                        id: e.message.id,
                                        message: e.message.message,
                                        image_path: e.message.image_path || (e.message.is_image ? e.message.message : null),
                                        video_path: e.message.video_path || null,
                                        created_at: e.message.created_at,
                                        sender_id: e.message.sender_id,
                                        recipient_id: page.props.auth.user.id,
                                        is_guest: true
                                    };
                                    
                                    messages.value.push(newMsg);
                                    messages.value.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                                    messageCache.value.set(userKey, [...messages.value]);
                                    scrollToBottom();
                                    receivedMessageIds.add(String(e.message.id));
                                    console.log('✅ Added guest message from admin chat channel to UI:', newMsg);
                                }
                                user.unread_count = 0;
                                user.hasNotification = false;
                            } else {
                                // Add to cache and show notification badge
                                const cachedMessages = messageCache.value.get(userKey) || [];
                                if (!cachedMessages.some(m => m.id === e.message.id)) {
                                    const newMsg = {
                                        id: e.message.id,
                                        message: e.message.message,
                                        image_path: e.message.image_path || (e.message.is_image ? e.message.message : null),
                                        video_path: e.message.video_path || null,
                                        created_at: e.message.created_at,
                                        sender_id: e.message.sender_id,
                                        recipient_id: page.props.auth.user.id,
                                        is_guest: true
                                    };
                                    cachedMessages.push(newMsg);
                                    cachedMessages.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                                    messageCache.value.set(userKey, cachedMessages);
                                    console.log('📦 Cached guest message for later:', newMsg);
                                }
                                user.unread_count = (user.unread_count || 0) + 1;
                                user.hasNotification = true;
                                window.adminNotifications.addNotification(user.id, true);
                            }
                            
                            users.value.splice(userIndex, 1, { ...user });
                        }
                    }
                }
            })

        // Listen for guest chat messages on admin channel
        window.Echo.private('guest-chat')
            .listen('NewGuestChatMessage', (e) => {
                console.log('📨 Received NewGuestChatMessage event:', e);
                if (e.message && e.message.sender_id) {
                    const isFromAdmin = String(e.message.sender_id) === String(page.props.auth.user.id);
                    const isGuestMessage = e.message.is_guest === true;
                    
                    // Handle guest messages (from guest to admin)
                    if (isGuestMessage && !isFromAdmin) {
                        playNotification();
                        
                        if ('Notification' in window && Notification.permission === 'granted') {
                            new Notification('New Support Message', {
                                body: `New message from guest`,
                                icon: '/favicon.ico'
                            });
                        }
                        
                        // Check cache first, then find or create guest user
                        const sessionId = e.message.sender_id;
                        let cachedGuest = guestUserCache.value.get(sessionId);
                        
                        if (cachedGuest) {
                            // Use cached guest user
                            let userIndex = users.value.findIndex(u => u.id === cachedGuest.id && u.is_guest);
                            if (userIndex === -1) {
                                users.value.push(cachedGuest);
                                userIndex = users.value.length - 1;
                            }
                        } else {
                            // Create new guest user and cache it
                            let userIndex = users.value.findIndex(u => u.id === sessionId && u.is_guest);
                            if (userIndex === -1) {
                                const newGuestUser = {
                                    id: sessionId,
                                    session_id: sessionId,
                                    name: e.message.guest_name || `Guest ${sessionId.substring(0, 8)}`,
                                    mobile_number: e.message.guest_mobile || 'N/A',
                                    avatar_url: null,
                                    unread_count: 1,
                                    is_guest: true,
                                    is_blocked: false,
                                    last_message_at: e.message.created_at
                                };
                                guestUserCache.value.set(sessionId, newGuestUser);
                                users.value.push(newGuestUser);
                                userIndex = users.value.length - 1;
                                console.log('👥 Created new guest user from message:', newGuestUser);
                            }
                            
                            // Handle message for existing or newly created user
                            const user = users.value[userIndex];
                            user.last_message_at = e.message.created_at;
                            
                            const userKey = `guest-${user.id}`;
                            
                            if (selectedUser.value && selectedUser.value.is_guest && selectedUser.value.id === user.id) {
                                // If selected, add to messages
                                if (!receivedMessageIds.has(String(e.message.id))) {
                                    const newMsg = {
                                        id: e.message.id,
                                        message: e.message.message,
                                        image_path: e.message.image_path || (e.message.is_image ? e.message.message : null),
                                        video_path: e.message.video_path || null,
                                        created_at: e.message.created_at,
                                        sender_id: e.message.sender_id,
                                        recipient_id: page.props.auth.user.id,
                                        is_guest: true
                                    };
                                    
                                    messages.value.push(newMsg);
                                    messages.value.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                                    messageCache.value.set(userKey, [...messages.value]); // Update cache
                                    scrollToBottom();
                                    receivedMessageIds.add(String(e.message.id));
                                    console.log('📨 Added guest message to chat:', newMsg);
                                }
                                user.unread_count = 0;
                                user.hasNotification = false;
                            } else {
                                // Add to cache and show notification badge
                                const cachedMessages = messageCache.value.get(userKey) || [];
                                if (!cachedMessages.some(m => m.id === e.message.id)) {
                                    const newMsg = {
                                        id: e.message.id,
                                        message: e.message.message,
                                        image_path: e.message.image_path || (e.message.is_image ? e.message.message : null),
                                        video_path: e.message.video_path || null,
                                        created_at: e.message.created_at,
                                        sender_id: e.message.sender_id,
                                        recipient_id: page.props.auth.user.id,
                                        is_guest: true
                                    };
                                    cachedMessages.push(newMsg);
                                    cachedMessages.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                                    messageCache.value.set(userKey, cachedMessages);
                                }
                                user.unread_count = (user.unread_count || 0) + 1;
                                user.hasNotification = true;
                                window.adminNotifications.addNotification(user.id, true);
                            }
                            
                            users.value.splice(userIndex, 1, { ...user });
                        }
                    }
                }
            })
            
        // Listen for chat deletion events
        window.Echo.private(`chat.${page.props.auth.user.id}`)
            .listen('ChatDeleted', (e) => {
                console.log('🗑️ Chat deleted:', e);
                // Remove user from list
                users.value = users.value.filter(u => {
                    if (e.is_guest) {
                        return !(u.id === e.user_id && u.is_guest);
                    } else {
                        return !(u.id === e.user_id && !u.is_guest);
                    }
                });
                
                // Clear selected user if it was deleted
                if (selectedUser.value?.id === e.user_id && 
                    selectedUser.value?.is_guest === e.is_guest) {
                    selectedUser.value = null;
                    messages.value = [];
                    
                    // Clear from cache
                    const userKey = `${e.is_guest ? 'guest' : 'user'}-${e.user_id}`;
                    messageCache.value.delete(userKey);
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
        // No intervals to clear
    };
});

onUnmounted(() => {
    // Clear currently selected user tracking
    window.currentSelectedUser = null;
    
    // Clean up Echo listeners
    if (echoChannel) {
        echoChannel.stopListening('NewChatMessage');
        echoChannel.stopListening('NewGuestChatMessage');
        echoChannel.stopListening('ChatDeleted');
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
const showMediaModal = ref(false);
const modalMediaSrc = ref('');
const modalMediaType = ref('');
const showBlockedGuestsModal = ref(false);
const blockedGuests = ref([]);

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
        let endpoint;
        if (userToDelete.value.is_guest) {
            // Extract session ID from guest user ID (remove 'guest_' prefix)
            const sessionId = userToDelete.value.id.replace('guest_', '');
            endpoint = `/admin/guest-chat/${sessionId}/delete-history`;
        } else {
            endpoint = `/admin/chat/${userToDelete.value.id}/delete-history`;
        }
        
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
            
            // Clear from cache
            const userKey = `${userToDelete.value.is_guest ? 'guest' : 'user'}-${userToDelete.value.id}`;
            messageCache.value.delete(userKey);
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
        // Extract session ID from guest user ID (remove 'guest_' prefix)
        const sessionId = user.id.replace('guest_', '');
        await axios.post(`/admin/guest-chat/${sessionId}/block`);
        
        // Remove user from list and clear selection
        users.value = users.value.filter(u => u.id !== user.id);
        if (selectedUser.value?.id === user.id) {
            selectedUser.value = null;
            messages.value = [];
        }
        
        // Clear from cache
        const userKey = `guest-${user.id}`;
        messageCache.value.delete(userKey);
        
    } catch (error) {
        console.error('Error blocking user:', error);
        alert('Failed to block user. Please try again.');
    }
};

const unblockGuestUser = async (user) => {
    try {
        // Extract session ID from guest user ID (remove 'guest_' prefix)
        const sessionId = user.id.replace('guest_', '');
        await axios.post(`/admin/guest-chat/${sessionId}/unblock`);
        loadUsers();
        if (selectedUser.value?.id === user.id) {
            selectedUser.value = { ...selectedUser.value, is_blocked: false };
        }
    } catch (error) {
        console.error('Error unblocking user:', error);
        alert('Failed to unblock user. Please try again.');
    }
};

const openBlockedGuestsModal = async () => {
    try {
        const response = await axios.get('/admin/guest-chat/blocked');
        blockedGuests.value = response.data;
        showBlockedGuestsModal.value = true;
    } catch (error) {
        console.error('Error loading blocked guests:', error);
    }
};

const closeBlockedGuestsModal = () => {
    showBlockedGuestsModal.value = false;
    blockedGuests.value = [];
};

const unblockGuest = async (sessionId) => {
    try {
        await axios.post(`/admin/guest-chat/${sessionId}/unblock`);
        blockedGuests.value = blockedGuests.value.filter(g => g.session_id !== sessionId);
    } catch (error) {
        console.error('Error unblocking guest:', error);
        alert('Failed to unblock guest. Please try again.');
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString();
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

const blockUser = async (user) => {
    if (confirm(`Are you sure you want to block ${user.name}?`)) {
        try {
            await router.post(route('admin.users.block', user.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    const userIndex = users.value.findIndex(u => u.id === user.id && !u.is_guest);
                    if (userIndex !== -1) {
                        users.value[userIndex].is_blocked = true;
                    }
                }
            });
        } catch (error) {
            console.error('Error blocking user:', error);
        }
    }
};

const unblockUser = async (user) => {
    if (confirm(`Are you sure you want to unblock ${user.name}?`)) {
        try {
            await router.post(route('admin.users.unblock', user.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    const userIndex = users.value.findIndex(u => u.id === user.id && !u.is_guest);
                    if (userIndex !== -1) {
                        users.value[userIndex].is_blocked = false;
                    }
                }
            });
        } catch (error) {
            console.error('Error unblocking user:', error);
        }
    }
};


</script>
