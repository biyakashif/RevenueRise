<template>
  <AdminLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="bg-white rounded-lg shadow">


        <!-- Desktop Images Section -->
        <div class="p-6">
          <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-lg font-semibold text-gray-900">Desktop Slider Images</h2>
              <button
                @click="showUploadModal('desktop')"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium"
              >
                Add Desktop Image
              </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
              <div
                v-for="image in desktopImages"
                :key="image.id"
                class="border border-gray-200 rounded-lg overflow-hidden"
              >
                <div class="aspect-video bg-gray-100 relative">
                  <img
                    :src="`/storage/${image.image_path}`"
                    :alt="image.title"
                    class="w-full h-full object-cover"
                  />
                  <div class="absolute top-2 right-2">
                    <button
                      @click="toggleImage(image)"
                      :class="image.is_active ? 'bg-green-500' : 'bg-gray-400'"
                      class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs"
                    >
                      <svg v-if="image.is_active" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                      </svg>
                      <svg v-else class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                      </svg>
                    </button>
                  </div>
                </div>
                <div class="p-4">
                  <h3 class="font-medium text-gray-900">{{ image.title || 'Untitled' }}</h3>
                  <p class="text-sm text-gray-500">Order: {{ image.sort_order }}</p>
                  <div class="mt-3 flex space-x-2">
                    <button
                      @click="editImage(image)"
                      class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                    >
                      Edit
                    </button>
                    <button
                      @click="deleteImage(image)"
                      class="text-red-600 hover:text-red-800 text-sm font-medium"
                    >
                      Delete
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Mobile Images Section -->
          <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-lg font-semibold text-gray-900">Mobile Slider Images</h2>
              <button
                @click="showUploadModal('mobile')"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium"
              >
                Add Mobile Image
              </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
              <div
                v-for="image in mobileImages"
                :key="image.id"
                class="border border-gray-200 rounded-lg overflow-hidden"
              >
                <div class="aspect-video bg-gray-100 relative">
                  <img
                    :src="`/storage/${image.image_path}`"
                    :alt="image.title"
                    class="w-full h-full object-cover"
                  />
                  <div class="absolute top-2 right-2">
                    <button
                      @click="toggleImage(image)"
                      :class="image.is_active ? 'bg-green-500' : 'bg-gray-400'"
                      class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs"
                    >
                      <svg v-if="image.is_active" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                      </svg>
                      <svg v-else class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                      </svg>
                    </button>
                  </div>
                </div>
                <div class="p-4">
                  <h3 class="font-medium text-gray-900">{{ image.title || 'Untitled' }}</h3>
                  <p class="text-sm text-gray-500">Order: {{ image.sort_order }}</p>
                  <div class="mt-3 flex space-x-2">
                    <button
                      @click="editImage(image)"
                      class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                    >
                      Edit
                    </button>
                    <button
                      @click="deleteImage(image)"
                      class="text-red-600 hover:text-red-800 text-sm font-medium"
                    >
                      Delete
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Upload/Edit Modal -->
      <div
        v-if="showModal"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        @click="closeModal"
      >
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" @click.stop>
          <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
              {{ isEditing ? 'Edit' : 'Upload' }} {{ modalType === 'desktop' ? 'Desktop' : 'Mobile' }} Image
            </h3>

            <form @submit.prevent="submitForm" enctype="multipart/form-data">
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Title (Optional)</label>
                <input
                  v-model="form.title"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter image title"
                />
              </div>

              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Image Upload
                </label>
                <input
                  ref="imageInput"
                  @change="handleImageChange"
                  type="file"
                  accept="image/*"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                />
                <p class="text-xs text-gray-500 mt-1">
                  Max size: {{ modalType === 'desktop' ? '2MB' : '1MB' }}. Formats: JPEG, PNG, JPG, GIF, WebP
                  <span v-if="modalType === 'desktop'"> - Desktop version</span>
                  <span v-else> - Mobile optimized</span>
                </p>
              </div>

              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                <input
                  v-model.number="form.sort_order"
                  type="number"
                  min="0"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="0"
                />
              </div>

              <div class="flex justify-end space-x-3">
                <button
                  type="button"
                  @click="closeModal"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  :disabled="loading"
                  class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50"
                >
                  {{ loading ? 'Uploading...' : (isEditing ? 'Update' : 'Upload') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const desktopImages = ref([])
const mobileImages = ref([])
const showModal = ref(false)
const modalType = ref('desktop')
const isEditing = ref(false)
const editingImage = ref(null)
const loading = ref(false)

const form = ref({
  title: '',
  image: null,
  sort_order: 0
})

const imageInput = ref(null)

onMounted(() => {
  loadImages()
})

const loadImages = () => {
  router.reload({
    onSuccess: (page) => {
      desktopImages.value = page.props.desktopImages || []
      mobileImages.value = page.props.mobileImages || []
    }
  })
}

const showUploadModal = (type) => {
  modalType.value = type
  isEditing.value = false
  editingImage.value = null
  form.value = {
    title: '',
    image: null,
    sort_order: 0
  }
  showModal.value = true
}

const editImage = (image) => {
  modalType.value = image.type
  isEditing.value = true
  editingImage.value = image
  form.value = {
    title: image.title || '',
    sort_order: image.sort_order || 0
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  form.value = {
    title: '',
    image: null,
    sort_order: 0
  }
  if (imageInput.value) imageInput.value.value = ''
}

const handleImageChange = (event) => {
  form.value.image = event.target.files[0]
}

const submitForm = () => {
  loading.value = true

  const formData = new FormData()
  formData.append('title', form.value.title)
  formData.append('type', modalType.value)
  formData.append('sort_order', form.value.sort_order)

  if (form.value.image) {
    formData.append('image', form.value.image)
  }

  const url = isEditing.value
    ? `/admin/sliders/${editingImage.value.id}`
    : '/admin/sliders'

  const method = isEditing.value ? 'post' : 'post'

  router[method](url, formData, {
    onSuccess: () => {
      loading.value = false
      closeModal()
      loadImages()
    },
    onError: () => {
      loading.value = false
    }
  })
}

const toggleImage = (image) => {
  router.post(`/admin/sliders/${image.id}/toggle`, {}, {
    onSuccess: () => {
      loadImages()
    }
  })
}

const deleteImage = (image) => {
  if (confirm('Are you sure you want to delete this image?')) {
    router.delete(`/admin/sliders/${image.id}`, {
      onSuccess: () => {
        loadImages()
      }
    })
  }
}
</script>
