<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, computed } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";

// Access page props using usePage()
const page = usePage();

// Search filter
const searchQuery = ref("");
const searchType = ref("");

// Filter products by product_id, title, and type
const filteredProducts = computed(() => {
  let filtered = page.props.products;
  
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(product =>
      product.product_id.toString().includes(searchQuery.value) ||
      (product.title && product.title.toLowerCase().includes(query))
    );
  }
  
  if (searchType.value) {
    filtered = filtered.filter(product =>
      product.type === searchType.value
    );
  }
  
  return filtered;
});

// Product counts by type
const productCounts = computed(() => {
  const counts = {
    VIP1: 0, VIP2: 0, VIP3: 0, VIP4: 0, VIP5: 0, VIP6: 0, VIP7: 0, 'Lucky Order': 0
  };
  page.props.products.forEach(product => {
    if (counts.hasOwnProperty(product.type)) {
      counts[product.type]++;
    }
  });
  return counts;
});

const totalProducts = computed(() => page.props.products.length);

// Upload Form
const form = useForm({
  title: "",
  description: "",
  type: "VIP1",
  purchase_price: "",
  selling_price: "",
  commission_percentage: "",
  image: null,
  _token: page.props.csrf_token,
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
  if (form.image) {
    const reader = new FileReader();
    reader.onload = (event) => {
      imagePreview.value = event.target.result;
    };
    reader.readAsDataURL(form.image);
  }
}

// Handle paste event for screenshots
function handlePaste(e) {
  const items = e.clipboardData?.items;
  if (!items) return;
  
  for (let i = 0; i < items.length; i++) {
    if (items[i].type.indexOf('image') !== -1) {
      const blob = items[i].getAsFile();
      form.image = blob;
      e.target.value = ''; // Clear textarea
      
      // Show preview
      const reader = new FileReader();
      reader.onload = (event) => {
        imagePreview.value = event.target.result;
      };
      reader.readAsDataURL(blob);
      break;
    }
  }
}

// Upload product
function submit() {
  successMessage.value = "";
  errorMessage.value = "";
  
  // Refresh CSRF token
  form._token = page.props.csrf_token;

  form.post(route("admin.products.store"), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      successMessage.value = "✅ Product uploaded successfully!";
      setTimeout(() => successMessage.value = "", 3000);
      form.reset();
      form.type = "VIP1";
      form._token = page.props.csrf_token;
      imagePreview.value = null;
      searchQuery.value = "";
      showModal.value = false;
    },
    onError: (errors) => {
      if (errors && (errors.message?.includes('419') || errors.status === 419)) {
        window.location.reload();
        return;
      }
      errorMessage.value = "❌ Failed to upload product: " + JSON.stringify(errors);
      setTimeout(() => errorMessage.value = "", 5000);
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
    data: {
      _token: page.props.csrf_token
    },
    onSuccess: () => {
      successMessage.value = "🗑️ Product deleted successfully!";
      setTimeout(() => successMessage.value = "", 3000);
      showDeleteModal.value = false;
      deleteProductId.value = null;
    },
    onError: (errors) => {
      if (errors && (errors.message?.includes('419') || errors.status === 419)) {
        window.location.reload();
        return;
      }
      errorMessage.value = "❌ Failed to delete product: " + JSON.stringify(errors);
      setTimeout(() => errorMessage.value = "", 5000);
      showDeleteModal.value = false;
      deleteProductId.value = null;
    },
  });
}

// Edit product
const editing = ref(null);
const imagePreview = ref(null);

const editForm = useForm({
  title: "",
  description: "",
  type: "VIP1",
  purchase_price: "",
  selling_price: "",
  commission_percentage: "",
  image: null,
  _token: page.props.csrf_token,
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
  
  // Refresh CSRF token
  editForm._token = page.props.csrf_token;

  editForm.post(route("admin.products.update", id), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      successMessage.value = "✏️ Product updated successfully!";
      setTimeout(() => successMessage.value = "", 3000);
      editing.value = null;
    },
    onError: (errors) => {
      if (errors && (errors.message?.includes('419') || errors.status === 419)) {
        window.location.reload();
        return;
      }
      errorMessage.value = "❌ Failed to update product: " + JSON.stringify(errors);
      setTimeout(() => errorMessage.value = "", 5000);
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

// Bulk Commission Update
const showCommissionModal = ref(false);
const commissionForm = useForm({
  product_type: "VIP1",
  commission_percentage: "",
  _token: page.props.csrf_token,
});

function updateBulkCommission() {
  successMessage.value = "";
  errorMessage.value = "";
  
  // Refresh CSRF token
  commissionForm._token = page.props.csrf_token;

  commissionForm.post(route("admin.products.bulk-update-commission"), {
    preserveScroll: true,
    onSuccess: () => {
      successMessage.value = `✅ Commission updated successfully for all ${commissionForm.product_type} products!`;
      setTimeout(() => successMessage.value = "", 3000);
      commissionForm.commission_percentage = "";
      showCommissionModal.value = false;
    },
    onError: (errors) => {
      if (errors && (errors.message?.includes('419') || errors.status === 419)) {
        window.location.reload();
        return;
      }
      errorMessage.value = "❌ Failed to update commission: " + JSON.stringify(errors);
      setTimeout(() => errorMessage.value = "", 5000);
    },
  });
}
</script>

<template>
  <AdminLayout>
    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 rounded-2xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto">
      <h1 class="text-lg font-bold text-slate-800 drop-shadow-sm mb-4">Product Management</h1>

      <!-- Product Statistics -->
      <div class="bg-white/20 backdrop-blur-sm px-4 py-3 rounded-lg mb-4 border border-white/30">
        <div class="flex items-center justify-between flex-wrap gap-3 text-xs text-slate-700">
          <span class="font-semibold text-slate-800">Products:</span>
          <span class="bg-white/30 px-2 py-1 rounded">VIP1: <strong>{{ productCounts.VIP1 }}</strong></span>
          <span class="bg-white/30 px-2 py-1 rounded">VIP2: <strong>{{ productCounts.VIP2 }}</strong></span>
          <span class="bg-white/30 px-2 py-1 rounded">VIP3: <strong>{{ productCounts.VIP3 }}</strong></span>
          <span class="bg-white/30 px-2 py-1 rounded">VIP4: <strong>{{ productCounts.VIP4 }}</strong></span>
          <span class="bg-white/30 px-2 py-1 rounded">VIP5: <strong>{{ productCounts.VIP5 }}</strong></span>
          <span class="bg-white/30 px-2 py-1 rounded">VIP6: <strong>{{ productCounts.VIP6 }}</strong></span>
          <span class="bg-white/30 px-2 py-1 rounded">VIP7: <strong>{{ productCounts.VIP7 }}</strong></span>
          <span class="bg-yellow-100/50 px-2 py-1 rounded">Lucky: <strong>{{ productCounts['Lucky Order'] }}</strong></span>
          <span class="bg-blue-100/50 px-2 py-1 rounded text-blue-700 font-semibold">Total: <strong>{{ totalProducts }}</strong></span>
          <button
            @click="showCommissionModal = true"
            class="px-3 py-1 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white rounded-lg text-xs font-medium transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl ml-auto"
          >
            Change Commission
          </button>
        </div>
      </div>

      <!-- Search Bar and Add Product Button -->
      <div class="bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm p-4 rounded-xl shadow-lg border border-white/30 mb-6">
          <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div class="flex-1 space-y-4">
              <h2 class="text-base font-medium text-slate-800 drop-shadow-sm">Search Products</h2>
              <div class="flex flex-col sm:flex-row gap-3">
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search by Product ID or Title..."
                  class="flex-1 h-12 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 placeholder-slate-400 backdrop-blur-sm shadow-lg"
                />
                <select
                  v-model="searchType"
                  class="flex-1 h-12 rounded-xl bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 backdrop-blur-sm shadow-lg"
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
              class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg text-sm font-medium transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl"
            >
              Add New Product
            </button>
          </div>
        </div>

        <!-- Success/Error Messages -->
        <div v-if="successMessage" class="mb-6 p-4 rounded-xl bg-green-100/80 border border-green-200 text-green-700 text-sm flex items-center gap-2 backdrop-blur-sm">
          <span>{{ successMessage }}</span>
        </div>
        <div v-if="errorMessage" class="mb-6 p-4 rounded-xl bg-red-100/80 border border-red-200 text-red-700 text-sm flex items-center gap-2 backdrop-blur-sm">
          <span>{{ errorMessage }}</span>
        </div>

    <!-- Add Product Modal -->
    <transition name="modal">
      <div
        v-if="showModal"
        class="fixed inset-0 bg-black bg-opacity-40 flex items-start justify-center z-50 p-4 overflow-y-auto"
        @click="showModal = false"
      >
        <div
          class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl p-6 w-full max-w-2xl mx-4 border border-white/20 shadow-2xl my-8"
          @click.stop
        >
          <h2 class="text-lg font-semibold text-white text-center mb-4">Add New Product</h2>
          <form @submit.prevent="submit" class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-white mb-1">Title</label>
                <input
                  v-model="form.title"
                  type="text"
                  class="w-full h-9 rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-3 text-sm placeholder-slate-400 backdrop-blur-sm shadow-lg"
                  required
                />
                <span v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</span>
              </div>

              <div>
                <label class="block text-xs font-medium text-white mb-1">Type</label>
                <select
                  v-model="form.type"
                  class="w-full h-9 rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-3 text-sm backdrop-blur-sm shadow-lg"
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
            </div>

            <div class="grid grid-cols-3 gap-2">
              <div>
                <label class="block text-xs font-medium text-white mb-1">Purchase Price</label>
                <input
                  v-model="form.purchase_price"
                  type="number"
                  step="0.01"
                  class="w-full h-9 rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-2 text-sm placeholder-slate-400 backdrop-blur-sm shadow-lg"
                  required
                />
                <span v-if="form.errors.purchase_price" class="text-red-500 text-xs mt-1">{{ form.errors.purchase_price }}</span>
              </div>

              <div>
                <label class="block text-xs font-medium text-white mb-1">Selling Price</label>
                <input
                  v-model="form.selling_price"
                  type="number"
                  step="0.01"
                  class="w-full h-9 rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-2 text-sm placeholder-slate-400 backdrop-blur-sm shadow-lg"
                  required
                />
                <span v-if="form.errors.selling_price" class="text-red-500 text-xs mt-1">{{ form.errors.selling_price }}</span>
              </div>

              <div>
                <label class="block text-xs font-medium text-white mb-1">Commission %</label>
                <input
                  v-model="form.commission_percentage"
                  type="number"
                  step="0.01"
                  max="100"
                  class="w-full h-9 rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-2 text-sm placeholder-slate-400 backdrop-blur-sm shadow-lg"
                  required
                />
                <span v-if="form.errors.commission_percentage" class="text-red-500 text-xs mt-1">{{ form.errors.commission_percentage }}</span>
              </div>
            </div>

            <div>
              <label class="block text-xs font-medium text-white mb-1">Product Image</label>
              <div class="space-y-2">
                <input
                  type="file"
                  @change="handleFileChange"
                  accept="image/*"
                  class="w-full text-xs text-white file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-white/50 file:text-slate-900 file:hover:bg-white/70 file:text-xs backdrop-blur-sm"
                />
                <div class="flex items-center gap-2">
                  <div class="flex-1 h-px bg-white/30"></div>
                  <span class="text-xs text-white/70">OR</span>
                  <div class="flex-1 h-px bg-white/30"></div>
                </div>
                <div class="border-2 border-dashed border-white/30 rounded-lg p-2 hover:border-cyan-400 transition">
                  <textarea
                    @paste="handlePaste"
                    placeholder="Paste image (Ctrl+V)"
                    class="w-full border-0 text-xs focus:ring-0 focus:outline-none resize-none bg-transparent text-white placeholder-white/50"
                    rows="1"
                  ></textarea>
                </div>
                <div v-if="imagePreview" class="mt-2">
                  <img :src="imagePreview" class="h-16 w-16 object-cover rounded border border-white/30" />
                </div>
              </div>
              <span v-if="form.errors.image" class="text-red-500 text-xs mt-1">{{ form.errors.image }}</span>
            </div>

            <div>
              <label class="block text-xs font-medium text-white mb-1">Description</label>
              <textarea
                v-model="form.description"
                class="w-full rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-3 py-2 text-sm placeholder-slate-400 backdrop-blur-sm shadow-lg resize-none"
                rows="2"
              ></textarea>
              <span v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</span>
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <button
                type="button"
                @click="showModal = false"
                class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-lg text-sm hover:bg-white/30 text-white border border-white/30"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="form.processing"
                class="px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg text-sm hover:from-green-600 hover:to-green-700 transform hover:scale-105 transition-all duration-200 disabled:opacity-50"
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

    <!-- Bulk Commission Update Modal -->
    <transition name="modal">
      <div
        v-if="showCommissionModal"
        class="fixed inset-0 bg-black bg-opacity-40 flex items-start justify-center z-50 p-4 overflow-y-auto"
        @click="showCommissionModal = false"
      >
        <div
          class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl p-6 w-full max-w-md mx-4 border border-white/20 shadow-2xl my-8"
          @click.stop
        >
          <h2 class="text-lg font-semibold text-white text-center mb-6">Change Commission by Product Type</h2>
          <form @submit.prevent="updateBulkCommission" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-white mb-2">Select Product Type</label>
              <select
                v-model="commissionForm.product_type"
                class="w-full h-11 rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 text-sm backdrop-blur-sm shadow-lg"
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
              <span v-if="commissionForm.errors.product_type" class="text-red-500 text-xs mt-1">{{ commissionForm.errors.product_type }}</span>
            </div>

            <div>
              <label class="block text-sm font-medium text-white mb-2">New Commission Percentage</label>
              <input
                v-model="commissionForm.commission_percentage"
                type="number"
                step="0.01"
                min="0"
                max="100"
                placeholder="Enter commission percentage"
                class="w-full h-11 rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-4 text-sm placeholder-slate-400 backdrop-blur-sm shadow-lg"
                required
              />
              <span v-if="commissionForm.errors.commission_percentage" class="text-red-500 text-xs mt-1">{{ commissionForm.errors.commission_percentage }}</span>
            </div>

            <div class="bg-yellow-100/20 border border-yellow-300/30 rounded-lg p-3 mt-4">
              <p class="text-xs text-white">
                ⚠️ This will update the commission percentage for <strong>all products</strong> of type <strong>{{ commissionForm.product_type }}</strong>.
              </p>
            </div>

            <div class="flex justify-end gap-3 pt-2">
              <button
                type="button"
                @click="showCommissionModal = false"
                class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-lg text-sm hover:bg-white/30 text-white border border-white/30 transition"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="commissionForm.processing"
                class="px-4 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 text-white rounded-lg text-sm hover:from-cyan-600 hover:to-blue-700 transform hover:scale-105 transition-all duration-200 disabled:opacity-50 font-medium"
              >
                Update Commission
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>

      <!-- Product List -->
      <div class="bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm rounded-xl shadow-lg overflow-hidden border border-white/30 mt-6">
        <div class="p-4">
          <h2 class="text-base font-medium text-slate-800 drop-shadow-sm mb-4">Product List</h2>
          <div class="bg-white/20 backdrop-blur-sm rounded-xl shadow-lg overflow-hidden border border-white/30">
              <div class="overflow-x-auto">
                <table class="w-full">
                  <thead>
                    <tr class="bg-white/20 text-slate-700 text-xs font-medium">
                      <th class="px-2 py-2 text-left">ID</th>
                      <th class="px-2 py-2 text-left">Image</th>
                      <th class="px-2 py-2 text-left">Title</th>
                      <th class="px-2 py-2 text-left">Type</th>
                      <th class="px-2 py-2 text-left">Description</th>
                      <th class="px-2 py-2 text-left">Buy</th>
                      <th class="px-2 py-2 text-left">Sell</th>
                      <th class="px-2 py-2 text-left">Reward</th>
                      <th class="px-2 py-2 text-left">%</th>
                      <th class="px-2 py-2 text-left">Actions</th>
                    </tr>
                  </thead>
            <tbody>
                    <tr
                      v-for="product in filteredProducts"
                      :key="product.id"
                      class="border-t border-white/20 hover:bg-white/10 transition-all duration-200"
                    >
                      <td class="px-2 py-2 text-xs text-slate-800 font-medium">{{ product.product_id }}</td>
                      <td class="px-2 py-2">
                        <img
                          :src="`/storage/${product.image_path}`"
                          class="w-8 h-8 object-cover rounded cursor-pointer hover:scale-105 transition"
                          @click="openLightbox(`/storage/${product.image_path}`)"
                        />
                      </td>

                <!-- Normal View -->
                <template v-if="editing !== product.id">
                  <td class="px-2 py-2 text-xs text-gray-700">{{ product.title }}</td>
                  <td class="px-2 py-2 text-xs text-gray-700">
                    <span
                      class="inline-block px-1 py-0.5 rounded text-xs"
                      :class="{
                        'bg-purple-100 text-purple-700': product.type === 'VIP1' || product.type === 'VIP2' || product.type === 'VIP3' || product.type === 'VIP4' || product.type === 'VIP5' || product.type === 'VIP6' || product.type === 'VIP7',
                        'bg-yellow-100 text-yellow-700': product.type === 'Lucky Order',
                      }"
                    >
                      {{ product.type }}
                    </span>
                  </td>
                  <td class="px-2 py-2 text-xs text-gray-600 max-w-xs truncate">{{ product.description }}</td>
                  <td class="px-2 py-2 text-xs text-gray-700">${{ product.purchase_price }}</td>
                  <td class="px-2 py-2 text-xs text-gray-700">${{ product.selling_price }}</td>
                  <td class="px-2 py-2 text-xs text-gray-700">${{ product.commission_reward }}</td>
                  <td class="px-2 py-2 text-xs text-gray-700">{{ product.commission_percentage }}%</td>
                  <td class="px-2 py-2 flex gap-1">
                    <button
                      @click="startEdit(product)"
                      class="px-2 py-1 bg-blue-600 text-white rounded text-xs font-medium hover:bg-blue-700 transition"
                    >
                      Edit
                    </button>
                    <button
                      @click="deleteProduct(product.id)"
                      class="px-2 py-1 bg-red-600 text-white rounded text-xs font-medium hover:bg-red-700 transition"
                    >
                      Del
                    </button>
                  </td>
                </template>

                <!-- Edit Mode -->
                <template v-else>
                  <td class="px-2 py-2">
                    <input
                      v-model="editForm.title"
                      class="w-full border border-gray-300 rounded px-1 py-1 text-xs focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition"
                    />
                    <span v-if="editForm.errors.title" class="text-red-500 text-xs mt-1">{{ editForm.errors.title }}</span>
                  </td>
                  <td class="px-2 py-2">
                    <select
                      v-model="editForm.type"
                      class="w-full border border-gray-300 rounded px-1 py-1 text-xs focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition"
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
                  <td class="px-2 py-2">
                    <textarea
                      v-model="editForm.description"
                      class="w-full border border-gray-300 rounded px-1 py-1 text-xs focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition"
                      rows="1"
                    ></textarea>
                    <span v-if="editForm.errors.description" class="text-red-500 text-xs mt-1">{{ editForm.errors.description }}</span>
                  </td>
                  <td class="px-2 py-2">
                    <input
                      v-model="editForm.purchase_price"
                      type="number"
                      step="0.01"
                      class="w-full border border-gray-300 rounded px-1 py-1 text-xs focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition"
                    />
                    <span v-if="editForm.errors.purchase_price" class="text-red-500 text-xs mt-1">{{ editForm.errors.purchase_price }}</span>
                  </td>
                  <td class="px-2 py-2">
                    <input
                      v-model="editForm.selling_price"
                      type="number"
                      step="0.01"
                      class="w-full border border-gray-300 rounded px-1 py-1 text-xs focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition"
                    />
                    <span v-if="editForm.errors.selling_price" class="text-red-500 text-xs mt-1">{{ editForm.errors.selling_price }}</span>
                  </td>
                  <td class="px-2 py-2">
                    <input
                      v-model="editForm.commission_percentage"
                      type="number"
                      step="0.01"
                      max="100"
                      class="w-full border border-gray-300 rounded px-1 py-1 text-xs focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition"
                    />
                    <span v-if="editForm.errors.commission_percentage" class="text-red-500 text-xs mt-1">{{ editForm.errors.commission_percentage }}</span>
                  </td>
                  <td class="px-2 py-2">
                    <input
                      type="file"
                      @change="handleEditFile"
                      accept="image/*"
                      class="text-xs text-gray-500 file:mr-1 file:py-1 file:px-2 file:rounded file:border-0 file:bg-purple-50 file:text-purple-700 file:hover:bg-purple-100"
                    />
                    <span v-if="editForm.errors.image" class="text-red-500 text-xs mt-1">{{ editForm.errors.image }}</span>
                  </td>
                  <td class="px-2 py-2 flex gap-1">
                    <button
                      @click="updateProduct(product.id)"
                      class="px-2 py-1 bg-green-600 text-white rounded text-xs font-medium hover:bg-green-700 transition"
                    >
                      Save
                    </button>
                    <button
                      @click="editing = null"
                      class="px-2 py-1 bg-gray-200 text-gray-700 rounded text-xs font-medium hover:bg-gray-300 transition"
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