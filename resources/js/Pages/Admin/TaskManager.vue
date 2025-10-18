<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, onMounted, onBeforeUnmount, computed, nextTick } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import Echo from 'laravel-echo';

const page = usePage();
const users = page.props.users;
const tasksByUser = ref([]);
const showModal = ref(false);
const modalUser = ref(null);
const showLuckyOrderModal = ref(false);
const luckyOrderProducts = ref([]);
const selectedTaskId = ref(null);
const selectedProducts = ref([]);
const resetSuccess = ref("");
let echoChannel = null; // public admin channel

const searchQuery = ref('');
const commissionSearchQuery = ref('');
const assignedUsers = ref(new Set(page.props.assignedUserIds || [])); // Track users with assigned tasks

const filteredUsers = computed(() => {
  if (!users) return [];
  if (!searchQuery.value) {
    return users;
  }
  const query = searchQuery.value.toLowerCase();
  return users.filter(user => 
    (user.name && user.name.toLowerCase().includes(query)) ||
    (user.vip_level && user.vip_level.toLowerCase().includes(query)) ||
    (user.mobile_number && user.mobile_number.includes(query))
  );
});

const filteredLuckyOrderProducts = computed(() => {
  if (!luckyOrderProducts.value) return [];
  if (!commissionSearchQuery.value) {
    return luckyOrderProducts.value;
  }
  const query = commissionSearchQuery.value.toLowerCase();
  return luckyOrderProducts.value.filter(product => 
    product.commission_reward && product.commission_reward.toString().includes(query)
  );
});

async function viewTasks(userId) {
  const response = await fetch(`/admin/tasks/${userId}`);
  const data = await response.json();
  tasksByUser.value = data.tasks;
  modalUser.value = data.user;
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  modalUser.value = null;
  tasksByUser.value = [];
}

async function applyLuckyOrder(taskId, userId) {
  selectedTaskId.value = taskId;
  const response = await fetch('/admin/products/lucky-orders');
  const data = await response.json();
  luckyOrderProducts.value = data.products;
  showLuckyOrderModal.value = true;
}

function toggleProductSelection(productId) {
  const index = selectedProducts.value.indexOf(productId);
  if (index > -1) {
    selectedProducts.value.splice(index, 1);
  } else {
    selectedProducts.value = [productId];
  }
}

async function saveSelectedProduct() {
  if (selectedProducts.value.length === 0) {
    resetSuccess.value = 'Please select a product';
    setTimeout(() => {
      resetSuccess.value = '';
    }, 2000);
    return;
  }

  try {
    const response = await fetch(`/admin/tasks/${modalUser.value.id}/replace/${selectedTaskId.value}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': page.props.csrf_token,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        product_id: selectedProducts.value[0]
      }),
    });

    if (!response.ok) {
      if (response.status === 419) {
        window.location.reload();
        return;
      }
      throw new Error('Failed to replace task');
    }

    resetSuccess.value = 'Product replaced successfully!';
    setTimeout(() => {
      resetSuccess.value = '';
    }, 1000);
    closeLuckyOrderModal();
    viewTasks(modalUser.value.id);
  } catch (error) {
    resetSuccess.value = 'Error: ' + error.message;
    setTimeout(() => {
      resetSuccess.value = '';
    }, 2000);
  }
}

function closeLuckyOrderModal() {
  showLuckyOrderModal.value = false;
  selectedProducts.value = [];
  selectedTaskId.value = null;
  commissionSearchQuery.value = '';
}

async function resetTasks(userId) {
  try {
    const response = await fetch(`/admin/tasks/${userId}/reset`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': page.props.csrf_token,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
      body: JSON.stringify({}),
    });
    if (!response.ok) {
      if (response.status === 419) {
        window.location.reload();
        return;
      }
      throw new Error('Failed to reset tasks');
    }
    resetSuccess.value = "Tasks reset and reassigned successfully!";
    setTimeout(() => {
      resetSuccess.value = "";
    }, 1000); // Hide after 1 second
  } catch (error) {
    resetSuccess.value = "Error: " + error.message;
    setTimeout(() => {
      resetSuccess.value = "";
    }, 2000); // Hide error after 2 seconds
  }
}

const showAssignTasksModal = ref(false);
const selectedUser = ref(null);
const tasksNumber = ref(0);

function openAssignTasksModal(user) {
  if (assignedUsers.value.has(user.id)) {
    resetSuccess.value = 'Tasks already assigned to this user. Delete tasks first to reassign.';
    setTimeout(() => {
      resetSuccess.value = '';
    }, 2000);
    return;
  }
  selectedUser.value = user;
  showAssignTasksModal.value = true;
}

async function assignTasks() {
  if (!selectedUser.value || tasksNumber.value <= 0) {
    resetSuccess.value = 'Please enter valid inputs.';
    setTimeout(() => {
      resetSuccess.value = '';
    }, 2000);
    return;
  }

  try {
    const response = await fetch('/admin/tasks/assign', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': page.props.csrf_token,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        userId: selectedUser.value.id,
        tasksNumber: tasksNumber.value,
      }),
    });

    const data = await response.json();

    if (!response.ok) {
      if (response.status === 419) {
        window.location.reload();
        return;
      }
      resetSuccess.value = 'Error: ' + (data.message || 'Unknown error');
      setTimeout(() => {
        resetSuccess.value = '';
      }, 3000);
      return;
    }

    resetSuccess.value = 'Tasks assigned successfully!';
    assignedUsers.value.add(selectedUser.value.id);
    
    // Notify AdminLayout to update badge
    if (window.taskAssignmentUpdated) {
      window.taskAssignmentUpdated();
    }
    
    setTimeout(() => {
      resetSuccess.value = '';
    }, 1000);
    closeAssignTasksModal();
  } catch (error) {
    resetSuccess.value = 'Error: ' + error.message;
    setTimeout(() => {
      resetSuccess.value = '';
    }, 2000);
  }
}

async function deleteTasks(userId) {
  try {
    const response = await fetch(`/admin/tasks/${userId}/delete`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': page.props.csrf_token,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
      body: JSON.stringify({}),
    });
    if (!response.ok) {
      if (response.status === 419) {
        window.location.reload();
        return;
      }
      throw new Error('Failed to delete tasks');
    }
    resetSuccess.value = 'Tasks deleted successfully!';
    assignedUsers.value.delete(userId); // Remove user from assigned tasks tracking
    
    // Notify AdminLayout to update badge
    if (window.taskAssignmentUpdated) {
      window.taskAssignmentUpdated();
    }
    
    setTimeout(() => {
      resetSuccess.value = '';
    }, 1000);
    // Optionally, clear modal if open
    showModal.value = false;
    tasksByUser.value = [];
    modalUser.value = null;
  } catch (error) {
    resetSuccess.value = 'Error: ' + error.message;
    setTimeout(() => {
      resetSuccess.value = '';
    }, 2000);
  }
}

function closeAssignTasksModal() {
  showAssignTasksModal.value = false;
  selectedUser.value = null;
  tasksNumber.value = 0;
}

// Expose function to window for AdminLayout to call
window.taskAssignmentUpdated = () => {
  // This can be used to refetch data if needed
};

onMounted(() => {
  if (window.Echo) {
    echoChannel = window.Echo.channel('admin.orders')
      .listen('.OrderConfirmed', (e) => {
        console.log('OrderConfirmed received:', e);
        
        if (modalUser.value && e.order && e.order.user_id === modalUser.value.id) {
          const productId = e.order.product_id;
          const updatedTasks = [...tasksByUser.value];
          const taskIndex = updatedTasks.findIndex(t => t.product_id === productId);
          
          if (taskIndex !== -1) {
            updatedTasks[taskIndex] = { ...updatedTasks[taskIndex], status: 'confirmed' };
            tasksByUser.value = updatedTasks;
            console.log('Updated task status live for product:', productId);
          }
        }
      });
  }
});

onBeforeUnmount(() => {
  if (echoChannel) {
    window.Echo.leave('admin.orders');
  }
});
</script>

<template>
  <AdminLayout>
    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 rounded-2xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto">
      <h1 class="text-lg font-bold text-slate-800 drop-shadow-sm mb-4">Task Manager</h1>

          <!-- Search Bar -->
          <div class="mb-6">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search by name, mobile, or VIP level..."
              class="w-full h-12 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 placeholder-slate-400 backdrop-blur-sm shadow-lg"
            />
          </div>

    <!-- Assign Tasks Modal -->
    <div v-if="showAssignTasksModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
      <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl p-6 w-full max-w-md mx-4 border border-white/20 shadow-2xl">
        <h2 class="text-lg font-semibold mb-4 text-white text-center">Assign Tasks to {{ selectedUser?.name }}</h2>
        <div class="mb-4">
          <label class="block text-sm font-medium text-white mb-2">Enter Tasks Number</label>
          <input v-model="tasksNumber" type="number" class="w-full h-10 rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-3 placeholder-slate-400 backdrop-blur-sm shadow-lg" />
        </div>
        <div class="flex space-x-2">
          <button @click="closeAssignTasksModal" class="flex-1 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-lg text-sm hover:bg-white/30 text-white border border-white/30">
            Cancel
          </button>
          <button @click="assignTasks" class="flex-1 px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded text-sm hover:from-green-600 hover:to-green-700 transform hover:scale-105 transition-all duration-200">
            Assign
          </button>
        </div>
      </div>
    </div>

          <!-- Success message -->
          <div v-if="resetSuccess" class="mb-4 p-4 rounded-xl bg-green-100/80 border border-green-200 text-green-700 text-sm text-center backdrop-blur-sm">
            {{ resetSuccess }}
          </div>

          <!-- Users Table -->
          <div class="bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm rounded-xl shadow-lg overflow-hidden border border-white/30">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr class="bg-white/20 text-slate-700 text-sm font-medium">
                    <th class="p-4 text-left">Name</th>
                    <th class="p-4 text-left">Mobile Number</th>
                    <th class="p-4 text-left">VIP Level</th>
                    <th class="p-4 text-left">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="user in filteredUsers" :key="user.id" class="border-t border-white/20 hover:bg-white/10 transition-all duration-200">
                    <td class="p-4 text-sm text-slate-800 font-medium relative">
                      {{ user.name }}
                      <span v-if="!assignedUsers.has(user.id) && user.role !== 'admin'" class="ml-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 inline-flex items-center justify-center">!</span>
                    </td>
                    <td class="p-4 text-sm text-slate-700">{{ user.mobile_number }}</td>
                    <td class="p-4 text-sm text-slate-700">{{ user.vip_level }}</td>
                    <td class="p-4">
                      <div class="flex flex-wrap gap-2">
                        <button
                          @click="openAssignTasksModal(user)"
                          :disabled="assignedUsers.has(user.id)"
                          :class="assignedUsers.has(user.id) ? 'from-gray-400 to-gray-500' : 'from-green-500 to-green-600 hover:from-green-600 hover:to-green-700'"
                          class="bg-gradient-to-r text-white px-3 py-1 rounded-lg text-xs font-medium transition-all duration-200 shadow-lg min-w-[100px]"
                        >
                          {{ assignedUsers.has(user.id) ? 'Assigned' : 'Assign Tasks' }}
                        </button>
                        <button @click="viewTasks(user.id)" class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-3 py-1 rounded-lg text-xs font-medium transition-all duration-200 shadow-lg min-w-[100px]">Tasks Details</button>
                        <button @click="resetTasks(user.id)" class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-3 py-1 rounded-lg text-xs font-medium transition-all duration-200 shadow-lg min-w-[100px]">Reset Task</button>
                        <button @click="deleteTasks(user.id)" class="bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white px-3 py-1 rounded-lg text-xs font-medium transition-all duration-200 shadow-lg min-w-[100px]">Delete Tasks</button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
    </div>

    <!-- Modal for tasks -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
      <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl p-6 w-full max-w-md mx-4 border border-white/20 shadow-2xl overflow-y-auto max-h-[90vh]">
        <h2 class="text-lg font-semibold mb-4 text-white text-center">
          Tasks for {{ modalUser?.name }} ({{ modalUser?.vip_level }})
        </h2>
        <div v-if="tasksByUser && tasksByUser.length === 0" class="text-center text-white/70">No tasks assigned.</div>
        <ul v-if="tasksByUser && tasksByUser.length > 0" class="space-y-3 mb-6">
          <li
            v-for="task in tasksByUser"
            :key="task.id"
            class="flex items-center rounded-lg p-3 shadow-sm backdrop-blur-sm border border-white/20"
            :class="task.product_type === 'Lucky Order' ? 'bg-yellow-100/20' : 'bg-white/10'"
          >
            <img
              :src="task.product?.image_path ? '/storage/' + task.product.image_path : '/default-product.png'"
              alt="Product"
              class="w-10 h-10 object-cover rounded mr-3 border border-white/30"
            />
            <div class="flex-1">
              <div class="font-semibold text-sm text-white">{{ task.product?.title }}</div>
              <div class="text-xs text-white/70 mb-1">{{ task.product_type }}</div>
              <div class="flex gap-2 text-xs">
                <span class="text-green-300">Buy: {{ task.product?.purchase_price }}</span>
                <span class="text-blue-300">Sell: {{ task.product?.selling_price }}</span>
              </div>
            </div>
            <div class="text-xs text-white/50 ml-2">#{{ task.position }}</div>
            <span v-if="task.status && task.status.toString().trim().toLowerCase() === 'confirmed'" class="ml-2 bg-green-500 text-white px-2 py-1 rounded text-xs">
              Confirmed
            </span>
            <button
              v-else-if="task.product_type !== 'Lucky Order' && modalUser?.id"
              @click="applyLuckyOrder(task.id, modalUser.id)"
              class="ml-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white px-2 py-1 rounded text-xs hover:from-blue-600 hover:to-blue-700 transition-all duration-200"
            >
              Apply L/O
            </button>
          </li>
        </ul>
        <button @click="closeModal" class="w-full px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded text-sm hover:from-blue-600 hover:to-blue-700 transform hover:scale-105 transition-all duration-200">Close</button>
      </div>
    </div>

    <!-- Lucky Order Products Modal -->
    <div v-if="showLuckyOrderModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
      <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl p-6 w-full max-w-md mx-4 border border-white/20 shadow-2xl overflow-y-auto max-h-[90vh]">
        <h2 class="text-lg font-semibold mb-4 text-white text-center">Select Lucky Order Product</h2>
        <div class="mb-4">
          <input
            v-model="commissionSearchQuery"
            type="text"
            placeholder="Search by commission..."
            class="w-full h-10 rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-3 placeholder-slate-400 backdrop-blur-sm shadow-lg"
          />
        </div>
        <div v-if="luckyOrderProducts.length === 0" class="text-center text-white/70">No Lucky Order products available.</div>
        <div v-if="luckyOrderProducts.length > 0" class="space-y-3 mb-6">
          <div
            v-for="product in filteredLuckyOrderProducts"
            :key="product.id"
            @click="toggleProductSelection(product.id)"
            class="flex items-center rounded-lg p-3 shadow-sm backdrop-blur-sm border cursor-pointer transition-all duration-200"
            :class="selectedProducts.includes(product.id) ? 'bg-blue-500/30 border-blue-400' : 'bg-white/10 border-white/20 hover:bg-white/20'"
          >
            <input
              type="checkbox"
              :checked="selectedProducts.includes(product.id)"
              class="mr-3 h-5 w-5 rounded"
              @click.stop="toggleProductSelection(product.id)"
            />
            <img
              :src="product.image_path ? '/storage/' + product.image_path : '/default-product.png'"
              alt="Product"
              class="w-16 h-16 object-cover rounded mr-3 border border-white/30"
            />
            <div class="flex-1">
              <div class="font-semibold text-sm text-white">{{ product.title }}</div>
              <div class="text-xs text-white/70 mb-1">{{ product.type }}</div>
              <div class="flex gap-3 text-xs">
                <span class="text-green-300">Purchase: {{ product.purchase_price }}</span>
                <span class="text-blue-300">Sell: {{ product.selling_price }}</span>
                <span class="text-yellow-300">Commission: {{ product.commission_reward }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="flex space-x-2">
          <button @click="closeLuckyOrderModal" class="flex-1 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-lg text-sm hover:bg-white/30 text-white border border-white/30">
            Cancel
          </button>
          <button @click="saveSelectedProduct" class="flex-1 px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded text-sm hover:from-green-600 hover:to-green-700 transform hover:scale-105 transition-all duration-200">
            Save
          </button>
        </div>
      </div>
    </div>


  </AdminLayout>
</template>