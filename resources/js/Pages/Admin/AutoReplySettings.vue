<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, Link, router, usePage } from '@inertiajs/vue3';
import { ref, onUnmounted } from 'vue';

const page = usePage();

const props = defineProps({
    settings: Object,
    messages: Array,
    contactSettings: Object,
});

const form = useForm({
    is_enabled: props.settings.is_enabled || false,
    messages: props.messages.length > 0 ? props.messages : [
        { message: 'Welcome! How can we help you today?', include_contact_info: false, image_path: null, video_path: null },
        { message: 'Hello [user_name]! Thank you for contacting us.', include_contact_info: false, image_path: null, video_path: null },
        { message: 'Thank you for your message. We will get back to you soon.', include_contact_info: false, image_path: null, video_path: null }
    ]
});

const addMessage = () => {
    form.messages.push({ message: '', include_contact_info: false, image_path: null, video_path: null });
};

const removeMessage = (index) => {
    if (form.messages.length > 1) {
        const message = form.messages[index];
        if (message.image_preview) {
            URL.revokeObjectURL(message.image_preview);
        }
        if (message.video_preview) {
            URL.revokeObjectURL(message.video_preview);
        }
        form.messages.splice(index, 1);
    }
};

const imageInputs = ref([]);
const videoInputs = ref([]);

const handleImageUpload = (index, event) => {
    const file = event.target.files[0];
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            alert('Image file size must not exceed 5MB.');
            event.target.value = '';
            return;
        }
        if (form.messages[index].image_preview) {
            URL.revokeObjectURL(form.messages[index].image_preview);
        }
        form.messages[index].image_file = file;
        form.messages[index].image_preview = URL.createObjectURL(file);
    }
};

const handleVideoUpload = (index, event) => {
    const file = event.target.files[0];
    if (file) {
        if (file.size > 30 * 1024 * 1024) {
            alert('Video file size must not exceed 30MB.');
            event.target.value = '';
            return;
        }
        if (form.messages[index].video_preview) {
            URL.revokeObjectURL(form.messages[index].video_preview);
        }
        form.messages[index].video_file = file;
        form.messages[index].video_preview = URL.createObjectURL(file);
    }
};

const removeImage = (index) => {
    if (form.messages[index].image_preview) {
        URL.revokeObjectURL(form.messages[index].image_preview);
    }
    form.messages[index].image_file = null;
    form.messages[index].image_preview = null;
    if (imageInputs.value[index]) {
        imageInputs.value[index].value = '';
    }
};

const removeVideo = (index) => {
    if (form.messages[index].video_preview) {
        URL.revokeObjectURL(form.messages[index].video_preview);
    }
    form.messages[index].video_file = null;
    form.messages[index].video_preview = null;
    if (videoInputs.value[index]) {
        videoInputs.value[index].value = '';
    }
};

const submit = () => {
    const formData = new FormData();
    formData.append('is_enabled', form.is_enabled ? '1' : '0');
    
    form.messages.forEach((message, index) => {
        formData.append(`messages[${index}][message]`, message.message);
        formData.append(`messages[${index}][include_contact_info]`, message.include_contact_info ? '1' : '0');
        
        if (message.image_file) {
            formData.append(`messages[${index}][image]`, message.image_file);
        }
        if (message.video_file) {
            formData.append(`messages[${index}][video]`, message.video_file);
        }
    });

    formData.append('_token', page.props.csrf_token);

    fetch(route('admin.auto-reply.update'), {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
        },
        body: formData,
    })
    .then(response => {
        if (response.status === 419) {
            window.location.reload();
            return;
        }
        return response.json();
    })
    .then(data => {
        if (data && data.success) {
            router.visit('/admin/support');
        } else {
            alert('Error saving settings. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving settings. Please try again.');
    });
};

onUnmounted(() => {
    form.messages.forEach(message => {
        if (message.image_preview) {
            URL.revokeObjectURL(message.image_preview);
        }
        if (message.video_preview) {
            URL.revokeObjectURL(message.video_preview);
        }
    });
});
</script>

<template>
    <Head title="Auto Reply Settings" />
    <AdminLayout>
        <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-6 rounded-2xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto">
            <div class="flex justify-between items-center mb-4 sm:mb-6">
                <div class="flex items-center gap-3">
                    <Link href="/admin/support" class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-slate-500/80 to-gray-600/80 hover:from-slate-600/90 hover:to-gray-700/90 rounded-xl shadow-lg transition-all duration-200 transform hover:scale-105">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold text-slate-800 drop-shadow-sm">Auto Reply Settings</h1>
                        <p class="text-xs sm:text-sm text-slate-600 font-medium">Configure automatic reply messages</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm rounded-xl shadow-lg border border-white/30 p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Enable Auto Reply -->
                    <div class="bg-white/20 rounded-lg p-4 border border-white/30">
                        <div class="flex items-center mb-4">
                            <input
                                id="is_enabled"
                                v-model="form.is_enabled"
                                type="checkbox"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <label for="is_enabled" class="ml-2 text-sm font-medium text-slate-700">
                                Enable Auto Reply
                            </label>
                        </div>
                        <p class="text-xs text-slate-600">When enabled, automatic messages will be sent to users after they send messages.</p>
                    </div>

                    <!-- Auto Reply Messages -->
                    <div class="bg-white/20 rounded-lg p-4 border border-white/30">
                        <h3 class="text-lg font-semibold text-slate-800 mb-4">Auto Reply Messages</h3>
                        <div class="space-y-4">
                            <div v-for="(message, index) in form.messages" :key="index" class="bg-white/10 rounded-lg p-4 border border-white/20">
                                <div class="flex items-start justify-between mb-2">
                                    <label class="block text-sm font-medium text-slate-700">Message {{ index + 1 }}</label>
                                    <button
                                        v-if="form.messages.length > 1"
                                        type="button"
                                        @click="removeMessage(index)"
                                        class="text-xs bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded"
                                    >
                                        Remove
                                    </button>
                                </div>
                                
                                <!-- Media Upload Buttons -->
                                <div class="flex items-center space-x-2 mb-3">
                                    <input 
                                        type="file" 
                                        :ref="el => imageInputs[index] = el"
                                        class="hidden" 
                                        @change="handleImageUpload(index, $event)"
                                        accept="image/*">
                                    <button 
                                        type="button" 
                                        @click="imageInputs[index]?.click()"
                                        class="p-2 bg-white/50 backdrop-blur-sm rounded-full shadow-md focus:outline-none border border-white/30 hover:bg-white/70 transition-all"
                                        :title="'Upload Image for Message ' + (index + 1)">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                    
                                    <input 
                                        type="file" 
                                        :ref="el => videoInputs[index] = el"
                                        class="hidden" 
                                        @change="handleVideoUpload(index, $event)"
                                        accept="video/mp4,video/x-mkv">
                                    <button 
                                        type="button" 
                                        @click="videoInputs[index]?.click()"
                                        class="p-2 bg-white/50 backdrop-blur-sm rounded-full shadow-md focus:outline-none border border-white/30 hover:bg-white/70 transition-all"
                                        :title="'Upload Video for Message ' + (index + 1)">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                                
                                <!-- Media Preview -->
                                <div v-if="message.image_preview || message.image_path" class="mb-3 relative">
                                    <img :src="message.image_preview || (message.image_path ? `/storage/${message.image_path}` : '')" 
                                         alt="Message image" 
                                         class="w-20 h-20 object-cover rounded cursor-pointer">
                                    <button @click="removeImage(index)" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs">
                                        ×
                                    </button>
                                </div>
                                
                                <div v-if="message.video_preview || message.video_path" class="mb-3 relative">
                                    <video :src="message.video_preview || (message.video_path ? `/storage/${message.video_path}` : '')" 
                                           class="w-20 h-20 object-cover rounded cursor-pointer" controls>
                                    </video>
                                    <button @click="removeVideo(index)" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs">
                                        ×
                                    </button>
                                </div>
                                
                                <textarea
                                    v-model="message.message"
                                    rows="3"
                                    class="w-full rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-3 py-2 placeholder-slate-400 backdrop-blur-sm shadow-lg"
                                    :placeholder="`Enter auto reply message ${index + 1}...`"
                                    required
                                ></textarea>
                                <div v-if="form.errors[`messages.${index}.message`]" class="text-red-500 text-sm mt-1">
                                    {{ form.errors[`messages.${index}.message`] }}
                                </div>
                                <div class="mt-3 flex items-center">
                                    <input
                                        :id="`include_contact_info_${index}`"
                                        v-model="message.include_contact_info"
                                        type="checkbox"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    />
                                    <label :for="`include_contact_info_${index}`" class="ml-2 text-sm font-medium text-slate-700">
                                        Include contact information with this message
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <button
                            type="button"
                            @click="addMessage"
                            class="mt-4 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-4 py-2 rounded-lg font-medium transition-all duration-200 shadow-lg transform hover:scale-105"
                        >
                            Add Message
                        </button>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-all duration-200 shadow-lg transform hover:scale-105 disabled:opacity-50"
                        >
                            <span v-if="form.processing">Saving...</span>
                            <span v-else>Save Settings</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>