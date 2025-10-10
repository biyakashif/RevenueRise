<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();

const props = defineProps({
    settings: Object,
});

const form = useForm({
    show_email: props.settings.show_email || false,
    email: props.settings.email || '',
    show_whatsapp: props.settings.show_whatsapp || false,
    whatsapp: props.settings.whatsapp || '',
    show_telegram: props.settings.show_telegram || false,
    telegram: props.settings.telegram || '',
    show_office: props.settings.show_office || false,
    office_address: props.settings.office_address || '',
    _token: page.props.csrf_token,
});

const submit = () => {
    form._token = page.props.csrf_token;
    form.post(route('admin.contact-settings.update'), {
        preserveScroll: true,
        onError: (errors) => {
            if (errors && (errors.message?.includes('419') || errors.status === 419)) {
                window.location.reload();
            }
        },
    });
};
</script>

<template>
    <Head title="Contact Settings" />
    <AdminLayout>
        <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-6 rounded-2xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto">
            <h1 class="text-2xl font-bold text-slate-800 mb-6">Contact Settings</h1>
            
            <div class="bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm rounded-xl shadow-lg border border-white/30 p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Email Settings -->
                    <div class="bg-white/20 rounded-lg p-4 border border-white/30">
                        <h3 class="text-lg font-semibold text-slate-800 mb-4">Email Settings</h3>
                        <div class="flex items-center mb-4">
                            <input
                                id="show_email"
                                v-model="form.show_email"
                                type="checkbox"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <label for="show_email" class="ml-2 text-sm font-medium text-slate-700">
                                Display Email on Contact Page
                            </label>
                        </div>
                        <div v-if="form.show_email">
                            <label for="email" class="block text-sm font-medium text-slate-700 mb-2">Email Address</label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="w-full h-10 rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-3 placeholder-slate-400 backdrop-blur-sm shadow-lg"
                                placeholder="support@example.com"
                            />
                            <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
                        </div>
                    </div>

                    <!-- WhatsApp Settings -->
                    <div class="bg-white/20 rounded-lg p-4 border border-white/30">
                        <h3 class="text-lg font-semibold text-slate-800 mb-4">WhatsApp Settings</h3>
                        <div class="flex items-center mb-4">
                            <input
                                id="show_whatsapp"
                                v-model="form.show_whatsapp"
                                type="checkbox"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <label for="show_whatsapp" class="ml-2 text-sm font-medium text-slate-700">
                                Display WhatsApp on Contact Page
                            </label>
                        </div>
                        <div v-if="form.show_whatsapp">
                            <label for="whatsapp" class="block text-sm font-medium text-slate-700 mb-2">WhatsApp Number</label>
                            <input
                                id="whatsapp"
                                v-model="form.whatsapp"
                                type="text"
                                class="w-full h-10 rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-3 placeholder-slate-400 backdrop-blur-sm shadow-lg"
                                placeholder="+1234567890"
                            />
                            <div v-if="form.errors.whatsapp" class="text-red-500 text-sm mt-1">{{ form.errors.whatsapp }}</div>
                        </div>
                    </div>

                    <!-- Telegram Settings -->
                    <div class="bg-white/20 rounded-lg p-4 border border-white/30">
                        <h3 class="text-lg font-semibold text-slate-800 mb-4">Telegram Settings</h3>
                        <div class="flex items-center mb-4">
                            <input
                                id="show_telegram"
                                v-model="form.show_telegram"
                                type="checkbox"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <label for="show_telegram" class="ml-2 text-sm font-medium text-slate-700">
                                Display Telegram on Contact Page
                            </label>
                        </div>
                        <div v-if="form.show_telegram">
                            <label for="telegram" class="block text-sm font-medium text-slate-700 mb-2">Telegram Username/Link</label>
                            <input
                                id="telegram"
                                v-model="form.telegram"
                                type="text"
                                class="w-full h-10 rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-3 placeholder-slate-400 backdrop-blur-sm shadow-lg"
                                placeholder="@username or https://t.me/username"
                            />
                            <div v-if="form.errors.telegram" class="text-red-500 text-sm mt-1">{{ form.errors.telegram }}</div>
                        </div>
                    </div>

                    <!-- Office Address Settings -->
                    <div class="bg-white/20 rounded-lg p-4 border border-white/30">
                        <h3 class="text-lg font-semibold text-slate-800 mb-4">Office Address Settings</h3>
                        <div class="flex items-center mb-4">
                            <input
                                id="show_office"
                                v-model="form.show_office"
                                type="checkbox"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <label for="show_office" class="ml-2 text-sm font-medium text-slate-700">
                                Display Office Address on Contact Page
                            </label>
                        </div>
                        <div v-if="form.show_office">
                            <label for="office_address" class="block text-sm font-medium text-slate-700 mb-2">Office Address</label>
                            <textarea
                                id="office_address"
                                v-model="form.office_address"
                                rows="3"
                                class="w-full rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-3 py-2 placeholder-slate-400 backdrop-blur-sm shadow-lg"
                                placeholder="123 Business Street, Luxembourg City, L-1234, Luxembourg"
                            ></textarea>
                            <div v-if="form.errors.office_address" class="text-red-500 text-sm mt-1">{{ form.errors.office_address }}</div>
                        </div>
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