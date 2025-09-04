<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { ClipboardDocumentListIcon } from '@heroicons/vue/24/solid';
import TetherIcon from '@/assets/tether.svg'; // Import the SVG

const page = usePage();
const { translations = {} } = page.props;
const t = (key) => translations[key] || key;

const props = defineProps({
    depositDetails: { type: Object, required: true },
    vip: { type: [String, null], default: null },
    prefillAmount: { type: [Number, String, null], default: null },
});

const isCopied = ref(false);
const copyError = ref(null);
const showHistory = ref(false);
const history = ref([]);
const historyError = ref(null);
const successMessage = ref(null || page.props.flash?.success);

// initialize form with prefilled amount if VIP purchase
const form = ref({
    amount: props.prefillAmount || '',
    slip: null,
    vip: props.vip || null,
});

watch(() => props.prefillAmount, (v) => {
    if (v) form.value.amount = v;
});

const copyAddress = async () => {
    const address = props.depositDetails.address;
    copyError.value = null;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        try {
            await navigator.clipboard.writeText(address);
            isCopied.value = true;
            setTimeout(() => (isCopied.value = false), 2000);
            return;
        } catch (err) {
            copyError.value = 'Failed to copy address. Please copy manually.';
        }
    }
    try {
        const textarea = document.createElement('textarea');
        textarea.value = address;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        isCopied.value = true;
        setTimeout(() => (isCopied.value = false), 2000);
    } catch (err) {
        copyError.value = 'Failed to copy address. Please copy manually.';
    }
};

const fetchHistory = async () => {
    try {
        const response = await fetch(route('deposit.history'));
        const data = await response.json();
        history.value = data.deposits;
        historyError.value = null;
        showHistory.value = true;
    } catch (error) {
        historyError.value = 'Failed to load deposit history. Please try again later.';
        history.value = [];
        showHistory.value = true;
    }
};

const submitDeposit = () => {
    const formData = new FormData();
    formData.append('amount', form.value.amount);
    formData.append('slip', form.value.slip);
    if (form.value.vip) {
        formData.append('vip', form.value.vip);
    }
    router.post(route('deposit.store'), formData, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = 'Deposit submitted successfully! Awaiting approval.';
            setTimeout(() => (successMessage.value = null), 3000);
            form.value = { amount: props.prefillAmount || '', slip: null, vip: props.vip || null };
        },
        onError: (errors) => {
            alert('Error submitting deposit: ' + JSON.stringify(errors));
        },
    });
};
</script>

<template>
    <Head :title="t('Deposit')" />
    <AuthenticatedLayout>
        <!--
          Responsive improvements:
          - Reduced font sizes and spacing on small screens (mobile)
          - Slightly larger and more comfortable layout on md+ screens
          - On large screens, center the card and ensure it fills the viewport height visually
        -->
        <div class="py-4 px-3 bg-gray-100 lg:py-6 lg:px-6">
            <div class="max-w-2xl mx-auto">
                <div
                    class="bg-white rounded-lg shadow-sm p-4 md:p-6 lg:p-8
                           lg:min-h-[calc(100vh-4rem)] lg:flex lg:flex-col lg:justify-center"
                >
                    <div class="flex justify-between items-center mb-3 md:mb-6">
                        <div class="flex items-center">
                            <img :src="TetherIcon" alt="USDT Logo" class="w-7 h-7 md:w-8 md:h-8 mr-2 md:mr-3" />
                            <h1 class="text-2xl font-bold">{{ t('Deposit') }}</h1>
                        </div>
                        <button
                            @click="fetchHistory"
                            class="flex items-center text-purple-600 font-medium text-xs md:text-sm hover:text-purple-700 transition-all duration-200"
                        >
                            <ClipboardDocumentListIcon class="w-4 h-4 md:w-5 md:h-5 mr-1" />
                            {{ t('History') }}
                        </button>
                    </div>

                    <div v-if="successMessage" class="mb-3 md:mb-4 p-2 bg-green-100 text-green-800 rounded-lg text-xs md:text-sm text-center">
                        {{ successMessage }}
                    </div>

                    <div class="mb-3 md:mb-6">
                        <label class="block text-xs md:text-sm font-medium text-gray-600 mb-1">{{ t('Network') }}</label>
                        <input disabled type="text" :value="depositDetails.network || 'TBD'" class="mt-1 block w-full rounded-full border-none bg-gray-50 text-gray-900 cursor-not-allowed text-sm md:text-base" />
                    </div>

                    <div class="mb-3 md:mb-6 flex justify-center">
                        <img
                            :src="depositDetails.qr_code ?
                             '/storage/' + depositDetails.qr_code : 'https://via.placeholder.com/150?text=No+QR+Code'"
                            alt="QR Code"
                            class="w-28 h-28 sm:w-32 sm:h-32 md:w-40 md:h-40"
                        />
                    </div>

                    <div class="mb-3 md:mb-6">
                        <label class="block text-xs md:text-sm font-medium text-gray-600 mb-1">{{ t('Address') }}</label>
                        <div class="flex items-center border border-gray-200 p-2 rounded-lg">
                            <span class="text-xs md:text-sm text-gray-800 flex-1 break-all">{{ depositDetails.address }}</span>
                            <button
                                @click="copyAddress"
                                class="ml-2 px-2 py-1 rounded-lg text-white text-xs md:text-sm"
                            >
                                {{ t('Copy Address') }}
                            </button>
                        </div>
                        <div v-if="copyError" class="mt-1 text-xs text-red-500">{{ copyError }}</div>
                    </div>

                    <div class="mb-3 md:mb-6">
                        <label class="block text-xs md:text-sm font-medium text-gray-600 mb-1">{{ t('Amount') }}</label>
                        <!-- If this is a VIP purchase, lock the amount field and show VIP note -->
                        <input
                            v-model="form.amount"
                            :disabled="!!form.vip"
                            type="number"
                            step="any"
                            :placeholder="t('Enter amount')"
                            class="mt-1 block w-full rounded-full border-none focus:ring-2 focus:ring-purple-300 text-gray-900 text-sm md:text-base py-2"
                        />
                        <div v-if="form.vip" class="mt-1 text-xs md:text-sm text-purple-700">{{ t('This deposit is for') }} <strong>{{ form.vip }}</strong>. {{ t('Amount is fixed for this VIP purchase.') }}</div>
                    </div>

                    <div class="mb-3 md:mb-6">
                        <label class="block text-xs md:text-sm font-medium text-gray-600 mb-1">{{ t('Upload Screenshot') }}</label>
                        <input
                            type="file"
                            @change="form.slip = $event.target.files[0]"
                            accept="image/*"
                            class="mt-1 block w-full rounded-full border-none focus:ring-2 focus:ring-purple-300 text-gray-900 text-sm md:text-base"
                        />
                    </div>

                    <button
                        @click="submitDeposit"
                        :disabled="!form.amount || !form.slip"
                        class="w-full px-3 md:px-4 py-2 md:py-3 bg-purple-600 text-white font-semibold text-sm md:text-lg rounded-full hover:bg-purple-700 transition-all duration-200"
                    >
                        {{ t('Submit Deposit') }}
                    </button>
                </div>
            </div>

            <!-- HISTORY MODAL -->
            <div v-if="showHistory" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white p-3 md:p-6 rounded-lg w-11/12 max-w-md max-h-[80vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-3">
                        <h2 class="text-base md:text-xl font-semibold text-gray-800">{{ t('Deposit History') }}</h2>
                        <button @click="showHistory = false" class="text-purple-600 hover:text-purple-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L5 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div v-if="historyError" class="text-red-500 text-xs md:text-sm mb-2">{{ historyError }}</div>
                    <div v-else-if="history.length === 0" class="text-gray-500 text-xs md:text-sm">{{ t('No deposit history available.') }}</div>
                    <div v-else class="space-y-2">
                        <div v-for="deposit in history" :key="deposit.id" class="p-2 md:p-3 border border-gray-200 rounded-lg">
                            <div class="flex justify-between">
                                <span class="text-gray-800 font-medium text-sm md:text-base">{{ deposit.amount }} USDT</span>
                                <span :class="{
                                    'text-yellow-600': deposit.status === 'pending',
                                    'text-green-600': deposit.status === 'approved',
                                    'text-red-600': deposit.status === 'rejected'
                                }" class="text-xs md:text-sm">
                                    {{ deposit.status.charAt(0).toUpperCase() + deposit.status.slice(1) }}
                                </span>
                            </div>
                            <div class="text-gray-500 text-xs md:text-sm mt-1">{{ new Date(deposit.created_at).toLocaleString() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>