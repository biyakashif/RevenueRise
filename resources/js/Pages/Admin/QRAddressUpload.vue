<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    initialAddress: { type: String, default: '' },
    initialQrCode: { type: String, default: '' },
});

const form = useForm({
    qr_code: null,
    address: props.initialAddress,
});

const onFileChange = (event) => {
    form.qr_code = event.target.files[0];
};

const submit = () => {
    form.post(route('admin.qr-address-upload.store'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('qr_code');
        },
        onError: (errors) => {
            alert('Error uploading QR code and wallet address: ' + JSON.stringify(errors));
        },
    });
};
</script>

<template>
    <Head title="Upload QR Code and Wallet Address" />
    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-bold">Upload QR Code and Wallet Address (USDT)</h1>
        </template>

        <div class="py-6">
            <div class="max-w-3xl mx-auto px-4">
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <form @submit.prevent="submit" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="qr_code" class="block text-sm font-medium text-gray-700 mb-1">QR Code Image</label>
                            <input
                                type="file"
                                id="qr_code"
                                accept="image/png, image/jpeg, image/jpg"
                                @change="onFileChange"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            />
                            <span v-if="form.errors.qr_code" class="text-red-500 text-xs">{{ form.errors.qr_code }}</span>
                            <div v-if="initialQrCode" class="mt-2">
                                <img :src="'/storage/' + initialQrCode" alt="Current QR Code" class="w-32 h-32" />
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Wallet Address</label>
                            <input
                                v-model="form.address"
                                type="text"
                                id="address"
                                class="block w-full px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            />
                            <span v-if="form.errors.address" class="text-red-500 text-xs">{{ form.errors.address }}</span>
                        </div>

                        <button
                            type="submit"
                            class="px-4 py-2 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-600"
                            :disabled="form.processing"
                        >
                            Update
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>