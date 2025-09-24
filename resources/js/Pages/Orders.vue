<template>
  <AuthenticatedLayout>
    <Head :title="t('My Orders')" />

    <div class="py-4 sm:py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- User Info Section -->
        <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30 mb-4 sm:mb-6">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
            <div>
              <h1 class="text-xl font-bold text-slate-800 drop-shadow-sm">{{ t('Hi') }}, {{ user.name || t('User') }}</h1>
              <p class="inline-block mt-2 px-3 py-1 rounded-full bg-gradient-to-r from-emerald-400/80 to-green-400/80 text-white text-xs font-semibold shadow-sm">
                {{ user.vip_level }}
              </p>
            </div>
          </div>

          <!-- Stats Cards -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm p-4 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-white/30">
              <transition name="fade" mode="out-in">
                <p v-if="balanceErrorMessage" key="error" class="text-xs text-red-600 font-medium w-full text-center">
                  {{ balanceErrorMessage }}
                </p>
                <div v-else key="balance" class="flex justify-between w-full items-center">
                  <p class="text-xs text-slate-600 font-medium">{{ t('Total Balance') }}</p>
                  <p class="text-lg font-bold text-slate-800 flex items-center gap-1">
                    {{ (user.balance || 0).toFixed(2) }}
                    <span class="text-xs text-slate-500">USDT</span>
                  </p>
                </div>
              </transition>
            </div>
            <div class="bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm p-4 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-white/30">
              <div class="flex justify-between w-full items-center">
                <p class="text-xs text-slate-600 font-medium">{{ t('Frozen Balance') }}</p>
                <p class="text-lg font-bold text-slate-800 flex items-center gap-1">
                  {{ Number(user.frozen_balance ?? 0).toFixed(2) }}
                  <span class="text-xs text-slate-500">USDT</span>
                </p>
              </div>
            </div>
            <div class="bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm p-4 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-white/30">
              <div class="flex justify-between w-full items-center">
                <p class="text-xs text-slate-600 font-medium">{{ t("Today's Profit") }}</p>
                <p class="text-lg font-bold text-slate-800 flex items-center gap-1">
                  {{ (user.todays_profit || 0).toFixed(2) }}
                  <span class="text-xs text-slate-500">USDT</span>
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Today's Progress Section -->
        <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30 mb-4 sm:mb-6">
          <div class="flex justify-between items-center mb-3">
            <h3 class="text-sm font-semibold text-slate-800 drop-shadow-sm">{{ t("Today's Progress") }}</h3>
            <span class="text-sm font-bold text-slate-700 bg-white/30 px-2 py-1 rounded-full backdrop-blur-sm">{{ taskProgress }}/{{ taskItemsCount }}</span>
          </div>
          <div class="w-full h-3 bg-white/20 rounded-full overflow-hidden backdrop-blur-sm">
            <div
              class="h-full bg-gradient-to-r from-emerald-400 to-green-500 transition-all duration-500 shadow-sm"
              :style="{ width: taskItemsCount > 0 ? ((taskProgress / taskItemsCount) * 100) + '%' : '0%' }"
            ></div>
          </div>
        </div>

        <!-- Task Container -->
        <div
          v-if="activeTask && activeTask.products && activeTask.products.length"
          class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-2xl"
        >
          <div class="text-center mb-4">
            <h2 class="text-xl font-bold text-slate-800 drop-shadow-sm mb-2">{{ t('Order Task Set') }}</h2>
            <p class="text-sm text-slate-600 font-medium">{{ activeTask.name }}</p>
          </div>

          <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
            <div class="bg-white/20 backdrop-blur-sm p-3 rounded-xl">
              <p class="text-xs text-slate-600 font-medium text-center">{{ t('Progress') }}</p>
              <p class="text-lg font-bold text-slate-800 text-center">{{ taskProgress }}/{{ taskItemsCount }}</p>
            </div>

            <div v-if="taskProgress >= taskItemsCount && taskItemsCount > 0" class="bg-gradient-to-r from-emerald-400/20 to-green-400/20 backdrop-blur-sm text-emerald-700 p-4 rounded-xl text-sm font-medium text-center shadow-lg max-w-xs">
              {{ t("Congratulations you have done your today's task, your next task will be updated after midnight") }}
            </div>
            <button
              v-else
              @click="grabOrders"
              class="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl font-medium transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl flex items-center gap-2"
              :class="{ 'opacity-70 cursor-not-allowed': isGrabbing || isSubmitting || showModal }"
              :aria-busy="isGrabbing"
              :disabled="activeTask.products.length === 0 || isGrabbing || isSubmitting || showModal"
            >
              <svg v-if="isGrabbing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <span>{{ t('Grab Orders') }}</span>
            </button>

            <div class="bg-white/20 backdrop-blur-sm p-3 rounded-xl">
              <p class="text-xs text-slate-600 font-medium text-center">{{ t('Reward') }}</p>
              <p class="text-lg font-bold text-slate-800 text-center">{{ (user.order_reward && user.order_reward > 0 ? user.order_reward : lastReward).toFixed(2) }} USDT</p>
            </div>
          </div>
        </div>

        <!-- No tasks available -->
    <div v-if="!taskItemsCount" class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-6 rounded-2xl sm:rounded-3xl shadow-2xl border border-cyan-300/30 text-center">
      <p class="text-slate-600 font-medium">{{ t('No tasks available for your VIP level') }}</p>
    </div>
      </div>
    </div>

    <!-- Product Modal (centered on all screens) -->
    <transition name="fade" mode="out-in">
      <div v-if="showModal && currentTaskProduct" class="fixed inset-0 bg-black/50 flex items-end sm:items-center justify-center z-50 p-4 pb-20 sm:pb-4">
        <div class="bg-gradient-to-br from-slate-800/95 via-blue-900/95 to-indigo-900/95 backdrop-blur-xl rounded-3xl shadow-2xl max-w-sm w-full max-h-[90vh] overflow-y-auto animate-scale border border-cyan-300/20">
          <div class="bg-gradient-to-r from-cyan-400/60 via-blue-500/50 to-indigo-600/60 text-white p-4 rounded-t-3xl backdrop-blur-sm">
            <h3 class="text-sm font-semibold text-center">{{ t('Ebay - Order') }} ({{ currentTaskProduct.commission_percentage }}%)</h3>
            <p v-if="currentTaskProduct?.type === 'Lucky Order'" class="mt-2 text-yellow-300 font-bold text-xs text-center">🎉 {{ t('Congratulations! You got a Lucky Order') }}</p>
          </div>
          <div class="p-4">
            <div class="mb-3 text-xs">
              <div class="flex justify-between mb-1">
                <span class="text-slate-300">{{ t('Order number:') }}</span>
                <span class="font-semibold text-white"># {{ taskProgress + 1 }}</span>
              </div>
              <div class="flex justify-between mb-1">
                <span class="text-slate-300">{{ t('Received Date:') }}</span>
                <span class="font-semibold text-white">{{ new Date().toISOString().split('T')[0] }}</span>
              </div>
              <div class="flex justify-between">
                <span v-if="modalErrorMessage" class="text-red-400 font-medium w-full text-center">{{ modalErrorMessage }}</span>
                <template v-else>
                  <span class="text-slate-300">{{ t('Balance:') }}</span>
                  <span class="font-semibold text-emerald-400">{{ (user.balance || 0).toFixed(2) }} USDT</span>
                </template>
              </div>
            </div>
            <div class="border-t border-cyan-300/30 my-3"></div>
            <div class="mb-3">
              <div class="flex items-start justify-between">
                <div class="flex items-start">
                  <img
                    :src="currentTaskProduct.image_path ? '/storage/' + currentTaskProduct.image_path : 'https://via.placeholder.com/80?text=No+Image'"
                    :alt="currentTaskProduct.title"
                    class="w-16 h-16 object-cover rounded-lg mr-3 shadow-lg border border-cyan-300/20"
                  />
                  <div class="flex-1">
                    <h4 class="text-xs font-semibold mb-1 text-white">{{ currentTaskProduct.title }}</h4>
                    <p class="text-[10px] text-slate-300 mb-1 leading-tight">{{ currentTaskProduct.description }}</p>
                    <div class="text-xs font-bold text-cyan-400">{{ currentTaskProduct.selling_price }} USDT</div>
                  </div>
                </div>
                <div class="text-[10px] text-slate-400 font-medium">X 1</div>
              </div>
            </div>
            <div class="border-t border-cyan-300/30 my-3"></div>
            <div class="text-xs space-y-1">
              <div class="flex justify-between">
                <span class="text-slate-300">{{ t('Purchase Price') }}</span>
                <span class="font-semibold text-white">{{ currentTaskProduct.selling_price }} USDT</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-300">{{ t('Selling Price') }}</span>
                <span class="font-semibold text-white">{{ (parseFloat(currentTaskProduct.selling_price) + 0.112).toFixed(3) }} USDT</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-300">{{ t('Commission') }} ({{ currentTaskProduct.commission_percentage }}%)</span>
                <span class="font-semibold text-emerald-400">{{ currentTaskProduct.commission_reward }} USDT</span>
              </div>
            </div>
          </div>
          <div class="bg-gradient-to-r from-slate-700/50 to-blue-800/50 p-4 rounded-b-3xl backdrop-blur-sm">
            <button
              @click="confirmProduct"
              class="w-full h-10 rounded-xl bg-gradient-to-r from-cyan-400/70 via-blue-500/60 to-indigo-600/70 hover:from-cyan-500/80 hover:via-blue-600/70 hover:to-indigo-700/80 text-white font-semibold text-sm transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl backdrop-blur-sm"
              :class="{ 'opacity-50 cursor-not-allowed': isSubmitting }"
              :disabled="isSubmitting"
            >
              {{ t('Confirm') }}
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Loading Modal for Insufficient Tasks -->
    <transition name="fade" mode="out-in">
      <div v-if="notEnoughTasks" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl p-6 max-w-sm w-full text-center border border-white/40">
          <p class="text-slate-700 font-medium mb-2">{{ t('Fetching orders...') }}</p>
          <p class="text-xs text-slate-500">{{ t('There are not enough products available right now. Please wait.') }}</p>
          <div class="mt-4">
            <svg class="animate-spin h-6 w-6 text-blue-500 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
          </div>
        </div>
      </div>
    </transition>

  <!-- Spinner moved to the Grab button only to avoid double loading overlay -->
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
  products: { type: Array, default: () => [] },
  currentProductIndex: { type: Number, default: 0 },
  user: { type: Object, default: () => ({ balance: 0, frozen_balance: 0, todays_profit: 0, vip_level: 'VIP1' }) },
  tasks: { type: Array, default: () => [] },
  taskTotalCount: { type: Number, default: 0 },
  confirmedCount: { type: Number, default: 0 },
  flash: { type: Object, default: () => ({}) },
});

const page = usePage();
const translations = computed(() => page.props.translations || {});
const t = (key) => translations.value[key] || key;

const balanceErrorMessage = ref('');
const completionMessage = ref('');
const modalErrorMessage = ref(''); // New state for modal-specific error messages

// Cache reward; keep in sync with server including zero
const lastReward = ref(props.user.order_reward ?? 0);

watch(() => props.user.order_reward, (newReward) => {
  if (typeof newReward === 'number') {
    lastReward.value = newReward;
  }
});

// Watch server flash for errors
watch(() => props.flash, (newFlash) => {
  if (newFlash.error && !showModal.value) {
    balanceErrorMessage.value = newFlash.error;
    setTimeout(() => (balanceErrorMessage.value = ''), 5000);
  }
}, { deep: true });

// Helpers
const buildActiveTaskFrom = (tasksArr) => {
  if (!Array.isArray(tasksArr) || tasksArr.length === 0) return null;
  const products = tasksArr.map((t) => {
    const p = t.product ?? null;
    if (p && typeof p === 'object') {
      return {
        id: p.id ?? p.product_id ?? t.product_id ?? null,
        product_id: p.product_id ?? p.id ?? t.product_id ?? null,
        title: p.title ?? '',
        description: p.description ?? '',
        purchase_price: p.purchase_price ?? 0,
        selling_price: p.selling_price ?? 0,
        commission_reward: p.commission_reward ?? 0,
        commission_percentage: p.commission_percentage ?? 0,
        image_path: p.image_path ?? '',
        type: p.type ?? t.product_type ?? '',
        status: t.status ?? 'confirmed',
        is_forced: p.is_forced ?? false,
      };
    }
    return {
      id: t.product_id ?? t.id ?? null,
      product_id: t.product_id ?? null,
      title: t.title ?? '',
      description: t.description ?? '',
      purchase_price: t.purchase_price ?? 0,
      selling_price: t.selling_price ?? 0,
      commission_reward: t.commission_reward ?? 0,
      commission_percentage: t.commission_percentage ?? 0,
      image_path: t.image_path ?? '',
      type: t.product_type ?? '',
      status: t.status ?? 'confirmed',
      is_forced: t.is_forced ?? false,
    };
  }).filter(p => p && p.id != null);

  if (products.length === 0) return null;
  return {
    name: tasksArr[0].name || (props.user.vip_level ?? 'Task'),
    products,
  };
};

// Reactive state
const currentIndex = ref(props.currentProductIndex);
const activeTask = ref(buildActiveTaskFrom(props.tasks));
const taskProgress = ref(props.confirmedCount || 0);
const taskItemsCount = ref(props.taskTotalCount || 0);

// Small derived computed
const notEnoughTasks = computed(() => false); // Always allow tasks to show, even if less than 40

// Modal & loading state
const showModal = ref(false);
const isSubmitting = ref(false);
const isGrabbing = ref(false);
const currentTaskProductIndex = ref(taskProgress.value || 0);
const modalProduct = ref(null);

const currentTaskProduct = computed(() => {
  if (modalProduct.value) return modalProduct.value;
  if (activeTask.value && Array.isArray(activeTask.value.products) && activeTask.value.products.length) {
    const idx = Math.min(Math.max(0, currentTaskProductIndex.value), activeTask.value.products.length - 1);
    return activeTask.value.products[idx];
  }
  return null;
});

const hasCompletedAllTasks = computed(() => {
  return taskProgress.value >= taskItemsCount.value && taskItemsCount.value > 0;
});

const loadPersistedModalState = () => {
  const persistedState = localStorage.getItem('luckyOrderModalState');
  if (persistedState) {
    try {
      const state = JSON.parse(persistedState);
      if (state.modalProduct?.type === 'Lucky Order') {
        modalProduct.value = state.modalProduct;
        showModal.value = true;
      } else {
        clearModalState();
      }
    } catch (e) {
      clearModalState();
    }
  }
};

const saveModalState = (product) => {
  if (product?.type === 'Lucky Order') {
    localStorage.setItem('luckyOrderModalState', JSON.stringify({ modalProduct: product }));
  }
};

const clearModalState = () => {
  localStorage.removeItem('luckyOrderModalState');
  modalProduct.value = null;
  showModal.value = false;
  modalErrorMessage.value = '';
};

// Reserve / grab (use Inertia router.post to keep session cookies)
const grabOrders = debounce(() => {
  if (
    isGrabbing.value ||
    !activeTask.value ||
    !activeTask.value.products ||
    activeTask.value.products.length === 0 ||
    currentTaskProductIndex.value >= activeTask.value.products.length ||
    showModal.value
  ) {
    balanceErrorMessage.value = 'No tasks available or action in progress.';
    setTimeout(() => (balanceErrorMessage.value = ''), 3000);
    return;
  }

  isGrabbing.value = true;

  // Pre-flight check to sync state with the backend and get the correct next task
  router.reload({
    only: ['tasks', 'taskTotalCount', 'confirmedCount', 'user', 'flash'],
    preserveState: true,
    preserveScroll: true,
    onSuccess: (page) => {
      // Update state with fresh data from the server
      updateFromProps();

      // Now that state is synced, get the truly current product
      const currentProduct = activeTask.value.products[currentTaskProductIndex.value];
      if (!currentProduct) {
        balanceErrorMessage.value = 'Failed to sync the current task. Please try again.';
        setTimeout(() => (balanceErrorMessage.value = ''), 4000);
        isGrabbing.value = false;
        return;
      }

      // If the current task is already pending (e.g., a genuine lucky order), show the modal directly
      if (currentProduct.status === 'pending') {
        modalProduct.value = currentProduct;
        saveModalState(modalProduct.value);
        showModal.value = true;
        isGrabbing.value = false;
        return;
      }

      // Proceed with the balance check using the correct product info
      const productId = currentProduct.id;
      router.post(route('orders.check-balance'), {
        product_id: productId,
        task_name: activeTask.value.name,
        is_forced: currentProduct.is_forced,
      }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
          if (page.props?.flash?.success) {
            // The balance check was successful, show the modal.
            // We need to reload again to get the 'pending' status for the lucky order.
            router.reload({
              only: ['user', 'tasks', 'taskTotalCount', 'confirmedCount', 'flash'],
              preserveState: true,
              onSuccess: () => {
                updateFromProps();
                modalProduct.value = activeTask.value.products[currentTaskProductIndex.value];
                saveModalState(modalProduct.value);
                showModal.value = true;
                isGrabbing.value = false;
              },
              onError: () => {
                balanceErrorMessage.value = 'Failed to refresh data after reservation.';
                setTimeout(() => (balanceErrorMessage.value = ''), 4000);
                isGrabbing.value = false;
              }
            });
          } else {
            const err = page.props?.flash?.error || 'Failed to reserve order.';
            balanceErrorMessage.value = err;
            setTimeout(() => (balanceErrorMessage.value = ''), 4000);
            isGrabbing.value = false;
          }
        },
        onError: (errors) => {
          const msg = errors?.message || 'Failed to grab order';
          balanceErrorMessage.value = msg;
          setTimeout(() => (balanceErrorMessage.value = ''), 4000);
          isGrabbing.value = false;
        }
      });
    },
    onError: () => {
      balanceErrorMessage.value = 'Failed to sync with server. Please try again.';
      setTimeout(() => (balanceErrorMessage.value = ''), 4000);
      isGrabbing.value = false;
    }
  });
}, 500);

// Confirm product and save to database
const confirmProduct = () => {
  if (!currentTaskProduct.value || !activeTask.value || isSubmitting.value) return;

  isSubmitting.value = true;
  router.post(route('orders.store'), {
    product_id: currentTaskProduct.value.id ?? currentTaskProduct.value.product_id,
    task_name: activeTask.value.name,
    is_forced: currentTaskProduct.value.is_forced,
  }, {
    preserveScroll: true,
    onSuccess: (page) => {
      if (page.props?.flash?.success) {
        showModal.value = false;
        clearModalState();
        modalProduct.value = null;
        // Always reload to get the latest confirmedCount from server
        router.reload({
          only: ['tasks', 'taskTotalCount', 'confirmedCount', 'user', 'flash'],
          preserveScroll: true,
          onSuccess: () => updateFromProps(),
        });
      } else if (page.props?.flash?.error) {
        modalErrorMessage.value = page.props.flash.error;
        setTimeout(() => (modalErrorMessage.value = ''), 4000);
      }
    },
    onError: (errors) => {
      modalErrorMessage.value = errors?.message || 'Error saving order. Please try again.';
      setTimeout(() => (modalErrorMessage.value = ''), 4000);
    },
    onFinish: () => {
      isSubmitting.value = false;
    },
  });
};

const updateFromProps = () => {
  activeTask.value = buildActiveTaskFrom(props.tasks);
  taskItemsCount.value = props.taskTotalCount || 0;
  taskProgress.value = props.confirmedCount || 0;
  currentTaskProductIndex.value = Math.min(taskProgress.value, activeTask.value?.products?.length - 1 || 0);

  // Keep reward in sync with server (handles reset/delete to 0)
  if (typeof props.user?.order_reward === 'number') {
    lastReward.value = props.user.order_reward;
  }

  if (taskProgress.value >= taskItemsCount.value && taskItemsCount.value > 0) {
    completionMessage.value = "Congratulations you have done your today's task, your next task will be updated after midnight";
    showModal.value = false;
    modalProduct.value = null;
    clearModalState();
  }

  // Check for pending Lucky Order
  if (activeTask.value && activeTask.value.products) {
    const pendingOrder = activeTask.value.products.find(p => p.type === 'Lucky Order' && p.status === 'pending');
    if (pendingOrder) {
      modalProduct.value = pendingOrder;
      saveModalState(modalProduct.value);
      showModal.value = true;
    } else if (props.user?.balance >= 0 && !modalErrorMessage.value) {
      clearModalState();
    }
  }
};

// Polling every 5s
let pollInterval = null;
onMounted(() => {
  loadPersistedModalState();
  updateFromProps();

  // Listen for progress reset event
  if (window.Echo && props.user && props.user.id) {
    try {
      window.Echo.private(`orders.${props.user.id}`)
        .listen('UserProgressReset', () => {
          // Reload the page data when tasks are reset
          router.reload({ 
            only: ['tasks', 'taskTotalCount', 'confirmedCount', 'user'],
            preserveState: true,
            preserveScroll: true
          });
        })
        .error((error) => {
          console.error('Echo error:', error);
        });
    } catch (error) {
      console.error('Echo setup error:', error);
    }
  }

  pollInterval = setInterval(() => {
    if (!isSubmitting.value && !isGrabbing.value && !showModal.value && !hasCompletedAllTasks.value) {
      router.reload({ 
        only: ['tasks', 'taskTotalCount', 'confirmedCount', 'user'],
        preserveState: true,
        preserveScroll: true
      });
    }
  }, 5000);
});

onBeforeUnmount(() => {
  if (pollInterval) clearInterval(pollInterval);
});

watch(
  () => [props.tasks, props.taskTotalCount, props.confirmedCount, props.user, props.flash],
  () => {
    updateFromProps();
    loadPersistedModalState();
  },
  { deep: true, immediate: true }
);
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.animate-scale {
  animation: scaleIn 0.25s ease-out;
}
@keyframes scaleIn {
  from {
    transform: scale(0.9);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

/* Removed bottom-sheet styles — modal is centered for all screens and nothing is fixed at bottom */
</style>