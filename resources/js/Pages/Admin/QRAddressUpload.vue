<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    initialAddress: { type: String, default: '' },
    initialQrCode: { type: String, default: '' },
    initialCurrency: { type: String, default: '' },
    initialNetwork: { type: String, default: '' },
    cryptoList: { type: Array, default: () => [] },
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
    address: props.initialAddress,
    currency: props.initialCurrency,
    network: props.initialNetwork,
});

// Track if network was manually modified
const networkManuallyModified = ref(false);

// Initialize network modification status
if (props.initialCurrency && props.initialNetwork && networkMapping[props.initialCurrency]) {
    if (props.initialNetwork !== networkMapping[props.initialCurrency]) {
        networkManuallyModified.value = true;
    }
}

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
        <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 rounded-2xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto">
            <h1 class="text-lg font-bold text-slate-800 drop-shadow-sm mb-4">QR Code & Wallet Address</h1>
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
                            <span v-if="form.errors.currency" class="text-red-500 text-xs mt-1">{{ form.errors.currency }}</span>
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
                            <span v-if="form.errors.network" class="text-red-500 text-xs mt-1">{{ form.errors.network }}</span>
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
                            <span v-if="form.errors.qr_code" class="text-red-500 text-xs mt-1">{{ form.errors.qr_code }}</span>
                            <div v-if="initialQrCode" class="mt-2">
                                <img :src="'/storage/' + initialQrCode" alt="Current QR Code" class="w-32 h-32 rounded-xl shadow-lg" />
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
                            <span v-if="form.errors.address" class="text-red-500 text-xs mt-1">{{ form.errors.address }}</span>
                        </div>

                        <button
                            type="submit"
                            class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg text-sm font-medium transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl"
                            :disabled="form.processing"
                        >
                            Update
                        </button>
                    </form>
        </div>
    </AdminLayout>
</template>