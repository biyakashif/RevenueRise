<template>
  <AuthenticatedLayout>
    <Head :title="t('My Orders')" />

    <div class="py-4 sm:py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- User Info Section -->
        <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
            <div>
              <h1 class="text-xl font-bold text-gray-800">{{ t('Hi') }}, {{ user.name || t('User') }}</h1>
              <p class="inline-block mt-1 px-3 py-1 rounded-md bg-black text-white text-xs font-semibold">
                {{ user.vip_level }}
              </p>
            </div>
          </div>

          <!-- Stats Cards -->
          <div class="flex flex-col sm:flex-row gap-3">
            <div class="bg-purple-50 p-4 rounded-lg border border-purple-100 flex-1 flex justify-between items-center">
              <transition name="fade" mode="out-in">
                <p v-if="balanceErrorMessage" key="error" class="text-xs text-red-600 font-medium w-full text-center">
                  {{ balanceErrorMessage }}
                </p>
                <div v-else key="balance" class="flex justify-between w-full items-center">
                  <p class="text-xs text-purple-600 font-medium">{{ t('Total Balance') }}</p>
                  <p class="text-lg font-bold text-gray-800 flex items-center gap-1">
                    {{ (user.balance || 0).toFixed(2) }}
                    <span class="text-xs text-gray-500">USDT</span>
                  </p>
                </div>
              </transition>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg border border-purple-100 flex-1 flex justify-between items-center">
              <p class="text-xs text-purple-600 font-medium">{{ t('Frozen Balance') }}</p>
              <p class="text-lg font-bold text-gray-800 flex items-center gap-1">
                {{ Number(user.frozen_balance ?? 0).toFixed(2) }}
                <span class="text-xs text-gray-500">USDT</span>
              </p>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg border border-purple-100 flex-1 flex justify-between items-center">
              <p class="text-xs text-purple-600 font-medium">{{ t("Today's Profit") }}</p>
              <p class="text-lg font-bold text-gray-800 flex items-center gap-1">
                {{ (user.todays_profit || 0).toFixed(2) }}
                <span class="text-xs text-gray-500">USDT</span>
              </p>
            </div>
          </div>
        </div>

        <!-- Today's Progress Section -->
        <div class="bg-white shadow-sm sm:rounded-lg px-4 py-3 mb-6">
          <div class="flex justify-between items-center mb-2">
            <h3 class="text-sm font-semibold text-gray-800">{{ t("Today's Progress") }}</h3>
            <span class="text-sm font-bold text-purple-600">{{ taskProgress }}/{{ taskItemsCount }}</span>
          </div>
          <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
            <div
              class="h-full bg-purple-600 transition-all duration-500"
              :style="{ width: taskItemsCount > 0 ? ((taskProgress / taskItemsCount) * 100) + '%' : '0%' }"
            ></div>
          </div>
        </div>

        <!-- Task Container -->
        <div
          v-if="activeTask && activeTask.products && activeTask.products.length"
          class="bg-white shadow-sm sm:rounded-lg p-6 text-center relative overflow-hidden"
        >
          <div class="relative z-10">
            <h2 class="text-xl font-bold mb-2">{{ t('Order Task Set') }}</h2>
            <p class="text-sm text-gray-600 mb-4">{{ activeTask.name }}</p>

            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
              <div class="text-left">
                <p class="text-xs text-gray-500">{{ t('Progress') }}</p>
                <p class="text-lg font-bold">{{ taskProgress }}/{{ taskItemsCount }}</p>
              </div>

              <div v-if="taskProgress >= taskItemsCount && taskItemsCount > 0" class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-lg text-sm font-medium text-center">
                {{ t("Congratulations you have done your today's task, your next task will be updated after midnight") }}
              </div>
              <button
                v-else
                @click="grabOrders"
                class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium transition flex items-center gap-2"
                :class="{ 'opacity-70 cursor-not-allowed': isGrabbing || isSubmitting || showModal }"
                :aria-busy="isGrabbing"
                :disabled="activeTask.products.length === 0 || isGrabbing || isSubmitting || showModal"
              >
                <svg v-if="isGrabbing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>{{ isGrabbing ? t('Reserving...') : t('Grab Orders') }}</span>
              </button>

              <div class="text-right">
                <p class="text-xs text-gray-500">{{ t('Reward') }}</p>
                <p class="text-lg font-bold">{{ (user.order_reward && user.order_reward > 0 ? user.order_reward : lastReward).toFixed(2) }} USDT</p>
              </div>
            </div>
          </div>
        </div>

        <!-- No tasks available -->
    <div v-if="!taskItemsCount" class="bg-white shadow-sm sm:rounded-lg p-6 text-center">
      <p class="text-gray-500">{{ t('No tasks available for your VIP level') }} ({{ user.vip_level }})</p>
    </div>
      </div>
    </div>

    <!-- Product Modal (centered on all screens) -->
    <transition name="fade" mode="out-in">
      <div v-if="showModal && currentTaskProduct" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-2">
        <div class="bg-white rounded-lg shadow-xl max-w-sm w-full max-h-[90vh] overflow-y-auto animate-scale">
          <div class="bg-purple-600 text-white p-3 rounded-t-lg">
            <h3 class="text-sm font-semibold text-center">{{ t('Ebay - Order') }} ({{ currentTaskProduct.commission_percentage }}%)</h3>
            <p v-if="currentTaskProduct?.type === 'Lucky Order'" class="mt-1 text-yellow-400 font-bold text-xs text-center">🎉 {{ t('Congratulations! You got a Lucky Order') }}</p>
          </div>
          <div class="p-3">
            <div class="mb-3 text-xs">
              <div class="flex justify-between mb-1">
                <span class="text-gray-500">{{ t('Order number:') }}</span>
                <span class="font-semibold"># {{ taskProgress + 1 }}</span>
              </div>
              <div class="flex justify-between mb-1">
                <span class="text-gray-500">{{ t('Received Date:') }}</span>
                <span class="font-semibold">{{ new Date().toISOString().split('T')[0] }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">{{ t('Balance:') }}</span>
                <span class="font-semibold text-green-600">{{ (user.balance || 0).toFixed(2) }} USDT</span>
              </div>
            </div>
            <div v-if="modalErrorMessage" class="text-xs text-red-600 font-medium text-center mb-3">
              {{ modalErrorMessage }}
            </div>
            <div class="border-t border-gray-200 my-2"></div>
            <div class="mb-3">
              <div class="flex items-start justify-between">
                <div class="flex items-start">
                  <img
                    :src="currentTaskProduct.image_path ? '/storage/' + currentTaskProduct.image_path : 'https://via.placeholder.com/80?text=No+Image'"
                    :alt="currentTaskProduct.title"
                    class="w-16 h-16 object-cover rounded mr-3"
                  />
                  <div class="flex-1">
                    <h4 class="text-xs font-semibold mb-1">{{ currentTaskProduct.title }}</h4>
                    <p class="text-[10px] text-gray-600 mb-1 leading-tight">{{ currentTaskProduct.description }}</p>
                    <div class="text-xs font-bold">{{ currentTaskProduct.selling_price }} USDT</div>
                  </div>
                </div>
                <div class="text-[10px] text-gray-500">X 1</div>
              </div>
            </div>
            <div class="border-t border-gray-200 my-2"></div>
            <div class="text-xs">
              <div class="flex justify-between mb-1">
                <span class="text-gray-600">{{ t('Purchase Price') }}</span>
                <span class="font-semibold">{{ currentTaskProduct.selling_price }} USDT</span>
              </div>
              <div class="flex justify-between mb-1">
                <span class="text-gray-600">{{ t('Selling Price') }}</span>
                <span class="font-semibold">{{ (parseFloat(currentTaskProduct.selling_price) + 0.112).toFixed(3) }} USDT</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">{{ t('Commission') }} ({{ currentTaskProduct.commission_percentage }}%)</span>
                <span class="font-semibold text-green-600">{{ currentTaskProduct.commission_reward }} USDT</span>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 p-3 rounded-b-lg flex justify-end">
            <button
              @click="confirmProduct"
              class="px-4 py-2 text-xs bg-purple-600 text-white rounded hover:bg-purple-700 font-medium transition"
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
      <div v-if="notEnoughTasks" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full text-center">
          <p class="text-gray-700 font-medium mb-2">{{ t('Fetching orders...') }}</p>
          <p class="text-xs text-gray-500">{{ t('There are not enough products available right now. Please wait.') }}</p>
          <div class="mt-4">
            <svg class="animate-spin h-6 w-6 text-purple-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
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

// Cache last non-zero reward
const lastReward = ref(props.user.order_reward || 0);

watch(() => props.user.order_reward, (newReward) => {
  if (newReward && newReward > 0) {
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
    onSuccess: () => {
      showModal.value = false;
      clearModalState();
      modalProduct.value = null;
      // Always reload to get the latest confirmedCount from server
      router.reload({
        only: ['tasks', 'taskTotalCount', 'confirmedCount', 'user', 'flash'],
        preserveScroll: true,
        onSuccess: () => updateFromProps(),
      });
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
    } else if (props.user?.balance >= 0) {
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