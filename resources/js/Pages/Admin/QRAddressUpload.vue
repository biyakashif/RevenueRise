<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    cryptoList: { type: Array, default: () => [] },
    existingCryptos: { type: Array, default: () => [] },
});

// Destructure props to ensure they're available in scope
const { cryptoList } = props;

// Fallback cryptocurrency list in case API fails
const fallbackCryptoList = [
    { id: 'bitcoin', symbol: 'BTC', name: 'Bitcoin', image: 'https://assets.coingecko.com/coins/images/1/large/bitcoin.png' },
    { id: 'ethereum', symbol: 'ETH', name: 'Ethereum', image: 'https://assets.coingecko.com/coins/images/279/large/ethereum.png' },
    { id: 'tether', symbol: 'USDT', name: 'Tether', image: 'https://assets.coingecko.com/coins/images/325/large/Tether.png' },
    { id: 'binancecoin', symbol: 'BNB', name: 'Binance Coin', image: 'https://assets.coingecko.com/coins/images/825/large/bnb-icon2_2x.png' },
    { id: 'solana', symbol: 'SOL', name: 'Solana', image: 'https://assets.coingecko.com/coins/images/4128/large/solana.png' },
    { id: 'usd-coin', symbol: 'USDC', name: 'USD Coin', image: 'https://assets.coingecko.com/coins/images/6319/large/USD_Coin_icon.png' },
    { id: 'cardano', symbol: 'ADA', name: 'Cardano', image: 'https://assets.coingecko.com/coins/images/975/large/cardano.png' },
    { id: 'xrp', symbol: 'XRP', name: 'XRP', image: 'https://assets.coingecko.com/coins/images/44/large/xrp-symbol-white-128.png' },
    { id: 'polkadot', symbol: 'DOT', name: 'Polkadot', image: 'https://assets.coingecko.com/coins/images/12171/large/polkadot.png' },
    { id: 'dogecoin', symbol: 'DOGE', name: 'Dogecoin', image: 'https://assets.coingecko.com/coins/images/5/large/dogecoin.png' },
    { id: 'avalanche-2', symbol: 'AVAX', name: 'Avalanche', image: 'https://assets.coingecko.com/coins/images/12559/large/coin-round-red.png' },
    { id: 'shiba-inu', symbol: 'SHIB', name: 'Shiba Inu', image: 'https://assets.coingecko.com/coins/images/11939/large/shiba.png' },
    { id: 'chainlink', symbol: 'LINK', name: 'Chainlink', image: 'https://assets.coingecko.com/coins/images/877/large/chainlink-new-logo.png' },
    { id: 'matic-network', symbol: 'MATIC', name: 'Polygon', image: 'https://assets.coingecko.com/coins/images/4713/large/matic-token-icon.png' },
    { id: 'litecoin', symbol: 'LTC', name: 'Litecoin', image: 'https://assets.coingecko.com/coins/images/2/large/litecoin.png' },
];

// Computed property to use cryptoList or fallback
const availableCryptoList = computed(() => {
    return cryptoList && cryptoList.length >= 15 ? cryptoList : fallbackCryptoList;
});

// Network mapping for different cryptocurrencies
const networkMapping = {
    'BTC': 'Bitcoin',
    'ETH': 'ERC20',
    'USDT': 'TRC20',
    'BNB': 'BEP20',
    'ADA': 'Cardano',
    'SOL': 'Solana',
    'DOT': 'Polkadot',
    'DOGE': 'Dogecoin',
    'AVAX': 'Avalanche',
    'LINK': 'ERC20',
    'USDC': 'ERC20',
    'XRP': 'Ripple',
    'SHIB': 'ERC20',
    'MATIC': 'Polygon',
    'LTC': 'Litecoin',
};

const form = useForm({
    qr_code: null,
    address: '',
    currency: '',
    network: '',
});

const editingCrypto = ref(null);
const showForm = ref(false);
const showDeleteModal = ref(false);
const deletingCrypto = ref(null);
const cryptoDetails = ref({});

const networkManuallyModified = ref(false);

// Static crypto icons for instant loading
const staticCryptoIcons = {
    'BTC': 'https://assets.coingecko.com/coins/images/1/large/bitcoin.png',
    'ETH': 'https://assets.coingecko.com/coins/images/279/large/ethereum.png',
    'USDT': 'https://assets.coingecko.com/coins/images/325/large/Tether.png',
    'BNB': 'https://assets.coingecko.com/coins/images/825/large/bnb-icon2_2x.png',
    'ADA': 'https://assets.coingecko.com/coins/images/975/large/cardano.png',
    'SOL': 'https://assets.coingecko.com/coins/images/4128/large/solana.png',
    'DOT': 'https://assets.coingecko.com/coins/images/12171/large/polkadot.png',
    'DOGE': 'https://assets.coingecko.com/coins/images/5/large/dogecoin.png',
    'AVAX': 'https://assets.coingecko.com/coins/images/12559/large/coin-round-red.png',
    'LINK': 'https://assets.coingecko.com/coins/images/877/large/chainlink-new-logo.png',
    'USDC': 'https://assets.coingecko.com/coins/images/6319/large/USD_Coin_icon.png',
    'XRP': 'https://assets.coingecko.com/coins/images/44/large/xrp-symbol-white-128.png',
    'SHIB': 'https://assets.coingecko.com/coins/images/11939/large/shiba.png',
    'MATIC': 'https://assets.coingecko.com/coins/images/4713/large/matic-token-icon.png',
    'LTC': 'https://assets.coingecko.com/coins/images/2/large/litecoin.png'
};

// Get crypto icon instantly
const getCryptoIcon = (symbol) => {
    if (!cryptoDetails.value[symbol]) {
        cryptoDetails.value[symbol] = { image: staticCryptoIcons[symbol] };
    }
    return cryptoDetails.value[symbol]?.image;
};

// Watch for currency changes and update network accordingly
watch(() => form.currency, (newCurrency) => {
    if (newCurrency && networkMapping[newCurrency]) {
        // Auto-update network only if it hasn't been manually modified
        if (!networkManuallyModified.value) {
            form.network = networkMapping[newCurrency];
        }
    }
});

// Watch for manual network changes
watch(() => form.network, (newNetwork) => {
    if (newNetwork && newNetwork !== networkMapping[form.currency]) {
        networkManuallyModified.value = true;
    }
});

// Function to reset network to default for selected currency
const resetToDefaultNetwork = () => {
    if (form.currency && networkMapping[form.currency]) {
        form.network = networkMapping[form.currency];
        networkManuallyModified.value = false;
    }
};

const onFileChange = (event) => {
    form.qr_code = event.target.files[0];
};

const submit = () => {
    if (editingCrypto.value) {
        form.transform((data) => ({
            ...data,
            _method: 'PUT'
        })).post(route('admin.qr-address-upload.update', editingCrypto.value.id), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                editingCrypto.value = null;
                showForm.value = false;
                networkManuallyModified.value = false;
            },
            onError: (errors) => {
                // Errors are displayed in form
            },
        });
    } else {
        form.post(route('admin.qr-address-upload.store'), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                editingCrypto.value = null;
                showForm.value = false;
                networkManuallyModified.value = false;
            },
            onError: (errors) => {
                // Errors are displayed in form
            },
        });
    }
};

const editCrypto = (crypto) => {
    editingCrypto.value = crypto;
    form.currency = crypto.currency;
    form.network = crypto.network;
    form.address = crypto.address;
    form.qr_code = null;
    showForm.value = true;
    
    if (crypto.network !== networkMapping[crypto.currency]) {
        networkManuallyModified.value = true;
    }
};

const deleteCrypto = (crypto) => {
    deletingCrypto.value = crypto;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    form.delete(route('admin.qr-address-upload.destroy', deletingCrypto.value.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingCrypto.value = null;
        }
    });
};

const cancelDelete = () => {
    showDeleteModal.value = false;
    deletingCrypto.value = null;
};

const addNew = () => {
    editingCrypto.value = null;
    form.reset();
    networkManuallyModified.value = false;
    showForm.value = true;
};

// Preload all crypto icons instantly
props.existingCryptos.forEach(crypto => {
    getCryptoIcon(crypto.currency);
});

const cancelEdit = () => {
    editingCrypto.value = null;
    form.reset();
    networkManuallyModified.value = false;
    showForm.value = false;
};
</script>

<template>
    <Head title="Crypto Management" />
    <AdminLayout>
        <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 rounded-2xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-lg font-bold text-slate-800 drop-shadow-sm">Crypto Management</h1>
                <button @click="addNew" class="px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-lg text-sm font-medium transition-all duration-300 transform hover:scale-[1.02] shadow-lg">
                    Add Crypto
                </button>
            </div>
            
            <!-- Existing Cryptocurrencies -->
            <div v-if="existingCryptos.length > 0" class="mb-6">
                <h2 class="text-md font-semibold text-slate-700 mb-3">Active Cryptocurrencies</h2>
                <div class="grid gap-3">
                    <div v-for="crypto in existingCryptos" :key="crypto.id" class="bg-white/50 backdrop-blur-sm p-4 rounded-xl border border-white/30 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <img v-if="getCryptoIcon(crypto.currency)" :src="getCryptoIcon(crypto.currency)" alt="Crypto Logo" class="w-8 h-8 rounded-full" />
                                <div v-else class="w-8 h-8 bg-gradient-to-r from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold text-xs">
                                    {{ crypto.currency.substring(0, 2) }}
                                </div>
                                <div>
                                    <div class="font-medium text-slate-800">{{ crypto.currency }}</div>
                                    <div class="text-sm text-slate-600">{{ crypto.network }}</div>
                                    <div class="text-xs text-slate-500 truncate max-w-[200px]">{{ crypto.address }}</div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <img v-if="crypto.qr_code" :src="'/storage/' + crypto.qr_code" alt="QR" class="w-8 h-8 rounded" />
                                <button @click="editCrypto(crypto)" class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded text-xs transition-colors">
                                    Edit
                                </button>
                                <button @click="deleteCrypto(crypto)" class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded text-xs transition-colors">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Modal -->
            <div v-if="showForm" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                <div class="bg-gradient-to-br from-white/95 via-blue-50/90 to-indigo-50/95 backdrop-blur-xl p-6 rounded-2xl w-full max-w-md max-h-[80vh] overflow-y-auto shadow-2xl border border-white/40">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-slate-800">{{ editingCrypto ? 'Edit' : 'Add' }} Cryptocurrency</h2>
                        <button @click="cancelEdit" class="text-slate-400 hover:text-slate-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <form @submit.prevent="submit" enctype="multipart/form-data" class="space-y-4">
                        <div>
                            <label for="currency" class="block text-sm font-medium text-slate-700 mb-2 drop-shadow-sm">Cryptocurrency</label>
                            <select
                                v-model="form.currency"
                                id="currency"
                                class="block w-full h-12 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 backdrop-blur-sm shadow-lg"
                            >
                                <option v-for="crypto in availableCryptoList" :key="crypto.id" :value="crypto.symbol">
                                    {{ crypto.name }} ({{ crypto.symbol }})
                                </option>
                            </select>
                            <div v-if="form.errors.currency" class="mt-1 text-xs text-red-600 bg-red-50/80 p-2 rounded border border-red-200">{{ form.errors.currency }}</div>
                        </div>

                        <div>
                            <label for="network" class="block text-sm font-medium text-slate-700 mb-2 drop-shadow-sm">Network</label>
                            <div class="flex gap-2">
                                <input
                                    v-model="form.network"
                                    type="text"
                                    id="network"
                                    placeholder="e.g., TRC20, ERC20, BEP20, Bitcoin"
                                    class="flex-1 h-12 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 placeholder-slate-400 backdrop-blur-sm shadow-lg"
                                />
                                <button
                                    type="button"
                                    @click="resetToDefaultNetwork"
                                    class="px-4 py-2 bg-gradient-to-r from-white/60 to-white/40 hover:from-white/70 hover:to-white/50 text-slate-700 rounded-xl text-sm font-medium transition-all duration-200 shadow-lg backdrop-blur-sm border border-white/30"
                                    :disabled="!form.currency"
                                    title="Reset to default network for selected cryptocurrency"
                                >
                                    Reset
                                </button>
                            </div>
                            <div v-if="form.errors.network" class="mt-1 text-xs text-red-600 bg-red-50/80 p-2 rounded border border-red-200">{{ form.errors.network }}</div>
                            <p class="text-xs text-slate-500 mt-1">
                                Network auto-updates when you select a currency. Use "Reset" to restore the default network.
                                <span v-if="networkManuallyModified" class="text-orange-600 font-medium">(Modified)</span>
                            </p>
                        </div>

                        <div>
                            <label for="qr_code" class="block text-sm font-medium text-slate-700 mb-2 drop-shadow-sm">QR Code Image</label>
                            <input
                                type="file"
                                id="qr_code"
                                accept="image/png, image/jpeg, image/jpg"
                                @change="onFileChange"
                                class="block w-full h-12 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 backdrop-blur-sm shadow-lg file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100"
                            />
                            <div v-if="form.errors.qr_code" class="mt-1 text-xs text-red-600 bg-red-50/80 p-2 rounded border border-red-200">{{ form.errors.qr_code }}</div>
                            <div v-if="editingCrypto && editingCrypto.qr_code" class="mt-2">
                                <img :src="'/storage/' + editingCrypto.qr_code" alt="Current QR Code" class="w-32 h-32 rounded-xl shadow-lg" />
                            </div>
                        </div>

                        <div>
                            <label for="address" class="block text-sm font-medium text-slate-700 mb-2 drop-shadow-sm">Wallet Address</label>
                            <input
                                v-model="form.address"
                                type="text"
                                id="address"
                                class="block w-full h-12 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 placeholder-slate-400 backdrop-blur-sm shadow-lg"
                            />
                            <div v-if="form.errors.address" class="mt-1 text-xs text-red-600 bg-red-50/80 p-2 rounded border border-red-200">{{ form.errors.address }}</div>
                        </div>

                        <div class="flex space-x-2 pt-2">
                            <button
                                type="submit"
                                class="flex-1 px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg text-sm font-medium transition-all duration-300 shadow-lg"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Processing...' : (editingCrypto ? 'Update' : 'Add') }}
                            </button>
                            <button
                                type="button"
                                @click="cancelEdit"
                                class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg text-sm font-medium transition-colors"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Delete Confirmation Modal -->
            <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                <div class="bg-gradient-to-br from-white/95 via-red-50/90 to-red-100/95 backdrop-blur-xl p-6 rounded-2xl w-full max-w-sm shadow-2xl border border-white/40">
                    <div class="text-center">
                        <div class="w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-800 mb-2">Delete Cryptocurrency</h3>
                        <p class="text-slate-600 mb-4">Are you sure you want to delete <strong>{{ deletingCrypto?.currency }}</strong>? This action cannot be undone.</p>
                        <div class="flex space-x-2">
                            <button @click="cancelDelete" class="flex-1 px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg text-sm font-medium transition-colors">
                                Cancel
                            </button>
                            <button @click="confirmDelete" class="flex-1 px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm font-medium transition-colors" :disabled="form.processing">
                                {{ form.processing ? 'Deleting...' : 'Delete' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>