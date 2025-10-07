<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, onMounted, computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import Echo from 'laravel-echo';

const page = usePage();
const users = page.props.users;
const tasksByUser = ref([]);
const showModal = ref(false);
const modalUser = ref(null);
const resetSuccess = ref(""); // Success message
let pollInterval = null;

const searchQuery = ref('');

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

function startPolling(userId) {
  stopPolling();
  pollInterval = setInterval(() => {
    fetch(`/admin/tasks/${userId}`)
      .then(res => res.json())
      .then(data => {
        tasksByUser.value = data.tasks;
        modalUser.value = data.user;
      });
  }, 2000); // Poll every 2 seconds
}

function stopPolling() {
  if (pollInterval) clearInterval(pollInterval);
}

async function viewTasks(userId) {
  const response = await fetch(`/admin/tasks/${userId}`);
  const data = await response.json();
  tasksByUser.value = data.tasks;
  modalUser.value = data.user;
  showModal.value = true;
  startPolling(userId);
}

function closeModal() {
  showModal.value = false;
  modalUser.value = null;
  tasksByUser.value = [];
  stopPolling();
}

function applyLuckyOrder(taskId, userId) {
  router.post(`/admin/tasks/${userId}/replace/${taskId}`, {}, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      // Refetch tasks to show the update
      viewTasks(userId);
    },
    onError: (errors) => {
      console.error('Error replacing task:', errors);
      alert('Failed to replace task. See console for details.');
    }
  });
}

async function resetTasks(userId) {
  try {
    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    const token = tokenMeta ? tokenMeta.getAttribute('content') : '';
    const response = await fetch(`/admin/tasks/${userId}/reset`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
      body: JSON.stringify({}),
    });
    if (!response.ok) {
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

const assignedUsers = ref(new Set(page.props.assignedUserIds || [])); // Track users with assigned tasks

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
    alert('Please enter valid inputs.');
    return;
  }

  try {
    router.post(`/admin/tasks/assign`, {
      userId: selectedUser.value.id,
      tasksNumber: tasksNumber.value,
      luckyOrder: luckyOrder.value,
    }, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        resetSuccess.value = 'Tasks assigned successfully!';
        assignedUsers.value.add(selectedUser.value.id);
        setTimeout(() => {
          resetSuccess.value = '';
        }, 1000);
        closeAssignTasksModal();
      },
      onError: (errors) => {
        resetSuccess.value = 'Error: ' + (errors ? JSON.stringify(errors) : 'Unknown error');
        setTimeout(() => {
          resetSuccess.value = '';
        }, 2000);
      }
    });
  } catch (error) {
    resetSuccess.value = 'Error: ' + error.message;
    setTimeout(() => {
      resetSuccess.value = '';
    }, 2000);
  }
}

async function deleteTasks(userId) {
  try {
    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    const token = tokenMeta ? tokenMeta.getAttribute('content') : '';
    const response = await fetch(`/admin/tasks/${userId}/delete`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
      body: JSON.stringify({}),
    });
    if (!response.ok) {
      throw new Error('Failed to delete tasks');
    }
    resetSuccess.value = 'Tasks deleted successfully!';
    assignedUsers.value.delete(userId); // Remove user from assigned tasks tracking
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

const showAssignTasksModal = ref(false);
const selectedUser = ref(null);
const tasksNumber = ref(0);
const luckyOrder = ref(0);

function closeAssignTasksModal() {
  showAssignTasksModal.value = false;
  selectedUser.value = null;
  tasksNumber.value = 0;
  luckyOrder.value = 0;
}

onMounted(() => {
  if (window.Echo) {
    window.Echo.channel('admin.orders')
      .listen('.OrderConfirmed', (e) => {
        // Only update if modal is open for the user whose order was confirmed
        if (showModal.value && modalUser.value && e.order.user_id === modalUser.value.id) {
          // Find the task in tasksByUser and update its status
          const idx = tasksByUser.value.findIndex(t => t.product_id === e.order.product_id);
          if (idx !== -1) {
            tasksByUser.value[idx].status = 'confirmed';
          }
        }
      });
  }

  if (modalUser.value) {
    // Listen for the OrderConfirmed event
    Echo.private(`orders.${modalUser.value.id}`)
      .listen('OrderConfirmed', (event) => {
        const task = tasksByUser.value.find(t => t.id === event.order.task_id);
        if (task) {
          task.status = 'Success'; // Update the task status
        }
      });
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
        <div class="mb-4">
          <label class="block text-sm font-medium text-white mb-2">Enter Lucky Order</label>
          <input v-model="luckyOrder" type="number" class="w-full h-10 rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-3 placeholder-slate-400 backdrop-blur-sm shadow-lg" />
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
                    <td class="p-4 text-sm text-slate-800 font-medium">{{ user.name }}</td>
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
              v-if="task.product_type !== 'Lucky Order' && task.status !== 'confirmed' && modalUser?.id"
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


  </AdminLayout>
</template>