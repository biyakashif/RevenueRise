<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { ClipboardDocumentListIcon } from '@heroicons/vue/24/solid';

const page = usePage();
const translations = computed(() => page.props.translations || {});
const t = (key) => translations.value[key] || key;

const props = defineProps({
    cryptos: { type: Array, required: true },
    cryptoDetails: { type: Object, default: () => ({}) },
    vip: { type: [String, null], default: null },
    prefillAmount: { type: [Number, String, null], default: null },
});

const cryptos = ref([...props.cryptos]);

const cryptoDetails = ref({ ...props.cryptoDetails });
const isLoadingCrypto = ref(false);
const cryptoError = ref(null);
const isLoadingHistory = ref(false);
const activeTab = ref(0);
const scrollPosition = ref(0);
const showLeftArrow = ref(false);
const showRightArrow = ref(false);
const tabContainer = ref(null);

// Computed property for current crypto details
const selectedCrypto = computed(() => {
    const currentCurrency = currentCrypto.value?.currency;
    return currentCurrency ? cryptoDetails.value[currentCurrency] : null;
});

// Computed properties for current crypto
const currentCrypto = computed(() => {
    return cryptos.value[activeTab.value] || null;
});

const selectedCurrency = computed(() => currentCrypto.value?.currency || '');
const walletAddress = computed(() => currentCrypto.value?.address || '');
const qrCodeUrl = computed(() => currentCrypto.value?.qr_code ? `/storage/${currentCrypto.value.qr_code}` : '');

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
            'XRP': 'xrp',
            'SHIB': 'shiba-inu',
            'MATIC': 'matic-network',
            'LTC': 'litecoin',
        };
        const id = symbolToId[symbol] || symbol.toLowerCase();

        // Use backend API instead of direct CoinGecko call to avoid CORS
        const response = await fetch(`/crypto-details?crypto_id=${id}`, {
            headers: {
                'X-CSRF-TOKEN': page.props.csrf_token
            }
        });
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        cryptoDetails.value[symbol] = {
            name: data.name,
            image: data.image,
            symbol: symbol,
        };
    } catch (error) {
        cryptoError.value = 'Failed to load crypto details. Using fallback data.';

        // Fallback to static data
        const fallback = {
            'BTC': { name: 'Bitcoin', image: 'https://assets.coingecko.com/coins/images/1/large/bitcoin.png' },
            'ETH': { name: 'Ethereum', image: 'https://assets.coingecko.com/coins/images/279/large/ethereum.png' },
            'USDT': { name: 'Tether', image: 'https://assets.coingecko.com/coins/images/325/large/Tether.png' },
            'BNB': { name: 'Binance Coin', image: 'https://assets.coingecko.com/coins/images/825/large/bnb-icon2_2x.png' },
            'ADA': { name: 'Cardano', image: 'https://assets.coingecko.com/coins/images/975/large/cardano.png' },
            'SOL': { name: 'Solana', image: 'https://assets.coingecko.com/coins/images/4128/large/solana.png' },
            'DOT': { name: 'Polkadot', image: 'https://assets.coingecko.com/coins/images/12171/large/polkadot.png' },
            'DOGE': { name: 'Dogecoin', image: 'https://assets.coingecko.com/coins/images/5/large/dogecoin.png' },
            'AVAX': { name: 'Avalanche', image: 'https://assets.coingecko.com/coins/images/12559/large/coin-round-red.png' },
            'LINK': { name: 'Chainlink', image: 'https://assets.coingecko.com/coins/images/877/large/chainlink-new-logo.png' },
            'USDC': { name: 'USD Coin', image: 'https://assets.coingecko.com/coins/images/6319/large/USD_Coin_icon.png' },
            'XRP': { name: 'XRP', image: 'https://assets.coingecko.com/coins/images/44/large/xrp-symbol-white-128.png' },
            'SHIB': { name: 'Shiba Inu', image: 'https://assets.coingecko.com/coins/images/11939/large/shiba.png' },
            'MATIC': { name: 'Polygon', image: 'https://assets.coingecko.com/coins/images/4713/large/matic-token-icon.png' },
            'LTC': { name: 'Litecoin', image: 'https://assets.coingecko.com/coins/images/2/large/litecoin.png' },
        };
        cryptoDetails.value[symbol] = {
            ...fallback[symbol],
            symbol: symbol,
        };
    } finally {
        isLoadingCrypto.value = false;
    }
};

// Fetch deposit history
const fetchHistory = async (showLoading = true, pageNum = 1, perPage = 20) => {
    if (showLoading) {
        isLoadingHistory.value = true;
        historyError.value = null;
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || page.props.csrf_token;
        const response = await fetch(`/deposit/history?page=${pageNum}&per_page=${perPage}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (pageNum === 1) {
            history.value = data.deposits || data.data || [];
        } else {
            // Append new data for pagination
            history.value = [...history.value, ...(data.deposits || data.data || [])];
        }
        
        historyPagination.value = data.pagination || data.meta || {};
    } catch (error) {
        console.error('History fetch error:', error);
        historyError.value = 'Failed to load deposit history.';
        if (pageNum === 1) {
            history.value = [];
        }
    } finally {
        isLoadingHistory.value = false;
    }
};

onMounted(() => {
    // Initialize with first crypto if available
    if (cryptos.value.length > 0) {
        form.value.crypto_id = cryptos.value[0].id;
    }
    
    // Initialize arrows after mount
    setTimeout(() => updateArrows(), 100);

    // Listen for crypto updates from admin
    if (window.Echo) {
        console.log('Setting up crypto updates listener');
        window.Echo.channel('crypto-updates')
            .listen('.crypto.updated', (e) => {
                console.log('Received crypto update:', e);
                cryptos.value = [...e.cryptos];
                // Reset active tab if current crypto no longer exists
                if (!cryptos.value[activeTab.value]) {
                    activeTab.value = 0;
                }
                // Update form crypto_id
                if (cryptos.value[activeTab.value]) {
                    form.value.crypto_id = cryptos.value[activeTab.value].id;
                }
                updateArrows();
            })
            .error((error) => {
                console.error('Echo channel error:', error);
            });
    } else {
        console.log('Echo not available');
    }

    // Listen for real-time deposit status updates
    const userId = page.props.auth?.user?.id;
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
            .listen('.App\\Events\\DepositCreated', (e) => {
                connectionStatus.value = 'connected';
                // Add new deposit to history immediately if history modal is open
                if (showHistory.value && e.deposit) {
                    // Add to the beginning of the array (most recent first)
                    history.value.unshift(e.deposit);
                    // Limit history to prevent memory issues (keep last 50)
                    if (history.value.length > 50) {
                        history.value = history.value.slice(0, 50);
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
    const userId = page.props.auth?.user?.id;
    if (userId && window.Echo) {
        window.Echo.private(`user.${userId}`)
            .stopListening('.App\\Events\\DepositStatusUpdated')
            .stopListening('.App\\Events\\DepositCreated');
    }
    // Clean up crypto updates listener
    if (window.Echo) {
        window.Echo.leaveChannel('crypto-updates');
    }
    // Stop history polling if running
    stopHistoryPolling();
});

const isCopied = ref(false);
const copyError = ref(null);
const showHistory = ref(false);
const history = ref([]);
const historyPagination = ref({});
const historyError = ref(null);
const successMessage = ref(null || page.props.flash?.success);
const formErrors = ref({});

// initialize form with prefilled amount if VIP purchase
const form = ref({
    amount: props.prefillAmount || '',
    slip: null,
    vip: props.vip || null,
    crypto_id: null,
});

const switchTab = (index) => {
    activeTab.value = index;
    if (cryptos.value[index]) {
        form.value.crypto_id = cryptos.value[index].id;
    }
};

const scrollLeft = () => {
    scrollPosition.value = Math.max(0, scrollPosition.value - 100);
    updateArrows();
};

const scrollRight = () => {
    const maxScroll = (cryptos.value.length * 80) - (tabContainer.value?.offsetWidth || 300);
    scrollPosition.value = Math.min(maxScroll, scrollPosition.value + 100);
    updateArrows();
};

const updateArrows = () => {
    if (!tabContainer.value) return;
    const containerWidth = tabContainer.value.offsetWidth;
    const contentWidth = cryptos.value.length * 80;
    const maxScroll = contentWidth - containerWidth;
    showLeftArrow.value = scrollPosition.value > 0;
    showRightArrow.value = scrollPosition.value < maxScroll && maxScroll > 0;
};

// Watch for active tab changes and update crypto_id
watch(activeTab, (newTab) => {
    if (cryptos.value[newTab]) {
        form.value.crypto_id = cryptos.value[newTab].id;
    }
});



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
    const address = walletAddress.value;
    copyError.value = null;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        try {
            await navigator.clipboard.writeText(address);
            isCopied.value = true;
            setTimeout(() => (isCopied.value = false), 2000);
            return;
        } catch (err) {
            copyError.value = t('Failed to copy address. Please copy manually.');
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
        copyError.value = t('Failed to copy address. Please copy manually.');
    }
};

const startHistoryPolling = () => {
    // Stop existing polling
    stopHistoryPolling();

    // Set status to polling
    connectionStatus.value = 'polling';

    // Start polling every 3 seconds when history modal is open
    historyPollingInterval = setInterval(() => {
        if (showHistory.value) {
            fetchHistory(false);
        }
    }, 3000); // 3 seconds
};

const stopHistoryPolling = () => {
    if (historyPollingInterval) {
        clearInterval(historyPollingInterval);
        historyPollingInterval = null;
    }
};

const loadMoreHistory = () => {
    if (historyPagination.value.current_page < historyPagination.value.last_page) {
        const nextPage = historyPagination.value.current_page + 1;
        fetchHistory(true, nextPage, historyPagination.value.per_page);
    }
};

const submitDeposit = () => {
    if (!form.value.crypto_id) {
        alert(t('Please select a cryptocurrency'));
        return;
    }
    
    const formData = new FormData();
    formData.append('amount', form.value.amount);
    formData.append('slip', form.value.slip);
    formData.append('crypto_id', form.value.crypto_id);
    formData.append('_token', page.props.csrf_token);
    if (form.value.vip) {
        formData.append('vip', form.value.vip);
    }
    router.post(route('deposit.store'), formData, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = 'Deposit submitted successfully! Awaiting approval.';
            setTimeout(() => (successMessage.value = null), 3000);
            form.value = { 
                amount: props.prefillAmount || '', 
                slip: null, 
                vip: props.vip || null,
                crypto_id: cryptos.value[activeTab.value]?.id || null
            };
            formErrors.value = {};
            // Refresh history if modal is open to show the new deposit
            if (showHistory.value) {
                fetchHistory(false);
            }
        },
        onError: (errors) => {
            formErrors.value = errors;
        },
    });
};
</script>

<template>
    <Head :title="t('Deposit')" />
    <AuthenticatedLayout>
        <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-sm p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-sm p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-lg border border-cyan-300/30 w-full flex flex-col justify-between">
                    
                    <!-- No Cryptocurrencies Available -->
                    <div v-if="cryptos.length === 0" class="text-center py-8">
                        <div class="text-slate-600 mb-4">
                            <svg class="w-16 h-16 mx-auto mb-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                            </svg>
                        </div>
                        <h2 class="text-lg font-semibold text-slate-700 mb-2">{{ t('No Cryptocurrencies Available') }}</h2>
                        <p class="text-slate-500 text-sm">{{ t('Please contact admin to add cryptocurrency options.') }}</p>
                    </div>
                    
                    <!-- Cryptocurrency Content -->
                    <div v-else>
                    <!-- Crypto Tabs -->
                    <div v-if="cryptos.length > 1" class="mb-3 relative">
                        <div ref="tabContainer" class="flex space-x-1 bg-white/20 p-1 rounded-lg overflow-hidden">
                            <div class="flex space-x-1 transition-transform duration-300" :style="{ transform: `translateX(-${scrollPosition}px)` }">
                                <button
                                    v-for="(crypto, index) in cryptos"
                                    :key="crypto.id"
                                    @click="switchTab(index)"
                                    :class="{
                                        'bg-white/80 text-slate-800 shadow-lg': activeTab === index,
                                        'text-slate-600 hover:text-slate-800 hover:bg-white/40': activeTab !== index
                                    }"
                                    class="flex items-center px-2 py-1 rounded-md text-xs font-medium transition-all duration-200 whitespace-nowrap flex-shrink-0"
                                >
                                    <div v-if="isLoadingCrypto && activeTab === index" class="w-4 h-4 mr-1 bg-white/30 rounded-full animate-pulse"></div>
                                    <img v-else-if="cryptoDetails[crypto.currency]" :src="cryptoDetails[crypto.currency].image" alt="Crypto Logo" class="w-4 h-4 mr-1" />
                                    <div v-else class="w-4 h-4 bg-gradient-to-r from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold text-[8px] mr-1">
                                        {{ crypto.currency.substring(0, 2) }}
                                    </div>
                                    {{ crypto.currency }}
                                </button>
                            </div>
                        </div>
                        <button v-if="showLeftArrow" @click="scrollLeft" class="absolute left-0 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/30 text-slate-700 w-5 h-5 flex items-center justify-center backdrop-blur-sm z-10 text-xs">
                            ‹
                        </button>
                        <button v-if="showRightArrow" @click="scrollRight" class="absolute right-0 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/30 text-slate-700 w-5 h-5 flex items-center justify-center backdrop-blur-sm z-10 text-xs">
                            ›
                        </button>
                    </div>
                    
                    <div class="flex justify-between items-center mb-3">
                        <div class="flex items-center">
                            <div v-if="isLoadingCrypto" class="w-5 h-5 mr-2 bg-white/30 rounded-full animate-pulse"></div>
                            <img v-else-if="selectedCrypto" :src="selectedCrypto.image" alt="Crypto Logo" class="w-5 h-5 mr-2" />
                            <div>
                                <h1 class="text-md font-bold text-slate-800 drop-shadow-sm">
                                    {{ isLoadingCrypto ? t('Loading...') : (selectedCrypto ? selectedCrypto.name : (selectedCurrency || t('Deposit'))) }}
                                </h1>
                                <div v-if="cryptoError" class="text-xs text-red-500 mt-0.5">{{ cryptoError }}</div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button
                                @click="() => { 
                                    if (!showHistory) { 
                                        showHistory = true;
                                        fetchHistory(); 
                                        startHistoryPolling(); 
                                    } else {
                                        showHistory = false;
                                        stopHistoryPolling();
                                    }
                                }"
                                class="flex items-center text-cyan-600 font-medium text-sm hover:text-cyan-700 transition-all duration-200 drop-shadow-sm"
                                :disabled="isLoadingHistory"
                            >
                                <ClipboardDocumentListIcon class="w-4 h-4 mr-1" />
                                {{ isLoadingHistory ? t('Loading...') : t('History') }}
                                <span v-if="history.length > 0" class="ml-1 bg-cyan-100/80 text-cyan-800 text-xs px-2 py-0.5 rounded-full">
                                    {{ history.length }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <div v-if="successMessage" class="mb-3 p-2 bg-emerald-100/80 text-emerald-800 rounded-xl text-sm text-center border border-emerald-200">
                        {{ successMessage }}
                    </div>

                    <div class="mb-3">
                        <label class="block text-xs font-medium text-slate-700 mb-1 drop-shadow-sm">{{ t('Network') }}</label>
                        <input disabled type="text" :value="currentCrypto?.network || 'TBD'" class="mt-1 block w-full h-8 rounded-lg bg-white/30 border-0 text-slate-700 cursor-not-allowed px-3 backdrop-blur-sm shadow-lg text-sm" />
                    </div>

                    <div class="flex justify-center">
                        <img
                            :src="qrCodeUrl || 'https://via.placeholder.com/150?text=No+QR+Code'"
                            alt="QR Code"
                            class="w-24 h-24 sm:w-32 sm:h-32"
                        />
                    </div>

                    <div class="mb-2">
                        <label class="block text-xs font-medium text-slate-700 mb-1 drop-shadow-sm">{{ t('Address') }}</label>
                        <div class="flex items-center bg-white/50 border border-white/40 p-1 sm:p-2 rounded-lg backdrop-blur-sm shadow-lg">
                            <span class="text-[10px] sm:text-[9px] md:text-xs lg:text-sm text-slate-800 flex-1 break-all">{{ walletAddress }}</span>
                            <button
                                @click="copyAddress"
                                class="ml-2 px-2 py-1 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white text-xs rounded-md transition-all duration-200 shadow-lg"
                            >
                                {{ t('Copy') }}
                            </button>
                        </div>
                        <div v-if="copyError" class="mt-1 text-xs text-red-500">{{ copyError }}</div>
                    </div>

                    <div class="mb-2">
                        <label class="block text-xs font-medium text-slate-700 mb-1 drop-shadow-sm">{{ t('Enter amount in') }} {{ selectedCurrency }}</label>
                        <input
                            v-model="form.amount"
                            :disabled="!!form.vip"
                            type="number"
                            step="any"
                            :placeholder="t('Enter amount')"
                            class="mt-1 block w-full h-8 rounded-lg border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-3 placeholder-slate-400 backdrop-blur-sm shadow-lg text-sm"
                            :class="form.vip ? 'bg-white/30' : 'bg-white/50'"
                        />
                        <div v-if="formErrors.amount" class="text-red-500 text-xs mt-1">{{ formErrors.amount }}</div>
                        <div v-if="form.vip" class="mt-1 text-xs text-cyan-700 bg-cyan-50/80 p-2 rounded-lg border border-cyan-200">{{ t('This deposit is for') }} <strong>{{ form.vip }}</strong>. {{ t('Amount is fixed for this VIP purchase.') }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-slate-700 mb-1 drop-shadow-sm">{{ t('Upload Screenshot') }}</label>
                        <input
                            type="file"
                            @change="form.slip = $event.target.files[0]"
                            accept="image/*"
                            class="mt-1 block w-full h-8 rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-3 backdrop-blur-sm shadow-lg file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100"
                        />
                        <div v-if="formErrors.slip" class="text-red-500 text-xs mt-1">{{ formErrors.slip }}</div>
                    </div>

                    <button
                        @click="submitDeposit"
                        :disabled="!form.amount || !form.slip || !currentCrypto"
                        class="w-full px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-medium rounded-lg transition-all duration-200 transform hover:scale-[1.02] shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                    >
                        {{ t('Submit Deposit') }}
                    </button>
                    </div>
                </div>
            </div>

            <!-- HISTORY MODAL -->
            <div v-if="showHistory" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                <div class="bg-gradient-to-br from-white/95 via-blue-50/90 to-indigo-50/95 backdrop-blur-sm p-6 rounded-2xl sm:rounded-3xl w-full max-w-md max-h-[80vh] overflow-y-auto shadow-2xl border border-white/40">
                    <div class="flex justify-between items-center mb-4">
                        <div class="flex items-center">
                            <h2 class="text-lg font-semibold text-slate-800 drop-shadow-sm">{{ t('Deposit History') }}</h2>
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
                            <button @click="() => { showHistory = false; stopHistoryPolling(); }" class="text-slate-400 hover:text-slate-600 transition-colors duration-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div v-if="isLoadingHistory" class="text-center py-4">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-cyan-600 mx-auto"></div>
                        <p class="text-sm text-slate-500 mt-2">{{ t('Loading history...') }}</p>
                    </div>
                    <div v-else-if="historyError" class="text-red-500 text-sm mb-2 p-3 bg-red-50 rounded-xl border border-red-200">{{ historyError }}</div>
                    <div v-else-if="history.length === 0" class="text-slate-500 text-sm text-center py-4">{{ t('No deposit history available.') }}</div>
                    <div v-else class="space-y-3">
                        <div v-for="deposit in history" :key="deposit.id" class="bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm p-4 rounded-xl border border-white/30 shadow-lg">
                            <div class="flex justify-between">
                                <span class="text-slate-800 font-medium text-sm">{{ deposit.amount }} {{ deposit.symbol ? deposit.symbol.toUpperCase() : 'USDT' }}</span>
                                <span :class="{
                                    'px-3 py-1 rounded-full text-xs font-medium shadow-sm': true,
                                    'bg-yellow-100/80 text-yellow-800 border border-yellow-200': deposit.status === 'pending',
                                    'bg-green-100/80 text-green-800 border border-green-200': deposit.status === 'approved',
                                    'bg-red-100/80 text-red-800 border border-red-200': deposit.status === 'rejected'
                                }">
                                    {{ deposit.status.charAt(0).toUpperCase() + deposit.status.slice(1) }}
                                </span>
                            </div>
                            <div class="text-slate-500 text-xs mt-1">{{ new Date(deposit.created_at).toLocaleString() }}</div>
                        </div>
                        
                        <!-- Load More Button -->
                        <div v-if="historyPagination.current_page < historyPagination.last_page" class="text-center pt-4">
                            <button
                                @click="loadMoreHistory"
                                :disabled="isLoadingHistory"
                                class="px-4 py-2 bg-cyan-500 hover:bg-cyan-600 text-white text-sm rounded-lg transition-colors duration-200 disabled:opacity-50"
                            >
                                {{ isLoadingHistory ? t('Loading...') : t('Load More') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>