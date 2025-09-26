<template>
  <AuthenticatedLayout>
    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto">
      <div class="max-w-md mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-4 sm:mb-6">
            <div class="flex items-center gap-3">
                <Link href="/profile" class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-slate-500/80 to-gray-600/80 hover:from-slate-600/90 hover:to-gray-700/90 rounded-xl shadow-lg transition-all duration-200 transform hover:scale-105">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-slate-800 drop-shadow-sm">{{ t('Withdraw History') }}</h1>
                    <p class="text-xs sm:text-sm text-slate-600 font-medium">{{ t('View your withdrawal records') }}</p>
                </div>
            </div>
        </div>

        <!-- Withdrawal History -->
        <div class="space-y-3">
          <div v-if="withdrawals.length === 0" class="text-center py-12">
            <div class="bg-gradient-to-br from-white/40 via-white/30 to-white/20 backdrop-blur-sm rounded-2xl p-8 border border-white/30">
              <svg class="w-16 h-16 text-slate-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <p class="text-slate-600 font-medium">{{ t('No withdrawals yet') }}</p>
            </div>
          </div>

          <div v-for="withdrawal in withdrawals" :key="withdrawal.id" class="bg-gradient-to-br from-white/40 via-white/30 to-white/20 backdrop-blur-sm rounded-2xl p-2 sm:p-4 border border-white/30 shadow-lg">
            <div class="flex justify-between items-start mb-2 sm:mb-3">
              <div>
                <p class="text-[10px] sm:text-sm font-semibold text-slate-800">{{ withdrawal.amount_withdraw }} USDT</p>
                <p class="text-[9px] sm:text-xs text-slate-600">{{ formatDate(withdrawal.created_at) }}</p>
              </div>
              <span 
                class="px-2 py-0.5 sm:px-3 sm:py-1 rounded-full text-[9px] sm:text-xs font-medium"
                :class="{
                  'bg-yellow-100 text-yellow-800': withdrawal.status === 'under review' || withdrawal.status === 'pending',
                  'bg-green-100 text-green-800': withdrawal.status === 'approved',
                  'bg-red-100 text-red-800': withdrawal.status === 'rejected'
                }"
              >
                {{ getStatusText(withdrawal.status) }}
              </span>
            </div>
            
            <div class="text-[9px] sm:text-xs text-slate-600 space-y-1">
              <p><span class="font-medium">{{ t('Network') }}:</span> USDT (TRC20)</p>
              <p class="break-all"><span class="font-medium">{{ t('Address') }}:</span> {{ withdrawal.crypto_wallet }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { computed } from 'vue'

const page = usePage()
const translations = computed(() => page.props.translations || {})
const locale = computed(() => page.props.locale || 'en')
const t = (key) => {
    const translation = translations.value[key]
    if (!translation && key && process.env.NODE_ENV === 'development') {
        console.warn(`Missing translation for key: ${key} in locale: ${locale.value}`)
    }
    return translation || key
}

defineProps({
  withdrawals: Array
})

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusText = (status) => {
  const statusMap = {
    'under review': t('Pending'),
    'pending': t('Pending'),
    'approved': t('Approved'),
    'rejected': t('Rejected')
  }
  return statusMap[status] || status
}
</script>