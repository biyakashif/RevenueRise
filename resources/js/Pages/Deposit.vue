<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
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
const selectedNetwork = ref(null);
const availableNetworks = ref([]);

// Get unique currencies for tabs
const uniqueCurrencies = computed(() => {
    const seen = new Set();
    return cryptos.value.filter(crypto => {
        if (seen.has(crypto.currency)) return false;
        seen.add(crypto.currency);
        return true;
    });
});

const cryptoDetails = ref({ ...props.cryptoDetails });

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
    return uniqueCurrencies.value[activeTab.value] || null;
});

const selectedCurrency = computed(() => currentCrypto.value?.currency || '');

// Get current crypto based on selected network
const currentCryptoByNetwork = computed(() => {
    if (!currentCrypto.value) return null;
    
    const currency = currentCrypto.value.currency;
    if ((currency === 'USDT' || currency === 'USDC') && selectedNetwork.value) {
        return cryptos.value.find(c => c.currency === currency && c.network === selectedNetwork.value) || currentCrypto.value;
    }
    return currentCrypto.value;
});

const walletAddress = computed(() => currentCryptoByNetwork.value?.address || '');
const qrCodeUrl = computed(() => currentCryptoByNetwork.value?.qr_code ? `/storage/${currentCryptoByNetwork.value.qr_code}` : '');
const currentNetwork = computed(() => currentCryptoByNetwork.value?.network || '');

// History polling interval (only when modal is open)
let historyPollingInterval = null;

// Connection status for live indicator
const connectionStatus = ref('connecting'); // 'connecting', 'connected', 'disconnected', 'polling'



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
    // Preload all crypto icons immediately with static data
    const staticCryptoData = {
        'BTC': { name: 'Bitcoin', image: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Ccircle cx="16" cy="16" r="16" fill="%23F7931A"/%3E%3Cpath fill="%23FFF" fill-rule="nonzero" d="M23.189 14.02c.314-2.096-1.283-3.223-3.465-3.975l.708-2.84-1.728-.43-.69 2.765c-.454-.114-.92-.22-1.385-.326l.695-2.783L15.596 6l-.708 2.839c-.376-.086-.746-.17-1.104-.26l.002-.009-2.384-.595-.46 1.846s1.283.294 1.256.312c.7.175.826.638.805 1.006l-.806 3.235c.048.012.11.03.18.057l-.183-.045-1.13 4.532c-.086.212-.303.531-.793.41.018.025-1.256-.313-1.256-.313l-.858 1.978 2.25.561c.418.105.828.215 1.231.318l-.715 2.872 1.727.43.708-2.84c.472.127.93.245 1.378.357l-.706 2.828 1.728.43.715-2.866c2.948.558 5.164.333 6.097-2.333.752-2.146-.037-3.385-1.588-4.192 1.13-.26 1.98-1.003 2.207-2.538zm-3.95 5.538c-.533 2.147-4.148.986-5.32.695l.95-3.805c1.172.293 4.929.872 4.37 3.11zm.535-5.569c-.487 1.953-3.495.96-4.47.717l.86-3.45c.975.243 4.118.696 3.61 2.733z"/%3E%3C/g%3E%3C/svg%3E' },
        'ETH': { name: 'Ethereum', image: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Ccircle cx="16" cy="16" r="16" fill="%23627EEA"/%3E%3Cg fill="%23FFF" fill-rule="nonzero"%3E%3Cpath fill-opacity=".602" d="M16.498 4v8.87l7.497 3.35z"/%3E%3Cpath d="M16.498 4L9 16.22l7.498-3.35z"/%3E%3Cpath fill-opacity=".602" d="M16.498 21.968v6.027L24 17.616z"/%3E%3Cpath d="M16.498 27.995v-6.028L9 17.616z"/%3E%3Cpath fill-opacity=".2" d="M16.498 20.573l7.497-4.353-7.497-3.348z"/%3E%3Cpath fill-opacity=".602" d="M9 16.22l7.498 4.353v-7.701z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E' },
        'USDT': { name: 'Tether', image: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Ccircle cx="16" cy="16" r="16" fill="%2326A17B"/%3E%3Cpath fill="%23FFF" d="M17.922 17.383v-.002c-.11.008-.677.042-1.942.042-1.01 0-1.721-.03-1.971-.042v.003c-3.888-.171-6.79-.848-6.79-1.658 0-.809 2.902-1.486 6.79-1.66v2.644c.254.018.982.061 1.988.061 1.207 0 1.812-.05 1.925-.06v-2.643c3.88.173 6.775.85 6.775 1.658 0 .81-2.895 1.485-6.775 1.657m0-3.59v-2.366h5.414V7.819H8.595v3.608h5.414v2.365c-4.4.202-7.709 1.074-7.709 2.118 0 1.044 3.309 1.915 7.709 2.118v7.582h3.913v-7.584c4.393-.202 7.694-1.073 7.694-2.116 0-1.043-3.301-1.914-7.694-2.117"/%3E%3C/g%3E%3C/svg%3E' },
        'BNB': { name: 'Binance Coin', image: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"%3E%3Cg fill="none"%3E%3Ccircle cx="16" cy="16" r="16" fill="%23F3BA2F"/%3E%3Cpath fill="%23FFF" d="M12.116 14.404L16 10.52l3.886 3.886 2.26-2.26L16 6l-6.144 6.144 2.26 2.26zM6 16l2.26-2.26L10.52 16l-2.26 2.26L6 16zm6.116 1.596L16 21.48l3.886-3.886 2.26 2.259L16 26l-6.144-6.144-.003-.003 2.263-2.257zM21.48 16l2.26-2.26L26 16l-2.26 2.26L21.48 16zm-3.188-.002h.002V16L16 18.294l-2.291-2.29-.004-.004.004-.003.401-.402.195-.195L16 13.706l2.293 2.293z"/%3E%3C/g%3E%3C/svg%3E' },
        'USDC': { name: 'USD Coin', image: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Ccircle cx="16" cy="16" r="16" fill="%232775CA"/%3E%3Cpath fill="%23FFF" d="M15.75 27.5c5.385 0 9.75-4.365 9.75-9.75S21.135 8 15.75 8A9.752 9.752 0 006 17.75c0 5.385 4.365 9.75 9.75 9.75z"/%3E%3Cpath fill="%232775CA" d="M13.826 19.024c0 .818.615 1.112 1.635 1.112 1.491 0 2.394-.818 2.394-2.168 0-1.35-.903-2.106-2.394-2.106-.738 0-1.266.123-1.635.246v2.916zm0-5.586c.369-.123.82-.246 1.512-.246 1.389 0 2.19-.697 2.19-1.926 0-1.168-.8-1.803-2.19-1.803-1.02 0-1.512.287-1.512 1.044v2.93zm-1.86 6.318V13.07c0-1.577 1.143-2.333 3.372-2.333 2.415 0 3.927 1.23 3.927 3.254 0 1.168-.615 2.045-1.594 2.517 1.266.41 2.067 1.35 2.067 2.764 0 2.209-1.676 3.558-4.318 3.558-2.23 0-3.454-.82-3.454-2.476z"/%3E%3C/g%3E%3C/svg%3E' },
        'ADA': { name: 'Cardano', image: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"%3E%3Cg fill="none"%3E%3Ccircle cx="16" cy="16" r="16" fill="%230033AD"/%3E%3Cpath fill="%23FFF" d="M16 23.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm-5.5-2a1 1 0 100-2 1 1 0 000 2zm11 0a1 1 0 100-2 1 1 0 000 2zm-5.5-3.5a2 2 0 100-4 2 2 0 000 4zm-5-1a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2zm-5-3a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm-5.5-2a1 1 0 100-2 1 1 0 000 2zm11 0a1 1 0 100-2 1 1 0 000 2z"/%3E%3C/g%3E%3C/svg%3E' },
        'SOL': { name: 'Solana', image: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"%3E%3Cg fill="none"%3E%3Ccircle cx="16" cy="16" r="16" fill="url(%23a)"/%3E%3Cpath fill="%23FFF" d="M10.453 18.653c.107-.107.253-.16.4-.16h13.334c.266 0 .4.32.213.507l-2.72 2.72a.565.565 0 01-.4.16H7.946c-.266 0-.4-.32-.213-.507l2.72-2.72zm0-8.426c.107-.107.253-.16.4-.16h13.334c.266 0 .4.32.213.507l-2.72 2.72a.565.565 0 01-.4.16H7.946c-.266 0-.4-.32-.213-.507l2.72-2.72zm2.72 4.053a.565.565 0 00-.4-.16H7.946c-.266 0-.4.32-.213.507l2.72 2.72c.107.107.253.16.4.16h13.334c.266 0 .4-.32.213-.507l-2.72-2.72a.565.565 0 00-.4-.16H13.173z"/%3E%3Cdefs%3E%3ClinearGradient id="a" x1="3.009" x2="28.991" y1="2.699" y2="29.301" gradientUnits="userSpaceOnUse"%3E%3Cstop stop-color="%2300FFA3"/%3E%3Cstop offset="1" stop-color="%23DC1FFF"/%3E%3C/linearGradient%3E%3C/defs%3E%3C/g%3E%3C/svg%3E' },
    };
    
    cryptos.value.forEach(crypto => {
        if (!cryptoDetails.value[crypto.currency]) {
            cryptoDetails.value[crypto.currency] = staticCryptoData[crypto.currency] || { name: crypto.currency, image: '', symbol: crypto.currency };
        }
    });
    
    // Initialize with first crypto if available
    if (cryptos.value.length > 0) {
        const firstCrypto = cryptos.value[0];
        form.value.crypto_id = firstCrypto.id;
        
        // Initialize networks for USDT/USDC
        if (firstCrypto.currency === 'USDT' || firstCrypto.currency === 'USDC') {
            availableNetworks.value = cryptos.value
                .filter(c => c.currency === firstCrypto.currency)
                .map(c => c.network);
            selectedNetwork.value = firstCrypto.network;
        }
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
    const crypto = uniqueCurrencies.value[index];
    if (crypto) {
        form.value.crypto_id = crypto.id;
        
        // Update available networks for USDT/USDC
        if (crypto.currency === 'USDT' || crypto.currency === 'USDC') {
            availableNetworks.value = cryptos.value
                .filter(c => c.currency === crypto.currency)
                .map(c => c.network);
            selectedNetwork.value = crypto.network;
        } else {
            availableNetworks.value = [];
            selectedNetwork.value = null;
        }
    }
};

const switchNetwork = (network) => {
    selectedNetwork.value = network;
    const crypto = cryptos.value.find(c => 
        c.currency === currentCrypto.value.currency && c.network === network
    );
    if (crypto) {
        form.value.crypto_id = crypto.id;
    }
};

const scrollLeft = () => {
    scrollPosition.value = Math.max(0, scrollPosition.value - 100);
    updateArrows();
};

const scrollRight = () => {
    const maxScroll = (uniqueCurrencies.value.length * 80) - (tabContainer.value?.offsetWidth || 300);
    scrollPosition.value = Math.min(maxScroll, scrollPosition.value + 100);
    updateArrows();
};

const updateArrows = () => {
    if (!tabContainer.value) return;
    const containerWidth = tabContainer.value.offsetWidth;
    const contentWidth = uniqueCurrencies.value.length * 80;
    const maxScroll = contentWidth - containerWidth;
    showLeftArrow.value = scrollPosition.value > 0;
    showRightArrow.value = scrollPosition.value < maxScroll && maxScroll > 0;
};

// Watch for active tab changes and update crypto_id
watch(activeTab, (newTab) => {
    if (uniqueCurrencies.value[newTab]) {
        form.value.crypto_id = uniqueCurrencies.value[newTab].id;
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
        <!-- Floating Chat Icon -->
        <Link :href="route('chat.index')" class="fixed bottom-20 right-0 w-14 h-14 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-full flex items-center justify-center transition-all duration-300 transform hover:scale-110 z-50 shadow-lg">
            <i class="fas fa-headset text-lg"></i>
        </Link>
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
                    <div v-if="uniqueCurrencies.length > 1" class="mb-3 relative">
                        <div ref="tabContainer" class="flex space-x-1 bg-white/20 p-1 rounded-lg overflow-hidden">
                            <div class="flex space-x-1 transition-transform duration-300" :style="{ transform: `translateX(-${scrollPosition}px)` }">
                                <button
                                    v-for="(crypto, index) in uniqueCurrencies"
                                    :key="crypto.id"
                                    @click="switchTab(index)"
                                    :class="{
                                        'bg-white/80 text-slate-800 shadow-lg': activeTab === index,
                                        'text-slate-600 hover:text-slate-800 hover:bg-white/40': activeTab !== index
                                    }"
                                    class="flex items-center px-2 py-1 rounded-md text-xs font-medium transition-all duration-200 whitespace-nowrap flex-shrink-0"
                                >
                                    <img v-if="cryptoDetails[crypto.currency]" :src="cryptoDetails[crypto.currency].image" alt="Crypto Logo" class="w-4 h-4 mr-1" />
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
                            <img v-if="selectedCrypto" :src="selectedCrypto.image" alt="Crypto Logo" class="w-5 h-5 mr-2" />
                            <div>
                                <h1 class="text-md font-bold text-slate-800 drop-shadow-sm">
                                    {{ selectedCrypto ? selectedCrypto.name : (selectedCurrency || t('Deposit')) }}
                                </h1>
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
                        <select v-if="availableNetworks.length > 1" v-model="selectedNetwork" @change="switchNetwork(selectedNetwork)" class="mt-1 block w-full h-8 rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-3 backdrop-blur-sm shadow-lg text-sm">
                            <option v-for="network in availableNetworks" :key="network" :value="network">{{ network }}</option>
                        </select>
                        <input v-else disabled type="text" :value="currentNetwork || 'TBD'" class="mt-1 block w-full h-8 rounded-lg bg-white/30 border-0 text-slate-700 cursor-not-allowed px-3 backdrop-blur-sm shadow-lg text-sm" />
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
                            :step="(selectedCurrency === 'USDT' || selectedCurrency === 'USDC') ? '0.01' : 'any'"
                            :placeholder="t('Enter amount')"
                            @blur="(selectedCurrency === 'USDT' || selectedCurrency === 'USDC') && form.amount && (form.amount = parseFloat(form.amount).toFixed(2))"
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
                        <div v-for="deposit in history" :key="deposit.id" class="bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm p-2 rounded-xl border border-white/30 shadow-lg">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="text-slate-800 font-medium text-[11px]">
                                        {{ (deposit.symbol?.toUpperCase() === 'USDT' || deposit.symbol?.toUpperCase() === 'USDC') ? parseFloat(deposit.amount).toFixed(2) : deposit.amount }} 
                                        {{ deposit.symbol ? deposit.symbol.toUpperCase() : 'USDT' }}
                                    </span>
                                    <span v-if="(deposit.symbol?.toUpperCase() === 'USDT' || deposit.symbol?.toUpperCase() === 'USDC') && deposit.network" class="ml-1 px-1 py-0.5 bg-cyan-100 text-cyan-700 rounded text-[9px] font-medium">{{ deposit.network }}</span>
                                </div>
                                <span :class="{
                                    'px-2 py-0.5 rounded-full text-[9px] font-medium shadow-sm': true,
                                    'bg-yellow-100/80 text-yellow-800 border border-yellow-200': deposit.status === 'pending',
                                    'bg-green-100/80 text-green-800 border border-green-200': deposit.status === 'approved',
                                    'bg-red-100/80 text-red-800 border border-red-200': deposit.status === 'rejected'
                                }">
                                    {{ deposit.status.charAt(0).toUpperCase() + deposit.status.slice(1) }}
                                </span>
                            </div>
                            <div class="text-slate-500 text-[10px] mt-0.5">{{ new Date(deposit.created_at).toLocaleString() }}</div>
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