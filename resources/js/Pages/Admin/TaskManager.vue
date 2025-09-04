<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const users = page.props.users;
const tasksByUser = ref([]);
const showModal = ref(false);
const modalUser = ref(null);
const resetSuccess = ref(""); // Success message
let pollInterval = null;

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

async function applyLuckyOrder(taskId, userId) {
  try {
    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    const token = tokenMeta ? tokenMeta.getAttribute('content') : '';
    const response = await fetch(`/admin/tasks/${userId}/replace/${taskId}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json',
      },
      body: JSON.stringify({}),
    });
    if (!response.ok) {
      throw new Error('Failed to replace task');
    }
    const data = await response.json();
    tasksByUser.value = data.tasks;
  } catch (error) {
    alert('Error: ' + error.message);
  }
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
        'Accept': 'application/json',
      },
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

async function deleteTasks(userId) {
  try {
    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    const token = tokenMeta ? tokenMeta.getAttribute('content') : '';
    const response = await fetch(`/admin/tasks/${userId}/delete`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json',
      },
      body: JSON.stringify({}),
    });
    if (!response.ok) {
      throw new Error('Failed to delete tasks');
    }
    resetSuccess.value = "Tasks deleted successfully!";
    setTimeout(() => {
      resetSuccess.value = "";
    }, 1000);
    // Optionally, clear modal if open
    showModal.value = false;
    tasksByUser.value = [];
    modalUser.value = null;
  } catch (error) {
    resetSuccess.value = "Error: " + error.message;
    setTimeout(() => {
      resetSuccess.value = "";
    }, 2000);
  }
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
});
</script>

<template>
  <AdminLayout>
    <h1 class="text-2xl font-bold mb-4">User Task Assignment</h1>
        <!-- Success message -->
    <div v-if="resetSuccess" class="mb-2 text-green-600 font-semibold text-center">
      {{ resetSuccess }}
    </div>
    <table class="min-w-full border mb-4">
      <thead>
        <tr>
          <th class="p-2 border">Name</th>
          <th class="p-2 border">VIP Level</th>
          <th class="p-2 border">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="user in users" :key="user.id">
          <td class="p-2 border">{{ user.name }}</td>
          <td class="p-2 border">{{ user.vip_level }}</td>
          <td class="p-2 border">
            <button @click="viewTasks(user.id)" class="bg-blue-600 text-white px-3 py-1 rounded">Tasks Details</button>
            <button @click="resetTasks(user.id)" class="bg-red-500 text-white px-3 py-1 rounded ml-2">Reset Task</button>
            <button @click="deleteTasks(user.id)" class="bg-gray-500 text-white px-3 py-1 rounded ml-2">Delete Tasks</button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal for tasks -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-4 rounded shadow-lg w-full max-w-md mx-2 overflow-y-auto max-h-[90vh]">
        <h2 class="text-lg font-bold mb-4 text-center">
          Tasks for {{ modalUser?.name }} ({{ modalUser?.vip_level }})
        </h2>
        <div v-if="tasksByUser && tasksByUser.length === 0" class="text-center text-gray-500">No tasks assigned.</div>
        <ul v-if="tasksByUser && tasksByUser.length > 0" class="space-y-3">
          <li
            v-for="task in tasksByUser"
            :key="task.id"
            class="flex items-center rounded-lg p-2 shadow-sm"
            :class="task.product_type === 'Lucky Order' ? 'bg-yellow-100' : 'bg-gray-50'"
          >
            <img
              :src="task.product?.image_path ? '/storage/' + task.product.image_path : '/default-product.png'"
              alt="Product"
              class="w-10 h-10 object-cover rounded mr-2 border"
            />
            <div class="flex-1">
              <div class="font-semibold text-sm">{{ task.product?.title }}</div>
              <div class="text-xs text-gray-600 mb-1">{{ task.product_type }}</div>
              <div class="flex gap-2 text-xs">
                <span class="text-green-700">Buy: {{ task.product?.purchase_price }}</span>
                <span class="text-blue-700">Sell: {{ task.product?.selling_price }}</span>
              </div>
            </div>
            <div class="text-xs text-gray-400 ml-2">#{{ task.position }}</div>
            <span v-if="task.status === 'confirmed'" class="ml-2 bg-green-500 text-white px-2 py-1 rounded text-xs">
              Confirmed
            </span>
            <button
              v-else-if="task.product_type !== 'Lucky Order'"
              @click="applyLuckyOrder(task.id, modalUser.id)"
              class="ml-2 bg-yellow-400 text-white px-2 py-1 rounded text-xs"
            >
              Apply L/O
            </button>
          </li>
        </ul>
        <button @click="closeModal" class="mt-6 w-full bg-blue-600 text-white py-2 rounded">Close</button>
      </div>
    </div>


  </AdminLayout>
</template>