<script setup>

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, router, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
const page = usePage();
const translations = computed(() => page.props.translations || {});
const user = computed(() => page.props.auth?.user);
const t = (key) => translations.value[key] || key;

const props = defineProps({
  balances: Object,
  withdrawals: Array,
});

const amount = ref('');
const cryptoWallet = ref('');
const selectedCrypto = ref(null);
const conversionRates = ref({});
const availableCryptos = ref([]);
const showDropdown = ref(false);
const dropdownTop = ref(0);
const dropdownLeft = ref(0);
const dropdownWidth = ref(0);
const dropdownRef = ref(null);

// Static crypto icons
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

const getCryptoIcon = (symbol) => staticCryptoIcons[symbol] || null;

const fetchAvailableCryptos = async () => {
  try {
    const response = await fetch('/admin/qr-address-upload');
    if (response.ok) {
      const html = await response.text();
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');
      const scriptTag = doc.querySelector('script[data-page]');
      if (scriptTag) {
        const pageData = JSON.parse(scriptTag.textContent);
        availableCryptos.value = pageData.props.existingCryptos || [];
        return;
      }
    }
  } catch (error) {
    console.error('Failed to fetch available cryptos:', error);
  }
  
  // Fallback list with all cryptocurrencies from QRAddressUpload.vue
  availableCryptos.value = [
    { id: 1, currency: 'BTC', network: 'Bitcoin' },
    { id: 2, currency: 'ETH', network: 'ERC20' },
    { id: 3, currency: 'USDT', network: 'TRC20' },
    { id: 4, currency: 'BNB', network: 'BEP20' },
    { id: 5, currency: 'SOL', network: 'Solana' },
    { id: 6, currency: 'USDC', network: 'ERC20' },
    { id: 7, currency: 'ADA', network: 'Cardano' },
    { id: 8, currency: 'XRP', network: 'Ripple' },
    { id: 9, currency: 'DOT', network: 'Polkadot' },
    { id: 10, currency: 'DOGE', network: 'Dogecoin' },
    { id: 11, currency: 'AVAX', network: 'Avalanche' },
    { id: 12, currency: 'SHIB', network: 'ERC20' },
    { id: 13, currency: 'LINK', network: 'ERC20' },
    { id: 14, currency: 'MATIC', network: 'Polygon' },
    { id: 15, currency: 'LTC', network: 'Litecoin' }
  ];
};

const fetchConversionRates = async () => {
  try {
    const cryptos = ['BTC', 'ETH', 'USDT', 'BNB', 'SOL', 'USDC', 'ADA', 'XRP', 'DOT', 'DOGE', 'AVAX', 'SHIB', 'LINK', 'MATIC', 'LTC'];
    const rates = {};
    
    // Fetch rates from Coinbase API (supports CORS)
    for (const crypto of cryptos) {
      try {
        const response = await fetch(`https://api.coinbase.com/v2/exchange-rates?currency=${crypto}`);
        if (response.ok) {
          const data = await response.json();
          rates[crypto] = parseFloat(data.data.rates.USD);
        }
      } catch (err) {
        console.error(`Failed to fetch ${crypto} rate:`, err);
      }
    }
    
    // Set USDT and USDC to 1 if not fetched
    rates['USDT'] = rates['USDT'] || 1;
    rates['USDC'] = rates['USDC'] || 1;
    
    conversionRates.value = rates;
  } catch (error) {
    console.error('Failed to fetch live rates:', error);
  }
};

const getConvertedAmount = computed(() => {
  if (!selectedCrypto.value || !amount.value) return '0.00';
  const rate = conversionRates.value[selectedCrypto.value.currency] || 1;
  return (parseFloat(amount.value) / rate).toFixed(6);
});

// Polling for live updates
let pollingInterval = null;

const fetchStatus = async () => {
  try {
    const response = await fetch('/withdraw', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    });
    
    if (!response.ok) return;
    const data = await response.json();
    if (data.balances) {
      // update balances in-place with formatted values
      props.balances.usdt_balance = Number(data.balances.usdt_balance).toFixed(2);
      props.balances.withdraw_limit = Number(data.balances.withdraw_limit).toFixed(2);
    }
    if (Array.isArray(data.withdrawals)) {
      // Format amounts before updating array
      const formattedWithdrawals = data.withdrawals.map(w => ({
        ...w,
        amount_withdraw: Number(w.amount_withdraw).toFixed(2)
      }));
      props.withdrawals.splice(0, props.withdrawals.length, ...formattedWithdrawals);
    }
  } catch (err) {
    console.error('Error fetching status:', err);
  }
};

onMounted(() => {
  // Load fallback list with all cryptocurrencies from QRAddressUpload.vue
  availableCryptos.value = [
    { id: 1, currency: 'BTC', network: 'Bitcoin' },
    { id: 2, currency: 'ETH', network: 'ERC20' },
    { id: 3, currency: 'USDT', network: 'TRC20' },
    { id: 4, currency: 'BNB', network: 'BEP20' },
    { id: 5, currency: 'SOL', network: 'Solana' },
    { id: 6, currency: 'USDC', network: 'ERC20' },
    { id: 7, currency: 'ADA', network: 'Cardano' },
    { id: 8, currency: 'XRP', network: 'Ripple' },
    { id: 9, currency: 'DOT', network: 'Polkadot' },
    { id: 10, currency: 'DOGE', network: 'Dogecoin' },
    { id: 11, currency: 'AVAX', network: 'Avalanche' },
    { id: 12, currency: 'SHIB', network: 'ERC20' },
    { id: 13, currency: 'LINK', network: 'ERC20' },
    { id: 14, currency: 'MATIC', network: 'Polygon' },
    { id: 15, currency: 'LTC', network: 'Litecoin' }
  ];
  // Initial fetch
  fetchStatus();
  fetchAvailableCryptos();
  fetchConversionRates();
  // Set up polling every 10 seconds for status and every 30 seconds for rates
  pollingInterval = setInterval(() => {
    fetchStatus();
    fetchConversionRates(); // Update rates every 10 seconds
  }, 10000);

  // Listen for live balance updates
  try {
    if (window.Echo && user.value && user.value.id) {
      window.Echo.private('user.' + user.value.id)
        .listen('.balance-updated', (e) => {
          props.balances.usdt_balance = Number(e.balance).toFixed(2);
        });
    }
  } catch (error) {
    console.error('Error setting up Echo listener for balance:', error);
  }
  
  // Add click outside listener
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  clearInterval(pollingInterval);
  document.removeEventListener('click', handleClickOutside);
  try {
    if (window.Echo && user.value && user.value.id) {
      window.Echo.leave('user.' + user.value.id);
    }
  } catch (error) {
    console.error('Error cleaning up Echo listener for balance:', error);
  }
});

const submit = (e) => {
  // open confirmation modal instead of immediate submit
  showModal.value = true;
};

const withdrawPassword = ref('');
const showModal = ref(false);
const modalError = ref('');

const validateThenSubmit = async () => {
  modalError.value = '';
  
  if (!selectedCrypto.value) {
    modalError.value = 'Please select a cryptocurrency';
    return;
  }
  
  const cryptoAmount = getConvertedAmount.value;
  const payload = {
    amount_withdraw: amount.value, // USDT amount for validation
    crypto_symbol: selectedCrypto.value.currency,
    crypto_amount: cryptoAmount,   // Converted crypto amount for storage
    crypto_wallet: cryptoWallet.value,
    withdraw_password: withdrawPassword.value
  };
  
  // Backend should:
  // 1. Validate amount_withdraw against minimum limit (200 >= 30)
  // 2. Store crypto_amount in database amount_withdraw field (0.076923)
  // 3. Store crypto_symbol in database crypto_symbol field (ETH)

  router.post('/withdraw', payload, {
    preserveState: true,
    preserveScroll: true,
    onError: (errors) => {
      // Handle validation errors - check all possible error fields
      if (errors.amount_withdraw) {
        modalError.value = Array.isArray(errors.amount_withdraw) 
          ? errors.amount_withdraw[0] 
          : errors.amount_withdraw;
      } else if (errors.withdraw_password) {
        modalError.value = Array.isArray(errors.withdraw_password) 
          ? errors.withdraw_password[0] 
          : errors.withdraw_password;
      } else if (errors.crypto_wallet) {
        modalError.value = Array.isArray(errors.crypto_wallet) 
          ? errors.crypto_wallet[0] 
          : errors.crypto_wallet;
      } else if (errors.crypto_symbol) {
        modalError.value = Array.isArray(errors.crypto_symbol) 
          ? errors.crypto_symbol[0] 
          : errors.crypto_symbol;
      } else if (errors.crypto_amount) {
        modalError.value = Array.isArray(errors.crypto_amount) 
          ? errors.crypto_amount[0] 
          : errors.crypto_amount;
      } else if (errors.error) {
        modalError.value = Array.isArray(errors.error) 
          ? errors.error[0] 
          : errors.error;
      } else if (errors.message) {
        modalError.value = errors.message;
      } else {
        // Show the first available error message
        const firstError = Object.values(errors)[0];
        modalError.value = Array.isArray(firstError) ? firstError[0] : firstError;
      }
    },
    onSuccess: (page) => {
      // Check for flash error messages even on "success"
      if (page.props.flash && page.props.flash.error) {
        modalError.value = page.props.flash.error;
        return;
      }
      
      // Check for flash success message to confirm actual success
      if (page.props.flash && page.props.flash.success) {
        showModal.value = false;
        withdrawPassword.value = '';
        amount.value = '';
        cryptoWallet.value = '';
        selectedCrypto.value = null;
        fetchStatus(); // Refresh data after successful withdrawal
      }
    },
  });
};

const setMax = () => {
  const bal = props.balances?.usdt_balance ?? 0;
  amount.value = Number(bal).toFixed(2);
};

// helper to format USDT amounts with 2 decimal places
const formatUSDT = (val) => {
  if (val === null || val === undefined || val === '') return '0.00';
  const n = Number(val);
  if (!isFinite(n)) return '0.00';
  return n.toFixed(2);
};

const toggleDropdown = () => {
  if (!showDropdown.value && dropdownRef.value) {
    const rect = dropdownRef.value.getBoundingClientRect();
    dropdownTop.value = rect.bottom + window.scrollY + 4;
    dropdownLeft.value = rect.left + window.scrollX;
    dropdownWidth.value = rect.width;
  }
  showDropdown.value = !showDropdown.value;
};

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    showDropdown.value = false;
  }
};
</script>

<template>
  <Head :title="t('Withdraw')" />
  <AuthenticatedLayout>
    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto flex items-center justify-center">
      <div class="w-full">
        <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-2 sm:p-4 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30 mb-4 sm:mb-6">
          <div class="mb-2 sm:mb-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-4 sm:mb-6">
                <div class="flex items-center gap-3">
                    <Link href="/profile" class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-slate-500/80 to-gray-600/80 hover:from-slate-600/90 hover:to-gray-700/90 rounded-xl shadow-lg transition-all duration-200 transform hover:scale-105">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold text-slate-800 drop-shadow-sm">{{ t('Withdraw') }}</h1>
                        <p class="text-xs sm:text-sm text-slate-600 font-medium">{{ t('Withdraw your funds') }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm p-3 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-white/30">
              <div class="flex justify-between w-full items-center">
                <p class="text-xs text-slate-600 font-medium">{{ t('Available USDT') }}</p>
                <p class="text-lg font-bold text-slate-800 flex items-center gap-1">
                  {{ formatUSDT(balances.usdt_balance) }}
                  <span class="text-xs text-slate-500">USDT</span>
                </p>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30 mb-4 sm:mb-6 relative">
          <div class="absolute top-3 right-3">
            <span class="text-xs text-slate-500 font-medium bg-white/30 px-2 py-1 rounded-full backdrop-blur-sm">
              Min Limit {{ formatUSDT(balances.withdraw_limit) }}
            </span>
          </div>
          <form @submit.prevent="submit" class="space-y-3">
          <div>
            <label class="block text-xs font-medium text-slate-700 mb-1 drop-shadow-sm">{{ t('USDT Amount') }}</label>
            <div class="flex">
              <input name="amount_withdraw" v-model="amount" type="number" step="any" required class="flex-1 h-10 rounded-l-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-3 placeholder-slate-400 backdrop-blur-sm shadow-lg text-sm" />
              <button type="button" @click="setMax" class="bg-gradient-to-r from-white/60 to-white/40 hover:from-white/70 hover:to-white/50 px-3 rounded-r-xl border-l border-white/30 text-slate-700 font-medium transition-all duration-200 shadow-lg text-sm">{{ t('MAX') }}</button>
            </div>
          </div>

          <!-- Crypto Selection -->
          <div>
            <label class="block text-xs font-medium text-slate-700 mb-1 drop-shadow-sm">{{ t('Select Cryptocurrency') }}</label>
            <div class="relative mb-2">
              <div @click="showDropdown = !showDropdown" class="w-full h-10 rounded-xl bg-gradient-to-r from-white/60 to-white/40 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-3 backdrop-blur-sm shadow-lg text-sm cursor-pointer flex items-center justify-between">
                <div v-if="selectedCrypto" class="flex items-center">
                  <img v-if="getCryptoIcon(selectedCrypto.currency)" :src="getCryptoIcon(selectedCrypto.currency)" alt="Crypto Logo" class="w-6 h-6 rounded-full mr-2" />
                  <span>{{ selectedCrypto.currency }} - {{ selectedCrypto.network }}</span>
                </div>
                <span v-else class="text-slate-500">{{ t('Select cryptocurrency to withdraw') }}</span>
                <svg class="w-5 h-5 text-slate-500 transition-transform" :class="{ 'rotate-180': showDropdown }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
              <div v-if="showDropdown" class="absolute z-[99999] w-full mt-1 bg-white/95 backdrop-blur-xl rounded-xl shadow-2xl border border-cyan-300/30 max-h-32 overflow-y-auto">
                <div v-for="crypto in availableCryptos" :key="crypto.id" @click="selectedCrypto = crypto; showDropdown = false" class="flex items-center py-0.5 px-2 hover:bg-gradient-to-r hover:from-white/30 hover:to-white/20 cursor-pointer transition-all">
                  <img v-if="getCryptoIcon(crypto.currency)" :src="getCryptoIcon(crypto.currency)" alt="Crypto Logo" class="w-4 h-4 rounded-full mr-2" />
                  <div v-else class="w-4 h-4 bg-gradient-to-r from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold text-xs mr-2">
                    {{ crypto.currency.substring(0, 1) }}
                  </div>
                  <div>
                    <div class="font-medium text-slate-800 text-sm">{{ crypto.currency }}</div>
                    <div class="text-xs text-slate-600">{{ crypto.network }}</div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Live Conversion Display -->
            <div v-if="selectedCrypto && amount" class="bg-gradient-to-r from-blue-50/80 to-cyan-50/80 p-2 rounded-lg border border-blue-200/50 mb-2">
              <div class="flex items-center justify-between">
                <span class="text-xs text-slate-600">{{ t('You will receive') }}:</span>
                <div class="flex items-center">
                  <img v-if="getCryptoIcon(selectedCrypto.currency)" :src="getCryptoIcon(selectedCrypto.currency)" alt="Crypto Logo" class="w-4 h-4 mr-1" />
                  <span class="font-bold text-slate-800 text-sm">{{ getConvertedAmount }} {{ selectedCrypto.currency }}</span>
                </div>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-700 mb-1 drop-shadow-sm">
              {{ t('Recipient') }} {{ selectedCrypto ? selectedCrypto.currency : 'USDT' }} {{ t('Wallet Address') }}
            </label>
            <input name="crypto_wallet" v-model="cryptoWallet" type="text" required class="w-full h-10 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-3 placeholder-slate-400 backdrop-blur-sm shadow-lg text-sm" />
          </div>

          <div class="text-right">
            <button @click="submit" class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg text-sm font-medium transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl">{{ t('Withdraw') }}</button>
          </div>
          </form>
        </div>

      <!-- Withdraw Password Modal -->
      <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-start justify-center pt-40 z-50 p-4">
        <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl sm:rounded-3xl p-6 w-full max-w-md shadow-2xl border border-white/20">
          <h3 class="text-lg font-semibold mb-4 text-white drop-shadow-sm">{{ t('Confirm Withdraw') }}</h3>
          <p class="text-sm text-white/80 mb-2 drop-shadow-sm">{{ t('Enter withdraw password') }}</p>
          <input v-model="withdrawPassword" type="password" class="w-full h-8 sm:h-12 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 placeholder-slate-400 backdrop-blur-sm shadow-lg mb-2" autocomplete="off" />
          <div v-if="modalError" class="text-xs text-red-600 mb-2 bg-red-50 p-2 rounded-lg border border-red-200">{{ modalError }}</div>
          <div class="flex justify-end space-x-2">
            <button @click="showModal = false; withdrawPassword = ''; modalError = ''" class="px-4 py-2 bg-white/50 hover:bg-white/70 rounded-xl text-sm border border-white/40 transition-all duration-200 text-slate-700">{{ t('Cancel') }}</button>
            <button @click="validateThenSubmit" class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl text-sm transition-all duration-200 shadow-lg">{{ t('Confirm Withdraw') }}</button>
          </div>
        </div>
      </div>

        <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30">
          <h2 class="text-lg font-semibold text-slate-800 drop-shadow-sm mb-4">{{ t('Withdrawal History') }}</h2>
          <div v-if="withdrawals.length === 0" class="text-slate-500 mt-2 drop-shadow-sm">{{ t('No withdrawals yet') }}.</div>
          <div v-else class="space-y-3">
            <div v-for="w in withdrawals" :key="w.id" class="bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm rounded-xl p-2 sm:p-4 border border-white/30 shadow-lg">
              <div class="flex justify-between items-center">
                <div class="text-[10px] sm:text-sm">
                  <div class="flex items-center mb-1">
                    <img v-if="getCryptoIcon(w.crypto_symbol || 'USDT')" :src="getCryptoIcon(w.crypto_symbol || 'USDT')" alt="Crypto Logo" class="w-4 h-4 mr-2" />
                    <div v-else class="w-4 h-4 bg-gradient-to-r from-cyan-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold text-[8px] mr-2">
                      {{ (w.crypto_symbol || 'USDT').substring(0, 2) }}
                    </div>
                    <span class="text-slate-800 font-semibold drop-shadow-sm">
                      <strong>{{ w.crypto_amount ? (w.crypto_symbol === 'USDT' ? formatUSDT(w.crypto_amount) : Number(w.crypto_amount).toFixed(6)) : formatUSDT(w.amount_withdraw) }}</strong> {{ w.crypto_symbol || 'USDT' }}
                    </span>
                  </div>
                  <div class="text-[9px] sm:text-xs text-slate-600 drop-shadow-sm">To: {{ w.crypto_wallet }}</div>
                  <div class="text-[9px] sm:text-xs text-slate-500 drop-shadow-sm">{{ new Date(w.created_at).toLocaleString() }}</div>
                </div>
                <div>
                  <span :class="{
                    'px-2 py-0.5 sm:px-3 sm:py-1 rounded-full text-[9px] sm:text-xs font-medium shadow-sm': true,
                    'bg-yellow-100/80 text-yellow-800 border border-yellow-200': w.status === 'under review',
                    'bg-green-100/80 text-green-800 border border-green-200': w.status === 'approved',
                    'bg-red-100/80 text-red-800 border border-red-200': w.status === 'rejected'
                  }">
                    {{ w.status.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ') }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>