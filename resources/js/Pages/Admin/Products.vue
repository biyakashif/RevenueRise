<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, computed } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";

// Access page props using usePage()
const page = usePage();

// Search filter
const searchQuery = ref("");
const searchType = ref("");

// Filter products by product_id and type
const filteredProducts = computed(() => {
  let filtered = page.props.products;
  
  if (searchQuery.value) {
    filtered = filtered.filter(product =>
      product.product_id.toString().includes(searchQuery.value)
    );
  }
  
  if (searchType.value) {
    filtered = filtered.filter(product =>
      product.type === searchType.value
    );
  }
  
  return filtered;
});

// Upload Form
const form = useForm({
  title: "",
  description: "",
  type: "VIP1",
  purchase_price: "",
  selling_price: "",
  commission_percentage: "",
  image: null,
});

// Messages
const successMessage = ref("");
const errorMessage = ref("");

// Modal state for add product
const showModal = ref(false);

// Modal state for delete confirmation
const showDeleteModal = ref(false);
const deleteProductId = ref(null);

// File change handler
function handleFileChange(e) {
  form.image = e.target.files[0];
}

// Upload product
function submit() {
  successMessage.value = "";
  errorMessage.value = "";

  form.post(route("admin.products.store"), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      successMessage.value = "✅ Product uploaded successfully!";
      form.reset();
      form.type = "VIP1";
      searchQuery.value = "";
      showModal.value = false;
    },
    onError: (errors) => {
      errorMessage.value = "❌ Failed to upload product: " + JSON.stringify(errors);
    },
  });
}

// Delete product
function deleteProduct(id) {
  successMessage.value = "";
  errorMessage.value = "";
  deleteProductId.value = id;
  showDeleteModal.value = true;
}

function confirmDelete() {
  router.delete(route("admin.products.destroy", deleteProductId.value), {
    preserveScroll: true,
    onSuccess: () => {
      successMessage.value = "🗑️ Product deleted successfully!";
      showDeleteModal.value = false;
      deleteProductId.value = null;
    },
    onError: (errors) => {
      errorMessage.value = "❌ Failed to delete product: " + JSON.stringify(errors);
      showDeleteModal.value = false;
      deleteProductId.value = null;
    },
  });
}

// Edit product
const editing = ref(null);
const editForm = useForm({
  title: "",
  description: "",
  type: "VIP1",
  purchase_price: "",
  selling_price: "",
  commission_percentage: "",
  image: null,
});

function startEdit(product) {
  editing.value = product.id;
  editForm.title = product.title;
  editForm.description = product.description;
  editForm.type = product.type;
  editForm.purchase_price = product.purchase_price;
  editForm.selling_price = product.selling_price;
  editForm.commission_percentage = product.commission_percentage;
  editForm.image = null;
}

function handleEditFile(e) {
  editForm.image = e.target.files[0];
}

function updateProduct(id) {
  successMessage.value = "";
  errorMessage.value = "";

  editForm.post(route("admin.products.update", id), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      successMessage.value = "✏️ Product updated successfully!";
      editing.value = null;
    },
    onError: (errors) => {
      errorMessage.value = "❌ Failed to update product: " + JSON.stringify(errors);
    },
  });
}

// Lightbox
const lightboxImage = ref(null);
function openLightbox(imageUrl) {
  lightboxImage.value = imageUrl;
}
function closeLightbox() {
  lightboxImage.value = null;
}
</script>

<template>
  <AdminLayout>
    <!-- Header Section -->
    <div class="mb-6">
      <h1 class="text-2xl font-semibold text-gray-900">Product Management</h1>
      <p class="text-sm text-gray-600 mt-1">Manage and organize your product catalog</p>
    </div>

    <!-- Search Bar and Add Product Button -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6 border border-gray-200">
      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="flex-1 space-y-4">
          <h2 class="text-base font-medium text-gray-800">Search Products</h2>
          <div class="flex flex-col sm:flex-row gap-3">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search by Product ID..."
              class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
            />
            <select
              v-model="searchType"
              class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
            >
              <option value="">All Types</option>
              <option value="VIP1">VIP1</option>
              <option value="VIP2">VIP2</option>
              <option value="VIP3">VIP3</option>
              <option value="VIP4">VIP4</option>
              <option value="VIP5">VIP5</option>
              <option value="VIP6">VIP6</option>
              <option value="VIP7">VIP7</option>
              <option value="Lucky Order">Lucky Order</option>
            </select>
          </div>
        </div>
        <button
          @click="showModal = true"
          class="bg-purple-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-purple-700 focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition"
        >
          Add New Product
        </button>
      </div>
    </div>

    <!-- Success/Error Messages -->
    <div v-if="successMessage" class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm flex items-center gap-2">
      <span>{{ successMessage }}</span>
    </div>
    <div v-if="errorMessage" class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm flex items-center gap-2">
      <span>{{ errorMessage }}</span>
    </div>

    <!-- Add Product Modal -->
    <transition name="modal">
      <div
        v-if="showModal"
        class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4"
        @click="showModal = false"
      >
        <div
          class="bg-white rounded-lg shadow-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto"
          @click.stop
        >
          <h2 class="text-xl font-semibold text-gray-900 mb-6">Add New Product</h2>
          <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
              <input
                v-model="form.title"
                type="text"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
                required
              />
              <span v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</span>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
              <select
                v-model="form.type"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
                required
              >
                <option value="VIP1">VIP1</option>
                <option value="VIP2">VIP2</option>
                <option value="VIP3">VIP3</option>
                <option value="VIP4">VIP4</option>
                <option value="VIP5">VIP5</option>
                <option value="VIP6">VIP6</option>
                <option value="VIP7">VIP7</option>
                <option value="Lucky Order">Lucky Order</option>
              </select>
              <span v-if="form.errors.type" class="text-red-500 text-xs mt-1">{{ form.errors.type }}</span>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Price</label>
              <input
                v-model="form.purchase_price"
                type="number"
                step="0.01"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
                required
              />
              <span v-if="form.errors.purchase_price" class="text-red-500 text-xs mt-1">{{ form.errors.purchase_price }}</span>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Selling Price</label>
              <input
                v-model="form.selling_price"
                type="number"
                step="0.01"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
                required
              />
              <span v-if="form.errors.selling_price" class="text-red-500 text-xs mt-1">{{ form.errors.selling_price }}</span>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Commission Percentage (%)</label>
              <input
                v-model="form.commission_percentage"
                type="number"
                step="0.01"
                max="100"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
                required
              />
              <span v-if="form.errors.commission_percentage" class="text-red-500 text-xs mt-1">{{ form.errors.commission_percentage }}</span>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Product Image</label>
              <input
                type="file"
                @change="handleFileChange"
                accept="image/*"
                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-purple-50 file:text-purple-700 file:hover:bg-purple-100"
                required
              />
              <span v-if="form.errors.image" class="text-red-500 text-xs mt-1">{{ form.errors.image }}</span>
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <textarea
                v-model="form.description"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
                rows="4"
              ></textarea>
              <span v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</span>
            </div>

            <div class="md:col-span-2 flex justify-end gap-3">
              <button
                type="button"
                @click="showModal = false"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-300 focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="form.processing"
                class="px-4 py-2 bg-purple-600 text-white rounded-md text-sm font-medium hover:bg-purple-700 focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:opacity-50 transition"
              >
                Add Product
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>

    <!-- Delete Confirmation Modal -->
    <transition name="modal">
      <div
        v-if="showDeleteModal"
        class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4"
        @click="showDeleteModal = false"
      >
        <div
          class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md"
          @click.stop
        >
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Confirm Deletion</h2>
          <p class="text-sm text-gray-600 mb-6">Are you sure you want to delete this product? This action cannot be undone.</p>
          <div class="flex justify-end gap-3">
            <button
              @click="showDeleteModal = false"
              class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-300 focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition"
            >
              Cancel
            </button>
            <button
              @click="confirmDelete"
              class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Product List -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="p-6">
        <h2 class="text-base font-medium text-gray-800 mb-4">Product List</h2>
        <div class="overflow-x-auto">
          <table class="w-full border-collapse">
            <thead>
              <tr class="bg-gray-50 text-gray-700 text-sm font-medium">
                <th class="p-3 text-left">Product ID</th>
                <th class="p-3 text-left">Image</th>
                <th class="p-3 text-left">Title</th>
                <th class="p-3 text-left">Type</th>
                <th class="p-3 text-left">Description</th>
                <th class="p-3 text-left">Purchase Price</th>
                <th class="p-3 text-left">Selling Price</th>
                <th class="p-3 text-left">Commission Reward</th>
                <th class="p-3 text-left">Commission %</th>
                <th class="p-3 text-left">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="product in filteredProducts"
                :key="product.id"
                class="border-t border-gray-200 hover:bg-gray-50 transition"
              >
                <td class="p-3 text-sm text-gray-700">{{ product.product_id }}</td>
                <td class="p-3">
                  <img
                    :src="`/storage/${product.image_path}`"
                    class="w-10 h-10 object-cover rounded cursor-pointer hover:scale-105 transition"
                    @click="openLightbox(`/storage/${product.image_path}`)"
                  />
                </td>

                <!-- Normal View -->
                <template v-if="editing !== product.id">
                  <td class="p-3 text-sm text-gray-700">{{ product.title }}</td>
                  <td class="p-3 text-sm text-gray-700">
                    <span
                      class="inline-block px-2 py-1 rounded text-xs"
                      :class="{
                        'bg-purple-100 text-purple-700': product.type === 'VIP1' || product.type === 'VIP2' || product.type === 'VIP3' || product.type === 'VIP4' || product.type === 'VIP5' || product.type === 'VIP6' || product.type === 'VIP7',
                        'bg-yellow-100 text-yellow-700': product.type === 'Lucky Order',
                      }"
                    >
                      {{ product.type }}
                    </span>
                  </td>
                  <td class="p-3 text-xs text-gray-600 max-w-xs truncate">{{ product.description }}</td>
                  <td class="p-3 text-sm text-gray-700">${{ product.purchase_price }}</td>
                  <td class="p-3 text-sm text-gray-700">${{ product.selling_price }}</td>
                  <td class="p-3 text-sm text-gray-700">${{ product.commission_reward }}</td>
                  <td class="p-3 text-sm text-gray-700">{{ product.commission_percentage }}%</td>
                  <td class="p-3 flex gap-2">
                    <button
                      @click="startEdit(product)"
                      class="px-3 py-1 bg-blue-600 text-white rounded-md text-xs font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition"
                    >
                      Edit
                    </button>
                    <button
                      @click="deleteProduct(product.id)"
                      class="px-3 py-1 bg-red-600 text-white rounded-md text-xs font-medium hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition"
                    >
                      Delete
                    </button>
                  </td>
                </template>

                <!-- Edit Mode -->
                <template v-else>
                  <td class="p-3">
                    <input
                      v-model="editForm.title"
                      class="w-full border border-gray-300 rounded-md px-2 py-1 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
                    />
                    <span v-if="editForm.errors.title" class="text-red-500 text-xs mt-1">{{ editForm.errors.title }}</span>
                  </td>
                  <td class="p-3">
                    <select
                      v-model="editForm.type"
                      class="w-full border border-gray-300 rounded-md px-2 py-1 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
                    >
                      <option value="VIP1">VIP1</option>
                      <option value="VIP2">VIP2</option>
                      <option value="VIP3">VIP3</option>
                      <option value="VIP4">VIP4</option>
                      <option value="VIP5">VIP5</option>
                      <option value="VIP6">VIP6</option>
                      <option value="VIP7">VIP7</option>
                      <option value="Lucky Order">Lucky Order</option>
                    </select>
                    <span v-if="editForm.errors.type" class="text-red-500 text-xs mt-1">{{ editForm.errors.type }}</span>
                  </td>
                  <td class="p-3">
                    <textarea
                      v-model="editForm.description"
                      class="w-full border border-gray-300 rounded-md px-2 py-1 text-xs focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
                      rows="2"
                    ></textarea>
                    <span v-if="editForm.errors.description" class="text-red-500 text-xs mt-1">{{ editForm.errors.description }}</span>
                  </td>
                  <td class="p-3">
                    <input
                      v-model="editForm.purchase_price"
                      type="number"
                      step="0.01"
                      class="w-full border border-gray-300 rounded-md px-2 py-1 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
                    />
                    <span v-if="editForm.errors.purchase_price" class="text-red-500 text-xs mt-1">{{ editForm.errors.purchase_price }}</span>
                  </td>
                  <td class="p-3">
                    <input
                      v-model="editForm.selling_price"
                      type="number"
                      step="0.01"
                      class="w-full border border-gray-300 rounded-md px-2 py-1 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
                    />
                    <span v-if="editForm.errors.selling_price" class="text-red-500 text-xs mt-1">{{ editForm.errors.selling_price }}</span>
                  </td>
                  <td class="p-3">
                    <input
                      v-model="editForm.commission_percentage"
                      type="number"
                      step="0.01"
                      max="100"
                      class="w-full border border-gray-300 rounded-md px-2 py-1 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
                    />
                    <span v-if="editForm.errors.commission_percentage" class="text-red-500 text-xs mt-1">{{ editForm.errors.commission_percentage }}</span>
                  </td>
                  <td class="p-3">
                    <input
                      type="file"
                      @change="handleEditFile"
                      accept="image/*"
                      class="text-sm text-gray-500 file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:bg-purple-50 file:text-purple-700 file:hover:bg-purple-100"
                    />
                    <span v-if="editForm.errors.image" class="text-red-500 text-xs mt-1">{{ editForm.errors.image }}</span>
                  </td>
                  <td class="p-3 flex gap-2">
                    <button
                      @click="updateProduct(product.id)"
                      class="px-3 py-1 bg-green-600 text-white rounded-md text-xs font-medium hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition"
                    >
                      Save
                    </button>
                    <button
                      @click="editing = null"
                      class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md text-xs font-medium hover:bg-gray-300 focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition"
                    >
                      Cancel
                    </button>
                  </td>
                </template>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Lightbox Modal -->
    <transition name="modal">
      <div
        v-if="lightboxImage"
        class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50"
        @click="closeLightbox"
      >
        <img :src="lightboxImage" class="max-h-[90vh] max-w-[90vw] rounded-lg shadow-lg" />
      </div>
    </transition>
  </AdminLayout>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
.modal-enter-active .bg-white,
.modal-leave-active .bg-white {
  transition: transform 0.3s ease;
}
.modal-enter-from .bg-white,
.modal-leave-to .bg-white {
  transform: scale(0.95);
}
</style>