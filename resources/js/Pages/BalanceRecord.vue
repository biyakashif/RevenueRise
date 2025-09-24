<template>
  <AuthenticatedLayout>
    <div class="min-h-screen bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 pb-20">
      <div class="max-w-md mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <Link :href="route('profile.index')" class="p-2 rounded-full bg-white/20 backdrop-blur-sm hover:bg-white/30 transition-all">
            <svg class="w-5 h-5 text-slate-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </Link>
          <h1 class="text-xl font-bold text-slate-800">{{ t('Balance Record') }}</h1>
          <div class="w-9"></div>
        </div>

        <!-- Balance Records -->
        <div class="bg-gradient-to-br from-white/40 via-white/30 to-white/20 backdrop-blur-sm rounded-2xl border border-white/30 shadow-lg overflow-hidden">
          <div v-if="records.length === 0" class="text-center py-8">
            <svg class="w-12 h-12 text-slate-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
            </svg>
            <p class="text-slate-600 font-medium text-sm">{{ t('No balance records yet') }}</p>
          </div>

          <div v-for="(record, index) in records" :key="record.id" class="flex items-center justify-between p-2 border-b border-white/20 last:border-b-0">
            <span 
              class="px-2 py-1 rounded-full text-xs font-medium"
              :class="{
                'bg-green-100 text-green-800': record.type === 'invitation',
                'bg-blue-100 text-blue-800': record.type === 'task_completion',
                'bg-purple-100 text-purple-800': record.type === 'deposit'
              }"
            >
              {{ getTypeText(record.type) }}
            </span>
            
            <div class="flex-1 text-center text-xs text-slate-600">
              <span v-if="record.from_user_name">{{ record.from_user_name }} ({{ record.from_mobile_number }}) • </span>{{ formatDate(record.created_at) }}
            </div>
            
            <p class="text-sm font-semibold text-green-600">+{{ record.amount }}</p>
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
  records: Array
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

const getTypeText = (type) => {
  const typeMap = {
    'invitation': t('Invitation Bonus'),
    'task_completion': t('Task Bonus'),
    'deposit': t('Deposit')
  }
  return typeMap[type] || type
}
</script>