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
        <template #header>
            <h1 class="text-xl font-bold">Upload QR Code and Wallet Address</h1>
        </template>

        <div class="py-6">
            <div class="max-w-3xl mx-auto px-4">
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <form @submit.prevent="submit" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">Cryptocurrency</label>
                            <select
                                v-model="form.currency"
                                id="currency"
                                class="block w-full px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option v-for="crypto in availableCryptoList" :key="crypto.id" :value="crypto.symbol">
                                    {{ crypto.name }} ({{ crypto.symbol }})
                                </option>
                            </select>
                            <span v-if="form.errors.currency" class="text-red-500 text-xs">{{ form.errors.currency }}</span>
                        </div>

                        <div class="mb-3">
                            <label for="network" class="block text-sm font-medium text-gray-700 mb-1">Network</label>
                            <div class="flex gap-2">
                                <input
                                    v-model="form.network"
                                    type="text"
                                    id="network"
                                    placeholder="e.g., TRC20, ERC20, BEP20, Bitcoin"
                                    class="flex-1 px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                />
                                <button
                                    type="button"
                                    @click="resetToDefaultNetwork"
                                    class="px-3 py-1 bg-gray-100 text-gray-600 rounded-md text-sm hover:bg-gray-200"
                                    :disabled="!form.currency"
                                    title="Reset to default network for selected cryptocurrency"
                                >
                                    Reset
                                </button>
                            </div>
                            <span v-if="form.errors.network" class="text-red-500 text-xs">{{ form.errors.network }}</span>
                            <p class="text-xs text-gray-500 mt-1">
                                Network auto-updates when you select a currency. Use "Reset" to restore the default network.
                                <span v-if="networkManuallyModified" class="text-orange-600 font-medium">(Modified)</span>
                            </p>
                        </div>

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