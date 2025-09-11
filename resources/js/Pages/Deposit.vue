<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { ClipboardDocumentListIcon } from '@heroicons/vue/24/solid';

const page = usePage();
const translations = computed(() => page.props.translations || {});
const t = (key) => translations.value[key] || key;

const props = defineProps({
    depositDetails: { type: Object, required: true },
    vip: { type: [String, null], default: null },
    prefillAmount: { type: [Number, String, null], default: null },
});

const selectedCrypto = ref(null);
const isLoadingCrypto = ref(false);
const cryptoError = ref(null);
const isLoadingHistory = ref(false);

// Reactive variables for deposit details
const selectedCurrency = ref('');
const walletAddress = ref('');
const qrCodeUrl = ref('');

// History polling interval (only when modal is open)
let historyPollingInterval = null;

// Connection status for live indicator
const connectionStatus = ref('connecting'); // 'connecting', 'connected', 'disconnected', 'polling'

// Fetch crypto details from CoinGecko with loading states
const fetchCryptoDetails = async (symbol, showLoading = true) => {
    if (!symbol) return;

    if (showLoading) {
        isLoadingCrypto.value = true;
        cryptoError.value = null;
    }

    try {
        // Map symbol to CoinGecko ID
        const symbolToId = {
            'BTC': 'bitcoin',
            'ETH': 'ethereum',
            'USDT': 'tether',
            'BNB': 'binancecoin',
            'ADA': 'cardano',
            'SOL': 'solana',
            'DOT': 'polkadot',
            'DOGE': 'dogecoin',
            'AVAX': 'avalanche-2',
            'LINK': 'chainlink',
            'USDC': 'usd-coin',
            'XRP': 'ripple',
            'SHIB': 'shiba-inu',
            'MATIC': 'matic-network',
            'LTC': 'litecoin',
        };
        const id = symbolToId[symbol] || symbol.toLowerCase();

        // Use backend API instead of direct CoinGecko call to avoid CORS
        const response = await fetch(`/crypto-details?crypto_id=${id}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        selectedCrypto.value = {
            name: data.name,
            image: data.image,
            symbol: symbol,
        };
    } catch (error) {
        cryptoError.value = 'Failed to load crypto details. Using fallback data.';

        // Fallback to static data
        const fallback = {
            'BTC': { name: 'Bitcoin', image: 'https://cryptologos.cc/logos/bitcoin-btc-logo.png' },
            'ETH': { name: 'Ethereum', image: 'https://cryptologos.cc/logos/ethereum-eth-logo.png' },
            'USDT': { name: 'Tether', image: 'https://cryptologos.cc/logos/tether-usdt-logo.png' },
            'BNB': { name: 'Binance Coin', image: 'https://cryptologos.cc/logos/binance-coin-bnb-logo.png' },
            'ADA': { name: 'Cardano', image: 'https://cryptologos.cc/logos/cardano-ada-logo.png' },
            'SOL': { name: 'Solana', image: 'https://cryptologos.cc/logos/solana-sol-logo.png' },
            'DOT': { name: 'Polkadot', image: 'https://cryptologos.cc/logos/polkadot-new-dot-logo.png' },
            'DOGE': { name: 'Dogecoin', image: 'https://cryptologos.cc/logos/dogecoin-doge-logo.png' },
            'AVAX': { name: 'Avalanche', image: 'https://cryptologos.cc/logos/avalanche-avax-logo.png' },
            'LINK': { name: 'Chainlink', image: 'https://cryptologos.cc/logos/chainlink-link-logo.png' },
            'USDC': { name: 'USD Coin', image: 'https://cryptologos.cc/logos/usd-coin-usdc-logo.png' },
            'XRP': { name: 'XRP', image: 'https://cryptologos.cc/logos/xrp-xrp-logo.png' },
            'SHIB': { name: 'Shiba Inu', image: 'https://cryptologos.cc/logos/shiba-inu-shib-logo.png' },
            'MATIC': { name: 'Polygon', image: 'https://cryptologos.cc/logos/polygon-matic-logo.png' },
            'LTC': { name: 'Litecoin', image: 'https://cryptologos.cc/logos/litecoin-ltc-logo.png' },
        };
        selectedCrypto.value = {
            ...fallback[symbol],
            symbol: symbol,
        };
    } finally {
        isLoadingCrypto.value = false;
    }
};

// Fetch deposit history
const fetchHistory = async (showLoading = true) => {
    if (showLoading) {
        isLoadingHistory.value = true;
        historyError.value = null;
    }

    try {
        const response = await fetch('/deposit/history');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        history.value = data.deposits || [];
    } catch (error) {
        historyError.value = 'Failed to load deposit history.';
        history.value = [];
    } finally {
        isLoadingHistory.value = false;
    }
};

onMounted(() => {
    // If we have initial data from the server, use it
    if (props.depositDetails && props.depositDetails.currency) {
        selectedCurrency.value = props.depositDetails.currency;
        walletAddress.value = props.depositDetails.address;
        // Construct full QR code URL if qr_code path exists
        if (props.depositDetails.qr_code) {
            qrCodeUrl.value = `/storage/${props.depositDetails.qr_code}`;
        }
        fetchCryptoDetails(props.depositDetails.currency);
    }

    // Listen for real-time deposit status updates
    const userId = page.props.auth?.user?.mobile_number;
    if (userId && window.Echo) {
        connectionStatus.value = 'connecting';

        const channel = window.Echo.private(`user.${userId}`)
            .listen('.App\\Events\\DepositStatusUpdated', (e) => {
                connectionStatus.value = 'connected';
                // Update the deposit status in history if it's currently open
                if (history.value.length > 0) {
                    const updatedDeposit = history.value.find(d => d.id === e.deposit.id);
                    if (updatedDeposit) {
                        updatedDeposit.status = e.deposit.status;
                        // Force reactivity update
                        history.value = [...history.value];
                    }
                }
            })
            .error((error) => {
                connectionStatus.value = 'disconnected';
                // Start polling as fallback
                if (showHistory.value) {
                    startHistoryPolling();
                }
            });

        // Check connection after a delay
        setTimeout(() => {
            if (connectionStatus.value === 'connecting') {
                connectionStatus.value = 'polling';
                if (showHistory.value) {
                    startHistoryPolling();
                }
            }
        }, 3000);
    } else {
        // Echo is not available, use polling only
        connectionStatus.value = 'polling';
        if (showHistory.value) {
            startHistoryPolling();
        }
    }
});

onUnmounted(() => {
    // Clean up Echo listeners
    const userId = page.props.auth?.user?.mobile_number;
    if (userId && window.Echo) {
        window.Echo.private(`user.${userId}`)
            .stopListening('.App\\Events\\DepositStatusUpdated');
    }
    // Stop history polling if running
    stopHistoryPolling();
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

// Watch for currency changes and update crypto details
watch(() => props.depositDetails.currency, (newCurrency, oldCurrency) => {
    if (newCurrency && newCurrency !== oldCurrency) {
        fetchCryptoDetails(newCurrency);
    }
});

// Watch for depositDetails changes (in case the entire object changes)
watch(() => props.depositDetails, (newDetails, oldDetails) => {
    if (newDetails && newDetails.currency && (!oldDetails || newDetails.currency !== oldDetails.currency)) {
        selectedCurrency.value = newDetails.currency;
        walletAddress.value = newDetails.address;
        // Construct full QR code URL if qr_code path exists
        if (newDetails.qr_code) {
            qrCodeUrl.value = `/storage/${newDetails.qr_code}`;
        }
        fetchCryptoDetails(newDetails.currency);
    }
}, { deep: true });

// Watch for prefill amount changes
watch(() => props.prefillAmount, (newAmount) => {
    if (newAmount && newAmount !== form.value.amount) {
        form.value.amount = newAmount;
    }
});

// Watch for VIP changes
watch(() => props.vip, (newVip) => {
    if (newVip !== form.value.vip) {
        form.value.vip = newVip;
    }
});

const copyAddress = async () => {
    const address = walletAddress.value || props.depositDetails.address;
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

const startHistoryPolling = () => {
    // Stop existing polling
    stopHistoryPolling();

    // Set status to polling
    connectionStatus.value = 'polling';

    // Start polling every 10 seconds when history modal is open
    historyPollingInterval = setInterval(() => {
        if (showHistory.value) {
            fetchHistory(false);
        }
    }, 10000); // 10 seconds
};

const stopHistoryPolling = () => {
    if (historyPollingInterval) {
        clearInterval(historyPollingInterval);
        historyPollingInterval = null;
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
            // Refresh history if modal is open to show the new deposit
            if (showHistory.value) {
                fetchHistory(false);
            }
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
                            <div v-if="isLoadingCrypto" class="w-7 h-7 md:w-8 md:h-8 mr-2 md:mr-3 bg-gray-200 rounded-full animate-pulse"></div>
                            <img v-else-if="selectedCrypto" :src="selectedCrypto.image" alt="Crypto Logo" class="w-7 h-7 md:w-8 md:h-8 mr-2 md:mr-3" />
                            <div>
                                <h1 class="text-2xl font-bold">
                                    {{ isLoadingCrypto ? t('Loading...') : (selectedCrypto ? selectedCrypto.name : (selectedCurrency.value || props.depositDetails.currency || t('Deposit'))) }}
                                </h1>
                                <div v-if="cryptoError" class="text-xs text-red-500 mt-1">{{ cryptoError }}</div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button
                                @click="() => { fetchHistory(); showHistory = true; startHistoryPolling(); }"
                                class="flex items-center text-purple-600 font-medium text-xs md:text-sm hover:text-purple-700 transition-all duration-200"
                                :disabled="isLoadingHistory"
                            >
                                <ClipboardDocumentListIcon class="w-4 h-4 md:w-5 md:h-5 mr-1" />
                                {{ isLoadingHistory ? t('Loading...') : t('History') }}
                                <span v-if="history.length > 0" class="ml-1 bg-purple-100 text-purple-800 text-xs px-1 rounded">
                                    {{ history.length }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <div v-if="successMessage" class="mb-3 md:mb-4 p-2 bg-green-100 text-green-800 rounded-lg text-xs md:text-sm text-center">
                        {{ successMessage }}
                    </div>

                    <div class="mb-3 md:mb-6">
                        <label class="block text-xs md:text-sm font-medium text-gray-600 mb-1">{{ t('Network') }}</label>
                        <input disabled type="text" :value="props.depositDetails.network || 'TBD'" class="mt-1 block w-full rounded-full border-none bg-gray-50 text-gray-900 cursor-not-allowed text-sm md:text-base" />
                    </div>

                    <div class="mb-3 md:mb-6 flex justify-center">
                        <img
                            :src="qrCodeUrl || 'https://via.placeholder.com/150?text=No+QR+Code'"
                            alt="QR Code"
                            class="w-28 h-28 sm:w-32 sm:h-32 md:w-40 md:h-40"
                        />
                    </div>

                    <div class="mb-3 md:mb-6">
                        <label class="block text-xs md:text-sm font-medium text-gray-600 mb-1">{{ t('Address') }}</label>
                        <div class="flex items-center border border-gray-200 p-2 rounded-lg">
                            <span class="text-xs md:text-sm text-gray-800 flex-1 break-all">{{ walletAddress || props.depositDetails.address }}</span>
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
                        <div class="flex items-center">
                            <h2 class="text-base md:text-xl font-semibold text-gray-800">{{ t('Deposit History') }}</h2>
                            <span v-if="connectionStatus === 'connected'" class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <div class="w-2 h-2 bg-green-400 rounded-full mr-1 animate-pulse"></div>
                                Live
                            </span>
                            <span v-else-if="connectionStatus === 'polling'" class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <div class="w-2 h-2 bg-yellow-400 rounded-full mr-1 animate-pulse"></div>
                                Polling
                            </span>
                            <span v-else-if="connectionStatus === 'connecting'" class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <div class="w-2 h-2 bg-blue-400 rounded-full mr-1 animate-pulse"></div>
                                Connecting
                            </span>
                            <span v-else class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <div class="w-2 h-2 bg-red-400 rounded-full mr-1"></div>
                                Offline
                            </span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button @click="() => { showHistory = false; stopHistoryPolling(); }" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L5 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div v-if="isLoadingHistory" class="text-center py-4">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto"></div>
                        <p class="text-sm text-gray-500 mt-2">{{ t('Loading history...') }}</p>
                    </div>
                    <div v-else-if="historyError" class="text-red-500 text-xs md:text-sm mb-2 p-2 bg-red-50 rounded">{{ historyError }}</div>
                    <div v-else-if="history.length === 0" class="text-gray-500 text-xs md:text-sm text-center py-4">{{ t('No deposit history available.') }}</div>
                    <div v-else class="space-y-2">
                        <div v-for="deposit in history" :key="deposit.id" class="p-2 md:p-3 border border-gray-200 rounded-lg">
                            <div class="flex justify-between">
                                <span class="text-gray-800 font-medium text-sm md:text-base">{{ deposit.amount }} {{ deposit.symbol ? deposit.symbol.toUpperCase() : 'USDT' }}</span>
                                <span :class="{
                                    'text-yellow-600': deposit.status === 'pending',
                                    'text-green-600': deposit.status === 'approved',
                                    'text-red-600': deposit.status === 'rejected'
                                }" class="text-xs md:text-sm font-medium">
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